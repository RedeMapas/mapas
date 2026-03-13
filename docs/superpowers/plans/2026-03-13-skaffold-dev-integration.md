# Skaffold Dev Integration Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ativar `skaffold dev` como ambiente de staging local K8s (kind) com file sync de PHP, portForward e `skaffold verify` rodando PHPUnit no cluster.

**Architecture:** Um único arquivo é alterado (`skaffold.yaml`). Nenhum arquivo do docker-compose, Helm chart ou CI é tocado. O Skaffold usa o Helm chart existente (`helm/mapas/`) com `values-dev.yaml`.

**Tech Stack:** Skaffold v4beta11, Helm 3, kind, Docker BuildKit, PHPUnit 10.

---

## Chunk 1: Atualizar skaffold.yaml

### Task 1: Pré-requisitos (one-time setup)

**Files:**
- Nenhum arquivo criado/modificado — apenas comandos de ambiente

- [ ] **Step 1: Verificar que kind está instalado**

```bash
kind version
```
Expected: `kind v0.x.x`

Se não estiver instalado: `go install sigs.k8s.io/kind@latest` ou pelo package manager.

- [ ] **Step 2: Criar cluster kind (se não existir)**

```bash
kind get clusters
```

Se não houver cluster `kind` listado:

```bash
kind create cluster --config .github/kind-config.yaml
```

Expected: `Creating cluster "kind" ...` seguido de `Set kubectl context to "kind-kind"`.

- [ ] **Step 3: Baixar dependências Helm**

```bash
helm dependency update helm/mapas
```

Expected: diretório `helm/mapas/charts/` criado com arquivos `.tgz` do postgres e redis.

> **Nota:** `helm/mapas/charts/` pode estar no `.gitignore`. Este passo precisa ser re-executado após clonar o repo ou atualizar `Chart.yaml`.

---

### Task 2: Reescrever skaffold.yaml

**Files:**
- Modify: `skaffold.yaml`

- [ ] **Step 1: Ler o arquivo atual para confirmar o estado**

```bash
cat skaffold.yaml
```

Expected: arquivo existente com `build`, `deploy` (helm), `portForward` comentado, `profiles` (prod).

- [ ] **Step 2: Substituir o conteúdo completo**

Reescrever `skaffold.yaml` com o seguinte conteúdo:

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
              key: POSTGRES_PASSWORD  # deployment.yaml linha 55 usa esta chave; Task 4 Step 1 confirma em runtime
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

- [ ] **Step 3: Validar sintaxe com skaffold diagnose**

```bash
skaffold diagnose
```

Expected: sem erros de parse. Pode mostrar warnings sobre contexto K8s — ignorar se o kind estiver ativo.

---

### Task 3: Verificar portForward e sync

**Files:**
- Nenhum arquivo adicional

- [ ] **Step 1: Subir o ambiente**

```bash
skaffold dev
```

Expected:
- Build da imagem com target `development`
- Deploy Helm no namespace `mapas-dev`
- Linhas `Port forwarding service/mapas-dev in namespace mapas-dev, remote port 80 -> http://127.0.0.1:8080`
- Linhas `Port forwarding service/mapas-dev-postgresql in namespace mapas-dev, remote port 5432 -> 127.0.0.1:5432`
- Watcher ativo (não retorna ao prompt)

- [ ] **Step 2: Confirmar app respondendo**

Em outro terminal:

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/
```

Expected: `200` ou `302` (redirect para login).

- [ ] **Step 3: Confirmar sync de arquivo PHP**

Em outro terminal, adicionar uma linha temporária em qualquer arquivo PHP sob `src/`:

```bash
echo "// sync-test" >> src/core/App.php
```

Expected: no terminal do `skaffold dev`, aparecer linha como:
```
Syncing 1 files for ghcr.io/redemapas/mapas:...
```

Remover a linha adicionada:

```bash
# Remover a linha adicionada (usando o editor ou git checkout)
git restore src/core/App.php
```

Expected: sync reverso da remoção também aparece no log.

---

### Task 4: Verificar secret key e rodar PHPUnit

> **Pré-requisito:** `skaffold dev` deve estar rodando em outro terminal (Task 3 Step 1).

**Files:**
- Possivelmente modify: `skaffold.yaml` (se a chave do secret for diferente do esperado)

- [ ] **Step 1: Confirmar nome e chave do Secret postgres**

```bash
kubectl get secret mapas-dev-postgresql -n mapas-dev -o jsonpath='{.data}' | jq 'keys'
```

Expected: lista de chaves contendo `POSTGRES_PASSWORD`.

> **Nota de consistência:** O `deployment.yaml` (linha 55) usa explicitamente `POSTGRES_PASSWORD` como chave do Secret — o plano segue essa referência. O spec original documentou `superuserPassword` (chave do subchart groundhog2k), mas a chave real no Secret é determinada pelo template Helm. Se o resultado acima mostrar `superuserPassword` em vez de `POSTGRES_PASSWORD`, atualizar `skaffold.yaml` na seção `verify.container.env[DB_PASS].valueFrom.secretKeyRef.key` antes de prosseguir para o commit (Task 5).

- [ ] **Step 2: Rodar testes via skaffold verify** (somente após Step 1 confirmar a chave)

```bash
skaffold verify
```

Expected:
- Pod `phpunit` criado no namespace `mapas-dev`
- Output do PHPUnit no terminal (dots ou F/E por testes passando/falhando)
- Linha final: `OK (N tests, M assertions)` ou relatório de falhas conhecidas
- Pod termina e é removido automaticamente

> Se o pod falhar com `CrashLoopBackOff` ou `Error`, verificar logs:
> ```bash
> kubectl logs -n mapas-dev -l skaffold.dev/run-id --previous
> ```

---

### Task 5: Commit (somente após Task 4 Step 1 confirmar a chave do Secret)

**Files:**
- Modify: `skaffold.yaml`

- [ ] **Step 1: Confirmar apenas skaffold.yaml mudou**

```bash
git diff --name-only
```

Expected: apenas `skaffold.yaml`.

- [ ] **Step 2: Commit**

```bash
git add skaffold.yaml
git commit -m "feat: ativa skaffold dev com sync, portForward e verify PHPUnit

- Adiciona sync manual de src/, config/, public/, tests/ com strip correto
- Descomenta e configura portForward (8080→app, 5432→postgres)
- Adiciona verify com PHPUnit rodando no cluster via kubernetesCluster mode
- Mantém profile prod intacto; docker-compose não alterado"
```

---

## Referência: comandos do dia-a-dia

```bash
# Sobe staging K8s local
skaffold dev

# Roda PHPUnit no cluster (outro terminal, com skaffold dev ativo)
skaffold verify

# Derruba o ambiente (preserva cluster kind)
skaffold delete

# Destrói o cluster kind completamente
kind delete cluster
```
