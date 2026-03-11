# ActivityPub — Phase 1: Actor + Outbox

**Date:** 2026-03-11
**Status:** Approved
**Scope:** Expose every Agent as a Fediverse Actor with a public, paginated Outbox of their activities.

---

## Overview

Integrate ActivityPub into Mapas Culturais so that every Agent becomes a discoverable Fediverse profile. Any action the agent takes as owner (creating/updating Events, Spaces, Projects, Opportunities, Registrations, AgentRelations) is recorded as an ActivityPub Activity and published to their public Outbox. External Fediverse clients (Mastodon, Pleroma, etc.) can discover agents via WebFinger and read their activity streams.

**Out of scope for Phase 1:** Inbox, Follow/Unfollow, delivering activities to followers, comment UI, Like.

---

## Actor Mapping

Every `Agent` entity maps to an ActivityPub `Person` Actor:

| Mapas field              | ActivityPub field |
|--------------------------|-------------------|
| `agent.name`             | `name`            |
| `agent.shortDescription` | `summary`         |
| `agent.slug`             | handle (`@slug@domain`) |
| `agent.id`               | used in canonical URI |
| avatar file              | `icon`            |
| agent single URL         | `url`             |

Handle format: `@{slug}@{domain}` — e.g., `@maria-silva@redemapas.cultura.gov.br`
Actor URI: `https://{domain}/activitypub/agent/{slug}`

All Actor responses include:
```json
{
  "@context": ["https://www.w3.org/ns/activitystreams", "https://w3id.org/security/v1"],
  "type": "Person",
  "id": "https://{domain}/activitypub/agent/{slug}",
  "name": "...",
  "summary": "...",
  "url": "...",
  "icon": { "type": "Image", "url": "..." },
  "outbox": "https://{domain}/activitypub/agent/{slug}/outbox",
  "inbox": "https://{domain}/activitypub/agent/{slug}/inbox",
  "publicKey": {
    "id": "https://{domain}/activitypub/agent/{slug}#main-key",
    "owner": "https://{domain}/activitypub/agent/{slug}",
    "publicKeyPem": ""
  }
}
```

`publicKey.publicKeyPem` is empty string in Phase 1 (no HTTP Signature support yet). Most Fediverse clients check for the field's presence but only validate the key when verifying signed requests. This stub prevents clients from rejecting the Actor as malformed.

The `inbox` field points to a stub endpoint returning an empty `OrderedCollection`:
```json
{ "@context": "https://www.w3.org/ns/activitystreams", "type": "OrderedCollection", "totalItems": 0, "orderedItems": [] }
```
This satisfies Fediverse clients that check for the field's presence.

---

## Activity Mapping

| Agent action             | ActivityPub type | Object type  | Actor source          |
|--------------------------|------------------|--------------|-----------------------|
| Creates Event            | `Create`         | `Event`      | `$entity->ownerAgent` |
| Updates Event            | `Update`         | `Event`      | `$entity->ownerAgent` |
| Creates Space            | `Create`         | `Place`      | `$entity->ownerAgent` |
| Updates Space            | `Update`         | `Place`      | `$entity->ownerAgent` |
| Creates Project          | `Create`         | `Project`    | `$entity->ownerAgent` |
| Updates Project          | `Update`         | `Project`    | `$entity->ownerAgent` |
| Creates Opportunity      | `Create`         | `Note`       | `$entity->ownerAgent` |
| Updates Opportunity      | `Update`         | `Note`       | `$entity->ownerAgent` |
| Submits Registration     | `Announce`       | `Note`       | `$entity->ownerAgent` |
| AgentRelation (admin)    | `Add`            | `Relationship` | `$relation->agent` (the agent being added) |

Only entities with `status >= 1` (published) generate activities. Drafts, trash, and disabled entities are skipped.

### AgentRelation actor resolution

`AgentRelation` has two sides: `$relation->agent` (the agent being added/linked) and `$relation->owner` (the entity receiving the agent, e.g. a Space). The Actor for an `Add` activity is `$relation->agent`. The `object` field describes what the agent was added to:

```json
{
  "type": "Add",
  "actor": "https://{domain}/activitypub/agent/{agent.slug}",
  "object": {
    "type": "Relationship",
    "subject": "https://{domain}/activitypub/agent/{agent.slug}",
    "relationship": "administrator",
    "object": "{owner entity URL}"
  }
}
```

If `$relation->agent` has `status < 1`, the activity is skipped.

---

## Database Schema

New table `activitypub_activity`:

