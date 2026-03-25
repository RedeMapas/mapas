# Plugin ActivityPub

Expõe cada **Agente** do Mapas Culturais como um **Actor ActivityPub**, permitindo que instâncias do Fediverso (Mastodon, Pleroma, Misskey etc.) descubram e sigam agentes culturais via protocolo W3C ActivityPub.

## Como funciona

### Descoberta via WebFinger

O Fediverso descobre atores pelo protocolo [WebFinger (RFC 7033)](https://www.rfc-editor.org/rfc/rfc7033). Para buscar um agente chamado **Admin**:

```
GET /.well-known/webfinger?resource=acct:admin@seu.dominio
```

Resposta:
```json
{
  "subject": "acct:admin@seu.dominio",
  "links": [{
    "rel": "self",
    "type": "application/activity+json",
    "href": "https://seu.dominio/activitypub/agent/admin"
  }]
}
```

O username (`admin`) é gerado automaticamente a partir do nome do agente via slugify — acentos são transliterados, espaços viram hífens.

### Perfil do Actor (Person)

```
GET /activitypub/agent/{slug}
Accept: application/activity+json
```

Retorna um objeto `Person` com:
- `id`, `preferredUsername`, `name`, `summary`, `url`
- `inbox` e `outbox`
- `publicKey` (stub vazio — assinatura HTTP não implementada ainda)
- `icon` (avatar do agente, se disponível)

### Outbox paginada

```
GET /activitypub/agent/{slug}/outbox          # coleção total
GET /activitypub/agent/{slug}/outbox?page=1   # primeira página (20 itens)
```

Retorna `OrderedCollection` / `OrderedCollectionPage` com as atividades registradas do agente. As atividades são gravadas na tabela `activitypub_activity` via job assíncrono quando o agente salva eventos, espaços, projetos, oportunidades ou inscrições.

### Atividades suportadas

| Entidade Mapas       | Tipo ActivityPub | Disparado em       |
|----------------------|------------------|--------------------|
| Event                | `Event`          | Create / Update    |
| Space                | `Place`          | Create / Update    |
| Project              | `Note`           | Create / Update    |
| Opportunity          | `Note`           | Create / Update    |
| Registration         | `Note`           | Announce           |
| AgentRelation        | `Relationship`   | Add                |

## Endpoints

| Método | Rota                                              | Descrição               |
|--------|---------------------------------------------------|-------------------------|
| GET    | `/.well-known/webfinger?resource=acct:{slug}@{domain}` | Descoberta WebFinger |
| GET    | `/activitypub/agent/{slug}`                       | Perfil Actor (Person)   |
| GET    | `/activitypub/agent/{slug}/outbox[?page=N]`       | Outbox paginada         |
| GET    | `/activitypub/agent/{slug}/inbox`                 | Inbox pública vazia     |
| POST   | `/activitypub/agent/{slug}/inbox`                 | Aceita atividade com `202 Accepted` (stub) |
| GET    | `/activitypub/agent/{slug}/activities/{hash}`     | Atividade individual    |

O `{slug}` aceita tanto o nome slugificado (`admin`) quanto o ID numérico do agente (`42`) para compatibilidade.

## Configuração

Em `dev/config.d/0.main.php` (ou equivalente de produção):

```php
'activitypub.enabled' => true,
'activitypub.domain'  => '',   // deixar vazio para usar o host de base.url
```

Em `dev/config.d/plugins.php`:

```php
'ActivityPub' => ['namespace' => 'ActivityPub'],
```

## Banco de dados

A migration cria a tabela `activitypub_activity` automaticamente na inicialização do container:

```sql
id          BIGSERIAL PRIMARY KEY
agent_id    INTEGER REFERENCES agent(id) ON DELETE CASCADE
type        VARCHAR(50)          -- Create, Update, Announce, Add
object_type VARCHAR(100)         -- MapasCulturais\Entities\Event etc.
object_id   INTEGER
activity_id TEXT UNIQUE          -- URI canônica da atividade
payload     JSONB                -- payload completo ActivityPub
published   TIMESTAMPTZ
```

Índices: performance em `(agent_id, published DESC)` e deduplicação parcial em `(agent_id, object_type, object_id) WHERE type = 'Create'`.

## Testando localmente

```bash
# 1. Copiar plugin (container monta do repo principal)
cp -r src/plugins/ActivityPub /var/www/src/plugins/  # ou via bind mount

# 2. Aplicar migration
./scripts/db-update.sh

# 3. Descobrir um agente (substitua "admin" pelo slug do agente)
curl -s "http://localhost:8080/.well-known/webfinger?resource=acct:admin@localhost" | jq .

# 4. Ver perfil Actor
curl -s -H "Accept: application/activity+json" \
  "http://localhost:8080/activitypub/agent/admin" | jq .

# 5. Ver outbox
curl -s -H "Accept: application/activity+json" \
  "http://localhost:8080/activitypub/agent/admin/outbox" | jq .
```

Para descobrir slugs disponíveis:
```bash
dev/psql.sh -c "SELECT id, name FROM agent WHERE status = 1 LIMIT 10;"
```

## Limitações atuais

- **Sem assinatura HTTP**: o `publicKey.publicKeyPem` é vazio — outros servidores ActivityPub não conseguem verificar requisições de saída. Necessário para follow/unfollow real.
- **Inbox parcial**: `POST` retorna `202 Accepted` para compatibilidade mínima, mas o conteúdo recebido ainda não é processado.
- **Slug não único**: dois agentes com o mesmo nome geram o mesmo slug; o primeiro ativo encontrado é retornado.
- **Busca por slug em memória**: `findAgent()` itera todos os agentes ativos. Adequado para instâncias pequenas; instâncias grandes precisarão de coluna `activitypub_username` indexada.
