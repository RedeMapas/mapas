# ActivityPub Phase 1 — Actor + Outbox Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expor cada Agent do Mapas Culturais como um Actor ActivityPub com Outbox público paginado, permitindo descoberta via WebFinger por clientes Fediverse (Mastodon, Pleroma, etc.).

**Architecture:** Plugin `ActivityPub` que usa hooks do ciclo de vida das entidades para despachar um Job assíncrono que persiste as atividades em tabela própria. Um Slim middleware intercepta requisições ActivityPub antes do roteador catch-all e as despacha para o controller. Não há entrega a seguidores nesta fase.

**Tech Stack:** PHP 8.3, Doctrine ORM/DBAL, PostgreSQL, Slim 4, sistema de Jobs existente do Mapas Culturais.

**Spec:** `docs/superpowers/specs/2026-03-11-activitypub-actor-outbox-design.md`

---

## File Map

| Arquivo | Responsabilidade |
|---------|-----------------|
| `src/plugins/ActivityPub/Plugin.php` | `_init()`: registra hooks, middleware e job. `register()`: registra controller. |
| `src/plugins/ActivityPub/Controllers/ActivityPub.php` | Handlers HTTP: webfinger, actor, inbox, outbox, activity |
| `src/plugins/ActivityPub/Middleware/ActivityPubMiddleware.php` | Slim middleware — intercepta rotas AP antes do catch-all |
| `src/plugins/ActivityPub/Jobs/RecordActivity.php` | JobType assíncrono: persiste activity na tabela |
| `src/plugins/ActivityPub/ActivityBuilder.php` | Monta payload JSON-LD de cada tipo de atividade |
| `src/plugins/ActivityPub/ActorBuilder.php` | Monta JSON-LD Actor a partir de um Agent |
| `src/db-updates.php` | Migration: cria tabela `activitypub_activity` |
| `dev/config.d/0.main.php` | Habilita plugin em dev |
| `tests/src/ActivityPub/ActorBuilderTest.php` | Unit tests do ActorBuilder |
| `tests/src/ActivityPub/ActivityBuilderTest.php` | Unit tests do ActivityBuilder |
| `tests/src/ActivityPub/ActivityPubControllerTest.php` | Integration tests dos endpoints HTTP |

---

## Chunk 1: Migration e scaffold do plugin

### Task 1: Adicionar migration da tabela `activitypub_activity`

**Files:**
- Modify: `src/db-updates.php`

**Contexto:** Migrations são entradas de um array associativo no final de `src/db-updates.php`. O valor é um closure que redeclara `$conn` internamente via `$conn = $app->em->getConnection()`.

**Nota crítica sobre o constraint de dedup:** PostgreSQL não suporta cláusula `WHERE` em `ADD CONSTRAINT UNIQUE` dentro de `CREATE TABLE`. Para criar um unique constraint parcial (só onde `type = 'Create'`), usa-se `CREATE UNIQUE INDEX ... WHERE type = 'Create'`. Esse índice parcial age como constraint e previne duplicatas de `Create` sem bloquear múltiplos `Update` para o mesmo objeto.

**Nota sobre `ON CONFLICT` com índice parcial:** PostgreSQL exige que o `ON CONFLICT` especifique as colunas + cláusula `WHERE` — `ON CONFLICT ON CONSTRAINT nome` **não funciona** com partial indexes. A forma correta é: `ON CONFLICT (agent_id, object_type, object_id) WHERE type = 'Create' DO NOTHING`.

- [ ] **Step 1: Abrir `src/db-updates.php` e localizar o final do array** (antes do `+ $updates`)

- [ ] **Step 2: Adicionar a migration antes de `] + $updates;`**

```php
'ActivityPub: cria tabela activitypub_activity' => function() {
    $app = \MapasCulturais\App::i();
    $conn = $app->em->getConnection();

    $conn->executeQuery("
        CREATE TABLE IF NOT EXISTS activitypub_activity (
            id          BIGSERIAL PRIMARY KEY,
            agent_id    INT NOT NULL REFERENCES agent(id) ON DELETE CASCADE,
            activity_id TEXT NOT NULL,
            type        TEXT NOT NULL,
            object_type TEXT NOT NULL,
            object_id   INT NOT NULL,
            payload     JSONB NOT NULL,
            published   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            CONSTRAINT activitypub_activity_id_unique UNIQUE (activity_id)
        )
    ");

    // Índice de performance para leitura do Outbox
    $conn->executeQuery("
        CREATE INDEX IF NOT EXISTS activitypub_activity_agent_published
            ON activitypub_activity (agent_id, published DESC)
    ");

    // Partial unique index: impede duplicata de Create para o mesmo objeto.
    // Update/Announce/Add acumulam normalmente (sem restrição).
    $conn->executeQuery("
        CREATE UNIQUE INDEX IF NOT EXISTS activitypub_activity_create_dedup
            ON activitypub_activity (agent_id, object_type, object_id)
            WHERE type = 'Create'
    ");

    return true;
},
```

- [ ] **Step 3: Aplicar a migration dentro do container**

```bash
dev/bash.sh
./scripts/db-update.sh
```

Saída esperada: `ActivityPub: cria tabela activitypub_activity ... ok`

- [ ] **Step 4: Verificar tabela e índices criados**

```bash
dev/psql.sh
\d activitypub_activity
\di activitypub_activity*
```

Esperado: tabela com 8 colunas, 4 índices — pk + `activitypub_activity_id_unique` + `activitypub_activity_agent_published` + `activitypub_activity_create_dedup`.

- [ ] **Step 5: Commit**

```bash
git add src/db-updates.php
git commit -m "feat(activitypub): migration cria tabela activitypub_activity"
```

---

### Task 2: Criar scaffold do plugin e configurar autoload

**Files:**
- Create: `src/plugins/ActivityPub/Plugin.php`
- Modify: `composer.json`
- Modify: `dev/config.d/0.main.php`

**Contexto crítico — autoload:** O namespace `ActivityPub\` precisa ser registrado no Composer para que o PHP encontre as classes. Sem isso, todos os testes vão falhar imediatamente com "class not found".

- [ ] **Step 1: Verificar se o namespace já está no `composer.json`**

```bash
grep -n "ActivityPub" composer.json
```

Se não estiver, adicionar no bloco `autoload.psr-4`:

```json
"ActivityPub\\": "src/plugins/ActivityPub/"
```

Exemplo de como fica o bloco `autoload`:
```json
"autoload": {
    "psr-4": {
        "MapasCulturais\\": "src/core/",
        "ActivityPub\\": "src/plugins/ActivityPub/"
    }
}
```

- [ ] **Step 2: Regenerar autoload**

```bash
dev/bash.sh
composer dump-autoload
```

- [ ] **Step 3: Criar `src/plugins/ActivityPub/Plugin.php`**

```php
<?php
declare(strict_types=1);

namespace ActivityPub;

use MapasCulturais\App;

