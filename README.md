# SpediamoFacile

Intermediario BRT: preventivo, funnel spedizione, carrello, pagamento, account cliente, admin, wallet, coupon/referral, PUDO, tracking, documenti/etichette/fatture.

## Stack reale

- **Backend**: Laravel 11 alla **root** del repo (Sanctum 4 + Stripe SDK 18 + BRT REST 3.x)
- **Frontend**: Nuxt 4 SPA in **`apps/web/`** (Vue 3.5 + Pinia + Nuxt UI 4 + Tailwind 4)
- **Reverse proxy**: Caddy `:8787` → Laravel `:8000` API + Nuxt `:3001` SPA
- **DB**: SQLite (dev) / Postgres (prod)
- **Pagamenti**: Stripe + Bonifico + Wallet interno
- **Test**: PHPUnit backend + Playwright E2E + Vitest unit

## Quickstart

```bash
git clone <repo>
cd spedizionefacile

# Backend
composer install
cp .env.example .env && php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate:fresh --seed

# Frontend
cd apps/web && npm install && cd ..

# 3 processi paralleli (vedi .claude/launch.json):
#   php artisan serve --port=8000              # Laravel API
#   npm run dev --prefix apps/web              # Nuxt :3001
#   caddy run --config infra/caddy/Caddyfile   # Caddy :8787

# Apri http://127.0.0.1:8787
```

> Per setup Docker (Postgres+Redis+Laravel+Nuxt+Caddy con `make dev`),
> `Dockerfile`/`docker-compose.yml`/`Makefile` sono nell'archivio
> `~/Desktop/spedizionefacile-archive/docker-setup/`.

## Routing

- **API JSON** modulari: `routes/api/*.php` (auth, cart, orders, payments, shipment, admin, ...)
- **Webhook esterni**: `routes/web.php` (Stripe `/stripe/webhook`, BRT `/webhooks/brt/tracking`)
- **Sanctum CSRF**: `/sanctum/csrf-cookie`

## Convenzioni codice

- **Prezzi backend in cents** (`MyMoney` / moneyphp). Frontend mostra `(cents/100).toFixed(2) + ' €'`.
- **Auth Nuxt**: `useSanctumClient()` per chiamate API stateful.
- **Italiano** per stringhe utente (commenti, label, errori). **English** per identifier.
- **Palette**: teal `#095866` (primary) + arancione `#E44203` (cta) + neutri. **Mai blu**.

## Test

- Backend: `php artisan test`
- Frontend type-check: `cd apps/web && npm run typecheck`
- Frontend lint: `cd apps/web && npm run lint`
- Frontend unit: `cd apps/web && npm run test:unit`
- Frontend build: `cd apps/web && npm run build`
- E2E: `cd apps/web && npx playwright test`

## Account demo (seeder)

| Email | Password | Ruolo |
|---|---|---|
| `admin@spediamofacile.it` | `Password1!` | Admin |
| `cliente@spediamofacile.it` | `Password1!` | Cliente |
| `pro@spediamofacile.it` | `Password1!` | Partner Pro |

## Documentazione

- [`CLAUDE.md`](CLAUDE.md) — istruzioni AI + convenzioni codice + design system
- `pages/__design-system.vue` — showcase live componenti `Sf*` (dev-only)

## Design system

Frontend: **Tailwind utility puro + 23 componenti `Sf*` + Nuxt UI 4** primitive avanzate. Single source of truth: CSS variables in `apps/web/assets/css/main.css :root`, mappate a token Tailwind in `tailwind.config.js`. Showcase live in `/api/__design-system` (dev-only).

Componenti `Sf*` disponibili in `apps/web/components/sf/`:

| Categoria | Componenti |
|---|---|
| Form | `SfButton`, `SfInput`, `SfTextarea`, `SfSelect`, `SfCheckbox`, `SfRadio`, `SfSegmented`, `SfFormGroup` |
| Surface | `SfCard`, `SfModal`, `SfConfirmDialog`, `SfTooltip`, `SfDropdown`, `SfSkeleton` |
| Feedback | `SfBadge`, `SfStatusPill`, `SfStatCard`, `SfAvatar`, `SfAlert`, `SfEmptyState`, `SfAddressChip` |
| Navigation | `SfTabs`, `SfBreadcrumbs`, `SfPagination`, `SfTable` |

## File critici (idempotency / soldi reali)

Modificare solo con E2E gating Stripe (carta `4242 4242 4242 4242 09/30 123`):

- `app/Http/Controllers/Checkout/StripeCheckoutController.php`
- `app/Http/Controllers/Checkout/StripeWebhookController.php`
- `app/Services/StripePaymentService.php`
- `app/Services/OrderCreationService.php`
- `app/Services/WalletOrderPaymentService.php`
- `app/Models/Order.php`
- `app/Http/Controllers/Shipping/BrtWebhookController.php`
- `bootstrap/app.php`

## License

Proprietario — vedi [`LICENSE`](LICENSE).
