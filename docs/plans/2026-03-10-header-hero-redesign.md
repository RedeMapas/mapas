# Header + Hero Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Adicionar cabeçalho sticky (logo + login) e reorganizar o hero para coluna única centralizada sem imagem externa.

**Architecture:** O header é adicionado no `layouts/home.php` antes do `$TEMPLATE_CONTENT`. O hero em `views/site/index.php` é reescrito de grid 2 colunas para coluna única centralizada. O CSS em `home.scss` é atualizado removendo as regras de `.hero__art`, `.hero__copy` e adicionando `.site-header`. O `home.js` recebe o scroll shadow do header. `src/themes/RedeMapas/` é um submódulo git — commitar lá primeiro, depois atualizar o repo pai.

**Tech Stack:** PHP 8.3 (templates), SCSS (sass via pnpm), JavaScript ES modules (esbuild).

**Design doc:** `docs/plans/2026-03-10-header-hero-redesign-design.md`

**Build:** `cd src && pnpm run build`

**Ver no browser:** `http://localhost:8080`

---

## Task 1: Header HTML no layout

**Files:**
- Modify: `src/themes/RedeMapas/layouts/home.php`

**Step 1: Ler o arquivo**

```bash
cat src/themes/RedeMapas/layouts/home.php
```

Confirmar a posição do `<?= $TEMPLATE_CONTENT ?>` — o header vai antes dele.

**Step 2: Adicionar o header antes de `$TEMPLATE_CONTENT`**

Inserir o seguinte bloco entre o `<body ...>` e o `<?= $TEMPLATE_CONTENT ?>`:

```php
        <header class="site-header" data-site-header>
            <div class="container site-header__inner">
                <a class="site-header__logo" href="<?= $app->createUrl('site', 'index') ?>" aria-label="Rede Mapas — página inicial">
                    <img src="<?= $app->config['app.logo'] ?? '' ?>" alt="Rede Mapas" height="36" onerror="this.style.display='none'">
                    <span class="site-header__logo-text">Rede Mapas</span>
                </a>
                <nav class="site-header__actions" aria-label="<?= \MapasCulturais\i::__('Ações do cabeçalho') ?>">
                    <?php if (!$app->user->is('guest')): ?>
                    <a class="site-header__btn site-header__btn--panel" href="<?= $app->createUrl('panel', 'index') ?>">
                        <?= \MapasCulturais\i::__('Acessar painel') ?>
                    </a>
                    <?php else: ?>
                    <a class="site-header__btn site-header__btn--login" href="<?= $app->createUrl('auth', 'login') ?>">
                        <?= \MapasCulturais\i::__('Entrar') ?>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>
```

> **Nota:** `$app->config['app.logo']` pode não existir — o `onerror` garante que a imagem desaparece e o texto `site-header__logo-text` fica visível. Verifique a chave de config disponível lendo `dev/config.d/0.main.php` ou use diretamente a variável `$heroLogos` que está definida no `index.php`. Porém `$heroLogos` não está disponível no layout — use o fallback de texto por ora.

**Step 3: Build e verificar visualmente**

```bash
cd /home/lucas/code/redemapas/mapas/src && pnpm run build
```

Abrir `http://localhost:8080` e confirmar que um header aparece no topo (ainda sem CSS — pode estar sem estilo).

**Step 4: Commit no submódulo**

```bash
cd /home/lucas/code/redemapas/mapas/src/themes/RedeMapas
git add layouts/home.php
git commit -m "feat(home): adiciona header sticky com logo e botão de login"
```

**Step 5: Atualizar repo pai**

```bash
cd /home/lucas/code/redemapas/mapas
git add src/themes/RedeMapas
git commit -m "chore: atualiza submodule RedeMapas (header layout)"
```

---

## Task 2: CSS do header + scroll shadow JS