class Plugin extends \MapasCulturais\Plugin
{
    public function _init(): void
    {
        $app = App::i();

        if (!($app->config['activitypub.enabled'] ?? false)) {
            return;
        }

        // Hooks e middleware serão adicionados na Task 6
    }

    public function register(): void
    {
        $app = App::i();

        if (!($app->config['activitypub.enabled'] ?? false)) {
            return;
        }

        // Controller e job registrados na Task 6
    }
}
```

- [ ] **Step 4: Habilitar plugin em `dev/config.d/0.main.php`**

Localizar o array `'plugins'` e adicionar:

```php
'ActivityPub' => ['namespace' => 'ActivityPub'],
```

Adicionar também as config keys:

```php
'activitypub.enabled' => true,
'activitypub.domain'  => '',  // vazio = usa host de base.url automaticamente
```

- [ ] **Step 5: Verificar que o app inicializa sem erro**

```bash
dev/bash.sh
php -r "require 'vendor/autoload.php'; \$app = MapasCulturais\App::i();" 2>&1 | tail -5
```

Esperado: sem erros fatais.

- [ ] **Step 6: Commit**

```bash
git add src/plugins/ActivityPub/ dev/config.d/0.main.php composer.json composer.lock
git commit -m "feat(activitypub): scaffold do plugin + autoload"
```

---

## Chunk 2: ActorBuilder e ActivityBuilder

### Task 3: Implementar ActorBuilder

**Files:**
- Create: `src/plugins/ActivityPub/ActorBuilder.php`
- Create: `tests/src/ActivityPub/ActorBuilderTest.php`

**Contexto:** `ActorBuilder` é puro — sem acesso a banco ou App. Recebe um objeto Agent e um domain string, retorna array PHP (JSON-LD do Actor).

**Atenção ao avatar:** Entidades `Agent` reais expõem o avatar via `$agent->avatar` (objeto `File`), não via `$agent->avatarUrl`. A URL pública é `$agent->avatar->url ?? null`. O builder deve suportar ambas as formas para ser testável com stdClass e funcionar com Agent real.

- [ ] **Step 1: Escrever o teste**

```php
<?php
declare(strict_types=1);

namespace Tests\ActivityPub;

use PHPUnit\Framework\TestCase;
use ActivityPub\ActorBuilder;

class ActorBuilderTest extends TestCase
{
    private function makeAgent(): object
    {
        $agent = new \stdClass();
        $agent->id = 42;
        $agent->slug = 'maria-silva';
        $agent->name = 'Maria Silva';
        $agent->shortDescription = 'Artista visual';
        $agent->status = 1;
        // Avatar como objeto File simulado
        $avatar = new \stdClass();
        $avatar->url = 'https://example.com/avatar.jpg';
        $agent->avatar = $avatar;
        $agent->singleUrl = 'https://example.com/agente/maria-silva';
        return $agent;
    }

    public function testActorHasRequiredFields(): void
    {
        $actor = ActorBuilder::build($this->makeAgent(), 'example.com');

        $this->assertSame('Person', $actor['type']);
        $this->assertSame('https://example.com/activitypub/agent/maria-silva', $actor['id']);
        $this->assertSame('maria-silva', $actor['preferredUsername']);
        $this->assertSame('Maria Silva', $actor['name']);
        $this->assertSame('Artista visual', $actor['summary']);
        $this->assertSame('https://example.com/activitypub/agent/maria-silva/outbox', $actor['outbox']);
        $this->assertSame('https://example.com/activitypub/agent/maria-silva/inbox', $actor['inbox']);
    }

    public function testActorHasPublicKeyStub(): void
    {
        $actor = ActorBuilder::build($this->makeAgent(), 'example.com');

        $this->assertArrayHasKey('publicKey', $actor);
        $this->assertSame('https://example.com/activitypub/agent/maria-silva#main-key', $actor['publicKey']['id']);
        $this->assertSame('https://example.com/activitypub/agent/maria-silva', $actor['publicKey']['owner']);
        $this->assertSame('', $actor['publicKey']['publicKeyPem']);
    }

    public function testActorContextIncludesSecurityVocab(): void
    {
        $actor = ActorBuilder::build($this->makeAgent(), 'example.com');

        $this->assertIsArray($actor['@context']);
        $this->assertContains('https://w3id.org/security/v1', $actor['@context']);
        $this->assertContains('https://www.w3.org/ns/activitystreams', $actor['@context']);
    }

    public function testActorIconFromAvatarObject(): void
    {
        $actor = ActorBuilder::build($this->makeAgent(), 'example.com');

        $this->assertSame('Image', $actor['icon']['type']);
        $this->assertSame('https://example.com/avatar.jpg', $actor['icon']['url']);
    }

    public function testActorWithoutAvatarHasNoIcon(): void
    {
        $agent = $this->makeAgent();
        $agent->avatar = null;

        $actor = ActorBuilder::build($agent, 'example.com');

        $this->assertArrayNotHasKey('icon', $actor);
    }
}
```

- [ ] **Step 2: Rodar para ver falhar**

```bash
dev/bash.sh
vendor/bin/phpunit tests/src/ActivityPub/ActorBuilderTest.php -v
```

Esperado: FAIL — `ActivityPub\ActorBuilder not found`

- [ ] **Step 3: Implementar ActorBuilder**

```php
<?php
declare(strict_types=1);

namespace ActivityPub;

class ActorBuilder
{
    public static function build(object $agent, string $domain): array
    {
        $base = "https://{$domain}/activitypub/agent/{$agent->slug}";

        $actor = [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
            ],
            'type'              => 'Person',
            'id'                => $base,
            'preferredUsername' => $agent->slug,
            'name'              => $agent->name ?? '',
            'summary'           => $agent->shortDescription ?? '',
            'url'               => $agent->singleUrl ?? $base,
            'inbox'             => "{$base}/inbox",
            'outbox'            => "{$base}/outbox",
            'publicKey'         => [
                'id'           => "{$base}#main-key",
                'owner'        => $base,
                'publicKeyPem' => '',
            ],
        ];

        // Avatar: Agent real expõe $agent->avatar (File object com ->url).
        // Testes usam stdClass com $agent->avatar->url ou $agent->avatar = null.
        $avatarUrl = null;
        $avatar = $agent->avatar ?? null;
        if ($avatar !== null) {
            $avatarUrl = $avatar->url ?? null;
        }

        if ($avatarUrl) {
            $actor['icon'] = [
                'type' => 'Image',
                'url'  => $avatarUrl,
            ];
        }

        return $actor;
    }
}
```

- [ ] **Step 4: Rodar testes**

```bash
vendor/bin/phpunit tests/src/ActivityPub/ActorBuilderTest.php -v
```

Esperado: 5 testes PASS.

- [ ] **Step 5: Commit**

```bash
git add src/plugins/ActivityPub/ActorBuilder.php tests/src/ActivityPub/ActorBuilderTest.php
git commit -m "feat(activitypub): ActorBuilder com testes"
```

---

### Task 4: Implementar ActivityBuilder

**Files:**
- Create: `src/plugins/ActivityPub/ActivityBuilder.php`
- Create: `tests/src/ActivityPub/ActivityBuilderTest.php`

- [ ] **Step 1: Escrever os testes**

```php
<?php
declare(strict_types=1);