```sql
CREATE TABLE activitypub_activity (
    id          BIGSERIAL PRIMARY KEY,
    agent_id    INT NOT NULL REFERENCES agent(id) ON DELETE CASCADE,
    activity_id TEXT NOT NULL UNIQUE,
    type        TEXT NOT NULL,
    object_type TEXT NOT NULL,
    object_id   INT NOT NULL,
    payload     JSONB NOT NULL,
    published   TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    -- Prevents recording duplicate Create for the same object; Updates are allowed to accumulate
    CONSTRAINT activitypub_activity_create_dedup UNIQUE (agent_id, object_type, object_id)
        WHERE type = 'Create'
);

CREATE INDEX activitypub_activity_agent_published
    ON activitypub_activity (agent_id, published DESC);
```

- `activity_id`: canonical URI, e.g. `https://{domain}/activitypub/agent/{slug}/activities/{id}`
- `payload`: full JSON-LD Activity stored at write time — immutable historical record.
- **Dedup for Create:** The partial unique constraint `activitypub_activity_create_dedup` prevents double-recording a `Create` activity for the same object (e.g. job retry). The Job uses `INSERT ... ON CONFLICT ON CONSTRAINT activitypub_activity_create_dedup DO NOTHING` only for `Create` activities.
- **Update activities accumulate:** Multiple `Update` activities for the same object are all recorded — they form the edit history visible in the Outbox. No dedup constraint applies to `Update`, `Announce`, or `Add`.
- `activity_id` uniqueness remains as a global guard against duplicate URIs.

---

## Plugin Structure

```
src/plugins/ActivityPub/
├── Plugin.php                  # _init(): registers hooks, controller, job type
├── Controllers/
│   └── ActivityPub.php         # HTTP endpoint handlers
├── Jobs/
│   └── RecordActivity.php      # Async JobType: builds payload and persists
├── ActivityBuilder.php         # Builds JSON-LD Activity payload per object type
└── ActorBuilder.php            # Builds JSON-LD Actor from Agent entity
```

### Plugin.php hooks

`dispatch(string $type, Entity $entity)` extracts `entityClass = get_class($entity)` and `entityId = $entity->id` and enqueues a `RecordActivity` Job with payload `['activityType' => $type, 'entityClass' => $entityClass, 'entityId' => $entityId]`. The entity object itself is never passed to the job (not serializable).

```php
// Entity creation
$app->hook('entity(Event).insert:after',        fn($e) => $this->dispatch('Create', $e));
$app->hook('entity(Space).insert:after',        fn($e) => $this->dispatch('Create', $e));
$app->hook('entity(Project).insert:after',      fn($e) => $this->dispatch('Create', $e));
$app->hook('entity(Opportunity).insert:after',  fn($e) => $this->dispatch('Create', $e));

// Entity updates
$app->hook('entity(Event).update:after',        fn($e) => $this->dispatch('Update', $e));
$app->hook('entity(Space).update:after',        fn($e) => $this->dispatch('Update', $e));
$app->hook('entity(Project).update:after',      fn($e) => $this->dispatch('Update', $e));
$app->hook('entity(Opportunity).update:after',  fn($e) => $this->dispatch('Update', $e));

// Relational actions
$app->hook('entity(Registration).insert:after',  fn($e) => $this->dispatch('Announce', $e));
$app->hook('entity(AgentRelation).insert:after', fn($e) => $this->dispatchRelation($e));
```

`dispatchRelation()` enqueues the same job with `activityType = 'Add'`. Actor resolution happens inside the job (see Job section).

---

## HTTP Endpoints

All endpoints return `Content-Type: application/activity+json`. All are public (no auth required per ActivityPub spec for public Outbox).

### Error responses

All endpoints return JSON-LD errors:

