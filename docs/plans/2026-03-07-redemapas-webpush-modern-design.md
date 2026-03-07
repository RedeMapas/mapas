# Redemapas Web Push — Modernização (Design)

**Data:** 2026-03-07
**Tema:** RedeMapas
**Contexto:** Revisão e modernização da implementação de web push introduzida em 2026-03-06.
**Documento anterior:** `docs/plans/2026-03-06-redemapas-webpush-design.md`

## 1. Objetivo

Modernizar a infraestrutura de notificações web nativas do tema RedeMapas, corrigindo os pontos não-convencionais da implementação original sem reescrita total e sem migrações de banco de dados.

## 2. O que muda e por quê

### 2.1 Service Worker — de dinâmico para estático

**Problema:** `Controllers/Push.php::GET_serviceWorker()` gera JavaScript via heredoc PHP com interpolação de variáveis (`$icon`, `$fallbackUrl`). Isso impede cache adequado do browser, mistura entrega com lógica, e é não-convencional.

**Solução em duas etapas:**

1. Extrair lógica do SW para `assets-src/js/sw.js`, compilado pelo esbuild existente para `assets/js/sw.js`. Zero interpolação PHP — apenas eventos `push` e `notificationclick` puros.
2. Manter o endpoint `GET /push/serviceWorker` mas como proxy mínimo: lê `assets/js/sw.js` e serve com `Content-Type: application/javascript` e `Service-Worker-Allowed: /`. O header de escopo é necessário para controlar todas as páginas a partir de uma URL fora da raiz.

**Por que manter o endpoint PHP:** arquivos estáticos servidos pelo tema não recebem o header `Service-Worker-Allowed: /` automaticamente. O endpoint PHP é o menor passo para servir com headers corretos sem alterar configuração de servidor web.

**Variáveis removidas do SW:** `$icon` e `$fallbackUrl` eram defaults do SW para quando o payload não tinha esses valores. O backend (`SendWebPushNotification::buildPayload`) já inclui `icon` e `url` em todos os payloads, tornando os defaults desnecessários.

### 2.2 UI — de botão flutuante PHP para gatilhos declarativos

**Problema:** `Theme.php` injeta `<div>` flutuante via `template(<<*>>.body):after` com inline styles hardcoded e texto em inglês. `push-notifications.js` tem `createLauncher()` que recria os mesmos elementos — duplicação entre PHP e JS. A permissão é configurada ao carregar a página, não por ação explícita do usuário.

**Solução:**

- Remove o hook `template(<<*>>.body):after` e todo o HTML PHP inline.
- Remove `createLauncher()` do JS.
- O tema declara gatilhos via atributos `data-` em elementos já presentes no template (ex: menu do usuário, página de configurações):
  - `data-redemapas-install` — qualquer elemento que deve disparar o prompt de instalação PWA quando clicado.
  - `data-redemapas-push` — qualquer elemento que deve solicitar permissão de notificação quando clicado.
- O JS procura esses atributos no DOM e conecta o comportamento. Se nenhum elemento existir, nada acontece — sem fallback flutuante.
- A permissão (`Notification.requestPermission()`) só é chamada mediante clique explícito do usuário no elemento `data-redemapas-push`.

**Textos:** strings JS como "Notificações ativadas" / "Permissão negada" passam pelo hook `mapas.printJsObject:before` via PHP `i18n()`, chegando ao JS via `Mapas.redemapasPush.strings`. Zero texto hardcoded em inglês no JS.

### 2.3 Payload — link para entidade específica

**Problema:** `SendWebPushNotification::buildPayload()` sempre define `url = $app->createUrl('panel', 'index')`. Clicar em qualquer notificação leva ao painel genérico.

**Solução:** `buildPayload()` tenta construir a URL da entidade que originou a notificação usando `Notification::objectType` + `Notification::objectId`, convertendo o FQCN da entidade para o slug de controller via o mecanismo já existente no core. Em caso de falha (objectType nulo, entidade sem rota, exceção), mantém o fallback `panel/index`.

Exemplo:
```
objectType = "MapasCulturais\Entities\Opportunity", objectId = 42
→ url = /oportunidade/42
```

Mudança cirúrgica em `buildPayload()`, sem impacto no SW ou no frontend.

## 3. O que não muda

- `Push/SubscriptionStore.php` — lógica de upsert/remove permanece igual.
- `Push/PushConfigBuilder.php` — permanece igual.
- `Controllers/Push.php` — `POST_subscribe` e `POST_unsubscribe` permanecem iguais; apenas `GET_serviceWorker` muda de gerador para proxy.
- `Jobs/SendWebPushNotification.php` — apenas `buildPayload()` muda.
- Persistência de subscriptions no metadata do usuário — mantida por ora, sem migration.
- Biblioteca `minishlink/web-push` — mantida.
- Sistema de JobType para envio assíncrono — mantido.

## 4. Testes

### Novos testes necessários

- `GET_serviceWorker` como proxy: verifica que serve `assets/js/sw.js` com headers corretos.
- `buildPayload()` com URL específica:
  - `objectType` + `objectId` válidos → URL da entidade
  - `objectType` nulo → fallback `panel/index`
  - Exceção ao criar URL → fallback `panel/index`
  - Corpo longo (>180 chars) → truncado em 177 + `...`

### Testes existentes (sem mudança)

- `RedeMapasPushSubscriptionsTest` — `SubscriptionStore` não muda.
- `RedeMapasPushThemeConfigTest` — `PushConfigBuilder` não muda.
- `RedeMapasPwaTest` — builders de PWA não mudam.

### JS

Funções puras do JS refatorado (leitura de atributos `data-`, lógica de estado de permissão) podem ser testadas via `home-api.test.mjs` pattern já existente no tema.

## 5. Fora de escopo

- Migration de subscriptions para tabela dedicada (documentado como caminho futuro).
- Workbox ou offline support.
- Console administrativo de entregabilidade.
- Notificações agrupadas / badge count no browser.
- Segmentação por tipo de notificação.

## 6. Decisões

| Decisão | Escolha | Motivo |
|---|---|---|
| SW delivery | Endpoint PHP como proxy de arquivo estático | Headers corretos sem alterar servidor web |
| SW lógica | Arquivo compilado pelo esbuild | Testável, sem heredoc PHP, cache correto |
| UI trigger | Atributos `data-` declarativos | Remove inline styles PHP, segue padrão de separação de responsabilidades |
| Permissão | Apenas mediante clique explícito | Padrão de UX recomendado pelos browsers |
| Subscriptions | Metadata de usuário mantido | Sem migration; caminho para tabela documentado |
| Strings JS | Via `jsObject` com i18n PHP | Zero hardcode em inglês |
| URL da notificação | Entidade específica com fallback | Melhor UX sem risco de regressão |