namespace Tests\ActivityPub;

use PHPUnit\Framework\TestCase;
use ActivityPub\ActivityBuilder;

class ActivityBuilderTest extends TestCase
{
    private function makeActor(string $slug = 'maria-silva'): object
    {
        $a = new \stdClass();
        $a->slug = $slug;
        $a->id = 42;
        return $a;
    }

    private function makeEvent(): object
    {
        $e = new \stdClass();
        $e->id = 10;
        $e->name = 'Festival de Música';
        $e->shortDescription = 'Um evento incrível';
        $e->singleUrl = 'https://example.com/evento/10';
        $e->createTimestamp = new \DateTime('2026-01-15T10:00:00+00:00');
        $e->updateTimestamp = new \DateTime('2026-01-16T12:00:00+00:00');
        $e->occurrences = [];
        return $e;
    }

    private function makeSpace(): object
    {
        $s = new \stdClass();
        $s->id = 20;
        $s->name = 'Casa de Cultura';
        $s->shortDescription = 'Espaço cultural';
        $s->singleUrl = 'https://example.com/espaco/20';
        $s->createTimestamp = new \DateTime('2026-01-10T09:00:00+00:00');
        $s->updateTimestamp = new \DateTime('2026-01-11T09:00:00+00:00');
        $loc = new \stdClass();
        $loc->latitude = -23.5;
        $loc->longitude = -46.6;
        $s->location = $loc;
        return $s;
    }

    private function makeRegistration(): object
    {
        $r = new \stdClass();
        $r->id = 30;
        $r->createTimestamp = new \DateTime('2026-02-01T08:00:00+00:00');
        $r->updateTimestamp = new \DateTime('2026-02-01T08:00:00+00:00');
        $opp = new \stdClass();
        $opp->name = 'Edital de Cultura';
        $opp->singleUrl = 'https://example.com/oportunidade/5';
        $r->opportunity = $opp;
        return $r;
    }

    private function makeAgentRelation(): object
    {
        $ar = new \stdClass();
        $ar->id = 50;
        $ar->createTimestamp = new \DateTime('2026-02-10T10:00:00+00:00');
        $ar->updateTimestamp = new \DateTime('2026-02-10T10:00:00+00:00');
        $owner = new \stdClass();
        $owner->singleUrl = 'https://example.com/espaco/20';
        $ar->owner = $owner;
        return $ar;
    }

    private const ACT_ID = 'https://example.com/activitypub/agent/maria-silva/activities/abc123';

    public function testCreateEventActivity(): void
    {
        $activity = ActivityBuilder::build('Create', $this->makeEvent(), 'MapasCulturais\Entities\Event', $this->makeActor(), 'example.com', self::ACT_ID);

        $this->assertSame('Create', $activity['type']);
        $this->assertSame(self::ACT_ID, $activity['id']);
        $this->assertSame('https://example.com/activitypub/agent/maria-silva', $activity['actor']);
        $this->assertSame('Event', $activity['object']['type']);
        $this->assertSame('Festival de Música', $activity['object']['name']);
        $this->assertSame('https://example.com/evento/10', $activity['object']['url']);
        $this->assertSame('2026-01-15T10:00:00+00:00', $activity['published']); // createTimestamp
    }

    public function testUpdateSpaceActivity(): void
    {
        $activity = ActivityBuilder::build('Update', $this->makeSpace(), 'MapasCulturais\Entities\Space', $this->makeActor(), 'example.com', self::ACT_ID);

        $this->assertSame('Update', $activity['type']);
        $this->assertSame('Place', $activity['object']['type']);
        $this->assertSame('Casa de Cultura', $activity['object']['name']);
        $this->assertSame(-23.5, $activity['object']['latitude']);
        $this->assertSame(-46.6, $activity['object']['longitude']);
        $this->assertSame('2026-01-11T09:00:00+00:00', $activity['published']); // updateTimestamp
    }

    public function testAnnounceRegistrationActivity(): void
    {
        $activity = ActivityBuilder::build('Announce', $this->makeRegistration(), 'MapasCulturais\Entities\Registration', $this->makeActor(), 'example.com', self::ACT_ID);

        $this->assertSame('Announce', $activity['type']);
        $this->assertSame('Note', $activity['object']['type']);
        $this->assertStringContainsString('Edital de Cultura', $activity['object']['content']);
        $this->assertSame('https://example.com/oportunidade/5', $activity['object']['url']);
    }

    public function testAddAgentRelationActivity(): void
    {
        $activity = ActivityBuilder::build('Add', $this->makeAgentRelation(), 'MapasCulturais\Entities\AgentRelation', $this->makeActor(), 'example.com', self::ACT_ID);

        $this->assertSame('Add', $activity['type']);
        $this->assertSame('Relationship', $activity['object']['type']);
        $this->assertSame('https://example.com/activitypub/agent/maria-silva', $activity['object']['subject']);
        $this->assertSame('administrator', $activity['object']['relationship']);
        $this->assertSame('https://example.com/espaco/20', $activity['object']['object']);
    }

    public function testActivityHasContext(): void
    {
        $activity = ActivityBuilder::build('Create', $this->makeEvent(), 'MapasCulturais\Entities\Event', $this->makeActor(), 'example.com', self::ACT_ID);

        $this->assertSame('https://www.w3.org/ns/activitystreams', $activity['@context']);
    }

    public function testObjectAttributedToActor(): void
    {
        $activity = ActivityBuilder::build('Create', $this->makeEvent(), 'MapasCulturais\Entities\Event', $this->makeActor(), 'example.com', self::ACT_ID);

        $this->assertSame('https://example.com/activitypub/agent/maria-silva', $activity['object']['attributedTo']);
    }
}
```

- [ ] **Step 2: Rodar para ver falhar**

```bash
vendor/bin/phpunit tests/src/ActivityPub/ActivityBuilderTest.php -v
```

- [ ] **Step 3: Implementar ActivityBuilder**

```php
<?php
declare(strict_types=1);

namespace ActivityPub;

class ActivityBuilder
{
    private const OBJECT_TYPES = [
        'MapasCulturais\Entities\Event'         => 'Event',
        'MapasCulturais\Entities\Space'         => 'Place',
        'MapasCulturais\Entities\Project'       => 'Note',
        'MapasCulturais\Entities\Opportunity'   => 'Note',
        'MapasCulturais\Entities\Registration'  => 'Note',
        'MapasCulturais\Entities\AgentRelation' => 'Relationship',
    ];

