# Architecture — SpediamoFacile

## Sistema (Laravel API headless + Nuxt SPA + Caddy)

```
 +--------+        +-----------------+
 |        |--HTTPS-|                 |
 |Browser |        | Caddy   :8787   |  (single origin)
 |        |<-HTTP-|                 |
 +--------+        +--------+--------+
                            |
              split routing per path
                            |
            +---------------+---------------+
            |                               |
            v                               v
    +---------------+               +-------------------+
    | Laravel :8000 |               | Nuxt   :3001      |
    |  API JSON     |               |  Vue 3.5 SPA      |
    |  + Sanctum    |               |  + Pinia + Tailwind |
    |  + Webhooks   |               |  + apps/web/      |
    +-------+-------+               +-------------------+
            |
   +--------+----------+----------+----------+
   |        |          |          |          |
   v        v          v          v          v
+----+  +-------+  +-------+  +--------+  +--------+
|SQL |  |Stripe |  | BRT   |  | Sentry |  | SMTP   |
|ite |  |hosted |  |REST3.x|  |        |  | mail   |
|/PG |  |+webh. |  |+webh. |  |        |  |        |
+----+  +-------+  +-------+  +--------+  +--------+
```

**Routing Caddy**:
- `/api/* /sanctum/* /storage/* /auth/* /webhooks/* /stripe/*` → Laravel `:8000`
- tutto il resto (HTML pagine + asset) → Nuxt `:3001`

## Stack tecnologico

- **Backend**: Laravel 11 alla **root** del repo
  - Sanctum 4 (auth SPA cookie httpOnly stessa origine via Caddy)
  - Stripe SDK 18 (Checkout hosted + Elements + idempotency)
  - BRT REST 3.x (`App\Services\Brt\*` orchestrato da `BrtClient` facade)
- **Frontend**: Nuxt 4 SPA in **`apps/web/`**
  - Vue 3.5 + Pinia 3 + Nuxt UI 4 + Tailwind 4
  - `useSanctumClient()` per chiamate API stateful
- **Reverse proxy**: Caddy `:8787`
- **DB**: SQLite locale, Postgres 15 produzione (Eloquent parity)
- **Cache/Queue**: Redis 7 (file driver in dev)
- **Email**: SMTP via Laravel Mail
- **Errori**: Sentry PHP SDK (DSN opzionale)

## Flusso utente

```
Browser GET /                      → Nuxt apps/web/pages/index.vue (home)
Browser GET /preventivo            → Nuxt apps/web/pages/preventivo.vue (widget quick quote)
Browser POST /api/preventivo/calcola → Laravel routes/api/shipment.php → Service
Browser /la-tua-spedizione/2?step=colli → Nuxt funnel orchestrator
Browser POST /api/cart             → Laravel routes/api/cart.php → CartItemController
Browser POST /api/stripe/create-payment-intent → Laravel StripeCheckoutController
Browser → Stripe redirect (hosted) → POST /stripe/webhook (firma + idempotency)
Browser GET /checkout/return?session_id=cs_... → Nuxt success page
BRT → POST /webhooks/brt/tracking (HMAC + IP whitelist)
```

## Struttura cartelle

