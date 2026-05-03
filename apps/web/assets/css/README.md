# `assets/css/` — Mappa dei file CSS

Documentazione dei file CSS globali (foundation, motion, funnel checkout) caricati da Nuxt
via `app.config.ts`/`nuxt.config.ts`. La regola d'oro del design system
([ADR 004](../../../docs/adr/004-tailwind-utility-design-system.md)) è "Tailwind utility
+ componenti `Sf*` + Nuxt UI 4". I file qui sotto sono eccezioni documentate.

## Mappa file

| File | Categoria | LOC | Stripe-critical | Migrabile |
|------|-----------|-----|-----------------|-----------|
| `main.css` | Foundation | ~2700 | No (foundation) | NO — `:root` token brand mappati a Tailwind config |
| `motion.css` | Foundation | ~110 | No | NO — keyframes shared, riusati ovunque |
| `funnel-animations.css` | Funnel | ~50 | SI | NO — keyframes Stripe-critical, intoccabili senza E2E carta |
| `funnel-stages.css` | Funnel | ~1270 | SI | **NO MIGRATE** senza E2E carta |
| `funnel-quote.css` | Funnel | ~1115 | Adjacent (step preventivo precede checkout) | Cauto — modifiche solo dopo regression visiva |
| `funnel-checkout.css` | Funnel | ~1050 | SI | **NO MIGRATE** senza E2E carta |
| `shipment-flow.css` | Funnel | ~1110 | SI | **NO MIGRATE** senza E2E carta |
| `funnel-payment-methods.css` | Funnel | ~330 | SI | **NO MIGRATE** senza E2E carta |
| `funnel-cart-totals.css` | Funnel | ~210 | Adjacent | Cauto |
| `funnel-shared.css` | Funnel | ~245 | Mixto | Cauto — selettori condivisi tra step |
| `print.css` | Foundation | ~110 | No | NO — `@media print`, intoccabile |
| `components/*.css` | Component-scoped | varie | Vedi singolo file | Vedi singolo file |

## Cosa significa "Stripe-critical"

I file marcati Stripe-critical contengono cascade su selettori (`.checkout-payment-*`,
`.checkout-payment-card-form__*`, `.checkout-payment-choice*`, `.checkout-payment-option*`,
`.payment-summary-*`, `.service-surface*`, `.shipment-stage-*`) che governano **layout,
z-index, focus state e visibilità del PaymentIntent / 3DS challenge**. Modifiche silenti
che alterano cascade order, position absolute/fixed, o pseudo-elementi possono rompere
il flusso pagamento reale (Stripe Elements iframe + 3DS modale) senza che lo si veda
nei test unit/snapshot.

**Test gating obbligatorio**: prima di qualsiasi modifica logic-affecting su file
Stripe-critical, eseguire E2E con carta test `4242 4242 4242 4242 09/30 123` (vedi
`feedback_stripe_test_card.md` in MEMORY) verificando:

1. Selezione metodo (Carta / Bonifico / Wallet) si visualizza e seleziona.
2. PaymentIntent visibile, focus visibile, validazione campi.
3. 3DS challenge si apre dentro iframe (cross-origin: NON cliccabile da Playwright,
   serve test manuale Chrome).
4. Redirect post-pagamento `/dashboard/utenti/ordini/:id` corretto.

## Cleanup dead code (storico)

- **2026-05-03**: rimosse 6 regole DEAD (selettori 0 match in DOM `.vue/.ts/.js`):
  - `funnel-checkout.css`: `.checkout-payment-card-form__intro`, `.checkout-payment-card-form__text`
  - `shipment-flow.css`: `.service-panel__row-meta`, `.service-surface__price-pill--featured`
    (regola base + 2 regole con selettori discendenti `.service-stage-shell--funnel-standard *`)
  - Risparmio sorgente: -1130 byte. Build verde, typecheck pass, gzip stabile.

### Selettori candidati DEAD non rimossi (safety hold)

Hanno controparti in `@media`/selector-list condivisa: vincolo "NO toccare cascade".

- `.checkout-panel-head__text` — base in `funnel-checkout.css:256`, override `@media`
  in `funnel-stages.css:116`. Tenuta per coerenza cascade `@media`.
- `.checkout-payment-card-form__head` — base + override `@media`. Idem.
- `.service-panel__stack` — fa parte di selector list condivisa con
  `.service-panel__contrassegno-strip` (USATA). Rimuovere selettivamente è cascade-affecting.

## Convenzioni cassaforte

- NO `<style scoped>` con classi page-specific custom (`.account-*`, `.admin-*`, `.lp-*`).
- NO mischiare CSS custom + Tailwind utility nello stesso componente.
- NO toccare `@media`, `@keyframes`, pseudo-elementi (`::before`, `::after`,
  `:focus-visible`, `:has(...)`) senza E2E.
- Per nuovi token brand: sempre in `:root` di `main.css`, mappati in
  `tailwind.config.ts` come `bg-brand-*` / `text-brand-*` / `border-brand-*`.

## Riferimenti

- [ADR 004 — Tailwind utility design system](../../../docs/adr/004-tailwind-utility-design-system.md)
- [ADR 007 — TypeScript strategy](../../../docs/adr/007-typescript-strategy.md)
- `pages/__design-system.vue` — showcase `Sf*` components
- `CLAUDE.md` (root) — sezione "File critici (idempotency / soldi reali)"