    public static function build(
        string $activityType,
        object $entity,
        string $entityClass,
        object $actor,
        string $domain,
        string $activityId
    ): array {
        $actorUri  = "https://{$domain}/activitypub/agent/{$actor->slug}";
        $published = self::resolvePublished($activityType, $entity);

        return [
            '@context'  => 'https://www.w3.org/ns/activitystreams',
            'type'      => $activityType,
            'id'        => $activityId,
            'actor'     => $actorUri,
            'published' => $published,
            'object'    => self::buildObject($entityClass, $entity, $actorUri, $domain),
        ];
    }

    private static function resolvePublished(string $activityType, object $entity): string
    {
        $ts = match ($activityType) {
            'Create' => $entity->createTimestamp ?? new \DateTime(),
            default  => $entity->updateTimestamp ?? $entity->createTimestamp ?? new \DateTime(),
        };

        if (!$ts instanceof \DateTimeInterface) {
            $ts = new \DateTime();
        }

        return $ts->format(\DateTime::ATOM);
    }

    private static function buildObject(
        string $entityClass,
        object $entity,
        string $actorUri,
        string $domain
    ): array {
        $type = self::OBJECT_TYPES[$entityClass] ?? 'Note';
        $base = ['type' => $type, 'attributedTo' => $actorUri];

        return match ($entityClass) {
            'MapasCulturais\Entities\Event'         => $base + self::eventObject($entity),
            'MapasCulturais\Entities\Space'         => $base + self::spaceObject($entity),
            'MapasCulturais\Entities\Project'       => $base + self::noteObject($entity),
            'MapasCulturais\Entities\Opportunity'   => $base + self::noteObject($entity),
            'MapasCulturais\Entities\Registration'  => $base + self::registrationObject($entity),
            'MapasCulturais\Entities\AgentRelation' => $base + self::relationObject($entity, $actorUri),
            default                                 => $base + self::noteObject($entity),
        };
    }

    private static function eventObject(object $entity): array
    {
        $obj = ['name' => $entity->name ?? '', 'url' => $entity->singleUrl ?? ''];
        $occ = ($entity->occurrences ?? [])[0] ?? null;
        if ($occ && isset($occ->startsOn)) {
            $obj['startTime'] = (new \DateTime((string) $occ->startsOn))->format(\DateTime::ATOM);
        }
        return $obj;
    }

    private static function spaceObject(object $entity): array
    {
        $obj = ['name' => $entity->name ?? '', 'url' => $entity->singleUrl ?? ''];
        $loc = $entity->location ?? null;
        if ($loc) {
            $obj['latitude']  = $loc->latitude ?? null;
            $obj['longitude'] = $loc->longitude ?? null;
        }
        return $obj;
    }

    private static function noteObject(object $entity): array
    {
        return [
            'name'    => $entity->name ?? '',
            'content' => $entity->shortDescription ?? '',
            'url'     => $entity->singleUrl ?? '',
        ];
    }

    private static function registrationObject(object $entity): array
    {
        $opp    = $entity->opportunity ?? null;
        $oppName = $opp->name ?? 'edital';
        $oppUrl  = $opp->singleUrl ?? '';
        return ['content' => "Inscreveu-se em {$oppName}", 'url' => $oppUrl];
    }

    private static function relationObject(object $entity, string $actorUri): array
    {
        $ownerUrl = $entity->owner->singleUrl ?? '';
        return [
            'subject'      => $actorUri,
            'relationship' => 'administrator',
            'object'       => $ownerUrl,
        ];
    }
}
```

- [ ] **Step 4: Rodar testes**

```bash
vendor/bin/phpunit tests/src/ActivityPub/ActivityBuilderTest.php -v
```

Esperado: 7 testes PASS.

- [ ] **Step 5: Rodar todos os testes ActivityPub até agora**

```bash
vendor/bin/phpunit tests/src/ActivityPub/ -v
```

- [ ] **Step 6: Commit**

```bash
git add src/plugins/ActivityPub/ActivityBuilder.php tests/src/ActivityPub/ActivityBuilderTest.php
git commit -m "feat(activitypub): ActivityBuilder com testes (Registration, AgentRelation incluídos)"
```

---

## Chunk 3: Job RecordActivity

### Task 5: Implementar o Job RecordActivity

**Files:**
- Create: `src/plugins/ActivityPub/Jobs/RecordActivity.php`

**Contexto:** Jobs estendem `\MapasCulturais\Definitions\JobType`. `_execute()` deve retornar `true` sempre (inclusive em casos de skip) — `false` causa reprocessamento. `$job->payloadData` é o array passado na criação.

**Nota crítica — ON CONFLICT com partial index:** A forma correta para upsert com índice parcial no PostgreSQL é:
```sql
ON CONFLICT (agent_id, object_type, object_id) WHERE type = 'Create' DO NOTHING
```
**Não** usar `ON CONFLICT ON CONSTRAINT nome` — não funciona com partial indexes.

**Nota — DBAL LIMIT/OFFSET:** Para queries com LIMIT/OFFSET no Doctrine DBAL, não usar named params (`:limit`, `:offset`) — alguns drivers PDO não suportam. Usar valores inteiros interpolados diretamente na query (após validação/cast).

- [ ] **Step 1: Verificar assinatura de enqueueJob no App**

```bash
grep -n "function enqueueJob" src/core/App.php
```

Anotar a assinatura. Ajustar a chamada em Plugin.php se diferente de `enqueueJob(string $slug, array $data)`.

- [ ] **Step 2: Criar o arquivo**

```php
<?php
declare(strict_types=1);

namespace ActivityPub\Jobs;

use ActivityPub\ActivityBuilder;
use MapasCulturais\App;
use MapasCulturais\Definitions\JobType;
use MapasCulturais\Entities\Job;

class RecordActivity extends JobType
{
    public const SLUG = 'activitypub.record_activity';

    public function __construct()
    {
        parent::__construct(self::SLUG);
    }

    protected function _generateId(array $data, string $start_string, string $interval_string, int $iterations): string
    {
        // ID único por (type, entityClass, entityId) — evita jobs duplicados na fila
        return md5("{$data['activityType']}:{$data['entityClass']}:{$data['entityId']}");
    }

