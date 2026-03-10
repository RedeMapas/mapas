# Home Redesign — Rede Mapas Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesenhar a home do tema RedeMapas com abordagem dual-audience (agentes culturais + gestores públicos), conteúdo dinâmico via API e qualidade de produção.

**Architecture:** Reescrever `views/site/index.php` com nova estrutura de 7 seções. Atualizar `home.scss` com novo sistema visual. Expandir `home.js` e `home-api.js` para renderizar editais, espaços e eventos via API do Mapas Culturais. Sem novas dependências — usa fetch nativo e a API REST já existente.

**Tech Stack:** PHP 8.3 (view template), SCSS (compilado via `pnpm run build`), JavaScript ES modules (esbuild), API REST Mapas Culturais (`/api/{entity}/find`).

**Design doc:** `docs/plans/2026-03-10-home-redesign-design.md`

**Build:** `cd src && pnpm run build` (ou `pnpm run watch` para dev)

**Ver no browser:** `http://localhost:8080` (após `docker compose up -d`)

---

## Task 1: Correções urgentes — acentuação e tipografia

> Quick wins que corrigem problemas visíveis imediatamente.

**Files:**
- Modify: `src/themes/RedeMapas/views/site/index.php`
- Modify: `src/themes/RedeMapas/assets-src/sass/home.scss`

**Step 1: Corrigir acentuação no index.php**

Substituir os textos com erros de acentuação. Trocar:
- `gestao colaborativa` → `gestão colaborativa`
- `Voce` / `voce` → `Você` / `você`
- `governanca` → `governança`
- `rastreaveis` → `rastreáveis`
- `tecnologia` → mantém (já correto)
- `politicas publicas` → `políticas públicas`
- `colaboracao` → `colaboração`
- `atualizacoes` → `atualizações`
- `noticias` → `notícias`
- `Software Livre, Comunidade e Evolucao Continua` → `Software Livre, Comunidade e Evolução Contínua`
- `NOTICIAS E ATUALIZACOES DA REDE` → `NOTÍCIAS E ATUALIZAÇÕES DA REDE`

**Step 2: Corrigir tipografia no home.scss**

Aplicar os seguintes ajustes:

```scss
// hero h1 — line-height muito apertado
.redemapas-home-page .hero h1 {
  line-height: 1.05; // era 0.93
}

// hero description — line-height e tamanho
.redemapas-home-page .hero__description {
  font-size: 1rem; // era 0.95rem
  line-height: 1.5; // era 1.35
}

// botões — tamanho legível
.redemapas-home-page .btn {
  font-size: 0.875rem; // era 0.72rem
  padding: 0.6rem 1.4rem; // era 0.48rem 1rem
  min-height: 44px; // acessibilidade — alvo de toque
}

// body text — tamanho legível
.redemapas-home-page .about p,
.redemapas-home-page .join p,
.redemapas-home-page .circuits p,
.redemapas-home-page .notices li,
.redemapas-home-page .entity-card__body p,
.redemapas-home-page .map__description {
  font-size: 1rem; // era 0.93rem
  line-height: 1.5; // era 1.4
}
```

**Step 3: Build e verificar**

```bash
cd src && pnpm run build
```

Abrir `http://localhost:8080` e confirmar:
- Textos com acentuação correta
- H1 do hero com espaçamento confortável
- Botões com altura mínima de 44px

**Step 4: Commit**

```bash
git add src/themes/RedeMapas/views/site/index.php src/themes/RedeMapas/assets-src/sass/home.scss
git commit -m "fix(home): corrige acentuação e tipografia comprimida"
```

---

## Task 2: Hero reescrito + correção de all-caps

> Reescrever o hero com novo copy e remover all-caps dos h2.

**Files:**
- Modify: `src/themes/RedeMapas/views/site/index.php` — seção `.hero`
- Modify: `src/themes/RedeMapas/assets-src/sass/home.scss` — regras de h2

**Step 1: Reescrever a seção hero no index.php**

Substituir o bloco `<section class="hero">` por:

```php
<section class="hero">
    <div class="container hero__inner">
        <div class="hero__copy">
            <p class="kicker">Rede Mapas</p>
            <h1>Mapeamento colaborativo<br>para políticas públicas</h1>
            <p class="hero__description">Conectamos governos, universidades e agentes culturais para mapear e fortalecer territórios com dados abertos.</p>
            <div class="hero__logos">
                <img src="<?= htmlspecialchars($heroLogos, ENT_QUOTES, 'UTF-8') ?>" alt="Logos institucionais">
            </div>
            <?php if (!$app->user->is('guest')): ?>
            <a class="btn btn--panel" href="<?= $app->createUrl('panel', 'index') ?>">
                <?= \MapasCulturais\i::__('Acessar painel') ?>
            </a>
            <?php endif; ?>
        </div>
        <div class="hero__art" role="img" aria-label="Grafismo colorido">
            <img src="<?= htmlspecialchars($heroBanner, ENT_QUOTES, 'UTF-8') ?>" alt="Visualização de mapa cultural">
        </div>
    </div>
</section>
```

**Step 2: Remover all-caps dos h2 no home.scss**

Localizar e remover a regra `text-transform: uppercase` do seletor conjunto dos h2:

```scss
// ANTES — remover text-transform: uppercase deste bloco:
.redemapas-home-page .about h2,
.redemapas-home-page .infos h2,
.redemapas-home-page .map h2,
.redemapas-home-page .join h2,
.redemapas-home-page .notices h2,
.redemapas-home-page .circuits h2 {
  margin: 0 0 0.95rem;
  color: #0033a0;
  font-size: clamp(1.15rem, 2vw, 1.8rem);
  line-height: 1.05;
  // remover: text-transform: uppercase;
}
```

Manter uppercase apenas no `.hero h1` e no `.kicker`.

**Step 3: Ajustar tamanho dos h2**

```scss
.redemapas-home-page .about h2,
.redemapas-home-page .infos h2,
.redemapas-home-page .map h2,
.redemapas-home-page .notices h2,
.redemapas-home-page .circuits h2 {
  margin: 0 0 1rem;
  color: #0033a0;
  font-size: clamp(1.3rem, 2.2vw, 2rem);
  line-height: 1.1;
  font-weight: 700;
}

// join h2 é branco (seção escura)
.redemapas-home-page .join h2 {
  color: #fff;
  font-size: clamp(1.5rem, 2.5vw, 2.4rem);
  line-height: 1.1;
  font-weight: 700;
}
```

**Step 4: Build e verificar**

```bash
cd src && pnpm run build
```

Confirmar:
- H1 do hero sem all-caps, com novo copy
- H2 das seções sem all-caps, tamanho aumentado

**Step 5: Commit**

```bash
git add src/themes/RedeMapas/views/site/index.php src/themes/RedeMapas/assets-src/sass/home.scss
git commit -m "feat(home): hero com novo copy e h2 sem all-caps"
```

---

## Task 3: Seção Split — dois caminhos

> Adicionar a seção de split dual-audience logo após o hero.

**Files:**
- Modify: `src/themes/RedeMapas/views/site/index.php` — adicionar seção `.split` após `.hero`
- Modify: `src/themes/RedeMapas/assets-src/sass/home.scss` — adicionar estilos `.split`

**Step 1: Adicionar HTML da seção split**

Inserir após o fechamento de `</section>` do hero (antes da seção `.about`):

```php
<section class="split">
    <a class="split__card split__card--cultural" href="#explorar" aria-label="Sou agente cultural — explorar conteúdo">
        <div class="split__card-inner">
            <span class="split__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
            </span>
            <h2>Sou agente cultural</h2>
            <p>Descubra editais abertos, espaços culturais e eventos perto de você.</p>
            <span class="split__cta">Explorar conteúdo →</span>
        </div>
    </a>
    <a class="split__card split__card--gestor" href="#gestores" aria-label="Sou gestor público — conhecer a solução">
        <div class="split__card-inner">
            <span class="split__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
            </span>
            <h2>Sou gestor público</h2>
            <p>Veja como municípios usam a plataforma para gestão de políticas públicas.</p>
            <span class="split__cta">Conhecer a solução →</span>
        </div>
    </a>
</section>
```

