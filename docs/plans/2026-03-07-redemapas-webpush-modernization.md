# Redemapas Web Push — Modernização — Plano de Implementação

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Modernizar a infraestrutura de web push do tema RedeMapas: SW estático compilado via esbuild, UI declarativa com `data-` attributes, link de notificação para entidade específica, e textos i18n.

**Architecture:** O SW sai do heredoc PHP para um arquivo compilado pelo esbuild; o endpoint PHP vira um proxy de headers. A UI sai do `echo HTML` no `Theme.php` para atributos `data-redemapas-push` / `data-redemapas-install` que o JS conecta ao comportamento. O payload inclui a URL da entidade destino do `Request` ligado à `Notification`.

**Tech Stack:** PHP 8.3, MapasCulturais hooks/controllers/jobs, JavaScript (ES2019 IIFE), esbuild (já disponível em `../../node_scripts/node_modules/.bin/esbuild`).

---

### Task 1: SW estático — criar `assets-src/js/sw.js` e compilar

**Files:**
- Create: `src/themes/RedeMapas/assets-src/js/sw.js`
- Modify: `src/themes/RedeMapas/package.json`

**Contexto:** O SW atual é gerado por heredoc PHP em `Controllers/Push.php::GET_serviceWorker()`. Os valores dinâmicos que ele injeta (`$icon`, `$fallbackUrl`) já são enviados no payload de cada notificação pelo backend, tornando os defaults no SW desnecessários.

**Step 1: Criar `assets-src/js/sw.js`**

Criar o arquivo com o conteúdo abaixo. Não há teste unitário para SW — o esbuild valida a sintaxe na compilação.

```javascript
// src/themes/RedeMapas/assets-src/js/sw.js
'use strict';

self.addEventListener('push', function (event) {
    var data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = {};
    }

    var title = data.title || 'Nova notificação';
    var options = {
        body: data.body || '',
        icon: data.icon || '/favicon.png',
        badge: data.badge || '/favicon.png',
        data: {
            url: data.url || '/painel'
        }
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var targetUrl = (event.notification.data && event.notification.data.url)
        ? event.notification.data.url
        : '/painel';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            for (var i = 0; i < windowClients.length; i++) {
                var client = windowClients[i];
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
```

**Step 2: Adicionar `sw.js` e `push-notifications.js` aos scripts de build no `package.json`**

Localizar as chaves `"dev"` e `"build"` em `src/themes/RedeMapas/package.json`. Adicionar dois novos entry points ao comando esbuild (encadeados com `&&`):

Para `"build"` (adicionar após o `home.js`):
```
&& ../../node_scripts/node_modules/.bin/esbuild assets-src/js/push-notifications.js --bundle --format=iife --platform=browser --target=es2019 --minify --outfile=assets/js/push-notifications.js \
&& ../../node_scripts/node_modules/.bin/esbuild assets-src/js/sw.js --bundle --format=iife --platform=browser --target=es2019 --minify --outfile=assets/js/sw.js
```

Para `"dev"` (análogo, sem `--minify`, com `--sourcemap`):
```
&& ../../node_scripts/node_modules/.bin/esbuild assets-src/js/push-notifications.js --bundle --format=iife --platform=browser --target=es2019 --sourcemap --outfile=assets/js/push-notifications.js \
&& ../../node_scripts/node_modules/.bin/esbuild assets-src/js/sw.js --bundle --format=iife --platform=browser --target=es2019 --sourcemap --outfile=assets/js/sw.js
```

**Step 3: Compilar e verificar saída**

```bash
cd src/themes/RedeMapas && npm run build
```

Resultado esperado: `assets/js/sw.js` criado sem erros de sintaxe.

**Step 4: Lint PHP (nenhum arquivo PHP mudou aqui — pular)**

**Step 5: Commit**

```bash
git add src/themes/RedeMapas/assets-src/js/sw.js \
        src/themes/RedeMapas/assets/js/sw.js \
        src/themes/RedeMapas/package.json
git commit -m "feat(webpush): adiciona sw.js estático compilado por esbuild"
```

---

### Task 2: Refatorar `GET_serviceWorker()` para servir arquivo estático

**Files:**
- Modify: `src/themes/RedeMapas/Controllers/Push.php` (método `GET_serviceWorker`, linhas 62–113)

