# Home Redesign — Rede Mapas

**Data:** 2026-03-10
**Branch:** feat/delivery-extended-fields-new-monitoramento
**Abordagem escolhida:** C — "Dois Mundos, Uma Home"

---

## Contexto

A home atual apresenta:
- Texto sem acentuação em português
- Seção de mapa com `<div>` vazio (placeholder que chegou a produção)
- All-caps em todos os h2 (6 seções) — leitura exaustiva
- Micro-tipografia comprimida (botões 0.72rem, body 0.93rem, line-height 0.93 no h1)
- Conteúdo de notícias hardcoded/estático
- Imagens dependentes de URLs externas sem fallback
- Sem hover states em cards e links

**Usuários primários:** Gestores públicos (prefeituras, secretarias) + Agentes culturais (artistas, coletivos) — peso igual.
**Personalidade de marca:** Colaborativo · Acolhedor · Territorial
**Referência estética:** Eventbrite/Sympla — descoberta fácil, cards atrativos, conteúdo em primeiro plano.
**Direção:** Redesign ousado.

---

## Princípios de Design

1. **Conteúdo em primeiro plano** — Editais, eventos e espaços reais na home
2. **Calor antes de formalidade** — Linguagem humana, sem all-caps excessivo
3. **Descoberta intuitiva** — Cards claros, ações óbvias
4. **Identidade territorial brasileira** — Cor e diversidade como elementos visuais
5. **Acessibilidade sem concessões** — WCAG AA, mobile-first, alvos mín. 44px

---

## Estrutura de Seções

| # | Seção | Fundo | Público |
|---|---|---|---|
| 1 | Hero | Azul gradiente `#0033a0 → #0056c8` | Ambos |
| 2 | Split de caminhos | Branco `#fff` | Ambos |
| 3 | Editais + Espaços + Eventos | Cinza claro `#f2f2f2` | Agente cultural |
| 4 | Proposta de valor gestão | Azul muito claro `#f0f4ff` | Gestor público |
| 5 | Mapa colaborativo | Azul escuro `#0a1628` | Ambos |
| 6 | Comunidade e rede | Branco `#fff` | Ambos |
| 7 | Footer CTA | Gradiente azul-verde | Ambos |

---

## Seção 1 — Hero

**Layout:** Duas colunas (copy esquerda, visual direita). Min-height 420px.

- **Kicker:** "Rede Mapas" — `0.75rem`, uppercase, `opacity: 0.85`
- **H1:** "Mapeamento colaborativo para políticas públicas" — capitalização normal, `clamp(2.2rem, 4vw, 3.6rem)`, `line-height: 1.05`
- **Subtítulo:** "Conectamos governos, universidades e agentes culturais para mapear e fortalecer territórios com dados abertos." — `1rem`, `line-height: 1.5`, `opacity: 0.9`
- **Visual direito:** Imagem atual (ou fotografia real quando disponível)
- **Sem CTA** — o split abaixo é o ponto de conversão

**Correções tipográficas:**
- `line-height` do h1: `0.93` → `1.05`
- Texto sem acentuação: corrigir todos os casos (gestão, você, governança, etc.)

---

## Seção 2 — Split "Qual é o seu caminho?"

**Layout:** Dois cards side-by-side, 100% da largura, altura `~220px`. Mobile: empilhados.

### Card Esquerdo — Agente Cultural
- Fundo: `#00a76f` (verde)
- Ícone: cultural/palco
- Título: "Sou agente cultural"
- Texto: "Descubra editais abertos, espaços culturais e eventos perto de você."
- CTA: "Explorar conteúdo" → ancora `#explorar`

### Card Direito — Gestor Público
- Fundo: `#0056c8` (azul)
- Ícone: institucional/mapa
- Título: "Sou gestor público"
- Texto: "Veja como municípios usam a plataforma para gestão de políticas públicas."
- CTA: "Conhecer a solução" → ancora `#gestores`

---

## Seção 3 — Conteúdo Dinâmico (ancora `#explorar`)

### 3a — Editais Abertos

**Dados:** API `/api/opportunity/find?status=1&@limit=3&@order=registrationTo ASC`

Cards horizontais (3 no desktop, scroll no mobile):
- Cor de acento baseada na área temática
- Campos: nome, organização, cidade/UF, prazo de inscrição
- CTA por card: "Ver edital"
- Link "Ver todos →" no cabeçalho da seção
- **Empty state:** "Nenhum edital aberto no momento. [Ative notificações] para ser avisado."

