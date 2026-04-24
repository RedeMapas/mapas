# RedeMapas — Mapas Culturais

**RedeMapas** é a instalação de referência do [Mapas Culturais](https://github.com/mapasculturais/mapasculturais) mantida pela rede colaborativa de gestores e desenvolvedores que sustentam a plataforma no ecossistema cultural brasileiro. Este repositório serve como ponto de convergência entre a comunidade open source e as necessidades específicas de instalações federais.

> Mapas Culturais é uma plataforma livre de mapeamento cultural que reúne agentes, espaços, eventos e projetos — usada por municípios, estados e governo federal.

---

## Estrutura de Branches

Este fork mantém duas linhas de história paralelas:

| Branch | Sincronizado com | Descrição |
|---|---|---|
| `main` | — | Branch principal de produção da RedeMapas |
| `develop` | [`mapasculturais/mapasculturais`](https://github.com/mapasculturais/mapasculturais) | Linha comunitária, integra contribuições da comunidade |
| `develop-minc` | [`culturagovbr/mapasculturais`](https://github.com/culturagovbr/mapasculturais) | Linha MinC, integra o histórico e as funcionalidades do Ministério da Cultura |

**Convenção de sufixo:**

- Branches **com** `-minc` (ex: `feat/algo-minc`) carregam o histórico da linha MinC.
- Branches **sem** `-minc` (ex: `feat/algo`) estão na linha comunitária.

Ao abrir um PR, identifique para qual linha a contribuição pertence e aponte para o branch correspondente (`develop` ou `develop-minc`).

---

## Setup Local (Docker Compose)

### Pré-requisitos

- Docker + Docker Compose v2
- Git

### Subir o ambiente padrão

```bash
git clone https://github.com/redemapas/mapas.git
cd mapas
docker compose up -d
```

## Architecture

```
Internet
    ↓
Caddy (mapas.localhost:443/80)
    ├── /ingestor*, /api/ingestor* → Frontend (Astro:4321)
    ├── /api/* → Backend (Symfony:80)
    └── /* → Backend (Symfony:80)
```

## Services

- **Caddy**: Reverse proxy with local SSL certificate
  - URL: `https://mapas.localhost:8443`
  - Alternative: `https://localhost:8443`
  - Ports: 80 (HTTP), 8443 (HTTPS)
  
- **Frontend**: Astro dev server
  - Internal: `http://frontend:4321`
  - Routes: `/ingestor*`, `/api/ingestor*`
  
- **Backend (mapas)**: Symfony application
  - Internal: `http://mapas:80`
  - Routes: `/api/*`, all other paths
  
- **PostgreSQL**: Database
  - Internal: `postgres:5432`
  - External: `localhost:5432`
  
- **Mailpit**: Email testing
  - SMTP: `localhost:1025`
  - UI: `http://localhost:8025`

## Quick Start

```bash
# Start all services
docker compose up -d

# View logs
docker compose logs -f caddy

# Stop all services
docker compose down

# Reset everything (including data)
docker compose down -v
```

## Access URLs

- **Application**: <https://mapas.localhost:8443>
- **Mailpit UI**: <http://localhost:8025>
- **Database**: localhost:5432

## SSL Certificate

Caddy automatically generates a local SSL certificate for `mapas.localhost`.

### How Port Configuration Works

According to [Caddy documentation](https://caddyserver.com/docs/automatic-https):

- **`http_port`** and **`https_port`** are **internal-only** settings
- They tell Caddy which ports to expect traffic on (not client-facing ports)
- Since Docker maps `8443→8443`, we set `https_port 8443`

```caddy
{
 http_port 80      # Internal HTTP port (for redirects)
 https_port 8443   # Internal HTTPS port (matches Docker mapping)
}

mapas.localhost:8443 {  # Site address matches client-facing port
 tls internal
 # ...
}
```

### Trust the Certificate

To avoid browser warnings:

1. **Export the root CA**:

   ```bash
   docker compose exec caddy caddy trust
   ```

2. **Or manually trust** in your browser when prompted

3. **Or use curl** with `-k` flag to skip verification (development only)

## Network Configuration

All services run on the `mapas-dev` network (bridge driver).

## File Structure

- `compose.yaml` - Main configuration with all services
- `compose.override.yml` - Environment-specific overrides (BASE_URL)
- `Caddyfile` - Caddy routing configuration

## Adding New Routes

Edit `Caddyfile` to add new route matchers:

```caddy
@newroute path /your-path/*
handle @newroute {
    reverse_proxy frontend:4321  # or mapas:80
}
```

Then restart Caddy:

```bash
docker compose restart caddy
```

### Comandos úteis

```bash
dev/bash.sh          # Shell dentro do container PHP
dev/psql.sh          # Conecta ao PostgreSQL
dev/shell.sh         # PHP interativo (psysh)
dev/pnpm.sh <cmd>    # Executa pnpm dentro do container
```

### Build do frontend

```bash
# Dentro do container (via dev/bash.sh) ou via wrapper:
dev/pnpm.sh -C src install
dev/pnpm.sh -C src run build    # build de produção
dev/pnpm.sh -C src run watch    # modo watch para desenvolvimento
```

### Testes

Os testes precisam rodar dentro do container (requerem conexão com banco):

```bash
# Todos os testes
docker compose exec mapas vendor/bin/phpunit tests/

# Arquivo específico
docker compose exec mapas vendor/bin/phpunit tests/src/EntitiesTest.php

# Método específico
docker compose exec mapas vendor/bin/phpunit tests/src/EntitiesTest.php --filter testAgentCreation
```

Cada teste roda em transação de banco que sofre rollback automático no `tearDown`.

---

## Deploy em Produção (Helm)

O repositório inclui um Helm chart em `helm/mapas/` para deploy em Kubernetes. A imagem OCI é publicada em `ghcr.io/redemapas/mapas`.

### Instalação rápida

```bash
# Redis interno (padrão — recomendado para começar)
helm install mapas ./helm/mapas \
  --set postgresql.settings.superuserPassword=$(openssl rand -base64 32)
```

### Modos de Redis

| Modo | Comando |
|---|---|
| Redis interno (padrão) | `helm install mapas ./helm/mapas` |
| Redis externo com senha | `helm install mapas ./helm/mapas -f helm/mapas/values-dev.yaml` |
| Sem Redis (filesystem) | adicione `--set redis-cache.enabled=false --set redis-sessions.enabled=false` |
| Instância única de Redis | `--set mapas.useSameRedisForCacheAndSessions=true` |

Veja `helm/mapas/README.md` para documentação completa de valores.

### Skaffold (desenvolvimento em cluster)

```bash
# Deploy dev em cluster local (kind, minikube, etc.)
skaffold dev

# Build e deploy de produção
skaffold run -p prod
```

---

## Arquitetura Rápida

```
src/
  core/          → MapasCulturais\ (framework base)
  modules/       → MapasCulturais\Modules\ (funcionalidades modulares)
  themes/        → MapasCulturais\Themes\ (temas de instalação)
    RedeMapas/   → tema ativo desta instalação
config/          → configuração base (PHP arrays)
dev/config.d/    → overrides de desenvolvimento
helm/mapas/      → Helm chart para Kubernetes
```

- **App singleton**: `MapasCulturais\App::i()`
- **ORM**: Doctrine com atributos PHP 8 (`#[ORM\Entity]`)
- **Migrações**: `src/db-updates.php` (aplicadas automaticamente no boot)
- **Hooks**: `$app->hook('entity(Agent).save:before', fn() => ...)`

---

## Funcionalidades específicas da RedeMapas

- **PWA**: `src/themes/RedeMapas/Pwa/` — suporte a instalação como app
- **WebPush**: `src/themes/RedeMapas/Push/` + `Jobs/SendWebPushNotification.php` — notificações push via VAPID
- **Subsites**: suporte a múltiplas instalações no mesmo servidor (desativado em dev via `DISABLE_SUBSITES=true`)

### Criar um subsite via psysh

```bash
# Acesso via kubectl (produção)
kubectl exec -n <namespace> <pod-name> -- php /var/www/src/tools/psysh.php

# Acesso via Docker (dev)
dev/shell.sh
```

```php
$app = MapasCulturais\App::i();
$em = $app->em;
$agent = $app->repo("Agent")->find(1);

$subsite = new \MapasCulturais\Entities\Subsite();
$subsite->name = 'Nome do Subsite';
$subsite->url = 'subsite.mapas.tec.br';
$subsite->aliasUrl = 'subsite-alias';
$subsite->namespace = 'NomeDoTema';

$reflection = new ReflectionClass($subsite);
foreach (['owner' => $agent, '_ownerId' => $agent->id] as $prop => $val) {
    $p = $reflection->getProperty($prop);
    $p->setAccessible(true);
    $p->setValue($subsite, $val);
}

$app->disableAccessControl();
$em->persist($subsite);
$em->flush();
$app->enableAccessControl();

echo "Subsite criado: " . $subsite->id;
```

---

## Instalações da rede

### Federal / Internacional

- SNIIC — <https://mapa.cultura.gov.br/>
- Cultura Viva — <https://culturaviva.cultura.gov.br/>
- Rede das Artes — <https://rededasartes.cultura.gov.br/>
- IberculturaViva — <https://mapa.iberculturaviva.org/>
- Mapa Uruguai — <https://culturaenlinea.uy/>

### Estaduais (seleção)

Amapá, Ceará, Espírito Santo, Goiás, Maranhão, Mato Grosso, Pará, Pernambuco, Paraíba, Piauí, Tocantins — ver lista completa na [documentação da comunidade](https://mapasculturais.gitbook.io).

---

## Contribuindo

1. Verifique em qual linha a mudança se encaixa (comunitária ou MinC).
2. Crie um branch a partir de `develop` (comunitária) ou `develop-minc` (MinC).
3. Use o sufixo `-minc` no branch se estiver na linha MinC.
4. Abra o PR apontando para o branch correto.

---

## Documentação

- [Documentação para desenvolvedores](https://mapasculturais.gitbook.io/documentacao-para-desenvolvedores/)
- [Documentação para devops](https://mapasculturais.gitbook.io/documentacao-para-devops/)
- [Helm chart](helm/mapas/README.md)
- [CLAUDE.md](CLAUDE.md) — instruções para o assistente de código

---

## Canais

- Telegram: [![Telegram](https://patrolavia.github.io/telegram-badge/chat.png)](https://t.me/RedeMapas)

## Licença

[GPLv3](http://gplv3.fsf.org) — software livre.
