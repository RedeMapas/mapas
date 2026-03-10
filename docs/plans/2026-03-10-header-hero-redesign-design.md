# Header + Hero Redesign — Rede Mapas

**Data:** 2026-03-10
**Contexto:** Complemento ao redesign da home (2026-03-10-home-redesign-design.md)

---

## Problemas atuais

- `layouts/home.php` não tem cabeçalho — a página começa direto no hero sem nenhuma navegação
- O hero usa layout de duas colunas com imagem PNG externa do WordPress que não reflete o produto
- A imagem `$heroBanner` é irrelevante para a proposta de valor e depende de URL externa

---

## Decisões de design

### Cabeçalho
- **Elementos:** Logo à esquerda + botão login/painel à direita (minimalista)
- **Sem navegação** — split dual-audience cumpre a função de orientar o usuário
- **Sticky** com `z-index: 100`

### Hero
- **Layout:** Coluna única centralizada — sem imagem à direita
- **Remove:** `hero__art`, `hero__logos`, grid de duas colunas
- **Referência visual:** texto sobre gradiente azul, seta de scroll sutil

---

## Seção 1 — Header

**Arquivo:** `src/themes/RedeMapas/layouts/home.php`
**Posição:** Antes do `<?= $TEMPLATE_CONTENT ?>`

### Estrutura HTML

```html
<header class="site-header" data-site-header>
    <div class="container site-header__inner">
        <a class="site-header__logo" href="<?= $app->createUrl('site', 'index') ?>">
            <img src="$heroLogos" alt="Rede Mapas" height="36">
            <span class="site-header__logo-text">Rede Mapas</span>
        </a>
        <nav class="site-header__actions">
            <!-- se logado -->
            <a class="site-header__btn site-header__btn--panel" href="painel">Acessar painel</a>
            <!-- se visitante -->
            <a class="site-header__btn site-header__btn--login" href="login">Entrar</a>
        </nav>
    </div>
</header>
```

### CSS

- `background: #fff`
- `border-bottom: 1px solid #e8e8e8`
- `position: sticky; top: 0; z-index: 100`
- Scroll shadow: classe `.scrolled` adicionada via JS com `box-shadow: 0 2px 8px rgba(0,0,0,0.08)`
- Logo texto: fallback visível se imagem falhar (`font-weight: 700; color: #0033a0`)
- Botão "Entrar": `background: #007a52; color: #fff; border-radius: 999px; padding: 0.5rem 1.2rem; min-height: 44px`
- Botão "Acessar painel": mesmo estilo, `background: #0056c8`
- Mobile: logo menor (`max-height: 28px`), botão texto compactado

### JS (scroll shadow)

```js
// em home.js — dentro do DOMContentLoaded
const header = document.querySelector('[data-site-header]');
if (header) {
    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 4);
    }, { passive: true });
}
```

---

## Seção 2 — Hero Reorganizado

**Arquivo:** `src/themes/RedeMapas/views/site/index.php`
**Mudança:** Substituir layout de 2 colunas por coluna única centralizada

### Estrutura HTML

```html
<section class="hero">
    <div class="hero__inner">
        <p class="kicker">Rede Mapas</p>
        <h1>Mapeamento colaborativo<br>para políticas públicas</h1>
        <p class="hero__description">
            Conectamos governos, universidades e agentes culturais
            para mapear e fortalecer territórios com dados abertos.
        </p>
        <!-- se logado -->
        <a class="btn btn--panel" href="painel">Acessar painel</a>
        <!-- seta de scroll -->
        <span class="hero__scroll-cue" aria-hidden="true">↓</span>
    </div>
</section>
```

**Remove do HTML:**
- `<div class="hero__copy">` e `<div class="hero__art">` (grid de 2 colunas)
- `<div class="hero__logos">` com `$heroLogos`
- `container` wrapping o grid — hero passa a usar `.hero__inner` direto

### CSS

```scss
.redemapas-home-page .hero {
  background: linear-gradient(135deg, #0033a0 0%, #0056c8 100%);
  color: #fff;
  min-height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 4rem 1.5rem;
}

.redemapas-home-page .hero__inner {
  max-width: 640px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.redemapas-home-page .hero h1 {
  font-size: clamp(2.4rem, 5vw, 4rem);
  line-height: 1.05;
}

.redemapas-home-page .hero__description {
  max-width: 480px;
  font-size: 1.1rem;
  line-height: 1.5;
  color: rgba(255,255,255,0.88);
}

.redemapas-home-page .hero__scroll-cue {
  margin-top: 1rem;
  font-size: 1.5rem;
  opacity: 0.6;
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(6px); }
}
```

**Remove do CSS:**
- `.hero__inner` grid de 2 colunas (`grid-template-columns: 1.02fr 0.98fr`)
- `.hero__copy` padding
- `.hero__art` e `.hero__art img`
- `.hero__logos` e `.hero__logos img`
- Regras responsivas de `.hero__art` e `.hero__art img` no `@media 900px`

---

## Arquivos a modificar

| Arquivo | Mudança |
|---|---|
| `src/themes/RedeMapas/layouts/home.php` | Adicionar `<header>` antes do `$TEMPLATE_CONTENT` |
| `src/themes/RedeMapas/views/site/index.php` | Reescrever seção `.hero` (remover 2 colunas) |
| `src/themes/RedeMapas/assets-src/sass/home.scss` | Reescrever `.hero`, adicionar `.site-header` |
| `src/themes/RedeMapas/assets-src/js/home.js` | Adicionar scroll shadow no header |