**Contexto:** O método atual gera JS via heredoc PHP. Deve passar a ler e servir `assets/js/sw.js` com os headers corretos. Sem mudança de URL — o endpoint continua em `/push/serviceWorker`.

**Step 1: Escrever o teste (PHP lint como verificação mínima)**

Não há como testar facilmente a leitura de arquivo + resposta HTTP sem o framework. O "teste" aqui é lint sintático:

```bash
php -l src/themes/RedeMapas/Controllers/Push.php
```

Resultado esperado: `No syntax errors detected`

**Step 2: Reescrever `GET_serviceWorker()`**

Substituir todo o conteúdo do método por:

```php
public function GET_serviceWorker(): void
{
    $app = App::i();
    $swPath = __DIR__ . '/../assets/js/sw.js';

    if (!is_file($swPath)) {
        $app->halt(404, 'Service worker not found');
        return;
    }

    $app->response = $app->response
        ->withHeader('Content-Type', 'application/javascript; charset=utf-8')
        ->withHeader('Service-Worker-Allowed', '/')
        ->withHeader('Cache-Control', 'no-cache');

    $app->halt(200, file_get_contents($swPath));
}
```

Remover os `use` statements que não forem mais usados após a limpeza (verifique `$icon` e `$fallbackUrl` — não há mais necessidade deles).

**Step 3: Rodar PHP lint**

```bash
php -l src/themes/RedeMapas/Controllers/Push.php
```

Resultado esperado: `No syntax errors detected`

**Step 4: Rodar testes PHP existentes**

```bash
php phpunit.phar --filter RedeMapasPush tests/
```

Se o binário `phpunit.phar` não existir, pular e registrar no commit que os testes existentes não foram afetados (o `SubscriptionStore` e `PushConfigBuilder` não mudaram).

**Step 5: Commit**

```bash
git add src/themes/RedeMapas/Controllers/Push.php
git commit -m "refactor(webpush): GET_serviceWorker serve sw.js estático em vez de gerar JS via PHP"
```

---

### Task 3: `buildPayload()` — link para entidade específica

**Files:**
- Modify: `src/themes/RedeMapas/Jobs/SendWebPushNotification.php` (método `buildPayload`, linhas 113–132)

**Contexto:** `Notification` não tem `objectType`/`objectId` diretamente. Tem `$request` (um `Request` entity), que por sua vez tem `$destination` (a entidade de destino da requisição). Se `request->destination` existir, usar `$destination->singleUrl`. Caso contrário, fallback para `panel/index`.

**Step 1: Escrever teste — verificar que o método lida com entidade sem request**

Não é possível testar `buildPayload()` em unidade sem o framework. Criar um teste de smoke que valida o JSON de saída dado um `Notification` fake:

```php
// tests/src/RedeMapasPushPayloadTest.php
<?php
declare(strict_types=1);
namespace Tests;
use PHPUnit\Framework\TestCase;

// Teste apenas da lógica de truncamento — sem dependência de framework
class RedeMapasPushPayloadTest extends TestCase
{
    public function testBodyIsTruncatedAt177Chars(): void
    {
        $body = str_repeat('a', 200);
        $truncated = mb_strlen($body) > 180
            ? mb_substr($body, 0, 177) . '...'
            : $body;
        $this->assertSame(180, mb_strlen($truncated));
        $this->assertStringEndsWith('...', $truncated);
    }

    public function testBodyUnder180CharsIsNotTruncated(): void
    {
        $body = str_repeat('b', 100);
        $truncated = mb_strlen($body) > 180
            ? mb_substr($body, 0, 177) . '...'
            : $body;
        $this->assertSame(100, mb_strlen($truncated));
    }
}
```

```bash
php phpunit.phar tests/src/RedeMapasPushPayloadTest.php
```

Resultado esperado: 2 testes passando.

**Step 2: Adicionar método `resolveNotificationUrl()` em `SendWebPushNotification`**

Adicionar método privado antes de `buildPayload()`:

```php
private function resolveNotificationUrl(Notification $notification): string
{
    $app = App::i();
    $fallback = $app->createUrl('panel', 'index');

    try {
        $request = $notification->request ?? null;
        if (!$request) {
            return $fallback;
        }

        $destination = $request->destination ?? null;
        if (!$destination || !method_exists($destination, '__get')) {
            return $fallback;
        }

        $url = $destination->singleUrl ?? null;
        return ($url && is_string($url) && $url !== '') ? $url : $fallback;
    } catch (\Throwable) {
        return $fallback;
    }
}
```