**Step 2: Adicionar estilos da seção split no home.scss**

Adicionar ao final do arquivo (antes do `@media`):

```scss
/* ── SPLIT ─────────────────────────────────────── */
.redemapas-home-page .split {
  display: grid;
  grid-template-columns: 1fr 1fr;
}

.redemapas-home-page .split__card {
  display: block;
  text-decoration: none;
  color: #fff;
  padding: 2.5rem 2rem;
  transition: filter 0.15s;
}

.redemapas-home-page .split__card:hover {
  filter: brightness(1.07);
}

.redemapas-home-page .split__card--cultural {
  background: #00a76f;
}

.redemapas-home-page .split__card--gestor {
  background: #0056c8;
}

.redemapas-home-page .split__card-inner {
  max-width: 460px;
}

.redemapas-home-page .split__icon {
  display: block;
  margin-bottom: 1rem;
  opacity: 0.9;
}

.redemapas-home-page .split__card h2 {
  margin: 0 0 0.6rem;
  color: #fff;
  font-size: clamp(1.3rem, 2vw, 1.8rem);
  line-height: 1.1;
}

.redemapas-home-page .split__card p {
  margin: 0 0 1.2rem;
  color: rgba(255,255,255,0.9);
  font-size: 1rem;
  line-height: 1.5;
}

.redemapas-home-page .split__cta {
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: rgba(255,255,255,0.95);
  border-bottom: 2px solid rgba(255,255,255,0.4);
  padding-bottom: 2px;
}

@media (max-width: 900px) {
  .redemapas-home-page .split {
    grid-template-columns: 1fr;
  }
}
```

**Step 3: Build e verificar**

```bash
cd src && pnpm run build
```

Confirmar:
- Dois cards lado a lado, verde e azul
- Cards clicáveis e com hover visível
- Mobile: empilhados

**Step 4: Commit**

```bash
git add src/themes/RedeMapas/views/site/index.php src/themes/RedeMapas/assets-src/sass/home.scss
git commit -m "feat(home): seção split dual-audience (agente cultural / gestor público)"
```

---

## Task 4: Seção de Editais Abertos (dinâmico)

> Substituir a seção `.infos` estática por uma seção de editais com dados reais da API.

**Files:**
- Modify: `src/themes/RedeMapas/views/site/index.php`
- Modify: `src/themes/RedeMapas/assets-src/js/services/home-api.js`
- Modify: `src/themes/RedeMapas/assets-src/js/home.js`
- Modify: `src/themes/RedeMapas/assets-src/sass/home.scss`

**Step 1: Substituir seção `.infos` no index.php**

Substituir o bloco `<section class="infos" id="infos">` por:

```php
<section class="opportunities" id="explorar">
    <div class="container">
        <div class="section-header">
            <h2>Editais abertos</h2>
            <a class="section-header__link" href="<?= $app->createUrl('search', 'opportunities') ?>">Ver todos →</a>
        </div>
        <div class="opportunities__grid" data-opportunities-grid>
            <div class="opportunities__loading" aria-live="polite">Carregando editais...</div>
        </div>
    </div>
</section>
```

**Step 2: Atualizar home-api.js com endpoint de oportunidades**

Abrir `src/themes/RedeMapas/assets-src/js/services/home-api.js` e adicionar a função:

```js
export async function fetchOpportunities(limit = 3) {
    const params = new URLSearchParams({
        'status': '1',
        '@limit': String(limit),
        '@order': 'registrationTo ASC',
        '@select': 'id,name,shortDescription,registrationFrom,registrationTo,ownerEntity',
    });
    const res = await fetch(`/api/opportunity/find?${params}`);
    if (!res.ok) return [];
    return res.json();
}
```

**Step 3: Atualizar home.js para renderizar editais**

Adicionar ao `src/themes/RedeMapas/assets-src/js/home.js`:

```js
import { fetchOpportunities } from './services/home-api.js';

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
}

function renderOpportunityCard(op) {
    const deadline = op.registrationTo ? `Até ${formatDate(op.registrationTo)}` : 'Prazo a confirmar';
    const org = op.ownerEntity?.name ?? '';
    return `
        <article class="opp-card">
            <div class="opp-card__header">
                <span class="opp-card__deadline">${deadline}</span>
            </div>
            <div class="opp-card__body">
                <h3 class="opp-card__title">${op.name}</h3>
                ${org ? `<p class="opp-card__org">${org}</p>` : ''}
                ${op.shortDescription ? `<p class="opp-card__desc">${op.shortDescription}</p>` : ''}
            </div>
            <a class="opp-card__link" href="/opportunity/${op.id}/info">Ver edital</a>
        </article>
    `;
}

async function loadOpportunities() {
    const grid = document.querySelector('[data-opportunities-grid]');
    if (!grid) return;

    const opps = await fetchOpportunities(3).catch(() => []);

    if (!opps.length) {
        grid.innerHTML = `
            <div class="opportunities__empty">
                <p>Nenhum edital aberto no momento.</p>
                <p>Ative as notificações para ser avisado quando surgirem novos editais.</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = opps.map(renderOpportunityCard).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    loadOpportunities();
    // ...código existente de notificações e smooth scroll...
});
```

> **Atenção:** O arquivo `home.js` já tem um `DOMContentLoaded`. Integrar `loadOpportunities()` dentro do listener existente, não criar um novo.

**Step 4: Adicionar estilos dos cards de oportunidade no home.scss**

```scss
/* ── OPPORTUNITIES ──────────────────────────────── */
.redemapas-home-page .opportunities {
  background: #f2f2f2;
  padding: 2.5rem 0;
}

.redemapas-home-page .section-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.redemapas-home-page .section-header__link {
  font-size: 0.875rem;
  font-weight: 700;
  color: #0056c8;
  text-decoration: none;
}

.redemapas-home-page .section-header__link:hover {
  text-decoration: underline;
}

.redemapas-home-page .opportunities__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.redemapas-home-page .opportunities__loading,
.redemapas-home-page .opportunities__empty {
  grid-column: 1 / -1;
  color: #666;
  font-size: 0.95rem;
  padding: 1.5rem 0;
}

.redemapas-home-page .opp-card {
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  transition: box-shadow 0.15s, transform 0.15s;
  overflow: hidden;
}

.redemapas-home-page .opp-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.10);
  transform: translateY(-2px);
}

.redemapas-home-page .opp-card__header {
  background: #0056c8;
  padding: 0.6rem 1rem;
}

.redemapas-home-page .opp-card__deadline {
  font-size: 0.78rem;
  font-weight: 700;
  color: #fff;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.redemapas-home-page .opp-card__body {
  padding: 1rem;
  flex: 1;
}

.redemapas-home-page .opp-card__title {
  margin: 0 0 0.4rem;
  font-size: 1rem;
  font-weight: 700;
  color: #1f1f1f;
  line-height: 1.3;
}

.redemapas-home-page .opp-card__org {
  margin: 0 0 0.5rem;
  font-size: 0.82rem;
  color: #666;
}

.redemapas-home-page .opp-card__desc {
  margin: 0;
  font-size: 0.9rem;
  color: #474747;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.redemapas-home-page .opp-card__link {
  display: block;
  padding: 0.7rem 1rem;
  font-size: 0.82rem;
  font-weight: 700;
  color: #0056c8;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-top: 1px solid #eee;
  text-decoration: none;
}

.redemapas-home-page .opp-card__link:hover {
  background: #f5f9ff;
}

@media (max-width: 900px) {
  .redemapas-home-page .opportunities__grid {
    grid-template-columns: 1fr;
  }
}
```

**Step 5: Build e verificar**

```bash
cd src && pnpm run build
```

Abrir `http://localhost:8080` e confirmar:
- Seção "Editais abertos" aparece
- Cards carregam da API (ou empty state se não houver editais)
- Hover com elevação nos cards
- Mobile: coluna única

