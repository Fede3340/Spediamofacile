# MAP — dove sono le cose

> Una sola pagina per orientarsi. Se cerchi qualcosa, parti da qui.

## Stack

- **Backend** Laravel 11 alla root, **Frontend** Nuxt 4 in `apps/web/`
- **Reverse proxy** Caddy `:8787` → Laravel `:8000` (API) + Nuxt `:3001` (SPA)
- **Auth** Sanctum SPA cookie httpOnly, **DB** SQLite dev / Postgres prod
- **Pagamenti** Stripe SDK 18 + Bonifico + Wallet, **Corriere** BRT REST 3.x

## Regola d'oro

**Una sola strada per ogni cosa**:
- Form → `<SfFormGroup>` + `<SfInput>`
- Tabelle → `<SfTable>` (semplici) o `<UTable>` (sort/filter)
- Modal → `<SfModal>` o `<SfConfirmDialog>`
- Stati ordine → `<SfStatusPill>` (mapping centralizzato)
- Pagamenti → `composables/payment/*` (Stripe-critical)
- Stile → Tailwind utility puro + token `bg-brand-*` `text-brand-*`

## Frontend (`apps/web/`)

```
apps/web/
├── components/
│   ├── account/         18 componenti area account cliente
│   ├── admin/           36 componenti pannello amministrazione
│   ├── auth/             5 componenti login/registrazione/recupero
│   ├── cart/             3 componenti carrello
│   ├── checkout/         componenti checkout (NB: alcuni Stripe-adjacent)
│   ├── guide/            5 componenti guide blog
│   ├── layout/           Navbar, Footer, Header, Logo, ContenutoHeader
│   ├── pudo/             5 componenti punti BRT
│   ├── servizi/          3 componenti servizi
│   ├── sf/              25 componenti DESIGN SYSTEM (Sf*)
│   ├── shipment/         16 componenti funnel preventivo (Stripe-critical adjacent)
│   └── tracking/         3 componenti tracking
├── composables/         64 composable (con INDEX.md mappa per dominio)
│   └── payment/          INTOCCABILE — Stripe critical
├── pages/               48 pagine
│   └── la-tua-spedizione/[step].vue   shell funnel intoccabile
├── stores/              10 Pinia stores
│   └── admin/            sub-stores admin
├── utils/               27 utils helper
└── assets/css/
    ├── main.css         token :root + reset Tailwind
    ├── motion.css       motion library
    └── funnel-*.css     Stripe-critical (5 file, ~4870 LOC, intoccabili)
```

## Backend (root Laravel)

```
app/
├── Http/
│   ├── Controllers/     thin <200 LOC, delegano a service
│   │   ├── Admin/       gestione admin (orders, settings, users)
│   │   ├── Auth/        login/register/Google OAuth
│   │   ├── Cart/        carrello
│   │   ├── Catalog/     servizi, locations, price bands
│   │   ├── Checkout/    Stripe-critical (NON toccare senza E2E)
│   │   ├── Gdpr/        export + delete utente
│   │   ├── Order/       dettaglio + azioni ordini
│   │   ├── Shipping/    spedizioni, BRT webhook, session data
│   │   ├── Wallet/      saldo + ricarica
│   │   ├── Withdrawals/ prelievi Pro
│   │   └── Traits/      BuildsSessionPayload + HandlesOrderSubmissionContext
│   └── Requests/        FormRequest validation classes
├── Services/            30+ business logic (modular monolith ADR 006)
│   ├── Admin/           OrderManagementService
│   ├── Auth/            GoogleOAuthService
│   ├── Brt/             BrtClient + BordereauGenerator
│   ├── Cart/            CartItemService
│   ├── Catalog/         LocationLookupService
│   ├── Checkout/        CheckoutDiscountContextResolver
│   ├── Gdpr/            GdprService
│   ├── Invoice/         InvoicePdfService + Renderer
│   ├── Order/           OrderActionsService + OrderCreationService
│   ├── Pricing/         PriceBandService + PriceEngine + EuropePriceEngine
│   ├── Shipping/        SavedShipmentService + ShipmentExecutionService + SessionDataService
│   ├── Stripe/          StripePaymentService + Webhook handlers
│   └── (root)           StripePaymentService, WalletOrderPaymentService
├── Models/              28 Eloquent (NO business logic, solo schema + relazioni)
├── Support/             helper statici riusati cross-domain
└── ...

routes/
├── api.php              loader modulare
├── api/                 routes per dominio (auth, cart, orders, payments, shipment, admin, ...)
└── web.php              webhook Stripe + BRT + Sanctum CSRF + Google OAuth
```

## File critici (NON toccare senza E2E carta vera)

```
app/Http/Controllers/Checkout/StripeCheckoutController.php       PaymentIntent
app/Http/Controllers/Checkout/StripeWebhookController.php        Firma webhook
app/Services/StripePaymentService.php                            Idempotency-key
app/Services/OrderCreationService.php                            Carrello → Order
app/Services/WalletOrderPaymentService.php                       Lock saldo
app/Models/Order.php                                             payableTotalCents()
app/Http/Controllers/Shipping/BrtWebhookController.php           HMAC tracking
bootstrap/app.php                                                Esclusioni CSRF
apps/web/composables/payment/*                                   Stripe Elements client
apps/web/components/shipment/ShipmentFlowPage.vue                Funnel orchestrator
apps/web/assets/css/funnel-*.css                                 Cascade Stripe-critical
```

Stripe test card: `4242 4242 4242 4242 09/30 123`.

## Convenzioni

- **Prezzi backend in cents** (`MyMoney` / moneyphp). Frontend mostra `(cents/100).toFixed(2) + ' €'`
- **Auth Nuxt**: `useSanctumClient()` per chiamate API stateful
- **Italiano** per stringhe utente, **English** per identifier
- **Palette**: teal `#095866` + arancione `#E44203` + neutri. **Mai blu**
- **TypeScript** in `.ts` (composables/utils/stores) — **JavaScript** in `.vue` (template) — vedi ADR 007
- **Limiti LOC** controllati: Componente Vue ≤500, Page ≤400, Controller ≤200, Service ≤400 (ADR 006)

## Test

```bash
php artisan test                                  # 336 pass + 18 skip backend
cd apps/web && npm run test:unit                  # 371 pass frontend
cd apps/web && npx playwright test                # E2E funzionali + visual
cd apps/web && npm run typecheck                  # 0 errori
cd apps/web && npm run lint                       # 0 errori
cd apps/web && npm run build                      # production verde
```

## Account demo

| Email | Password | Ruolo |
|---|---|---|
| `admin@spediamofacile.it` | `password` | Admin |
| `cliente@spediamofacile.it` | `password` | Cliente |
| `pro@spediamofacile.it` | `password` | Partner Pro |

## ADR (Architectural Decision Records)

1. **001** — Sanctum SPA auth (cookie httpOnly stessa origin)
2. **002** — moneyphp cents (precisione monetaria)
3. **003** — BRT direct integration (no aggregator)
4. **004** — Tailwind utility + design system Sf\*
5. **005** — Stripe Elements + idempotency + webhook firmato
6. **006** — Service layer architecture (modular monolith)
7. **007** — TypeScript strategy (.ts logic, .vue JS)

## Showcase design system

`apps/web/pages/__design-system.vue` — pagina dev-only con esempio live di tutti i 25 componenti `Sf*`.