    protected function _execute(Job $job): bool
    {
        $app  = App::i();
        $data = $job->payloadData ?? [];

        // No Mapas, dados do job são acessados diretamente como propriedades via __get()
        // NÃO usar $job->payloadData — isso não existe. Usar $job->chaveDoPayload.
        $activityType = $job->activityType ?? null;
        $entityClass  = $job->entityClass  ?? null;
        $entityId     = (int) ($job->entityId ?? 0);

        if (!$activityType || !$entityClass || !$entityId) {
            $app->log->warning("[activitypub] Job com payload inválido");
            return true;
        }

        // 1. Carregar entidade
        $entity = $app->repo($entityClass)->find($entityId);
        if (!$entity) {
            return true; // entidade deletada — ok, ack
        }

        // 2. Verificar status da entidade
        if (($entity->status ?? 0) < 1) {
            return true; // rascunho / lixeira — skip
        }

        // 3. Resolver actor
        if ($entityClass === 'MapasCulturais\Entities\AgentRelation') {
            $actor = $entity->agent ?? null;
        } else {
            $actor = $entity->ownerAgent ?? null;
        }

        if (!$actor || ($actor->status ?? 0) < 1) {
            return true; // actor inativo — skip
        }

        // 4. Computar activity_id
        $domain    = $this->resolveDomain($app);
        $tsForHash = match ($activityType) {
            'Create' => ($entity->createTimestamp instanceof \DateTimeInterface)
                            ? $entity->createTimestamp->getTimestamp()
                            : time(),
            default  => time(), // cada Update é único (acumula histórico)
        };
        $hash       = substr(hash('sha256', "{$activityType}:{$entityClass}:{$entityId}:{$tsForHash}"), 0, 16);
        $activityId = "https://{$domain}/activitypub/agent/{$actor->slug}/activities/{$hash}";

        // 5. Construir payload JSON-LD
        $payload = ActivityBuilder::build($activityType, $entity, $entityClass, $actor, $domain, $activityId);

        // 6. Persistir
        $conn       = $app->em->getConnection();
        $objectType = $this->shortClass($entityClass);
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $published   = $payload['published'];

        try {
            if ($activityType === 'Create') {
                // Partial index dedup: ON CONFLICT com colunas + WHERE clause
                $conn->executeQuery(
                    "INSERT INTO activitypub_activity
                        (agent_id, activity_id, type, object_type, object_id, payload, published)
                     VALUES
                        (:agent_id, :activity_id, :type, :object_type, :object_id, :payload, :published)
                     ON CONFLICT (agent_id, object_type, object_id) WHERE type = 'Create'
                     DO NOTHING",
                    [
                        'agent_id'    => $actor->id,
                        'activity_id' => $activityId,
                        'type'        => $activityType,
                        'object_type' => $objectType,
                        'object_id'   => $entityId,
                        'payload'     => $payloadJson,
                        'published'   => $published,
                    ]
                );
            } else {
                // Update/Announce/Add: acumula — sem dedup, mas guarda contra activity_id duplicado
                $conn->executeQuery(
                    "INSERT INTO activitypub_activity
                        (agent_id, activity_id, type, object_type, object_id, payload, published)
                     VALUES
                        (:agent_id, :activity_id, :type, :object_type, :object_id, :payload, :published)
                     ON CONFLICT ON CONSTRAINT activitypub_activity_id_unique
                     DO NOTHING",
                    [
                        'agent_id'    => $actor->id,
                        'activity_id' => $activityId,
                        'type'        => $activityType,
                        'object_type' => $objectType,
                        'object_id'   => $entityId,
                        'payload'     => $payloadJson,
                        'published'   => $published,
                    ]
                );
            }
        } catch (\Throwable $e) {
            $app->log->error("[activitypub] Erro ao persistir activity: " . $e->getMessage());
            return false; // retentar
        }

        return true;
    }

    private function resolveDomain(App $app): string
    {
        $domain = (string) ($app->config['activitypub.domain'] ?? '');
        if ($domain !== '') {
            return $domain;
        }
        return (string) parse_url((string) ($app->config['base.url'] ?? ''), PHP_URL_HOST);
    }

    private function shortClass(string $entityClass): string
    {
        return (string) substr(strrchr($entityClass, '\\'), 1);
    }
}
```

- [ ] **Step 3: Verificar sintaxe**

```bash
dev/bash.sh
php -l src/plugins/ActivityPub/Jobs/RecordActivity.php
```

Esperado: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add src/plugins/ActivityPub/Jobs/RecordActivity.php
git commit -m "feat(activitypub): Job RecordActivity"
```

---

## Chunk 4: Plugin hooks e registros

### Task 6: Completar Plugin.php com hooks e middleware

**Files:**
- Modify: `src/plugins/ActivityPub/Plugin.php`

**Contexto:** Hooks de entidade recebem a entidade como `$this` dentro do closure (padrão do Mapas). O dispatch extrai apenas `entityClass` e `entityId` — nunca o objeto Entity (não serializável como payload de Job).

O `::i()` dos controllers do Mapas é um singleton com assinatura `::i()` sem argumentos (verificar em `src/core/Traits/Singleton.php`). O middleware usa `ActivityPubController::i()` — confirmar que não requer argumentos antes de usar.

- [ ] **Step 1: Verificar assinatura de `Controller::i()`**

```bash
grep -n "static.*function i\b" src/core/Controller.php src/core/Traits/Singleton.php 2>/dev/null | head -5
```

Se `::i()` exigir argumentos, ajustar as chamadas em Middleware e Controller conforme necessário.

- [ ] **Step 2: Atualizar Plugin.php**

```php
<?php
declare(strict_types=1);

namespace ActivityPub;

use ActivityPub\Controllers\ActivityPub as ActivityPubController;
use ActivityPub\Jobs\RecordActivity;
use ActivityPub\Middleware\ActivityPubMiddleware;
use MapasCulturais\App;
use MapasCulturais\Entity;

class Plugin extends \MapasCulturais\Plugin
{
    public function _init(): void
    {
        $app = App::i();

        if (!($app->config['activitypub.enabled'] ?? false)) {
            return;
        }

        $plugin = $this;

        // Middleware intercepta rotas ActivityPub antes do catch-all do RoutesManager
        $app->slim->add(new ActivityPubMiddleware());

        // Criações
        $app->hook('entity(Event).insert:after',       function() use ($plugin) { $plugin->dispatch('Create', $this); });
        $app->hook('entity(Space).insert:after',       function() use ($plugin) { $plugin->dispatch('Create', $this); });
        $app->hook('entity(Project).insert:after',     function() use ($plugin) { $plugin->dispatch('Create', $this); });
        $app->hook('entity(Opportunity).insert:after', function() use ($plugin) { $plugin->dispatch('Create', $this); });

        // Atualizações
        $app->hook('entity(Event).update:after',       function() use ($plugin) { $plugin->dispatch('Update', $this); });
        $app->hook('entity(Space).update:after',       function() use ($plugin) { $plugin->dispatch('Update', $this); });
        $app->hook('entity(Project).update:after',     function() use ($plugin) { $plugin->dispatch('Update', $this); });
        $app->hook('entity(Opportunity).update:after', function() use ($plugin) { $plugin->dispatch('Update', $this); });

        // Ações relacionais
        $app->hook('entity(Registration).insert:after',  function() use ($plugin) { $plugin->dispatch('Announce', $this); });
        $app->hook('entity(AgentRelation).insert:after', function() use ($plugin) { $plugin->dispatch('Add', $this); });
    }

    public function register(): void
    {
        $app = App::i();

        if (!($app->config['activitypub.enabled'] ?? false)) {
            return;
        }

        $app->registerController('activitypub', ActivityPubController::class);

        // Guard defensivo: testes reutilizam App e registerJobType lança exceção se já registrado
        if (!$app->getRegisteredJobType(RecordActivity::SLUG)) {
            $app->registerJobType(new RecordActivity());
        }
    }

    public function dispatch(string $activityType, Entity $entity): void
    {
        $app         = App::i();
        $entityClass = get_class($entity);
        $entityId    = (int) $entity->id;

        if (!$entityId) {
            return;
        }

        $app->enqueueJob(RecordActivity::SLUG, [
            'activityType' => $activityType,
            'entityClass'  => $entityClass,
            'entityId'     => $entityId,
        ]);
    }
}
```

