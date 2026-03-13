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
skaffold.yaml          — atualizado (sync, portForward, verify)
k8s/jobs/phpunit.yaml  — novo (Job manifest de referência; usado pelo verify)
```

Nenhum arquivo do docker-compose ou Helm chart é alterado.

---

## File Sync

Durante `skaffold dev`, mudanças nos arquivos abaixo são sincronizadas diretamente no pod via rsync, sem rebuild da imagem:

| Local | Container |
|---|---|
| `src/**` | `/var/www/src` |
| `config/**` | `/var/www/config` |
| `dev/config.d/**` | `/var/www/config/config.d` |
| `public/**` | `/var/www/html` |
| `scripts/**` | `/var/www/scripts` |
| `tests/**` | `/var/www/tests` |
| `phpunit.xml` | `/var/www/` |

**Rebuild** (imagem completa) ocorre apenas quando mudam: `composer.json`, `composer.lock`, `Dockerfile`, `docker/*.sh`, `dev/docker/php.ini`.

---

## Port Forwarding

| Serviço | Porta cluster | Porta local |
|---|---|---|
| App (nginx/php-fpm) | 80 | 8080 |
| PostgreSQL | 5432 | 5432 |

Mesmo mapeamento do docker-compose — sem necessidade de mudar strings de conexão.

---

## Testes via `skaffold verify`

O `verify` usa a seção nativa do Skaffold v4 com `kubernetesCluster` execution mode. Após o deploy estabilizar, o Skaffold cria um pod efêmero com a mesma image buildada, roda PHPUnit e exibe o output no terminal.

### Configuração

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
      envFrom:
        - configMapRef:
            name: mapas-dev-env
      env:
        - name: DB_PASS
          valueFrom:
            secretKeyRef:
              name: mapas-dev-postgresql
              key: POSTGRES_PASSWORD
```

O pod usa as mesmas credenciais do app (ConfigMap `mapas-dev-env` + Secret do postgres gerado pelo Helm).

---

## `skaffold.yaml` final

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
          - src: "config/**"
            dest: /var/www/config
          - src: "dev/config.d/**"
            dest: /var/www/config/config.d
          - src: "public/**"
            dest: /var/www/html
          - src: "scripts/**"
            dest: /var/www/scripts
          - src: "tests/**"
            dest: /var/www/tests
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
      envFrom:
        - configMapRef:
            name: mapas-dev-env
      env:
        - name: DB_PASS
          valueFrom:
            secretKeyRef:
              name: mapas-dev-postgresql
              key: POSTGRES_PASSWORD

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

# Instala dependências Helm (uma vez)
helm dependency update helm/mapas

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
