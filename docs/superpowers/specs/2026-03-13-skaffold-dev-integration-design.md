# Skaffold Dev Integration — Design Spec

**Data:** 2026-03-13
**Escopo:** Integrar Skaffold como ambiente de staging local (kind) em paralelo ao docker-compose existente, com file sync de PHP e runner de testes via `skaffold verify`.

---

## Contexto

O projeto já possui:
- `compose.yaml` — ambiente de dev local (postgres, mailpit, app com volume mounts)
- `skaffold.yaml` — build + deploy Helm básico (portForward comentado, sem sync, sem testes)
- `helm/mapas/` — Helm chart completo com postgres, redis e PVCs
- `.github/kind-config.yaml` — configuração de cluster kind para CI

**Objetivo:** ativar `skaffold dev` como ambiente de staging local Kubernetes, mantendo docker-compose intacto para o dia-a-dia.

---

## Arquitetura

### Dois ambientes paralelos

| Fluxo | Ferramenta | Quando usar |
|---|---|---|
| Dev local rápido | `docker compose up` | Iteração PHP diária |
| Staging local K8s | `skaffold dev` | Validar comportamento em K8s, testar infra |
| Testes no cluster | `skaffold verify` | Rodar PHPUnit contra o postgres do cluster |

### Componentes alterados/criados

```
skaffold.yaml    — atualizado (sync, portForward, verify)
```

Nenhum arquivo do docker-compose, Helm chart ou CI é alterado.

---

## File Sync

Durante `skaffold dev`, mudanças nos arquivos abaixo são sincronizadas diretamente no pod via rsync, sem rebuild da imagem.

O campo `strip` remove o prefixo local antes de aplicar o `dest`, garantindo que os caminhos no container sejam corretos.

| Local | strip | dest (container) |
|---|---|---|
| `src/**` | `src/` | `/var/www/src` |
| `config/**` | `config/` | `/var/www/config` |
| `dev/config.d/**` | `dev/config.d/` | `/var/www/config/config.d` |
| `public/**` | `public/` | `/var/www/html` |
| `scripts/**` | `scripts/` | `/var/www/scripts` |
| `tests/**` | `tests/` | `/var/www/tests` |
| `phpunit.xml` | `""` | `/var/www` |

**Rebuild** (imagem completa) ocorre apenas quando mudam: `composer.json`, `composer.lock`, `Dockerfile`, `docker/*.sh`, `dev/docker/php.ini`.

### Configuração YAML

```yaml
sync:
  manual:
    - src: "src/**"
      dest: /var/www/src
      strip: "src/"
    - src: "config/**"
      dest: /var/www/config
      strip: "config/"
    - src: "dev/config.d/**"
      dest: /var/www/config/config.d
      strip: "dev/config.d/"
    - src: "public/**"
      dest: /var/www/html
      strip: "public/"
    - src: "scripts/**"
      dest: /var/www/scripts
      strip: "scripts/"
    - src: "tests/**"
      dest: /var/www/tests
      strip: "tests/"
    - src: "phpunit.xml"
      dest: /var/www
```

---

## Port Forwarding

| Serviço K8s | Porta cluster | Porta local |
|---|---|---|
| `mapas-dev` (app) | 80 | 8080 |
| `mapas-dev-postgresql` | 5432 | 5432 |

O nome `mapas-dev` é resultado do helper `mapas.fullname` com release name `mapas-dev` e chart name `mapas` (o helper retorna o release name quando já contém o chart name). Mesmo mapeamento do docker-compose.

---

## Testes via `skaffold verify`

O `verify` usa a seção nativa do Skaffold v4 com `kubernetesCluster` execution mode. Após o deploy estabilizar, o Skaffold cria um pod efêmero com a mesma image buildada, roda PHPUnit e exibe o output.

### Limitações do `verify`

O schema de container do `verify` em Skaffold v4beta11 **não suporta `envFrom`** — apenas `env`, `command`, `args`, `image` e `name`. Por isso, as variáveis de ambiente necessárias são passadas explicitamente via `env`.

### Variáveis necessárias

As variáveis mínimas para o PHPUnit conectar ao postgres do cluster:

| Variável | Fonte |
|---|---|
| `DB_HOST` | fixo: `mapas-dev-postgresql` (nome do serviço K8s) |
| `DB_PORT` | fixo: `5432` |
| `DB_NAME` | fixo: `mapas` |
| `DB_USER` | fixo: `mapas` |
| `DB_PASS` | Secret `mapas-dev-postgresql`, chave `superuserPassword` (gerada pelo subchart groundhog2k/postgres) |
| `APP_ENV` | fixo: `development` |

> **Nota:** confirmar a chave exata do Secret após o primeiro `helm install`. O subchart groundhog2k/postgres 0.2.28 gera o Secret com a chave `superuserPassword` conforme `values-dev.yaml: superuserPassword: "mapas"`.

