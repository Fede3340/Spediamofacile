# Onboarding — SpediamoFacile

Tempo stimato: **20 minuti** dal clone al primo render.

## Cosa fa il sito (1 min)

Intermediario BRT per spedizioni Italia/EU. Utente fa preventivo (CAP partenza
+ destinazione + peso + dimensioni), aggiunge servizi extra, paga (Stripe /
bonifico / wallet), BRT ritira a domicilio e consegna. Wallet, fatture,
admin panel completo.

## Stack reale (1 min)

```
Browser ─→ Caddy :8787 (single origin)
              │
              ├─→ /api/* /sanctum/* /storage/* /auth/* /webhooks/* /stripe/*
              │     → Laravel :8000 (root repo, Sanctum SPA)
              │
              └─→ tutto il resto (HTML + asset)
                    → Nuxt :3001 (apps/web/, Vue 3.5 + Pinia + Tailwind 4)

Laravel
  ├─→ SQLite dev / Postgres prod
  ├─→ Stripe API (idempotency + 3DS)
  └─→ BRT REST 3.x (PUDO + tracking + label)
```

**Backend Laravel alla root**, **frontend Nuxt in `apps/web/`**, **Caddy** reverse proxy split-routing.

## Setup (2 min con Docker — consigliato)

```bash
git clone <repo> spediamofacile && cd spediamofacile

# 5 servizi (postgres + redis + laravel + nuxt + caddy) in un comando
make dev

# Apri http://127.0.0.1:8787 quando "caddy: healthy"
make logs    # tail dei log
make test    # PHPUnit + Vitest
make down    # stop (mantiene volumi)
make clean   # reset completo
```

Vedi `make help` per tutti i target. File: [`docker-compose.yml`](../docker-compose.yml) +
[`infra/docker/`](../infra/docker/) Dockerfile per ciascun servizio.

## Setup (10 min host nativo — alternativa)

```bash
git clone <repo> spediamofacile && cd spediamofacile

# Backend
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate:fresh --seed

# Frontend
cd apps/web && npm install && cd ..

# 3 processi paralleli (vedi .claude/launch.json):
php artisan serve --port=8000 &           # Laravel
npm run dev --prefix apps/web &           # Nuxt :3001
caddy run --config infra/caddy/Caddyfile  # Caddy :8787

# Apri browser
open http://127.0.0.1:8787
```

## Flow del codice (5 min)

```
1. Browser GET /preventivo
2. Caddy :8787 inoltra a Nuxt :3001 (HTML pagina)
3. Nuxt page apps/web/pages/preventivo.vue render
4. JS chiama API: POST /api/preventivo/calcola
5. Caddy inoltra a Laravel :8000
6. routes/api.php → routes/api/shipment.php → Controller → Service
7. JSON response → Nuxt aggiorna stato → render risultato
```

## I 5 errori che farai

1. **Cercare frontend dentro Laravel** (`resources/js/`): non c'è. Frontend è in **`apps/web/`** Nuxt.
2. **Aspettarsi Inertia**: rimosso. Il sito è Nuxt SPA + Laravel API headless.
3. **Toccare i file critici Stripe** senza E2E gating con carta test
   `4242 4242 4242 4242 09/30 123`. Vedi CLAUDE.md "File critici".
4. **`blue-*` Tailwind**: la palette è teal `#095866` + arancione `#E44203`.
5. **Dimenticare cents**: backend ritorna `payable_total_cents`, frontend mostra
   `(value/100).toFixed(2).replace('.', ',') + ' €'`.

## Struttura cartelle