**Step 6: Commit**

```bash
git add src/themes/RedeMapas/views/site/index.php \
        src/themes/RedeMapas/assets-src/js/services/home-api.js \
        src/themes/RedeMapas/assets-src/js/home.js \
        src/themes/RedeMapas/assets-src/sass/home.scss
git commit -m "feat(home): seção de editais abertos com dados dinâmicos da API"
```

---

## Task 5: Seção de Proposta de Valor para Gestores

> Adicionar seção `.gestores` com 4 features em grid, âncora `#gestores`.

**Files:**
- Modify: `src/themes/RedeMapas/views/site/index.php` — substituir `.about` por `.gestores`
- Modify: `src/themes/RedeMapas/assets-src/sass/home.scss`

**Step 1: Substituir seção `.about` no index.php**

Substituir o bloco `<section class="about" id="sobre">` por:

```php
<section class="gestores" id="gestores">
    <div class="container">
        <div class="section-header">
            <h2>Para gestores públicos</h2>
        </div>
        <p class="gestores__intro">Como o Mapas Culturais transforma a gestão territorial de políticas públicas.</p>
        <div class="gestores__grid">
            <div class="gestores__feature">
                <span class="gestores__feature-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </span>
                <h3>Fomento e editais</h3>
                <p>Gerencie chamadas públicas com fluxos de inscrição colaborativos e rastreáveis.</p>
            </div>
            <div class="gestores__feature">
                <span class="gestores__feature-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </span>
                <h3>Monitoramento e avaliação</h3>
                <p>Acompanhe indicadores territoriais em tempo real com dados georreferenciados.</p>
            </div>
            <div class="gestores__feature">
                <span class="gestores__feature-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </span>
                <h3>Transparência pública</h3>
                <p>Dados abertos e auditáveis por cidadãos, parceiros e organizações de controle.</p>
            </div>
            <div class="gestores__feature">
                <span class="gestores__feature-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/></svg>
                </span>
                <h3>Integração de dados</h3>
                <p>Interoperabilidade com outras plataformas via API aberta e padrões de dados.</p>
            </div>
        </div>
        <div class="gestores__cta">
            <a class="btn btn--panel" href="<?= $app->createUrl('auth', 'register') ?>">Quero ativar no meu município</a>
        </div>
    </div>
</section>
```

**Step 2: Adicionar estilos no home.scss**

```scss
/* ── GESTORES ───────────────────────────────────── */
.redemapas-home-page .gestores {
  background: #f0f4ff;
  padding: 2.5rem 0;
  border-top: 1px solid #dce6ff;
  border-bottom: 1px solid #dce6ff;
}

.redemapas-home-page .gestores__intro {
  margin: -0.5rem 0 2rem;
  color: #474747;
  font-size: 1rem;
  line-height: 1.5;
}

.redemapas-home-page .gestores__grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.redemapas-home-page .gestores__feature {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.redemapas-home-page .gestores__feature-icon {
  display: block;
  color: #0056c8;
  margin-bottom: 0.25rem;
}

.redemapas-home-page .gestores__feature h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #0033a0;
  line-height: 1.3;
}

.redemapas-home-page .gestores__feature p {
  margin: 0;
  font-size: 0.9rem;
  color: #474747;
  line-height: 1.5;
}

.redemapas-home-page .gestores__cta {
  text-align: center;
}

@media (max-width: 900px) {
  .redemapas-home-page .gestores__grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 540px) {
  .redemapas-home-page .gestores__grid {
    grid-template-columns: 1fr;
  }
}
```

**Step 3: Remover estilos obsoletos do `.about` no home.scss**

Remover os blocos CSS:
- `.redemapas-home-page .about { ... }`
- `.redemapas-home-page .about__inner { ... }`
- `.redemapas-home-page .about__text` (se existir)
- `.redemapas-home-page .about__map` (se existir)
- `.redemapas-home-page .about h2` do seletor conjunto

**Step 4: Build e verificar**

```bash
cd src && pnpm run build
```

