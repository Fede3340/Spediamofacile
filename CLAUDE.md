# CLAUDE.md — Istruzioni per Claude Code in questo repo

> Questo file viene letto automaticamente da Claude Code (versione CLI/SDK)
> all'apertura del progetto. Contiene convenzioni che valgono per TUTTE le
> sessioni AI sulla repo.

## Stack
- Frontend: `apps/web/` (Nuxt 4.1 + Vue 3.5 + Pinia 3 + Tailwind 4 + Nuxt UI 4)
  - Linguaggio: **TypeScript** canonico per `composables/`, `stores/`, `utils/`, `server/`, configs. Vue components in `<script setup>` plain (JS+JSDoc) o `<script setup lang="ts">` indifferentemente — ambedue accettati. JSDoc resta valido come `@typedef` import-style.
- Backend: `apps/api/` (Laravel 11 + Sanctum 4 + Stripe 18 + BRT REST 3.x)
  - **Struttura standard Laravel**: controller raggruppati per dominio in `app/Http/Controllers/<Dominio>/` (Auth, Catalog, Cart, Checkout, Shipping, Account, Admin, Gdpr, Communication). Niente `app/Modules/`.
  - Schema baseline in `database/schema/sqlite-schema.sql` (richiede sqlite3 CLI per `migrate:fresh`).
- Docs essenziali: `docs/` (4 doc canonici + ADR + operations + reference + legal)

## Quickstart
- Onboarding dev (~30 min): `docs/ONBOARDING.md`
- Architettura: `docs/ARCHITECTURE.md`

## Convenzioni codice
- **Prezzi**: backend in cents (`MyMoney` / moneyphp). Frontend usa `formatPrice()` che divide per 100.
- **Auth**: Sanctum SPA cookie + CSRF. Usa `useSanctumClient()` per chiamate API, NON $fetch raw.
- **Routes API**: `/api/*` prefix automatico per `routes/api/*.php`. Webhooks BRT su `/webhooks/brt/tracking` (web.php, NO `/api`).
- **Componenti**: configurato `pathPrefix: false` — componenti accessibili col loro nome file (es. `<ServizioGrid>`, non `<ServiziServizioGrid>`).
- **Palette**: teal `#095866` + arancione `#E44203` + neutri. **Mai blu** (no `blue-*`, `indigo-*`, `sky-*`, `slate-*` Tailwind).
- **Tokens CSS**: in `assets/css/main.css` (vedi `--color-brand-*`). Preferire `var(--token, #fallback)` a hex hardcoded.
- **TypeScript** lato frontend: composables/utils/stores/server/configs in `.ts`. Vue components in `<script setup>` plain (defineProps runtime) **oppure** `<script setup lang="ts">` (defineProps generico). JSDoc resta accettato come complemento.
- **Backend domain grouping**: nuovi controller di dominio vanno in `app/Http/Controllers/<Dominio>/`, namespace `App\Http\Controllers\<Dominio>`.

## CSS architecture (importante — evita bug visivi)
Alcuni CSS sono caricati SOLO da pagine/componenti specifici (code-splitting route-specific):
- `shipment-step.css` → solo `pages/la-tua-spedizione/[step].vue`
- `preventivo.css` → solo `components/Preventivo.vue`
- `autenticazione.css` → solo `components/auth/AuthOverlayModal.vue` + pages auth (`login`, `registrazione`, `recupera-password`, `aggiorna-password`, `verifica-email`)
- `contatti.css`, `servizi.css`, `homepage-servizi.css` → solo pagine/componenti corrispondenti

**REGOLA**: se scrivi una classe CSS **condivisa** tra componenti che possono vivere su pagine diverse (es. pill button, segmented control, form field), NON metterla in un CSS route-specific. Mettila in:
- `assets/css/components/sf-segment.css` (segmented + flow CTA + btn-compact già qui)
- `assets/css/main.css` (tokens globali)
- un nuovo file in `assets/css/components/` importato da `main.css`

Esempio vissuto: `.sf-shared-segment*` era solo in `shipment-step.css` → il segmented "Pacco/Pallet/Valigia" nell'homepage era senza stile. Spostato in `components/sf-segment.css` ora funziona ovunque.

**Come capire se una classe va globale**: grep il nome della classe fuori dal suo CSS di definizione. Se è usata in `components/` NON del dominio del CSS (es. classe in `shipment-step.css` usata da `auth/`), va spostata in globale.

## File critici (intoccabili senza test verdi)

Questi file gestiscono **soldi reali, idempotency, integrazioni esterne**. Modificarli senza test verdi puo' causare doppi addebiti o ordini fantasma.

- `apps/api/app/Http/Controllers/Checkout/StripeWebhookController.php` — verifica firma, idempotency
- `apps/api/app/Http/Controllers/Checkout/StripeCheckoutController.php` — PaymentIntent
- `apps/api/app/Services/StripePaymentService.php` — client Stripe + idempotency-key
- `apps/api/app/Services/OrderCreationService.php` — Carrello → Order
- `apps/api/app/Services/WalletOrderPaymentService.php` / `WalletOrderLinkService.php` — lock saldo wallet
- `apps/api/app/Http/Controllers/Wallet/WalletController.php` — top-up + pagamento saldo
- `apps/api/app/Models/Order.php` — `payableTotalCents()` autorita' fatturazione
- `apps/api/app/Http/Controllers/Shipping/BrtWebhookController.php` — HMAC tracking
- `apps/api/app/Http/Controllers/Shipping/BrtController.php` — etichette BRT pagate
- `apps/api/bootstrap/app.php` — esclusioni CSRF webhook, trustProxies

## Limiti dimensionali

- File runtime ≤ 400 LOC (eccezione documentata in commento iniziale).
- Composable ≤ 300 LOC (oltre, splitta o sposta utility puri in `~/utils/`).
- Componente Vue ≤ 500 LOC (template + script).
- Page Vue ≤ 400 LOC (orchestratore, non ospite di logica).

## Test
- Frontend: `cd apps/web && npx playwright test` (E2E)
- Backend: `cd apps/api && php artisan test` (Feature + Unit)
- Build: `cd apps/web && npm run build` deve essere verde

## Regole AI
- Mai `git commit` senza permesso esplicito utente
- Italiano per tutto (commenti, doc, output)
- Verifica con preview MCP dopo ogni modifica visibile
- Max 3 agent paralleli
- Riferimento standard UX: Awwwards / Baymard / NN Group

## Riferimenti
- `docs/README.md` — indice navigabile completo
- `docs/legal/SECURITY.md` — baseline OWASP
- `docs/operations/GOLIVE_CHECKLIST.md` — checklist deploy
- `docs/legal/GDPR_COMPLETO.md` — compliance GDPR