```
spedizionefacile/                  ← root Laravel
├── app/
│   ├── Http/
│   │   ├── Controllers/           ← organizzati per dominio
│   │   │   ├── Auth/              ← Login, Register, Password, Verify, Google
│   │   │   ├── Account/           ← Profilo, Indirizzi, ProRequest, ReferralCode, ...
│   │   │   ├── Admin/             ← Dashboard, OrderManagement, Users, Wallet, Coupons, ...
│   │   │   ├── Catalog/           ← Article, Coupon, Location, Package, PriceBand
│   │   │   ├── Cart/              ← CartItem, GuestCart, CartTotal
│   │   │   ├── Checkout/          ← StripeCheckout, StripeWebhook, Refund, StripeConnect, StripeCustomer
│   │   │   ├── Order/             ← OrderList, OrderDetail
│   │   │   ├── Shipping/          ← BrtController, BrtWebhook, ShipmentExecution, OrderExport
│   │   │   ├── Wallet/            ← Wallet, Withdrawal
│   │   │   └── Gdpr/              ← export account
│   │   ├── Middleware/            ← Sanctum, CheckAdmin, SecurityHeaders, SentryContext, ...
│   │   ├── Requests/              ← FormRequest validation
│   │   └── Resources/             ← API JSON
│   ├── Models/                    ← Eloquent (Order, User, Package, Coupon, ...)
│   ├── Services/
│   │   ├── Brt/                   ← 11 sub-service BRT REST 3.x
│   │   │   ├── ShipmentService, TrackingService, PudoService, PickupService
│   │   │   ├── BrtBordereauGenerator (PDF)
│   │   │   ├── PdfRenderingPrimitives (trait helper PDF)
│   │   │   ├── AddressNormalizer, ErrorTranslator, FilialeLookup
│   │   │   └── BrtConfig, BrtPayloadBuilder, PudoPointMapper
│   │   ├── Pricing/               ← AutomaticSupplementCalculator, PricingConfigNormalizer
│   │   ├── Invoice/               ← SinglePagePdfHelpers (trait PDF fattura)
│   │   ├── Catalog/               ← PhotonLocationFallback (geo OSM)
│   │   ├── Checkout/              ← SnapshotCompactingHelpers, StripeWebhookHelpers, StripeCheckoutHelpers
│   │   ├── Stripe/                ← IdempotencyAndMetadataHelpers
│   │   ├── BrtClient.php          ← FACADE unificata BRT
│   │   ├── StripePaymentService.php
│   │   ├── OrderCreationService.php
│   │   ├── WalletOrderPaymentService.php
│   │   ├── PriceEngineService.php, EuropePriceEngineService.php
│   │   └── ...
│   └── Events, Listeners, Jobs, Notifications, Mail
├── routes/
│   ├── web.php                    ← webhook (Stripe, BRT) + Sanctum CSRF + Google OAuth
│   ├── api.php                    ← loader API
│   └── api/                       ← API JSON modulari
│       ├── auth.php cart.php orders.php payments.php
│       ├── community.php admin.php public.php invoices.php
│       └── shipment.php
├── database/
│   ├── migrations/                ← (vuoto: schema dump-based)
│   ├── schema/sqlite-schema.sql   ← fonte di verità schema (Laravel 11 squash)
│   ├── seeders/                   ← User, Article, PriceBand, Location (713 CAP), PudoPoint
│   └── factories/
├── apps/
│   └── web/                       ← Nuxt 4 SPA
│       ├── pages/                 ← una page per route
│       │   ├── index.vue          ← home
│       │   ├── preventivo.vue
│       │   ├── la-tua-spedizione/[step].vue   ← funnel orchestrator
│       │   ├── carrello.vue
│       │   ├── traccia/[tracking].vue
│       │   ├── account/           ← cliente: dashboard, spedizioni, profilo, indirizzi, ...
│       │   ├── account/amministrazione/  ← admin: ordini, utenti, prezzi, ...
│       │   ├── chi-siamo.vue, faq.vue, contatti.vue, guide/...
│       │   └── cookie-policy.vue, privacy-policy.vue, termini-e-condizioni.vue
│       ├── components/            ← layout, shipment, checkout, sf, admin, account, ...
│       ├── composables/           ← useCart, useFunnel, useTrackingDetail, ...
│       ├── stores/                ← Pinia (admin/, cart, pudo, payment, ...)
│       ├── utils/                 ← helper puri
│       ├── assets/css/            ← Tailwind 4 + brand custom
│       ├── server/                ← Nuxt server middleware
│       └── tests/                 ← Vitest + Playwright E2E
├── infra/caddy/Caddyfile          ← reverse proxy split routing
├── tests/                         ← PHPUnit backend (333 test)
├── docs/                          ← documentazione
└── scripts/                       ← bisect-purge.py, multi-pass-purge.py CSS tools
```

## File critici (NON toccare senza E2E gating Stripe)

```
+----------------------------------------+    +---------------------------------------+
|  CHECKOUT                              |    |  PAYMENT/ORDER                        |
|  StripeCheckoutController.php          |    |  StripePaymentService.php             |
|  StripeWebhookController.php           |    |  OrderCreationService.php             |
|  Checkout/StripeCheckoutHelpers.php    |    |  WalletOrderPaymentService.php        |
|  Checkout/StripeWebhookHelpers.php     |    |  Stripe/IdempotencyAndMetadataHelpers |
|                                        |    |  Models/Order::payableTotalCents      |
|  TOCCARE SOLO CON:                     |    +---------------------------------------+
|  - carta test 4242 4242 4242 4242 09/30|    +---------------------------------------+
|  - DB snapshot pre/post                |    |  BRT                                  |
|  - rollback se diff                    |    |  BrtWebhookController (HMAC fail-closed)|
+----------------------------------------+    |  BrtClient.php (facade)               |
                                              +---------------------------------------+
```

## Riferimenti

- [`README.md`](../README.md) — quickstart
- [`ONBOARDING.md`](./ONBOARDING.md) — primo giorno dev
- [`audits/2026-04-v5-1-r4/`](./audits/2026-04-v5-1-r4/) — audit qualita repo V5.1R4 archiviato
- [`operations/DEPLOY.md`](./operations/DEPLOY.md) — pipeline prod
- [`legal/SECURITY.md`](./legal/SECURITY.md) — OWASP baseline
- [`legal/GDPR_COMPLETO.md`](./legal/GDPR_COMPLETO.md) — compliance
- [`adr/`](./adr/) — decisioni tecniche storiche