Confirmar:
- Seção azul claro com 4 features em grid
- Ícones SVG visíveis em azul
- CTA "Quero ativar no meu município" centralizado
- Mobile: 2 colunas → 1 coluna

**Step 5: Commit**

```bash
git add src/themes/RedeMapas/views/site/index.php src/themes/RedeMapas/assets-src/sass/home.scss
git commit -m "feat(home): seção de proposta de valor para gestores públicos"
```

---

## Task 6: Corrigir seção do mapa (eliminar div vazio)

> Substituir o placeholder `.map__frame` por iframe real ou fallback com link.

**Files:**
- Modify: `src/themes/RedeMapas/views/site/index.php`
- Modify: `src/themes/RedeMapas/assets-src/sass/home.scss`

**Step 1: Substituir seção `.map` no index.php**

Substituir o bloco `<section class="map" id="mapa">` por:

```php
<section class="map-section" id="mapa">
    <div class="container">
        <h2>Cartografia do território brasileiro</h2>
        <p class="map-section__description">Dados georreferenciados de agentes, espaços e eventos em todo o Brasil.</p>
        <div class="map-section__frame">
            <iframe
                src="<?= $app->createUrl('search', 'agents') ?>?embed=1"
                title="Mapa colaborativo Rede Mapas"
                loading="lazy"
                allowfullscreen
            ></iframe>
        </div>
        <div class="map-section__footer">
            <a class="btn" href="<?= $app->createUrl('search', 'agents') ?>">Abrir mapa completo →</a>
        </div>
    </div>
</section>
```

> **Nota:** Se a URL de embed não funcionar corretamente em `localhost`, usar um `<a>` estático no lugar do iframe — o importante é não ter um `<div>` vazio em produção. Testar se o iframe carrega corretamente no ambiente de desenvolvimento.

**Step 2: Atualizar estilos no home.scss**

Substituir o bloco `.map { ... }` e `.map__frame { ... }` por:

```scss
/* ── MAP SECTION ────────────────────────────────── */
.redemapas-home-page .map-section {
  background: #0a1628;
  padding: 2.5rem 0;
  color: #fff;
}

.redemapas-home-page .map-section h2 {
  margin: 0 0 0.75rem;
  color: #fff;
  font-size: clamp(1.3rem, 2.2vw, 2rem);
  line-height: 1.1;
  font-weight: 700;
}

.redemapas-home-page .map-section__description {
  margin: 0 0 1.5rem;
  color: rgba(255,255,255,0.8);
  font-size: 1rem;
  line-height: 1.5;
  max-width: 640px;
}

.redemapas-home-page .map-section__frame {
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,0.15);
  height: 400px;
}

.redemapas-home-page .map-section__frame iframe {
  width: 100%;
  height: 100%;
  border: 0;
  display: block;
}

.redemapas-home-page .map-section__footer {
  margin-top: 1.25rem;
  text-align: right;
}

.redemapas-home-page .map-section .btn {
  background: rgba(255,255,255,0.15);
  color: #fff;
  border: 1px solid rgba(255,255,255,0.3);
}

.redemapas-home-page .map-section .btn:hover {
  background: rgba(255,255,255,0.25);
}

@media (max-width: 900px) {
  .redemapas-home-page .map-section__frame {
    height: 280px;
  }
}
```

**Step 3: Remover estilos obsoletos**

Remover do home.scss:
- `.redemapas-home-page .map { ... }`
- `.redemapas-home-page .map__description { ... }`
- `.redemapas-home-page .map__frame { ... }`
- `.redemapas-home-page .map h2` do seletor conjunto

**Step 4: Build e verificar**

```bash
cd src && pnpm run build
```

Confirmar:
- Seção com fundo escuro `#0a1628`
- Iframe carregando (ou fallback visível — nunca div vazio)
- Link "Abrir mapa completo" presente

**Step 5: Commit**

```bash
git add src/themes/RedeMapas/views/site/index.php src/themes/RedeMapas/assets-src/sass/home.scss
git commit -m "fix(home): substitui div vazio do mapa por iframe real com fundo escuro"
```

