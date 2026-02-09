# AGENTS.md - MapaCultural Development Guide

## Project Overview
MapaCultural is a PHP 8.3 application using Doctrine ORM, Slim 4, and PostgreSQL/PostGIS.
Frontend uses pnpm workspaces with Laravel Mix for asset compilation.

## Development Environments

### 1. Docker Compose (Recommended for Development)
```bash
cd dev/
./start.sh              # Start all containers (app, db, redis, mailhog)
./start.sh -b           # Rebuild and start
./start.sh -d           # Down containers first, then start

# Helper scripts (run from dev/ directory)
./bash.sh               # Shell into mapas container
./psql.sh               # Connect to PostgreSQL
./shell.sh              # PHP interactive shell (psysh)
./compile-sass.sh       # Compile SASS files
./watch-sass.sh         # Watch SASS changes
./pnpm.sh <command>     # Run pnpm commands in container
```

**Access:** http://localhost (port 80)
**MailHog:** http://localhost:8025
**PostgreSQL:** localhost:5432 (user: mapas, pass: mapas, db: mapas)

### 2. Skaffold + Kubernetes (Infrastructure/Production-like)
```bash
skaffold dev            # Development mode with hot-reload
skaffold run            # Deploy to cluster

# Port forwards (automatic)
# App: localhost:8080
# PostgreSQL: localhost:5432
```

**Helm Chart:** `helm/mapas/`
**Dev Values:** `helm/mapas/values-dev.yaml`

## Build Commands

### PHP Dependencies
```bash
composer install                    # Install dependencies
composer dump-autoload              # Regenerate autoloader
```

### Frontend Assets
```bash
cd src/
pnpm install                        # Install all workspace dependencies
pnpm run build                      # Build all assets (modules, themes, plugins)
pnpm run dev                        # Development build with source maps
pnpm run watch                      # Watch mode
```

### Docker Build
```bash
docker build -t mapas:dev --target development .
docker build -t mapas:prod --target production .
```

## Testing

### Run All Tests
```bash
# Inside container (dev/bash.sh)
./scripts/run-tests-docker.sh

# Or using PHPUnit directly
vendor/bin/phpunit tests/
```

### Run Single Test File
```bash
vendor/bin/phpunit tests/src/EntitiesTest.php
```

### Run Single Test Method
```bash
vendor/bin/phpunit tests/src/EntitiesTest.php --filter testAgentCreation
```

### Test Structure
- Tests extend `Tests\Abstract\TestCase`
- Each test runs in a database transaction (auto-rollback)
- Builders in `tests/src/Builders/` for entity creation
- Directors in `tests/src/Directors/` for complex scenarios

## Code Style Guidelines

### PHP Conventions
- **PHP Version:** 8.3+ with strict types (`declare(strict_types=1);`)
- **Namespace:** PSR-4 autoloading
  - `MapasCulturais\` -> `src/core/`
  - `MapasCulturais\Modules\` -> `src/modules/`
  - `MapasCulturais\Themes\` -> `src/themes/`
  - `Tests\` -> `tests/`

### Naming Conventions
- **Classes:** PascalCase (`AgentController`, `UserBuilder`)
- **Methods:** camelCase (`getAgentById`, `createUser`)
- **Properties:** camelCase (`$entityManager`, `$validationErrors`)
- **Constants:** UPPER_SNAKE_CASE (`STATUS_ENABLED`, `STATUS_DRAFT`)
- **Files:** Match class name (`AgentController.php`)

### Entity Status Constants
```php
Entity::STATUS_ENABLED  = 1
Entity::STATUS_DRAFT    = 0
Entity::STATUS_DISABLED = -9
Entity::STATUS_TRASH    = -10
Entity::STATUS_ARCHIVED = -2
```

### Doctrine Annotations
Entities use Doctrine ORM annotations for mapping:
```php
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'agent')]
class Agent extends Entity { }
```

### Error Handling
- Use typed exceptions from `MapasCulturais\Exceptions\`
- `PermissionDenied` for access control
- `NotFound` for missing entities
- `WorkflowRequest` for workflow state issues

### Hooks System
Register hooks using `App::i()->hook()`:
```php
$app->hook('entity(Agent).save:before', function() { });
$app->hook('entity.insert:after', function() { });
```

### Imports Order
1. PHP built-in classes
2. Doctrine classes
3. Slim/PSR classes
4. Symfony components
5. MapasCulturais core classes
6. Application-specific classes

## Database

### Migrations
Database updates use PHP scripts in `db-updates.php`:
```bash
# Applied automatically on container start via entrypoint.sh
./scripts/db-update.sh

# Or manually
php src/tools/apply-updates.php
```

### Schema Dump
Initial schema: `helm/mapas/files/init.sql` (for Kubernetes)
Development dump: `dev/db/dump.sql`

### Restore Database
```bash
./scripts/restore-dump.sh -db=mapas -u=mapas -f=dump.sql
```

## Key Directories
```
src/
  core/           # Core framework (App, Entity, Controller, etc.)
  modules/        # Feature modules
  themes/         # UI themes (BaseV1, BaseV2)
  conf/           # Configuration loader
  tools/          # CLI tools (apply-updates.php)
config/           # Configuration files
public/           # Web root (index.php, assets, files)
scripts/          # Shell scripts for operations
tests/            # PHPUnit tests
helm/             # Kubernetes Helm chart
dev/              # Docker Compose development environment
docker/           # Docker configuration files
```

## Configuration
- Main config: `config/` directory (merged at runtime)
- Dev overrides: `dev/config.d/`
- Environment variables via `env()` function
- Key vars: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `REDIS_CACHE`

## Common Tasks

### Create New Entity
1. Create class in `src/core/Entities/`
2. Add Doctrine annotations
3. Register in entity manager
4. Create Controller if needed

### Add New Module
1. Create directory in `src/modules/ModuleName/`
2. Add `Module.php` with hooks registration
3. Register in configuration

### Debug
- Set `APP_DEBUG=true` in environment
- Logs: `var/logs/app.log`
- Use `dump()` or `dd()` for debugging
- PHP shell: `dev/shell.sh` (psysh)