```
spedizionefacile/                  ← root Laravel
├── app/
│   ├── Http/Controllers/          ← organizzati per dominio
│   │   ├── Auth/                  ← Login, Register, Password, Verify, Google
│   │   ├── Account/               ← profilo, indirizzi, ProRequest, ReferralCode
│   │   ├── Admin/                 ← Dashboard, OrderManagement, Users, Wallet
│   │   ├── Catalog/               ← Article, Coupon, Location, PackageController
│   │   ├── Cart/                  ← CartItem, GuestCart, CartTotal
│   │   ├── Checkout/              ← StripeCheckout, StripeWebhook, Refund
│   │   ├── Order/                 ← OrderList, OrderDetail
│   │   ├── Shipping/              ← BrtController, BrtWebhook, ShipmentExecution
│   │   └── Wallet/                ← Wallet, Withdrawal
│   ├── Models/                    ← Eloquent (Order, User, Package, Coupon...)
│   └── Services/                  ← business logic
│       ├── Brt/                   ← 11 sub-service BRT REST 3.x
│       ├── Pricing/               ← supplementi automatici
│       ├── Invoice/               ← PDF helpers
│       ├── BrtClient.php          ← facade unificata BRT
│       └── StripePaymentService.php, OrderCreationService.php, ...
├── routes/
│   ├── web.php                    ← webhook + Sanctum CSRF + Google OAuth
│   ├── api.php                    ← loader API
│   └── api/                       ← API JSON modulari
│       ├── auth.php cart.php orders.php payments.php
│       ├── community.php admin.php public.php invoices.php
│       └── shipment.php
├── database/
│   ├── migrations/                ← (vuoto: schema dump-based)
│   ├── schema/sqlite-schema.sql   ← fonte di verità schema
│   └── seeders/
├── apps/
│   └── web/                       ← Nuxt 4 SPA
│       ├── pages/                 ← una page per route
│       ├── components/            ← UI riusabili
│       ├── composables/           ← logic riusabili
│       ├── stores/                ← Pinia store
│       ├── utils/                 ← helper puri
│       └── assets/css/            ← Tailwind 4 + custom
├── infra/caddy/Caddyfile          ← reverse proxy split routing
├── tests/                         ← PHPUnit backend
└── docs/                          ← documentazione
```

## Comandi utili

```bash
# Backend
php artisan route:list             # tutte le rotte
php artisan tinker                 # REPL Eloquent
php artisan test                   # PHPUnit backend
php artisan test --filter=...      # test specifico
composer dump-autoload             # ricarica classmap

# Frontend (cd apps/web)
npm run dev                        # Nuxt dev server :3001
npm run build                      # build production
npm run typecheck                  # vue-tsc
npm run lint                       # ESLint
npm run test:unit                  # Vitest
npx playwright test                # E2E
```

## Design system frontend

Una sola strada per ogni cosa: **Tailwind utility puro + 23 componenti `Sf*` + Nuxt UI 4**.

| Categoria | Componenti `Sf*` |
|---|---|
| Form | `SfButton`, `SfInput`, `SfTextarea`, `SfSelect`, `SfCheckbox`, `SfRadio`, `SfSegmented`, `SfFormGroup` |
| Surface | `SfCard`, `SfModal`, `SfConfirmDialog`, `SfTooltip`, `SfDropdown`, `SfSkeleton` |
| Feedback | `SfBadge`, `SfStatusPill`, `SfStatCard`, `SfAvatar`, `SfAlert`, `SfEmptyState`, `SfAddressChip` |
| Navigation | `SfTabs`, `SfBreadcrumbs`, `SfPagination`, `SfTable` |

I `Sf*` sono auto-imported (Nuxt) — usabili direttamente in template. Showcase live in
[`pages/__design-system.vue`](../apps/web/pages/__design-system.vue) (dev-only).

**Token brand**: `bg-brand-primary` (teal), `bg-brand-accent` (arancione), `text-brand-text*`,
`shadow-sf*`, `rounded-button|control|card|pill`. CSS variables in
[`apps/web/assets/css/main.css`](../apps/web/assets/css/main.css) `:root` → mappate a Tailwind in
[`tailwind.config.js`](../apps/web/tailwind.config.js).

**Vietato**: `<style scoped>` con classi page-specific (`.account-*`, `.admin-*`, `.lp-*`),
mischiare CSS custom + Tailwind nello stesso componente, palette `blue-*`/`indigo-*`/`sky-*`.

Vedi [`docs/adr/004-tailwind-utility-design-system.md`](adr/004-tailwind-utility-design-system.md).

## Architettura backend

Modular monolith — controller HTTP magri, service per business logic, model Eloquent puri.
Per la regola di dove va cosa, e quando si può usare `DB::table()` invece di Eloquent, vedi
[`docs/adr/006-service-layer-architecture.md`](adr/006-service-layer-architecture.md).

## File da leggere PRIMA di scrivere codice

1. [`CLAUDE.md`](../CLAUDE.md) — convenzioni progetto
2. `routes/api.php` + `routes/api/*.php` — mapping API
3. `apps/web/pages/preventivo.vue` — page con widget quick quote
4. `apps/web/pages/la-tua-spedizione/[step].vue` — funnel orchestrator
5. `app/Http/Controllers/Checkout/StripeCheckoutController.php` — esempio controller Stripe-critical
6. [`docs/adr/`](adr/) — 6 Architectural Decision Records

Buon lavoro.