---

## Task 7: Seção comunidade e Footer CTA

> Reescrever `.circuits` (comunidade) e `.join` (footer CTA) com novo design.

**Files:**
- Modify: `src/themes/RedeMapas/views/site/index.php`
- Modify: `src/themes/RedeMapas/assets-src/sass/home.scss`

**Step 1: Substituir seção `.circuits` no index.php**

Substituir o bloco `<section class="circuits" id="circuitos">` por:

```php
<section class="community" id="comunidade">
    <div class="container community__inner">
        <div class="community__text">
            <h2>Software livre, comunidade e evolução contínua</h2>
            <p>O Mapas Culturais é desenvolvido em comunidade, com governança colaborativa, compartilhamento de conhecimento e melhoria constante das soluções digitais.</p>
            <p>Faça parte do ecossistema: contribua com código, use a plataforma e ajude a mapear o território brasileiro.</p>
            <a class="btn" href="https://rede.mapas.tec.br/" target="_blank" rel="noopener noreferrer">Conhecer a Rede Mapas</a>
        </div>
        <div class="community__art">
            <img src="<?= htmlspecialchars($circuitsImg, ENT_QUOTES, 'UTF-8') ?>" alt="Comunidade Rede Mapas" loading="lazy">
        </div>
    </div>
</section>
```

**Step 2: Substituir seção `.join` pelo footer CTA**

Substituir o bloco `<section class="join">` por:

```php
<section class="cta-footer">
    <div class="container cta-footer__inner">
        <h2>Pronto para começar?</h2>
        <div class="cta-footer__actions">
            <a class="btn cta-footer__btn--primary" href="<?= $app->createUrl('auth', 'register') ?>">
                Cadastre-se grátis
            </a>
            <a class="btn cta-footer__btn--secondary" href="https://rede.mapas.tec.br/" target="_blank" rel="noopener noreferrer">
                Fale com nossa equipe
            </a>
        </div>
        <p class="cta-footer__hint">Cadastre-se para agentes culturais · Fale conosco para gestores públicos</p>
    </div>
</section>
```

**Step 3: Remover seção `.notices` (conteúdo hardcoded)**

Remover o bloco `<section class="notices" id="editais">` completo — o conteúdo hardcoded não agrega valor.

**Step 4: Atualizar estilos no home.scss**

Remover blocos CSS obsoletos:
- `.redemapas-home-page .circuits { ... }` e filhos
- `.redemapas-home-page .join { ... }` e filhos (`.join::before`, `.join::after`, `.join-deco`, `.join__inner`)
- `.redemapas-home-page .notices { ... }` e filhos
- `.redemapas-home-page .notice-list { ... }`
- `.redemapas-home-page .tag { ... }`

Adicionar novos estilos:

```scss
/* ── COMMUNITY ──────────────────────────────────── */
.redemapas-home-page .community {
  background: #fff;
  padding: 2.5rem 0;
  border-top: 1px solid #ececec;
}

.redemapas-home-page .community__inner {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 3rem;
  align-items: center;
}

.redemapas-home-page .community__text h2 {
  color: #0033a0;
}

.redemapas-home-page .community__text p {
  color: #474747;
  font-size: 1rem;
  line-height: 1.5;
  margin: 0 0 0.9rem;
}

.redemapas-home-page .community__art img {
  width: 100%;
  display: block;
  border-radius: 8px;
}

/* ── CTA FOOTER ─────────────────────────────────── */
.redemapas-home-page .cta-footer {
  background: linear-gradient(135deg, #0056c8 0%, #00a76f 100%);
  color: #fff;
  padding: 3rem 0;
}

.redemapas-home-page .cta-footer__inner {
  text-align: center;
}

.redemapas-home-page .cta-footer h2 {
  color: #fff;
  font-size: clamp(1.5rem, 2.5vw, 2.4rem);
  margin: 0 0 1.5rem;
}

.redemapas-home-page .cta-footer__actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.redemapas-home-page .cta-footer__btn--primary {
  background: #fff;
  color: #0056c8;
}

.redemapas-home-page .cta-footer__btn--secondary {
  background: transparent;
  color: #fff;
  border: 2px solid rgba(255,255,255,0.7);
}

.redemapas-home-page .cta-footer__btn--secondary:hover {
  background: rgba(255,255,255,0.15);
}

.redemapas-home-page .cta-footer__hint {
  margin: 0;
  font-size: 0.82rem;
  color: rgba(255,255,255,0.75);
}

@media (max-width: 900px) {
  .redemapas-home-page .community__inner {
    grid-template-columns: 1fr;
  }

  .redemapas-home-page .community__art {
    order: -1;
  }
}
```

