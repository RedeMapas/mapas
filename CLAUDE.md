# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Mapas Culturais is a PHP 8.3 web application for cultural mapping (agents, spaces, events, projects). Built on Slim 4, Doctrine ORM, PostgreSQL/PostGIS. Frontend uses pnpm workspaces per theme/module. This repo is the **RedeMapas** installation of the Mapas Culturais platform.

Active theme: `redemapas` (namespace `redemapas`, extends `MapasCulturais\Themes\BaseV2\Theme`).

## Development Environment

```bash
# Start Docker Compose stack (app on :8080, mailpit on :8025)
docker compose up -d

# Utility scripts (run from repo root)
dev/bash.sh          # Shell into container
dev/psql.sh          # Connect to PostgreSQL
dev/shell.sh         # PHP interactive shell (psysh)
dev/pnpm.sh <cmd>    # Run pnpm commands inside container
```

## Build Commands

```bash
# PHP dependencies
composer install
composer dump-autoload

# Frontend assets (run inside container or via dev/pnpm.sh)
cd src/
pnpm install
pnpm run build   # production
pnpm run dev     # dev build with source maps
pnpm run watch   # watch mode
```

pnpm workspace packages: `src/modules/*`, `src/plugins/*`, `src/themes/*`, `src/node_scripts`.

## Testing

Tests must run inside the container (needs DB connection):

```bash
# All tests
vendor/bin/phpunit tests/

# Single file
vendor/bin/phpunit tests/src/EntitiesTest.php

# Single method
vendor/bin/phpunit tests/src/EntitiesTest.php --filter testAgentCreation

# Via script
./scripts/run-tests-docker.sh
```

Each test wraps in a DB transaction that auto-rolls back in `tearDown()`. Test helpers:
- `tests/src/Abstract/TestCase.php` — base class (extend this)
- `tests/src/Builders/` — entity builders
- `tests/src/Directors/` — test directors
- `tests/src/Factories/` — factories

## Architecture

### PSR-4 Namespaces → Directories

| Namespace | Directory |
|---|---|
| `MapasCulturais\` | `src/core/` |
| `MapasCulturais\Modules\` | `src/modules/` |
| `MapasCulturais\Themes\` | `src/themes/` |
| `Tests\` | `tests/` |

### Core Concepts

- **App** (`src/core/App.php`): Singleton. Access via `App::i()`. Holds entity manager (`$app->em`), cache, hook system, controller registry.
- **Entity** (`src/core/Entity.php`): Base for all Doctrine entities. Status constants: `STATUS_ENABLED=1`, `STATUS_DRAFT=0`, `STATUS_DISABLED=-9`, `STATUS_TRASH=-10`, `STATUS_ARCHIVED=-2`.
- **Controller** (`src/core/Controller.php`): Slim 4 route handlers. Register via `$app->registerController('name', ClassName::class)`.
- **Hooks** (`src/core/Hooks.php`): Event system. `$app->hook('entity(Agent).save:before', fn() => ...)`.

### Theme Hierarchy

`redemapas` (RedeMapas) → `BaseV2` → `BaseV1`. Theme folders under `src/themes/<ThemeName>/`:
- `Theme.php` — main class, `_init()` registers hooks/controllers/assets
- `views/` — PHP view templates
- `layouts/` — page layout wrappers
- `assets-src/` — source JS/CSS (built to `assets/`)

### Modules

Feature modules live in `src/modules/`. Each has its own `Module.php` with `_init()`. Key modules: `Components`, `Entities`, `EvaluationMethod*`, `Home`, `GeoDivisions`, etc.

### Configuration

- `config/` — base config files (PHP arrays)
- `dev/config.d/` — dev override config (active: `0.main.php`, sets `themes.active => 'redemapas'`)
- Env vars: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `REDIS_CACHE`, `APP_DEBUG`, `BASE_URL`

### Database

Migrations live in `src/db-updates.php` (auto-applied on container start via `docker/entrypoint.sh`).

```bash
./scripts/db-update.sh           # Apply pending DB updates
./scripts/restore-dump.sh -db=mapas -u=mapas -f=dump.sql
```

## Code Conventions

- `declare(strict_types=1);` in all PHP files
- Doctrine PHP 8 attributes for ORM mappings (`#[ORM\Entity]`, `#[ORM\Table(...)]`)
- Exceptions from `MapasCulturais\Exceptions\`: `PermissionDenied`, `NotFound`, `WorkflowRequest`
- Debug: set `APP_DEBUG=true`, logs at `var/logs/app.log`, use `dump()` / `dd()`
- `doctrine.isDev` in config should be `false` unless modifying entity mappings

## Design Context

### Users
Dois perfis primários com peso igual:
1. **Gestores públicos** (prefeituras, secretarias) — avaliando a plataforma para adoção em políticas públicas. Precisam de clareza institucional, confiança e evidência de adoção real.
2. **Agentes culturais** (artistas, coletivos, produtores) — buscando editais, cadastrando espaços e eventos. Precisam de descoberta fácil, cards atrativos e ações claras.

### Brand Personality
**Colaborativo, acolhedor e brasileiro.** A Rede Mapas é comunidade antes de ser software. Calor humano, diversidade territorial, software livre. Não é governo frio, não é startup fria — é ecossistema vivo.

Três palavras: **Colaborativo · Acolhedor · Territorial**

### Aesthetic Direction
Redesign ousado inspirado em Eventbrite/Sympla: descoberta fácil, cards atrativos, conteúdo em primeiro plano. Visual moderno e caloroso — não um portal governamental, não um SaaS genérico. Identidade brasileira presente nas cores e na linguagem.

**Anti-referência**: GOV.BR frio e burocrático; dark mode com glows; glassmorphism.

### Design Principles
1. **Conteúdo em primeiro plano** — Eventos, editais e espaços reais devem aparecer na home, não só descrições abstratas da plataforma.
2. **Calor antes de formalidade** — Linguagem humana, imagens de pessoas e territórios, espaço generoso. Evitar all-caps excessivo e tom institucional.
3. **Descoberta intuitiva** — Cards claros, hierarquia visual limpa, ações óbvias sem necessidade de instrução.
4. **Identidade territorial brasileira** — Cor, diversidade regional e cultura como elementos visuais, não só texto.
5. **Acessibilidade sem concessões** — WCAG AA mínimo, mobile-first, alvos de toque adequados (mín. 44px), contraste verificado.

### Palette Base
- Azul institucional: `#0033a0` / `#0056c8`
- Verde ação: `#00a76f`
- Background neutro: `#f2f2f2`
- Tipografia atual: Source Sans Pro (manter ou substituir por fonte com personalidade)

## RedeMapas-Specific Features

- **WebPush/PWA**: `src/themes/RedeMapas/Push/`, `Pwa/`, `Controllers/Push.php`; VAPID keys via env (`REDEMAPAS_VAPID_*`)
- **Push job**: `src/themes/RedeMapas/Jobs/SendWebPushNotification.php`
- **Subsite support**: disabled in dev (`DISABLE_SUBSITES=true`); subsites created via psysh (see README)