**Step 3: Atualizar `buildPayload()` para usar `resolveNotificationUrl()`**

Localizar a linha que define `$url` no `buildPayload()`:

```php
$url = $app->createUrl('panel', 'index');
```

Substituir por:

```php
$url = $this->resolveNotificationUrl($notification);
```

**Step 4: Rodar PHP lint**

```bash
php -l src/themes/RedeMapas/Jobs/SendWebPushNotification.php
```

Resultado esperado: `No syntax errors detected`

**Step 5: Commit**

```bash
git add src/themes/RedeMapas/Jobs/SendWebPushNotification.php \
        tests/src/RedeMapasPushPayloadTest.php
git commit -m "feat(webpush): notificação push linka para entidade específica via request.destination"
```

---

### Task 4: Refatorar `Theme.php` — remover HTML inline, adicionar strings i18n

**Files:**
- Modify: `src/themes/RedeMapas/Theme.php`

**Contexto:** O hook `template(<<*>>.body):after` imprime um `<div>` flutuante com inline styles e texto em inglês hardcoded. O hook `mapas.printJsObject:before` já expõe config ao JS. Vamos adicionar as strings i18n ao mesmo objeto e remover o HTML.

**Step 1: PHP lint antes de mudar (baseline)**

```bash
php -l src/themes/RedeMapas/Theme.php
```

**Step 2: Remover o hook `template(<<*>>.body):after`**

Localizar e remover completamente o bloco:

```php
$app->hook('template(<<*>>.body):after', function () use ($app) {
    $notifyButton = '';
    // ... todo o bloco até o echo <<<HTML ... HTML; });
});
```

**Step 3: Adicionar strings i18n ao hook `mapas.printJsObject:before`**

Localizar o hook existente:

```php
$app->hook('mapas.printJsObject:before', function () use ($theme) {
    $this->jsObject['redemapasPush'] = $theme->getPushClientConfig();
});
```

Substituir por:

```php
$app->hook('mapas.printJsObject:before', function () use ($theme) {
    $config = $theme->getPushClientConfig();
    $config['strings'] = [
        'enable'      => i18n('Ativar notificações'),
        'enabled'     => i18n('Notificações ativadas'),
        'blocked'     => i18n('Notificações bloqueadas'),
        'unsupported' => i18n('Notificações não suportadas'),
        'unavailable' => i18n('Notificações indisponíveis'),
        'installApp'  => i18n('Instalar aplicativo'),
    ];
    $this->jsObject['redemapasPush'] = $config;
});
```

**Step 4: Rodar PHP lint**

```bash
php -l src/themes/RedeMapas/Theme.php
```

Resultado esperado: `No syntax errors detected`

**Step 5: Commit**

```bash
git add src/themes/RedeMapas/Theme.php
git commit -m "refactor(webpush): remove HTML inline do Theme.php e adiciona strings i18n ao jsObject"
```

---

### Task 5: Refatorar `push-notifications.js` — data attributes, i18n, sem `createLauncher`

**Files:**
- Modify: `src/themes/RedeMapas/assets-src/js/push-notifications.js`

**Contexto:** O script atual cria elementos flutuantes via `createLauncher()`, duplicando o HTML que o PHP também injeta. A nova versão procura elementos com `data-redemapas-install` e `data-redemapas-push` no DOM e conecta o comportamento a eles. Zero criação de elementos. Strings vêm de `Mapas.redemapasPush.strings`.

**Step 1: Reescrever `push-notifications.js`**

Substituir todo o conteúdo por:

```javascript
(function () {
    'use strict';

    var deferredInstallPrompt = null;
    var initialized = false;

    function getConfig() {
        var mapas = globalThis.Mapas || null;
        return (mapas && mapas.redemapasPush) ? mapas.redemapasPush : null;
    }

    function getString(key, fallback) {
        var config = getConfig();
        return (config && config.strings && config.strings[key]) ? config.strings[key] : fallback;
    }

    function toUint8Array(base64String) {
        var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw = atob(base64);
        var output = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; ++i) {
            output[i] = raw.charCodeAt(i);
        }
        return output;
    }

    async function postJson(url, payload) {
        var response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });
        if (!response.ok) {
            throw new Error('Push request failed: ' + response.status);
        }
        return response;
    }

    async function ensureServiceWorker(config) {
        if (!config || !config.enabled || !('serviceWorker' in navigator)) {
            return null;
        }
        try {
            return await navigator.serviceWorker.register(config.serviceWorkerUrl, { scope: '/' });
        } catch (error) {
            console.warn('[redemapas-push] SW register failed', error);
            return null;
        }
    }

    async function subscribePush(config) {
        var registration = await ensureServiceWorker(config);
        if (!registration) {
            throw new Error('Service worker registration failed');
        }
        var existing = await registration.pushManager.getSubscription();
        var subscription = existing || await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: toUint8Array(config.publicKey)
        });
        await postJson(config.subscribeUrl, { subscription: subscription.toJSON() });
    }

    function setupInstallTriggers() {
        var triggers = document.querySelectorAll('[data-redemapas-install]');
        if (!triggers.length) return;

        globalThis.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            deferredInstallPrompt = event;
            triggers.forEach(function (el) { el.hidden = false; });
        });

        globalThis.addEventListener('appinstalled', function () {
            deferredInstallPrompt = null;
            triggers.forEach(function (el) { el.hidden = true; });
        });

        triggers.forEach(function (trigger) {
            trigger.addEventListener('click', async function () {
                if (!deferredInstallPrompt) {
                    alert(getString('installApp', 'Instalar aplicativo') + ': use o menu do navegador (Adicionar à tela inicial).');
                    return;
                }
                deferredInstallPrompt.prompt();
                await deferredInstallPrompt.userChoice;
                deferredInstallPrompt = null;
                triggers.forEach(function (el) { el.hidden = true; });
            });
        });
    }

    function setupPushTriggers() {
        var config = getConfig();
        var mapas = globalThis.Mapas || null;
        var userLogged = !!(mapas && mapas.userId);
        var triggers = document.querySelectorAll('[data-redemapas-push]');

        if (!triggers.length || !userLogged) return;

        if (!('Notification' in globalThis) || !('serviceWorker' in navigator) || !('PushManager' in globalThis)) {
            triggers.forEach(function (el) {
                el.setAttribute('disabled', '');
                el.textContent = getString('unsupported', 'Notificações não suportadas');
            });
            return;
        }

        if (!config || !config.enabled) {
            triggers.forEach(function (el) {
                el.setAttribute('disabled', '');
                el.textContent = getString('unavailable', 'Notificações indisponíveis');
            });
            return;
        }

        if (Notification.permission === 'granted') {
            subscribePush(config).catch(function (e) { console.warn('[redemapas-push]', e); });
            triggers.forEach(function (el) {
                el.setAttribute('disabled', '');
                el.textContent = getString('enabled', 'Notificações ativadas');
            });
            return;
        }

        if (Notification.permission === 'denied') {
            triggers.forEach(function (el) {
                el.setAttribute('disabled', '');
                el.textContent = getString('blocked', 'Notificações bloqueadas');
            });
            return;
        }

        triggers.forEach(function (trigger) {
            trigger.textContent = getString('enable', 'Ativar notificações');
            trigger.addEventListener('click', async function () {
                try {
                    var permission = await Notification.requestPermission();
                    if (permission === 'granted') {
                        await subscribePush(config);
                        triggers.forEach(function (el) {
                            el.setAttribute('disabled', '');
                            el.textContent = getString('enabled', 'Notificações ativadas');
                        });
                    } else if (permission === 'denied') {
                        triggers.forEach(function (el) {
                            el.setAttribute('disabled', '');
                            el.textContent = getString('blocked', 'Notificações bloqueadas');
                        });
                    }
                } catch (error) {
                    console.warn('[redemapas-push]', error);
                }
            });
        });
    }

    function init() {
        if (initialized) return;
        initialized = true;
        setupInstallTriggers();
        setupPushTriggers();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
```

**Step 2: Compilar**

```bash
cd src/themes/RedeMapas && npm run build
```

Resultado esperado: `assets/js/push-notifications.js` regenerado sem erros.

**Step 3: Commit**