**Files:**
- Modify: `src/themes/RedeMapas/assets-src/sass/home.scss`
- Modify: `src/themes/RedeMapas/assets-src/js/home.js`

**Step 1: Ler os arquivos**

Ler `home.scss` e `home.js` para entender o estado atual antes de editar.

**Step 2: Adicionar CSS do header no home.scss**

Adicionar no início do arquivo, após o `* { box-sizing: border-box; }` e antes do `body.redemapas-home`:

```scss
/* ── SITE HEADER ────────────────────────────────── */
.site-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: #fff;
  border-bottom: 1px solid #e8e8e8;
  transition: box-shadow 0.2s;
}

.site-header.scrolled {
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.site-header__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 56px;
}

.site-header__logo {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  text-decoration: none;
  color: #0033a0;
}

.site-header__logo img {
  height: 36px;
  width: auto;
  display: block;
}

.site-header__logo-text {
  font-weight: 700;
  font-size: 1rem;
  color: #0033a0;
  font-family: 'Source Sans Pro', Arial, sans-serif;
}

.site-header__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  padding: 0.5rem 1.2rem;
  min-height: 44px;
  font-weight: 700;
  font-size: 0.875rem;
  text-decoration: none;
  font-family: 'Source Sans Pro', Arial, sans-serif;
  transition: filter 0.15s;
}

.site-header__btn:hover {
  filter: brightness(1.08);
}

.site-header__btn--panel {
  background: #0056c8;
  color: #fff;
}

.site-header__btn--login {
  background: #007a52;
  color: #fff;
}

@media (max-width: 540px) {
  .site-header__logo img {
    height: 28px;
  }

  .site-header__btn {
    font-size: 0.8rem;
    padding: 0.4rem 0.9rem;
    min-height: 40px;
  }
}
```

**Step 3: Adicionar scroll shadow no home.js**

Dentro do `document.addEventListener('DOMContentLoaded', () => { ... })` existente, adicionar **antes** do código de notificações:

```js
    // scroll shadow no header
    const siteHeader = document.querySelector('[data-site-header]');
    if (siteHeader) {
        window.addEventListener('scroll', () => {
            siteHeader.classList.toggle('scrolled', window.scrollY > 4);
        }, { passive: true });
    }
```

**Step 4: Build e verificar**

```bash
cd /home/lucas/code/redemapas/mapas/src && pnpm run build
```

Confirmar em `http://localhost:8080`:
- Header branco fixo no topo
- Logo "Rede Mapas" visível à esquerda
- Botão "Entrar" ou "Acessar painel" à direita
- Ao scrollar, shadow sutil aparece

**Step 5: Commit no submódulo**

```bash
cd /home/lucas/code/redemapas/mapas/src/themes/RedeMapas
git add assets-src/sass/home.scss assets-src/js/home.js
git commit -m "feat(home): CSS e scroll shadow do header sticky"
```

**Step 6: Atualizar repo pai**

```bash
cd /home/lucas/code/redemapas/mapas
git add src/themes/RedeMapas
git commit -m "chore: atualiza submodule RedeMapas (CSS header)"
```

---

## Task 3: Hero coluna única centralizada

**Files:**
- Modify: `src/themes/RedeMapas/views/site/index.php`
- Modify: `src/themes/RedeMapas/assets-src/sass/home.scss`

**Step 1: Ler o index.php**

Ler `src/themes/RedeMapas/views/site/index.php` para ver o estado atual da seção `.hero`.

**Step 2: Reescrever a seção hero no index.php**

Substituir o bloco `<section class="hero">` completo (do `<section` até o `</section>` do hero) por:

```php
    <section class="hero">
        <div class="hero__inner">
            <p class="kicker">Rede Mapas</p>
            <h1>Mapeamento colaborativo<br>para políticas públicas</h1>
            <p class="hero__description">Conectamos governos, universidades e agentes culturais para mapear e fortalecer territórios com dados abertos.</p>
            <?php if (!$app->user->is('guest')): ?>
            <a class="btn btn--panel" href="<?= $app->createUrl('panel', 'index') ?>">
                <?= \MapasCulturais\i::__('Acessar painel') ?>
            </a>
            <?php endif; ?>
            <span class="hero__scroll-cue" aria-hidden="true">↓</span>
        </div>
    </section>
```

**Também remover do topo do index.php** as variáveis que não são mais usadas (verificar quais ainda têm referência no restante da página):
- `$heroBanner` — remover (era a imagem do hero)
- `$heroLogos` — remover (era os logos no hero)
- `$mapaBrasil` — remover (era imagem da seção about, que foi removida)
- `$agendaBg`, `$oportunidadesBg`, `$agentesBg`, `$espacosBg` — remover (eram os entity cards, que foram removidos)
- `$joinLeft`, `$joinRight` — remover (eram as decos do join, que foi removido)
- `$circuitsLogo` — remover (não é mais usado)
- `$circuitsImg` — **manter** (ainda usado na seção `.community`)

**Step 3: Ler home.scss e atualizar estilos do hero**

Ler o arquivo. Localizar todos os blocos relacionados ao hero e substituir pelo novo CSS.

**Remover do SCSS:**
- `.redemapas-home-page .hero__inner { display: grid; grid-template-columns: ...; min-height: 400px; }`
- `.redemapas-home-page .hero__copy { padding: ...; }`
- `.redemapas-home-page .hero__art { ... }` e `.redemapas-home-page .hero__art img { ... }`
- `.redemapas-home-page .hero__logos { ... }` e `.redemapas-home-page .hero__logos img,` do seletor conjunto
- No `@media 900px`: remover `.hero__copy { padding: ... }`, `.hero__art { min-height: ... }`, `.hero__art img`

**Adicionar CSS novo** — substituindo os blocos removidos:

```scss
.redemapas-home-page .hero {
  background: linear-gradient(135deg, #0033a0 0%, #0056c8 100%);
  color: #fff;
  min-height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 5rem 1.5rem 4rem;
}

.redemapas-home-page .hero__inner {
  max-width: 640px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.redemapas-home-page .hero h1 {
  margin: 0;
  font-size: clamp(2.4rem, 5vw, 4rem);
  line-height: 1.05;
  letter-spacing: 0.01em;
}

.redemapas-home-page .hero__description {
  margin: 0;
  max-width: 480px;
  color: rgba(255, 255, 255, 0.88);
  font-size: 1.1rem;
  line-height: 1.5;
}

.redemapas-home-page .hero__scroll-cue {
  margin-top: 0.5rem;
  font-size: 1.4rem;
  opacity: 0.55;
  animation: hero-bounce 2s ease-in-out infinite;
  display: block;
  line-height: 1;
}

@keyframes hero-bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(7px); }
}
```

**Step 4: Build e verificar**

```bash
cd /home/lucas/code/redemapas/mapas/src && pnpm run build
```

Confirmar em `http://localhost:8080`:
- Hero ocupa ~60vh, gradiente azul, texto centralizado
- H1 grande e confortável
- Seta bouncing visível
- Sem imagem à direita
- Header sticky visível acima do hero
- Scroll suave até o split

**Step 5: Commit no submódulo**

```bash
cd /home/lucas/code/redemapas/mapas/src/themes/RedeMapas
git add views/site/index.php assets-src/sass/home.scss
git commit -m "feat(home): hero coluna única centralizada, remove imagens externas"
```

**Step 6: Atualizar repo pai**

```bash
cd /home/lucas/code/redemapas/mapas
git add src/themes/RedeMapas
git commit -m "chore: atualiza submodule RedeMapas (hero centralizado)"
```

---

## Ordem de execução

1. Task 1 — Header HTML (layout)
2. Task 2 — CSS header + scroll shadow
3. Task 3 — Hero coluna única