### Configuração YAML

```yaml
verify:
  - name: phpunit
    executionMode:
      kubernetesCluster: {}
    container:
      name: phpunit
      image: ghcr.io/redemapas/mapas
      command: ["vendor/bin/phpunit"]
      args: ["--configuration", "/var/www/phpunit.xml", "--no-coverage"]
      env:
        - name: DB_HOST
          value: "mapas-dev-postgresql"
        - name: DB_PORT
          value: "5432"
        - name: DB_NAME
          value: "mapas"
        - name: DB_USER
          value: "mapas"
        - name: DB_PASS
          valueFrom:
            secretKeyRef:
              name: mapas-dev-postgresql
              key: superuserPassword
        - name: APP_ENV
          value: "development"
```

---

## `skaffold.yaml` completo

```yaml
apiVersion: skaffold/v4beta11
kind: Config
metadata:
  name: mapas

build:
  artifacts:
    - image: ghcr.io/redemapas/mapas
      docker:
        dockerfile: Dockerfile
        target: development
      sync:
        manual:
          - src: "src/**"
            dest: /var/www/src
            strip: "src/"
          - src: "config/**"
            dest: /var/www/config
            strip: "config/"
          - src: "dev/config.d/**"
            dest: /var/www/config/config.d
            strip: "dev/config.d/"
          - src: "public/**"
            dest: /var/www/html
            strip: "public/"
          - src: "scripts/**"
            dest: /var/www/scripts
            strip: "scripts/"
          - src: "tests/**"
            dest: /var/www/tests
            strip: "tests/"
          - src: "phpunit.xml"
            dest: /var/www
  local:
    useBuildkit: true

deploy:
  helm:
    releases:
      - name: mapas-dev
        chartPath: helm/mapas
        namespace: mapas-dev
        createNamespace: true
        valuesFiles:
          - helm/mapas/values-dev.yaml
        setValueTemplates:
          image.repository: "{{.IMAGE_REPO_ghcr_io_redemapas_mapas}}"
          image.tag: "{{.IMAGE_TAG_ghcr_io_redemapas_mapas}}"

portForward:
  - resourceType: service
    resourceName: mapas-dev
    namespace: mapas-dev
    port: 80
    localPort: 8080
  - resourceType: service
    resourceName: mapas-dev-postgresql
    namespace: mapas-dev
    port: 5432
    localPort: 5432

verify:
  - name: phpunit
    executionMode:
      kubernetesCluster: {}
    container:
      name: phpunit
      image: ghcr.io/redemapas/mapas
      command: ["vendor/bin/phpunit"]
      args: ["--configuration", "/var/www/phpunit.xml", "--no-coverage"]
      env:
        - name: DB_HOST
          value: "mapas-dev-postgresql"
        - name: DB_PORT
          value: "5432"
        - name: DB_NAME
          value: "mapas"
        - name: DB_USER
          value: "mapas"
        - name: DB_PASS
          valueFrom:
            secretKeyRef:
              name: mapas-dev-postgresql
              key: superuserPassword
        - name: APP_ENV
          value: "development"

profiles:
  - name: prod
    build:
      artifacts:
        - image: ghcr.io/redemapas/mapas
          docker:
            dockerfile: Dockerfile
            target: production
      local:
        useBuildkit: true
    deploy:
      helm:
        releases:
          - name: mapas
            chartPath: helm/mapas
            namespace: mapas
            createNamespace: true
            valuesFiles:
              - helm/mapas/values.yaml
            setValueTemplates:
              image.repository: "{{.IMAGE_REPO_ghcr_io_redemapas_mapas}}"
              image.tag: "{{.IMAGE_TAG_ghcr_io_redemapas_mapas}}"
```

---

## Workflow de comandos

```bash
# Pré-requisito: cluster kind (uma vez por máquina)
kind create cluster --config .github/kind-config.yaml

# Pré-requisito: dependências Helm (uma vez, ou após mudar Chart.yaml)
helm dependency update helm/mapas
# O diretório helm/mapas/charts/ deve existir antes do skaffold dev.
# Se estiver no .gitignore, rodar este passo manualmente antes do primeiro skaffold dev.

# Dev loop: build → deploy → portForward → watch sync
skaffold dev

# Em outro terminal: roda PHPUnit no cluster
skaffold verify

# Derruba tudo (preserva cluster kind)
skaffold delete
```

---

## O que NÃO muda

- `compose.yaml` — intocado
- `helm/mapas/` — intocado
- `.github/workflows/tests.yml` — intocado (continua usando docker-compose no CI)
- `Dockerfile` — intocado

---

## Fora de escopo

- Mailpit no cluster K8s (não existe subchart hoje)
- Coverage via `skaffold verify` (overhead de memória, manter separado)
- CI com Skaffold + kind (escopo futuro)
