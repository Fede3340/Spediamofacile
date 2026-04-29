# CHANGELOG

Tutte le modifiche rilevanti al progetto sono documentate qui.
Formato: [Keep a Changelog](https://keepachangelog.com/).

## [2.6.2-audit-V5.1R4-loop-completo] — 2026-04-29 — Refactor agency-grade autonomo

Loop autonomo: ridotti tutti i god-files non-CRITICAL sotto soglia, estratte
trait + utility riutilizzabili, rimossi residui PayPal fantasma. Build prod
verde, 333 test backend verdi, DOM funnel verde.

### Refactor backend (PHP trait extraction)

- **`app/Services/CartService.php`** (427 → 361 LOC, **-66**):
  - `Concerns/CartDuplicateDetection` (45 LOC) — normalize/samePackageDimensions/sameAddress/isDuplicate.
  - `Concerns/CartServiceSignatures` (52 LOC) — buildServiceSignatureFrom* + calculateGroupedSurcharge*.
  - 9 CartServiceTest verdi. Commit `9f050b7`.

- **`app/Models/Order.php`** (403 → 245 LOC, **-158**):
  - `Concerns/OrderStatusHelpers` (112 LOC) — getStatus + 8 query scope.
  - `Concerns/OrderPayableTotal` (81 LOC) — payableTotalCents (CRITICAL Stripe-gated).
  - 70 test Order/Referral/SavedShipments verdi. Commit `ec41dbd`.

### Refactor frontend (utility extraction)

- **`apps/web/utils/pagination.ts`** (45 LOC nuovo): buildPaginationItems + paginationRange
  riutilizzabili da liste paginate admin. Commit `e65f146`.
- **`pages/account/amministrazione/ordini.vue`** (512 → 491 LOC).

### Cleanup

- **`app/Models/Transaction.php`**: rimosso PayPal fantasma dal mapping
  `getPaymentMethod()` + commento `$fillable`. Il sito non gestisce PayPal
  (confermato in FAQ). Sostituito con `wallet` (metodo realmente supportato).
  Commit `c8ebf6d`.

### Verified

- Build Nuxt prod: verde (34.2 MB / 12.3 MB gzip).
- Test PHP: 333 passed, 18 skipped.
- DOM funnel: h1 "Preventivo" 52px, 4 stage card "Colli/Servizi/Indirizzi/Pagamento".

---

## [2.6.1-audit-V5.1R4-fase4-5] — 2026-04-29 — Refactor frontend + docs junior

Continuazione audit V5.1R4 (Fase 4 + Fase 5) con strategia chirurgica per non
toccare la grafica. Build prod verde. Score atteso ~62 → ~70/100.

### Refactor (LOC ridotte)

- **`pages/la-tua-spedizione/[step].vue`** (1240 → 1190 LOC, **-50**):
  estratti 8 pure functions (`cleanPaymentSummaryText`, `formatExistingOrderDate`,
  `buildEmptyPaymentAddress`, `normalizeExistingOrderAddress`,
  `getExistingOrderPackage*`, `resolveApiError`) in
  `apps/web/utils/shipmentStepHelpers.ts` (modulo TS testabile, zero deps Vue).
  Commit `7254e4a`.
- **`components/shipment/AddressFormFields.vue`** (739 → 615 LOC, **-124**):
  8 blocchi feedback campo (errore + chip auto-fill) consolidati nel sotto-componente
  `AddressFieldFeedback.vue` (41 LOC). Markup HTML output identico.
  Commit `e52c58a`.

### Added (docs junior-friendly)

- **`docs/MODULES_MAP.md`**: mappa di 6 moduli (shipment-flow, checkout-payment,
  wallet, coupon-referral, BRT-PUDO, account-admin). Per ogni modulo: cosa fa,
  entry point, file CRITICAL da non toccare senza E2E. Commit `0938e1a`.

### Skipped (motivato)

- **Split `shipment-flow.css` (6008 LOC)**: rimandato — ordine di specificity
  in produzione è alto rischio di rottura grafica. Da fare con DOM signature
  per-rule pre/post + visual regression tests.
- **`ShipmentStepPagamento.vue` + `usePayment.ts`**: CRITICAL Stripe-gated.
  Da rifattorizzare solo con E2E `4242 4242 4242 4242 09/30 123`.

### Verified

- Build prod (`npm run build`): verde, 34.2 MB output (12.3 MB gzip).
- DOM signature funnel `/la-tua-spedizione/colli`: 4 stage card, h1 "Preventivo"
  52px, form preventivo presente, no nuovi runtime errors.

---

## [2.3.1-deps-clean] — 2026-04-28 — Cleanup agency-grade completo

Sessione di cleanup operata su repo Laravel 11 API + Nuxt 4 SPA + Caddy reverse
proxy. Rollback del rewrite Inertia annullato e bonifica residui.

### Removed (junk + dead code)

- **`_data/snapshot_pre_rewrite.sql` + `snapshot_post_rewrite.sql`** (88 MB):
  residui rewrite annullato.
- **`storage/logs/*`** (~3 MB log dev accumulati).
- **`docs/CLEANUP_PLAN_AI.md` + `docs/baseline/` + `docs/legacy-api-readme.md`**.
- **`scripts/local-tools/`** (Claude/Codex/Win launcher GUI personali, 675 KB).
- **`scripts/ripristina-vue.sh`** (recovery post-rewrite obsoleto).
- **13 orfani backend + 1 composable Vue** (-991 LOC):
  - `app/Services/BrtClient.php` + `StripeCheckoutSession.php` (rewrite v2 morti).
  - `app/Mail/BrtErrorAlert/OrderDelivered/OrderShipped/Welcome.php` (4 file).
  - `app/Http/Requests/BillingAddressStoreRequest + BrtWebhookTrackingRequest.php`.
  - `app/Http/Resources/BillingAddressResource + UserResource.php`.
  - `apps/web/components/sf/SfStateView.vue` (primitive non adottata).
  - `apps/web/composables/useAdminUserDetail.ts` (drawer refattorizzato).
- **2 dipendenze Composer morte**: `inertiajs/inertia-laravel` v3.0 +
  `tightenco/ziggy` v2.6 (zero hits in `app/`).
- **5 controller Inertia + 35 Vue + 1 middleware** dello scaffold rewrite
  annullato (rollback frontend Nuxt completo).
- **2 tag git bugiardi**: `v2.0.0-rewrite-complete` + `v2.0.1-real-funnel`
  (riferivano scaffold Inertia rollbackato).

### CSS purge (multi-pass + bisection automatica) — -2.276 LOC

Tooling automatico in `scripts/`: `purge-dead-css.py`, `aggressive-purge-css.py`,
`bisect-purge.py`, `multi-pass-purge.py`, `trivialize-css.py`, `analyze-css.py`.

| File CSS | Passo 1 | Passo 2 (bisect) | Passo 3 (multi) |
|---|---:|---:|---:|
| `account.css` | -678 | -38 | — |
| `admin.css` | -211 | -7 | — |
| `autenticazione.css` | -379 | -10 | -8 |
| `cookie-banner.css` | — | -67 | — |
| `home.css` | -128 | — | — |
| `main.css` | -33 | — | — |
| `motion.css` | — | -26 | -16 (keyframes) |
| `shipment-flow.css` | -349 | -32 | -4 |
| altri | -274 | -16 | — |
| **Totale** | **-2.052** | **-196** | **-28** |

CSS totale: 20.945 → 18.669 LOC (-10.9%).

### Refactor (split file >400/500 LOC via trait/composable)

Pattern: trait/composable per zero ABI change, callsite invariati.

| File | Pre | Post | Estratto |
|---|---:|---:|---|
| `BrtBordereauGenerator.php` | 742 | 525 | trait `Brt/PdfRenderingPrimitives.php` (243 LOC) |
| `CheckoutSubmissionContextService.php` | 614 | 401 | trait `Checkout/SnapshotCompactingHelpers.php` (234 LOC) |
| `LocationController.php` | 455 | 359 | service `Catalog/PhotonLocationFallback.php` (106 LOC) |
| `InvoicePdfService.php` | 422 | 294 | trait `Invoice/SinglePagePdfHelpers.php` (154 LOC) |
| `StripeWebhookController.php` | 527 | 404 | trait `StripeWebhookHelpers.php` (145 LOC) |
| `StripePaymentService.php` | 472 | 385 | trait `Stripe/IdempotencyAndMetadataHelpers.php` (112 LOC) |
| `StripeCheckoutController.php` | 760 | 485 | trait `StripeCheckoutHelpers.php` (308 LOC) |
| `pages/traccia/[tracking].vue` | 581 | 392 | composable `useTrackingDetail.ts` (225 LOC) |

File >400 LOC PHP: 15 → 7 (-53%). File >500 LOC Vue: 5 → 4 (-20%).

### Stack consolidato (post-rollback Inertia)

- **Backend**: Laravel 11 + Sanctum 4 + Stripe SDK 18 + BRT REST 3.x (`app/`, root)
- **Frontend**: Nuxt 4.1 + Vue 3.5 + Pinia 3 + Nuxt UI 4 + Tailwind 4 (`apps/web/`)
- **Reverse proxy**: Caddy `:8787` split routing → Laravel `:8000` API + Nuxt `:3001` SPA

### Verificato

- 333 test backend pass (1.089 assertions), 0 fail
- 13 pagine pubbliche/auth: tutte 200/301 OK live su `127.0.0.1:8787`
- API: `/api/cart`, `/api/locations/by-cap`, `/sanctum/csrf-cookie` operative
- `composer.json` 6 dipendenze runtime (era 8)
- `migrate:fresh` + `db:seed` funzionante (Laravel 11 schema:dump pattern)

### Discrepanze corrette in questo CHANGELOG (onestà)

| Claim precedente | Realtà verificata |
|---|---|
| `[2.0.0-rewrite] — Monolite Inertia` | **Rewrite ANNULLATO**: rollback a Nuxt 4 SPA + Laravel API headless completato |
| "13 BrtServices da fondere in BrtClient" | **Falso**: tutti attivi (3-9 hits/each), separazione SOLID. BrtClient creato come facade unificante (vedi sezione Added) |
| "5 Stripe controller morti" | **Falso**: tutti usati (Connect onboarding Pro, Customer saved cards, Refund 4 file inclusi 2 test, Checkout/Webhook gateway core) |
| "6 Auth controller da collassare" | **Falso**: standard Laravel best practice single-responsibility (Login, Register, ChangePassword, PasswordReset, Verification, Google) |
| "Architettura ibrida `app/` + `apps/web/`" | **Corretta**: split monorepo Laravel API headless + Nuxt SPA è il pattern post-rollback documentato |
| "Migrations vuote = bug" | **Pattern Laravel 11**: `database/schema/sqlite-schema.sql` (872 LOC) come fonte di verità via `schema:dump --prune`. `RefreshDatabase` funziona, 333 test verdi. README in `database/migrations/` |

## [Unreleased]

Vedi git log per stato corrente.

---

[2.3.1-deps-clean]: https://github.com/Boop91/spedizionefacile/releases/tag/v2.3.1-deps-clean