| Condition                          | HTTP status | Body                                             |
|------------------------------------|-------------|--------------------------------------------------|
| Agent slug not found               | 404         | `{"error": "Actor not found"}`                   |
| Agent status < 1 (draft/disabled)  | 404         | `{"error": "Actor not found"}` (don't reveal status) |
| `page` param out of range          | 200         | Empty `OrderedCollectionPage` with `orderedItems: []` |
| `resource` missing in WebFinger    | 400         | `{"error": "resource parameter required"}`       |
| `resource` domain mismatch         | 404         | `{"error": "Actor not found"}`                   |

### WebFinger
```
GET /.well-known/webfinger?resource=acct:{slug}@{domain}
Content-Type: application/jrd+json
```
Looks up active agent (`status >= 1`) by slug. Returns JRD JSON:
```json
{
  "subject": "acct:{slug}@{domain}",
  "links": [{ "rel": "self", "type": "application/activity+json", "href": "https://{domain}/activitypub/agent/{slug}" }]
}
```
Returns 404 if slug not found or agent not active.

### Actor
```
GET /activitypub/agent/{slug}
```
Looks up active agent by slug. Returns JSON-LD `Person` (see Actor Mapping). Returns 404 if not found or not active.

### Inbox (stub)
```
GET /activitypub/agent/{slug}/inbox
```
Returns empty `OrderedCollection`. POST to inbox returns 405 Method Not Allowed (Phase 2).

### Outbox (collection)
```
GET /activitypub/agent/{slug}/outbox
```
Returns `OrderedCollection`:
```json
{
  "@context": "https://www.w3.org/ns/activitystreams",
  "type": "OrderedCollection",
  "id": "https://{domain}/activitypub/agent/{slug}/outbox",
  "totalItems": 42,
  "first": "https://{domain}/activitypub/agent/{slug}/outbox?page=1"
}
```

### Outbox (page)
```
GET /activitypub/agent/{slug}/outbox?page={n}
```
Returns `OrderedCollectionPage` with 20 items per page, ordered by `published DESC`:
```json
{
  "@context": "https://www.w3.org/ns/activitystreams",
  "type": "OrderedCollectionPage",
  "id": "https://{domain}/activitypub/agent/{slug}/outbox?page=2",
  "partOf": "https://{domain}/activitypub/agent/{slug}/outbox",
  "prev": "https://{domain}/activitypub/agent/{slug}/outbox?page=1",
  "next": "https://{domain}/activitypub/agent/{slug}/outbox?page=3",
  "orderedItems": [...]
}
```
`prev` is omitted on page 1. `next` is omitted on the last page. Out-of-range page returns 200 with empty `orderedItems`.

### Activity (canonical URI)
```
GET /activitypub/agent/{slug}/activities/{id}
```
Returns full JSON-LD Activity payload from `activitypub_activity.payload`. Returns 404 if not found or if the activity's `agent_id` does not match the slug's agent.

---

## Job: RecordActivity

`RecordActivity` extends `JobType`. Receives `['activityType' => string, 'entityClass' => string, 'entityId' => int]`.

1. Loads entity: `$app->repo($entityClass)->find($entityId)` — returns early if not found
2. Checks `$entity->status >= 1` — returns `true` (ack job) if draft/trash/disabled
3. Resolves actor agent:
   - For `AgentRelation`: actor = `$entity->agent` (the agent being linked)
   - For all others: actor = `$entity->ownerAgent`
   - Returns early if actor not found or actor `status < 1`
4. Calls `ActivityBuilder::build($activityType, $entity, $actor)` → JSON-LD array
5. Computes `activity_id` URI: `https://{domain}/activitypub/agent/{actor.slug}/activities/{hash}` where `hash = sha256("{activityType}:{entityClass}:{entityId}:{publishedTimestamp}")` truncated to 16 hex chars. For `Update` activities, `publishedTimestamp` is the current Unix timestamp (seconds), making each update unique.
6. For `Create` activities: inserts using `ON CONFLICT ON CONSTRAINT activitypub_activity_create_dedup DO NOTHING`. For all others: plain `INSERT`.
7. Returns `true`

---

## ActivityBuilder

All activities include:
```json
{
  "@context": "https://www.w3.org/ns/activitystreams",
  "type": "{Create|Update|Announce|Add}",
  "id": "{activity_id URI}",
  "actor": "https://{domain}/activitypub/agent/{actor.slug}",
  "published": "{ISO8601}",
  "object": { ... }
}
```

Object shapes per type:

| Entity type    | Object `type`  | Fields included                                              |
|----------------|----------------|--------------------------------------------------------------|
| `Event`        | `Event`        | `name`, `startTime` (first occurrence), `url`                        |
| `Space`        | `Place`        | `name`, `latitude`, `longitude`, `url`                               |
| `Project`      | `Note`         | `name`, `content` (shortDescription), `url`                          |
| `Opportunity`  | `Note`         | `name`, `content` (shortDescription), `url`                          |
| `Registration` | `Note`         | `content`: "Inscreveu-se em {opportunity.name}", `url` (opportunity URL — Registration has no public page) |
| `AgentRelation`| `Relationship` | `subject` (actor URI), `relationship`: "administrator", `object` (owner entity URL) |

All objects set `published` from the entity's `createTimestamp` (for `Create`) or `updateTimestamp` (for `Update`/`Announce`/`Add`) — not from job execution time. This ensures timestamps reflect when the action actually happened.

All objects include `attributedTo` pointing to the Actor URI.

`schema:Project` extension is not used in Phase 1 — `Note` is used for Project/Opportunity to avoid requiring a custom `@context`.

---

## Configuration

Added to app config:

```php
'activitypub.enabled' => true,
'activitypub.domain'  => '',  // defaults to parsed host from BASE_URL
```

Plugin checks `activitypub.enabled` before registering hooks and exposing endpoints. Domain falls back to `parse_url($app->config['base.url'], PHP_URL_HOST)` if empty.

---

## Out of Scope (Future Phases)

- **Phase 2:** Inbox — receive Follow, Unfollow, Reply, Like from external servers
- **Phase 3:** Fan-out delivery — push activities to followers via HTTP Signatures
- **Phase 4:** Comment UI — display Reply activities as comments on entity pages
- **Phase 5:** Delete activity — when entity is trashed, send `Delete` to followers