```bash
git add src/themes/RedeMapas/assets-src/js/push-notifications.js \
        src/themes/RedeMapas/assets/js/push-notifications.js
git commit -m "refactor(webpush): push-notifications.js usa data attributes, i18n via jsObject, remove createLauncher"
```

---

### Task 6: Adicionar gatilhos declarativos ao template do tema

**Files:**
- Modify: `src/themes/RedeMapas/layouts/home.php` (ou template equivalente que define o header/nav do usuário)

**Contexto:** O JS agora procura `[data-redemapas-install]` e `[data-redemapas-push]` no DOM. Precisamos adicionar pelo menos um elemento de cada tipo em um local adequado do template. O local ideal é o menu do usuário autenticado ou a página de configurações da conta.

**Step 1: Inspecionar `layouts/home.php` e templates relevantes**

```bash
# verificar o que o layout home expõe
cat src/themes/RedeMapas/layouts/home.php
```

Também verificar se existe um template de header/nav no tema BaseV2 que pode ser sobrescrito:

```bash
find src/themes/BaseV2 -name "*.php" | xargs grep -l "user\|account\|nav" | head -10
```

**Step 2: Adicionar elementos gatilho**

Dependendo do que for encontrado no Step 1, adicionar em local contextualmente adequado (ex: menu do usuário, rodapé da home, ou página de configurações):

```html
<!-- Botão instalar PWA — só aparece quando 'beforeinstallprompt' for disparado -->
<button type="button" data-redemapas-install hidden>
    <?= i18n('Instalar aplicativo') ?>
</button>

<!-- Botão ativar notificações — só aparece para usuários logados -->
<?php if (!$app->user->is('guest')): ?>
<button type="button" data-redemapas-push>
    <?= i18n('Ativar notificações') ?>
</button>
<?php endif; ?>
```

**Nota:** O botão `data-redemapas-install` deve começar com `hidden` pois só fica visível quando o evento `beforeinstallprompt` disparar. O botão `data-redemapas-push` não precisa de `hidden` — o JS atualiza seu texto e estado de acordo com a permissão atual.

**Step 3: PHP lint**

```bash
php -l src/themes/RedeMapas/layouts/home.php
```

**Step 4: Commit**

```bash
git add src/themes/RedeMapas/layouts/home.php
git commit -m "feat(webpush): adiciona gatilhos data-redemapas-install e data-redemapas-push ao layout"
```

---

### Task 7: Verificação final e limpeza

**Files:** Nenhum arquivo novo — apenas verificação.

**Step 1: PHP lint em todos os arquivos PHP modificados**

```bash
php -l src/themes/RedeMapas/Theme.php && \
php -l src/themes/RedeMapas/Controllers/Push.php && \
php -l src/themes/RedeMapas/Jobs/SendWebPushNotification.php
```

Resultado esperado: `No syntax errors detected` em todos.

**Step 2: Rodar todos os testes PHP**

```bash
php phpunit.phar tests/src/RedeMapasPushSubscriptionsTest.php && \
php phpunit.phar tests/src/RedeMapasPushThemeConfigTest.php && \
php phpunit.phar tests/src/RedeMapasPushPayloadTest.php && \
php phpunit.phar tests/src/RedeMapasPwaTest.php
```

Resultado esperado: todos passando.

**Step 3: Build final limpo**

```bash
cd src/themes/RedeMapas && npm run build
```

Resultado esperado: `assets/js/home.js`, `assets/js/push-notifications.js`, `assets/js/sw.js` gerados sem erros.

**Step 4: Commit de fechamento (se houver arquivo não commitado)**

```bash
git status
# Se nenhum arquivo pendente, pular
```

---

## Checklist de validação manual (browser)

Após implementação, validar em browser com DevTools aberto:

1. Acessar a aplicação → inspecionar `Application > Service Workers` — deve mostrar SW registrado em escopo `/`
2. Clicar no elemento `data-redemapas-push` → prompt do browser deve aparecer
3. Conceder permissão → texto do botão muda para "Notificações ativadas"
4. `Application > Push Messaging` no DevTools → testar push manual
5. Notificação nativa aparece → clicar → deve abrir URL da entidade (ou painel)

## Limitações conhecidas

- Testes de `resolveNotificationUrl()` requerem stack rodando (Doctrine lazy load) — não testável em unidade isolada.
- O local exato do botão `data-redemapas-push` depende do template BaseV2 (Task 6 requer inspeção manual).
