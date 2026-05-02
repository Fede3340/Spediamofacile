# CLAUDE.md — Istruzioni per Claude Code in questo repo

> SpediamoFacile — Laravel 11 API headless + Nuxt 4 SPA, dietro Caddy reverse proxy.
> Letto automaticamente da Claude Code all'apertura del progetto.

## Stack

- **Backend (root)**: Laravel 11 + Sanctum 4 + Stripe SDK 18 + BRT REST 3.x
- **Frontend (`apps/web/`)**: Nuxt 4.1 + Vue 3.5 + Pinia 3 + Nuxt UI 4 + Tailwind 4
- **Auth**: Sanctum SPA (cookie httpOnly stessa origin via Caddy)
- **DB**: SQLite dev / Postgres prod (parity via Eloquent)
- **Pagamenti**: Stripe SDK 18 (Elements + idempotency)
- **Corriere**: BRT REST 3.x via `App\Services\Brt\*` (facade `BrtClient`)
- **Reverse proxy**: Caddy `:8787` split routing → Laravel `:8000` (API) + Nuxt `:3001` (SPA)

## Quickstart

```bash
# Backend
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed

# Frontend
cd apps/web && npm install && cd ../..

# 3 processi in parallelo (preview MCP via .claude/launch.json):
#   laravel-backend  → :8000  (php artisan serve)
#   nuxt-dev         → :3001  (npm run dev --prefix apps/web)
#   caddy-proxy      → :8787  (caddy run --config infra/caddy/Caddyfile)

# Apri http://127.0.0.1:8787
```

## Routing & cookie cross-origin

Caddy `:8787` è il **single entry-point** del browser. Routing in `infra/caddy/Caddyfile`:

- `/api/* /sanctum/* /storage/* /auth/* /webhooks/* /stripe/*` → Laravel `:8000`
- tutto il resto (HTML pagine + asset Nuxt) → Nuxt `:3001`

Cookie sessione Laravel (Sanctum SPA) emessi su `127.0.0.1:8787` → condivisi tra API e SPA senza CORS.

## Convenzioni codice

- **Prezzi**: backend in **cents** (`MyMoney` / moneyphp). Frontend mostra `(cents/100).toFixed(2) + ' €'`.
- **Auth Nuxt**: `useSanctumClient()` per chiamate API stateful. Mai `$fetch` raw senza credenziali.
- **Routes**: tutte le rotte API in `routes/api/*.php` (loader: `routes/api.php`). `routes/web.php` solo per webhook + Sanctum CSRF + Google OAuth.
- **Pages Nuxt**: in `apps/web/pages/`, layout default in `apps/web/layouts/default.vue`.
- **Components Nuxt**: in `apps/web/components/`, auto-import abilitato (`<ServizioGrid>` direttamente).
- **Palette**: teal `#095866` + arancione `#E44203` + neutri. **Mai blu** (no `blue-*`, `indigo-*`, `sky-*`, `slate-*`).
- **Italiano** per stringhe utente (commenti, label, errori). **English** per identifier (variabili, funzioni, tabelle).

## Design system (regola d'oro UI)

- **UNA sola strada per lo styling**: Tailwind utility puro + componenti `Sf*` proprietari + Nuxt UI 4 per primitive. Vedi [`docs/adr/004-tailwind-utility-design-system.md`](docs/adr/004-tailwind-utility-design-system.md).
- **CSS custom solo in casi documentati**: `:root` token in `assets/css/main.css`, keyframes in `funnel-animations.css`, `funnel-*.css` Stripe-critical (intoccabili senza E2E carta). Tutti gli altri stili = Tailwind utility inline o `Sf*` component.
- **Naming token brand** (CSS variables in `:root` mappate a Tailwind config):
  - `bg-brand-primary`, `text-brand-primary`, `border-brand-primary` (teal)
  - `bg-brand-accent`, `text-brand-accent`, `bg-brand-accent-surface` (arancione)
  - `text-brand-text`, `text-brand-text-secondary`, `text-brand-text-muted`
  - `bg-brand-card`, `border-brand-border`, `bg-brand-bg-alt`
  - `bg-brand-success-bg`, `text-brand-success-fg`, `bg-brand-error`
  - `rounded-button`, `rounded-control`, `rounded-card`, `rounded-pill`
  - `shadow-sf`, `shadow-sf-sm`, `shadow-sf-lg`, `shadow-sf-focus`