- [ ] **Step 3: Verificar sintaxe e inicialização**

```bash
dev/bash.sh
php -l src/plugins/ActivityPub/Plugin.php
php -r "require 'vendor/autoload.php'; \$app = MapasCulturais\App::i();" 2>&1 | tail -5
```

- [ ] **Step 4: Commit**

```bash
git add src/plugins/ActivityPub/Plugin.php
git commit -m "feat(activitypub): Plugin hooks e registro de controller/job"
```

---

## Chunk 5: Middleware e Controller HTTP

### Task 7: Implementar o Middleware ActivityPub

**Files:**
- Create: `src/plugins/ActivityPub/Middleware/ActivityPubMiddleware.php`

**Contexto:** Slim middlewares implementam `Psr\Http\Server\MiddlewareInterface`. São executados **antes** das rotas — isso garante que o middleware captura as requisições ActivityPub antes do catch-all `[/{args:.*}]` do RoutesManager. O middleware deve aplicar a method check correta: WebFinger só aceita GET; inbox aceita GET (retorna empty collection) e rejeita POST com 405.

- [ ] **Step 1: Criar o middleware**

```php
<?php
declare(strict_types=1);

namespace ActivityPub\Middleware;

use ActivityPub\Controllers\ActivityPub as ActivityPubController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class ActivityPubMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path   = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());

        // WebFinger — apenas GET
        if ($path === '/.well-known/webfinger') {
            if ($method !== 'GET') {
                return $this->methodNotAllowed(['GET']);
            }
            return $this->ctrl()->webfinger($request);
        }

        // /activitypub/agent/{slug}[/sub-resource]
        if (str_starts_with($path, '/activitypub/agent/')) {
            $rest  = substr($path, strlen('/activitypub/agent/'));
            $parts = explode('/', trim($rest, '/'));
            $slug  = $parts[0] ?? '';

            if ($slug === '') {
                return $handler->handle($request);
            }

            $ctrl = $this->ctrl();

            // /activitypub/agent/{slug}  (somente GET)
            if (count($parts) === 1) {
                if ($method !== 'GET') {
                    return $this->methodNotAllowed(['GET']);
                }
                return $ctrl->actor($request, $slug);
            }

            $sub = $parts[1] ?? '';

            // /activitypub/agent/{slug}/outbox
            if ($sub === 'outbox') {
                if ($method !== 'GET') {
                    return $this->methodNotAllowed(['GET']);
                }
                return $ctrl->outbox($request, $slug);
            }

            // /activitypub/agent/{slug}/inbox
            if ($sub === 'inbox') {
                if ($method === 'GET') {
                    return $ctrl->inbox($request, $slug);
                }
                // POST e outros métodos → 405 (Inbox não implementado ainda)
                return $this->methodNotAllowed(['GET']);
            }

            // /activitypub/agent/{slug}/activities/{hash}
            if ($sub === 'activities' && isset($parts[2])) {
                if ($method !== 'GET') {
                    return $this->methodNotAllowed(['GET']);
                }
                return $ctrl->activity($request, $slug, $parts[2]);
            }
        }

        return $handler->handle($request);
    }

    private function ctrl(): ActivityPubController
    {
        return ActivityPubController::i();
    }

    private function methodNotAllowed(array $allowed): ResponseInterface
    {
        $response = new Response(405);
        return $response->withHeader('Allow', implode(', ', $allowed));
    }
}
```

- [ ] **Step 2: Verificar sintaxe**

```bash
php -l src/plugins/ActivityPub/Middleware/ActivityPubMiddleware.php
```

- [ ] **Step 3: Commit**

```bash
git add src/plugins/ActivityPub/Middleware/ActivityPubMiddleware.php
git commit -m "feat(activitypub): Middleware intercepta rotas AP com method validation"
```

---

### Task 8: Implementar o Controller HTTP

**Files:**
- Create: `src/plugins/ActivityPub/Controllers/ActivityPub.php`
- Create: `tests/src/ActivityPub/ActivityPubControllerTest.php`

**Contexto crítico — LIMIT/OFFSET:** Doctrine DBAL com PDO PostgreSQL não suporta named params para `LIMIT` e `OFFSET`. Usar valores inteiros interpolados diretamente (após cast seguro para int) na query raw.

**Contexto — busca de Agent por slug:** `$app->repo('Agent')->findOneBy(['slug' => $slug])` retorna o Agent ou `null`. A verificação de status (`>= 1`) deve ser feita manualmente após o fetch.

- [ ] **Step 1: Verificar assinatura de `::i()` no Singleton trait**

```bash
grep -n "static.*function i\b\|function i(" src/core/Traits/Singleton.php 2>/dev/null | head -5
```

Confirmar se `::i()` aceita 0 argumentos. Se exigir o controller_id, usar `ActivityPubController::i('activitypub')`.

- [ ] **Step 2: Escrever os testes de integração**

Estes testes precisam rodar dentro do container (precisam de conexão com banco):