### 3b — Espaços e Eventos

Layout de duas colunas:

**Espaços em destaque** (esquerda):
- Dados: `/api/space/find?status=1&@limit=3`
- Lista simples: nome + cidade/UF + tipo
- Link "Ver todos os espaços →"

**Próximos eventos** (direita):
- Dados: `/api/event/find?status=1&@limit=4&@order=startsOn ASC`
- Lista com data, nome e município
- Link "Ver todos os eventos →"

---

## Seção 4 — Proposta de Valor para Gestores (ancora `#gestores`)

**Fundo:** `#f0f4ff`

Grid de 4 features (2×2 mobile, 4×1 desktop):
1. **Fomento e editais** — Gerencie chamadas públicas com fluxos colaborativos e rastreáveis
2. **Monitoramento e avaliação** — Acompanhe indicadores territoriais em tempo real
3. **Transparência pública** — Dados abertos e auditáveis por cidadãos e parceiros
4. **Integração de dados** — Interoperabilidade com outras plataformas via API aberta

CTA único: "Quero ativar no meu município →" → `$app->createUrl('auth', 'register')`

---

## Seção 5 — Mapa Colaborativo

**Fundo:** `#0a1628` (azul escuro), texto branco.

- **H2:** "Cartografia do território brasileiro"
- **Subtítulo:** "Dados georreferenciados de agentes, espaços e eventos em todo o Brasil."
- **Mapa:** iframe do Mapas Culturais público (altura 400px) — se inviável, screenshot estática com link. **Nunca um `<div>` vazio.**
- Link: "Abrir mapa completo →"

---

## Seção 6 — Comunidade e Rede

**Fundo:** `#fff`. Duas colunas (texto esquerda, imagem direita).

- **H2:** "Software livre, comunidade e evolução contínua" — capitalização normal, não all-caps
- Parágrafo: 2-3 linhas sobre governança colaborativa e ecossistema aberto
- CTA: "Conhecer a Rede Mapas →" → `https://rede.mapas.tec.br/`
- Imagem: local (não URL externa) — fotografia de território ou ilustração vetorial

**Remove:** seção "Notícias" com conteúdo hardcoded

---

## Seção 7 — Footer CTA

**Fundo:** Gradiente `#0056c8 → #00a76f` (mantém padrão atual).

- **H2:** "Pronto para começar?" — conversacional
- Dois botões lado a lado:
  - "Cadastre-se grátis" → `$app->createUrl('auth', 'register')` (fundo branco, texto azul)
  - "Fale com nossa equipe" → link externo Rede Mapas (fundo transparente, borda branca, texto branco)

---

## Correções Transversais

### Tipografia
- Botões: `font-size: 0.875rem`, `padding: 0.6rem 1.4rem`
- Body geral: `font-size: 1rem`
- H1 hero `line-height`: `0.93` → `1.05`
- H2 seções: remover `text-transform: uppercase` (manter apenas hero kicker)

### Acentuação
Corrigir no `index.php`: gestão, você, governança, rastreáveis, tecnologia, colaboração, etc.

### Hover States
- `.entity-card` e novos cards: `transition: box-shadow 0.15s, transform 0.15s`
- `.entity-card:hover`: `box-shadow: 0 4px 16px rgba(0,0,0,0.10)`, `transform: translateY(-2px)`
- Links internos: underline no hover

### Imagens
- Imagens críticas (hero, split) devem ser locais em `assets/img/home/`
- Imagens externas restantes: adicionar `loading="lazy"` e dimensões explícitas

### Acessibilidade
- Alvos de toque mínimo 44×44px para todos os botões
- `aria-label` nos cards de split
- Contraste WCAG AA verificado em todos os pares texto/fundo

---

## Arquivos a Modificar

| Arquivo | Mudança |
|---|---|
| `src/themes/RedeMapas/views/site/index.php` | Reescrever estrutura HTML completa |
| `src/themes/RedeMapas/assets-src/sass/home.scss` | Reescrever estilos |
| `src/themes/RedeMapas/assets-src/js/home.js` | Adicionar fetch de editais, espaços, eventos |
| `src/themes/RedeMapas/assets-src/js/services/home-api.js` | Expandir endpoints da API |
| `src/themes/RedeMapas/assets/img/home/` | Adicionar imagens locais |