**Step 5: Build e verificar**

```bash
cd src && pnpm run build
```

Confirmar:
- Seção comunidade: duas colunas com imagem
- Footer CTA: gradiente azul→verde, dois botões lado a lado
- Seção de notícias removida
- Mobile: empilhado corretamente

**Step 6: Commit**

```bash
git add src/themes/RedeMapas/views/site/index.php src/themes/RedeMapas/assets-src/sass/home.scss
git commit -m "feat(home): seção comunidade e footer CTA dual — remove notices hardcoded"
```

---

## Task 8: Hover states e polish final

> Adicionar interatividade visual e ajustes finais de polimento.

**Files:**
- Modify: `src/themes/RedeMapas/assets-src/sass/home.scss`

**Step 1: Adicionar hover states para entity-cards (se ainda existirem)**

Verificar se ainda há `.entity-card` no HTML. Se sim, adicionar:

```scss
.redemapas-home-page .entity-card {
  transition: box-shadow 0.15s, transform 0.15s;
}

.redemapas-home-page .entity-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.10);
  transform: translateY(-2px);
}

.redemapas-home-page .entity-card__link:hover {
  text-decoration: underline;
}
```

**Step 2: Adicionar hover para botões gerais**

```scss
.redemapas-home-page .btn {
  transition: filter 0.15s, box-shadow 0.15s;
}

.redemapas-home-page .btn:hover {
  filter: brightness(1.08);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
```

**Step 3: Verificar contraste WCAG AA**

Pares críticos a verificar manualmente (usar DevTools > Accessibility ou https://webaim.org/resources/contrastchecker/):
- Texto branco `#fff` em verde `#00a76f` → deve ser ≥ 4.5:1 para texto pequeno
- Texto branco em azul `#0056c8` → deve ser ≥ 4.5:1
- Texto `#474747` em fundo `#f2f2f2` → verificar

> Se `#00a76f` falhar para texto pequeno (< 18px), escurecer para `#008f5d`.

**Step 4: Garantir min-height dos alvos de toque**

```scss
.redemapas-home-page .btn,
.redemapas-home-page .split__card,
.redemapas-home-page .opp-card__link,
.redemapas-home-page .section-header__link {
  min-height: 44px;
}
```

**Step 5: Build final e revisão completa**

```bash
cd src && pnpm run build
```

Revisar todas as 7 seções em desktop (1280px) e mobile (375px):
- [ ] Hero: tipografia confortável, copy com acentos
- [ ] Split: dois cards verdes/azuis, clicáveis
- [ ] Editais: cards dinâmicos carregando ou empty state
- [ ] Gestores: 4 features em grid
- [ ] Mapa: fundo escuro com iframe
- [ ] Comunidade: duas colunas com imagem
- [ ] Footer CTA: gradiente, dois botões

**Step 6: Commit final**

```bash
git add src/themes/RedeMapas/assets-src/sass/home.scss
git commit -m "polish(home): hover states, contraste WCAG e touch targets"
```

---

## Ordem de execução

1. Task 1 — Correções urgentes (acentuação + tipografia)
2. Task 2 — Hero reescrito
3. Task 3 — Split dual-audience
4. Task 4 — Editais dinâmicos
5. Task 5 — Gestores
6. Task 6 — Mapa (fix div vazio)
7. Task 7 — Comunidade + Footer CTA
8. Task 8 — Polish final