```php
<?php
declare(strict_types=1);

namespace Tests\ActivityPub;

use Tests\Abstract\TestCase;
use MapasCulturais\App;
use Slim\Psr7\Factory\ServerRequestFactory;

class ActivityPubControllerTest extends TestCase
{
    private function req(string $method, string $uri): \Psr\Http\Message\ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest($method, $uri);
    }

    private function ctrl(): \ActivityPub\Controllers\ActivityPub
    {
        return \ActivityPub\Controllers\ActivityPub::i();
    }

    // --- WebFinger ---

    public function testWebFingerReturns400WithoutResource(): void
    {
        $resp = $this->ctrl()->webfinger($this->req('GET', '/.well-known/webfinger'));
        $this->assertSame(400, $resp->getStatusCode());
        $body = json_decode((string) $resp->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testWebFingerReturns404ForWrongDomain(): void
    {
        $req  = $this->req('GET', '/.well-known/webfinger?resource=acct:slug@wrong.domain');
        $resp = $this->ctrl()->webfinger($req->withQueryParams(['resource' => 'acct:slug@wrong.domain']));
        $this->assertSame(404, $resp->getStatusCode());
    }

    // --- Actor ---

    public function testActorReturns404ForUnknownSlug(): void
    {
        $resp = $this->ctrl()->actor($this->req('GET', '/x'), 'nonexistent-slug-xyz-987');
        $this->assertSame(404, $resp->getStatusCode());
    }

    public function testActorReturns200WithValidPersonPayload(): void
    {
        $app = App::i();
        $app->disableAccessControl();
        $agent = new \MapasCulturais\Entities\Agent();
        $agent->name = 'Teste Actor AP';
        $agent->status = 1;
        $agent->save(true);
        $app->enableAccessControl();

        // Agent::slug é auto-gerado do name no save(). Confirmar que não é null.
        $slug = $agent->slug;
        $this->assertNotEmpty($slug, 'Agent deveria ter slug após save()');
        $resp = $this->ctrl()->actor($this->req('GET', "/activitypub/agent/{$slug}"), $slug);

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertStringContainsString('activity+json', $resp->getHeaderLine('Content-Type'));

        $body = json_decode((string) $resp->getBody(), true);
        $this->assertSame('Person', $body['type']);
        $this->assertStringContainsString($slug, $body['id']);
        $this->assertArrayHasKey('outbox', $body);
        $this->assertArrayHasKey('inbox', $body);
        $this->assertArrayHasKey('publicKey', $body);
        $this->assertSame('', $body['publicKey']['publicKeyPem']);
    }

    // --- Outbox ---

    public function testOutboxReturnsOrderedCollection(): void
    {
        $app = App::i();
        $app->disableAccessControl();
        $agent = new \MapasCulturais\Entities\Agent();
        $agent->name = 'Teste Outbox AP';
        $agent->status = 1;
        $agent->save(true);
        $app->enableAccessControl();

        $slug = $agent->slug;
        $resp = $this->ctrl()->outbox($this->req('GET', "/activitypub/agent/{$slug}/outbox"), $slug);

        $this->assertSame(200, $resp->getStatusCode());
        $body = json_decode((string) $resp->getBody(), true);
        $this->assertSame('OrderedCollection', $body['type']);
        $this->assertArrayHasKey('totalItems', $body);
        $this->assertArrayHasKey('first', $body);
    }

    public function testOutboxPageReturnsOrderedCollectionPage(): void
    {
        $app = App::i();
        $app->disableAccessControl();
        $agent = new \MapasCulturais\Entities\Agent();
        $agent->name = 'Teste Outbox Paginado AP';
        $agent->status = 1;
        $agent->save(true);
        $app->enableAccessControl();

        $slug = $agent->slug;
        $req  = $this->req('GET', "/activitypub/agent/{$slug}/outbox?page=1")
                    ->withQueryParams(['page' => '1']);
        $resp = $this->ctrl()->outbox($req, $slug);

        $this->assertSame(200, $resp->getStatusCode());
        $body = json_decode((string) $resp->getBody(), true);
        $this->assertSame('OrderedCollectionPage', $body['type']);
        $this->assertArrayHasKey('orderedItems', $body);
        $this->assertArrayHasKey('partOf', $body);
        // Página 1 não tem 'prev'
        $this->assertArrayNotHasKey('prev', $body);
    }

    public function testOutboxReturns404ForUnknownSlug(): void
    {
        $resp = $this->ctrl()->outbox($this->req('GET', '/x'), 'nonexistent-slug-xyz-987');
        $this->assertSame(404, $resp->getStatusCode());
    }
}
```

- [ ] **Step 3: Rodar para ver falhar**

```bash
dev/bash.sh
vendor/bin/phpunit tests/src/ActivityPub/ActivityPubControllerTest.php -v
```

- [ ] **Step 4: Implementar o Controller**

```php
<?php
declare(strict_types=1);

namespace ActivityPub\Controllers;

use ActivityPub\ActorBuilder;
use MapasCulturais\App;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class ActivityPub extends \MapasCulturais\Controller
{
    private const CONTENT_TYPE_AP  = 'application/activity+json; charset=utf-8';
    private const CONTENT_TYPE_JRD = 'application/jrd+json; charset=utf-8';
    private const PAGE_SIZE        = 20;

    // -----------------------------------------------------------------------
    // WebFinger
    // -----------------------------------------------------------------------

    public function webfinger(ServerRequestInterface $request): ResponseInterface
    {
        $params   = $request->getQueryParams();
        $resource = trim($params['resource'] ?? '');

        if ($resource === '') {
            return $this->ap(['error' => 'resource parameter required'], 400, self::CONTENT_TYPE_JRD);
        }

        if (!str_starts_with($resource, 'acct:')) {
            return $this->ap(['error' => 'Actor not found'], 404, self::CONTENT_TYPE_JRD);
        }

        [$slug, $incomingDomain] = array_pad(explode('@', substr($resource, 5), 2), 2, '');
        $domain = $this->domain();

        if ($incomingDomain !== $domain) {
            return $this->ap(['error' => 'Actor not found'], 404, self::CONTENT_TYPE_JRD);
        }

        if (!$this->findAgent($slug)) {
            return $this->ap(['error' => 'Actor not found'], 404, self::CONTENT_TYPE_JRD);
        }

        return $this->ap([
            'subject' => "acct:{$slug}@{$domain}",
            'links'   => [[
                'rel'  => 'self',
                'type' => 'application/activity+json',
                'href' => "https://{$domain}/activitypub/agent/{$slug}",
            ]],
        ], 200, self::CONTENT_TYPE_JRD);
    }

    // -----------------------------------------------------------------------
    // Actor
    // -----------------------------------------------------------------------

    public function actor(ServerRequestInterface $request, string $slug): ResponseInterface
    {
        $agent = $this->findAgent($slug);
        if (!$agent) {
            return $this->ap(['error' => 'Actor not found']);
        }

        return $this->ap(ActorBuilder::build($agent, $this->domain()));
    }

    // -----------------------------------------------------------------------
    // Inbox (stub)
    // -----------------------------------------------------------------------

    public function inbox(ServerRequestInterface $request, string $slug): ResponseInterface
    {
        if (!$this->findAgent($slug)) {
            return $this->ap(['error' => 'Actor not found'], 404);
        }

        return $this->ap([
            '@context'     => 'https://www.w3.org/ns/activitystreams',
            'type'         => 'OrderedCollection',
            'totalItems'   => 0,
            'orderedItems' => [],
        ]);
    }

    // -----------------------------------------------------------------------
    // Outbox
    // -----------------------------------------------------------------------

    public function outbox(ServerRequestInterface $request, string $slug): ResponseInterface
    {
        $agent = $this->findAgent($slug);
        if (!$agent) {
            return $this->ap(['error' => 'Actor not found'], 404);
        }

        $params    = $request->getQueryParams();
        $page      = isset($params['page']) ? max(1, (int) $params['page']) : null;
        $domain    = $this->domain();
        $outboxUri = "https://{$domain}/activitypub/agent/{$slug}/outbox";

        $app   = App::i();
        $conn  = $app->em->getConnection();
        $agentId = (int) $agent->id;

        $total = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM activitypub_activity WHERE agent_id = :id",
            ['id' => $agentId]
        );

        if ($page === null) {
            return $this->ap([
                '@context'   => 'https://www.w3.org/ns/activitystreams',
                'type'       => 'OrderedCollection',
                'id'         => $outboxUri,
                'totalItems' => $total,
                'first'      => "{$outboxUri}?page=1",
            ]);
        }

        // Usar inteiros interpolados — DBAL PDO não suporta named params para LIMIT/OFFSET
        $limit  = self::PAGE_SIZE;
        $offset = ($page - 1) * $limit;

        $rows = $conn->fetchAllAssociative(
            "SELECT payload FROM activitypub_activity
             WHERE agent_id = {$agentId}
             ORDER BY published DESC
             LIMIT {$limit} OFFSET {$offset}"
        );

        $items = array_map(fn($r) => json_decode($r['payload'], true), $rows);

        $result = [
            '@context'     => 'https://www.w3.org/ns/activitystreams',
            'type'         => 'OrderedCollectionPage',
            'id'           => "{$outboxUri}?page={$page}",
            'partOf'       => $outboxUri,
            'orderedItems' => $items,
        ];

        if ($page > 1) {
            $result['prev'] = "{$outboxUri}?page=" . ($page - 1);
        }

        $lastPage = max(1, (int) ceil($total / self::PAGE_SIZE));
        if ($page < $lastPage) {
            $result['next'] = "{$outboxUri}?page=" . ($page + 1);
        }

        return $this->ap($result);
    }

    // -----------------------------------------------------------------------
    // Activity individual
    // -----------------------------------------------------------------------

    public function activity(ServerRequestInterface $request, string $slug, string $hash): ResponseInterface
    {
        $agent = $this->findAgent($slug);
        if (!$agent) {
            return $this->ap(['error' => 'Actor not found'], 404);
        }

        $domain     = $this->domain();
        $activityId = "https://{$domain}/activitypub/agent/{$slug}/activities/{$hash}";
        $agentId    = (int) $agent->id;

        $app  = App::i();
        $conn = $app->em->getConnection();
        $row  = $conn->fetchAssociative(
            "SELECT payload FROM activitypub_activity
             WHERE activity_id = :aid AND agent_id = :agentid",
            ['aid' => $activityId, 'agentid' => $agentId]
        );

        if (!$row) {
            return $this->ap(['error' => 'Activity not found'], 404);
        }

        return $this->ap(json_decode($row['payload'], true));
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function findAgent(string $slug): ?object
    {
        $app   = App::i();
        $agent = $app->repo('Agent')->findOneBy(['slug' => $slug]);

        if (!$agent || ($agent->status ?? 0) < 1) {
            return null;
        }

        return $agent;
    }

    private function domain(): string
    {
        $app    = App::i();
        $domain = (string) ($app->config['activitypub.domain'] ?? '');
        if ($domain !== '') {
            return $domain;
        }
        return (string) parse_url((string) ($app->config['base.url'] ?? ''), PHP_URL_HOST);
    }

    private function ap(array $data, int $status = 200, string $ct = self::CONTENT_TYPE_AP): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', $ct);
    }
}
```

