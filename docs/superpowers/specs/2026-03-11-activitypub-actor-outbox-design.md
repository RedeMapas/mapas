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

| Mapas field        | ActivityPub field |
|--------------------|-------------------|
| `agent.name`       | `name`            |
| `agent.shortDescription` | `summary`   |
| `agent.slug`       | handle (`@slug@domain`) |
| `agent.id`         | used in canonical URI |
| avatar file        | `icon`            |
| agent single URL   | `url`             |

Handle format: `@{slug}@{domain}` — e.g., `@maria-silva@redemapas.cultura.gov.br`
Actor URI: `https://{domain}/activitypub/agent/{slug}`

---

## Activity Mapping

| Agent action               | ActivityPub type | Object type   |
|----------------------------|-----------------|---------------|
| Creates Event              | `Create`        | `Event`       |
| Updates Event              | `Update`        | `Event`       |
| Creates Space              | `Create`        | `Place`       |
| Updates Space              | `Update`        | `Place`       |
| Creates Project            | `Create`        | `Project`     |
| Updates Project            | `Update`        | `Project`     |
| Creates Opportunity        | `Create`        | `Note`        |
| Updates Opportunity        | `Update`        | `Note`        |
| Submits Registration       | `Announce`      | `Note`        |
| Gains admin relation       | `Add`           | (agent/space) |

Only entities with `status >= 1` (published) generate activities. Drafts, trash, and disabled entities are skipped.

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
    published   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX activitypub_activity_agent_published
    ON activitypub_activity (agent_id, published DESC);
```

- `activity_id`: canonical URI, e.g. `https://{domain}/activitypub/agent/{slug}/activities/{id}`
- `payload`: full JSON-LD Activity stored at write time — immutable historical record
- Duplicate guard: `(agent_id, type, object_type, object_id)` unique constraint prevents double-recording on retried jobs

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
$app->hook('entity(Registration).insert:after', fn($e) => $this->dispatch('Announce', $e));
$app->hook('entity(AgentRelation).insert:after', fn($e) => $this->dispatchRelation($e));
```

`dispatch()` enqueues a `RecordActivity` Job — does not block the save.

---

## HTTP Endpoints

All endpoints return `Content-Type: application/activity+json`. All are public (no auth required per ActivityPub spec for public Outbox).

### WebFinger
```
GET /.well-known/webfinger?resource=acct:{slug}@{domain}
```
Returns JRD JSON with `rel=self` link pointing to the Actor URI.

### Actor
```
GET /activitypub/agent/{slug}
```
Returns JSON-LD `Person` object with `id`, `name`, `summary`, `icon`, `url`, `outbox`, `inbox` (stub URL, returns empty OrderedCollection for now).

### Outbox (collection)
```
GET /activitypub/agent/{slug}/outbox
```
Returns `OrderedCollection` with `totalItems` and `first` link.

### Outbox (page)
```
GET /activitypub/agent/{slug}/outbox?page={n}
```
Returns `OrderedCollectionPage` with 20 activities ordered by `published DESC`. Reads from `activitypub_activity`.

### Activity (canonical URI)
```
GET /activitypub/agent/{slug}/activities/{id}
```
Returns full JSON-LD Activity payload. Required for external servers to dereference activity references.

---

## Job: RecordActivity

`RecordActivity` extends `JobType`. Receives `['activityType', 'entityClass', 'entityId']`.

1. Loads the entity by class + id
2. Checks `status >= 1` (skip if draft/trash/disabled)
3. Resolves the agent owner (`$entity->ownerAgent`)
4. Calls `ActivityBuilder::build(type, entity, agent)` → JSON-LD array
5. Inserts into `activitypub_activity` (upsert on `activity_id` to handle retries)

---

## ActivityBuilder

Builds the JSON-LD payload for each object type:

- **Event** → `{"type": "Event", "name": ..., "startTime": ..., "location": ..., "url": ...}`
- **Space** → `{"type": "Place", "name": ..., "latitude": ..., "longitude": ..., "url": ...}`
- **Project** → `{"type": "Project", "name": ..., "summary": ..., "url": ...}` (uses `schema:Project`)
- **Opportunity** → `{"type": "Note", "name": ..., "content": ..., "url": ...}`
- **Registration** → `{"type": "Note", "content": "Inscreveu-se em {opportunity.name}", "url": ...}`

All objects include `attributedTo` pointing to the Actor URI.

---

## Configuration

Added to app config:

```php
'activitypub.enabled' => true,
'activitypub.domain'  => 'redemapas.cultura.gov.br',  // defaults to BASE_URL host
```

Plugin checks `activitypub.enabled` before registering hooks and exposing endpoints.

---

## Out of Scope (Future Phases)

- **Phase 2:** Inbox — receive Follow, Unfollow, Reply, Like from external servers
- **Phase 3:** Fan-out delivery — push activities to followers via HTTP Signatures
- **Phase 4:** Comment UI — display Reply activities as comments on entity pages
- **Phase 5:** Delete activity — when entity is trashed, send `Delete` to followers