- **Componenti `Sf*` disponibili** (auto-imported, vedi `pages/__design-system.vue`):
  - Form: `SfButton`, `SfInput`, `SfTextarea`, `SfSelect`, `SfCheckbox`, `SfRadio`, `SfSegmented`, `SfFormGroup`
  - Surface: `SfCard`, `SfModal`, `SfConfirmDialog`, `SfTooltip`, `SfDropdown`, `SfSkeleton`
  - Feedback: `SfBadge`, `SfStatusPill`, `SfStatCard`, `SfAvatar`, `SfAlert`, `SfEmptyState`, `SfAddressChip`
  - Navigation: `SfTabs`, `SfBreadcrumbs`, `SfPagination`, `SfTable`
- **Nuxt UI 4** (`<UModal>`, `<UTable>`, `<UInput>`, `<UFormGroup>`, `<UTabs>`, `<UAvatar>`, `<UIcon>`): primitive avanzate quando `Sf*` non basta.
- **MAI**: `<style scoped>` con classi page-specific custom (`.account-*`, `.admin-*`, `.lp-*`). MAI mischiare CSS custom + Tailwind nello stesso componente.

## File critici (idempotency / soldi reali)

Modificare solo con E2E gating Stripe (`4242 4242 4242 4242 09/30 123`):

- `app/Http/Controllers/Checkout/StripeCheckoutController.php` — PaymentIntent + 3DS
- `app/Http/Controllers/Checkout/StripeWebhookController.php` — firma + idempotency
- `app/Services/StripePaymentService.php` — client Stripe + idempotency-key
- `app/Services/OrderCreationService.php` — Carrello → Order
- `app/Services/WalletOrderPaymentService.php` — lock saldo wallet
- `app/Models/Order.php` — `payableTotalCents()` autorità fatturazione
- `app/Http/Controllers/Shipping/BrtWebhookController.php` — HMAC tracking
- `bootstrap/app.php` — esclusioni CSRF webhook, `statefulApi()`, `trustProxies('*')`

## Limiti dimensionali

- File runtime ≤ 400 LOC.
- Componente Vue ≤ 500 LOC.
- Page Vue ≤ 400 LOC.
- Controller ≤ 200 LOC.
- Service ≤ 400 LOC.

Eccezioni documentate inline con `// CRITICAL:` + motivazione.

## DB::table() autorizzati

Vedi [`docs/adr/006-service-layer-architecture.md`](docs/adr/006-service-layer-architecture.md) per il razionale completo. In sintesi:

- **Pivot pure**: `cart_user`, `package_order`, `saved_shipments`
- **Laravel internals**: `password_reset_tokens`, `sessions`, `cache`, `jobs`, `cookie_consents`
- **Bulk import / counter atomico**: `locations` (GeoNames), `invoice_counters` (counter fattura)
- **Lock pessimistico esplicito**: `users` con `lockForUpdate` (Stripe payment + wallet)
- **Cleanup massivo / report aggregato**: comandi console (`CleanupOrders`, `SendAbandonedCartEmails`)

Tutti gli altri accessi DB usano Eloquent.

## Test

- **Backend**: `php artisan test` → 333 pass, 18 skipped (parallel richiede paratest 7.x)
- **Frontend**: `cd apps/web && npm run build` (verifica build prod) + `npm run test` (vitest)
- **E2E Playwright**: `cd apps/web && npx playwright test`

## Regole AI

- **Mai `git commit` senza permesso esplicito utente**.
- **Italiano** per commenti, doc, output utente.
- **Verifica con preview MCP** dopo ogni modifica visibile (`http://127.0.0.1:8787`).
- **Max 3 agent paralleli**.
- **Standard UX**: Awwwards / Baymard / NN Group.