- [ ] **Step 5: Rodar testes de integração**

```bash
dev/bash.sh
vendor/bin/phpunit tests/src/ActivityPub/ActivityPubControllerTest.php -v
```

Esperado: 6 testes PASS.

- [ ] **Step 6: Rodar toda a suite ActivityPub**

```bash
vendor/bin/phpunit tests/src/ActivityPub/ -v
```

Esperado: todos os testes PASS.

- [ ] **Step 7: Commit**

```bash
git add src/plugins/ActivityPub/Controllers/ActivityPub.php tests/src/ActivityPub/ActivityPubControllerTest.php
git commit -m "feat(activitypub): Controller HTTP com endpoints e testes de integração"
```

---

## Chunk 6: Smoke test end-to-end

### Task 9: Verificação manual no ambiente Docker

- [ ] **Step 1: Subir o ambiente**

```bash
docker compose up -d
```

- [ ] **Step 2: Pegar o slug de um agente existente**

```bash
dev/psql.sh
SELECT slug FROM agent WHERE status = 1 LIMIT 5;
```

Anotar um slug para usar nos próximos steps.

- [ ] **Step 3: WebFinger**

```bash
curl -s "http://localhost:8080/.well-known/webfinger?resource=acct:SLUG@localhost" | jq .
```

Esperado: JSON com `subject` e `links[0].rel = "self"`.

- [ ] **Step 4: Actor**

```bash
curl -s "http://localhost:8080/activitypub/agent/SLUG" \
  -H "Accept: application/activity+json" | jq '{type, id, outbox, publicKey}'
```

Esperado: `type: "Person"`, campos `id`, `outbox`, `publicKey` presentes.

- [ ] **Step 5: Inbox — GET retorna empty collection, POST retorna 405**

```bash
curl -s "http://localhost:8080/activitypub/agent/SLUG/inbox" | jq .type
# Esperado: "OrderedCollection"

curl -s -o /dev/null -w "%{http_code}" -X POST \
  "http://localhost:8080/activitypub/agent/SLUG/inbox"
# Esperado: 405
```

- [ ] **Step 6: Outbox vazio**

```bash
curl -s "http://localhost:8080/activitypub/agent/SLUG/outbox" | jq '{type, totalItems}'
```

Esperado: `type: "OrderedCollection"`, `totalItems` como número.

- [ ] **Step 7: Rodar suite completa de testes**

```bash
dev/bash.sh
vendor/bin/phpunit tests/src/ActivityPub/ -v
```

Esperado: todos os testes PASS, 0 falhas.

- [ ] **Step 8: Commit final**

```bash
git add .
git commit -m "feat(activitypub): Phase 1 completa — Actor + Outbox"
```

---

## Notas de implementação

**Jobs e processamento:** No Mapas Culturais, jobs são processados em background via cron ou processo worker. Para testar em dev, verificar se existe `./scripts/run-jobs.sh` ou similar. Se não houver, criar um evento via UI e verificar a tabela `job` no banco para confirmar que o job foi criado corretamente.

**`Controller::i()` sem argumentos:** Se o Singleton exigir um argumento (ex: o controller_id), ajustar todas as chamadas `ActivityPubController::i()` para `ActivityPubController::i('activitypub')` no Middleware e nos testes.

**Testes que criam entidades:** Precisam rodar dentro do container via `dev/bash.sh` antes de `vendor/bin/phpunit`. O `tearDown()` em `TestCase` faz rollback automático da transação — os dados criados não persistem entre testes.

**DBAL `fetchOne`:** Verificar se `fetchOne()` existe na versão do Doctrine DBAL usada. Se não, usar `fetchColumn()` ou `fetchOne()` equivalente. Alternativa: `fetchAllAssociative("SELECT COUNT(*)...")[0]['count']`.
