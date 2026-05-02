# ADR 006 — Service-Layer Architecture (Modular Monolith)

Data: 2026-05-02
Status: Accepted

## Contesto

SpediamoFacile è un'API headless Laravel + SPA Nuxt. Il backend è un **modular monolith**:
un solo deploy, ma codice organizzato in domini ortogonali (Auth, Cart, Catalog, Checkout,
Order, Shipping, Wallet, Communication, Gdpr, Admin). Servono regole esplicite per
dove va la business logic, dove le query database, dove gli helper, e quando si può
"barare" usando `DB::table()` invece di Eloquent.

## Decisione

Quattro layer, responsabilità nette:

| Layer | Cartella | Responsabilità | Limite LOC |
|---|---|---|---|
| **Controller** | `app/Http/Controllers/<Domain>/` | Validazione input HTTP, chiamata service, mapping output JSON | ≤ 200 |
| **Service** | `app/Services/<Domain>/` o `app/Services/` | Business logic, transazione, idempotency, side-effects | ≤ 400 |
| **Model** | `app/Models/` | Schema Eloquent + accessor/mutator + relazioni + scope. **Mai** business logic | n/a |
| **Support** | `app/Support/` | Helper puro statici riusabili (cookie, formatter, normalizer) | ≤ 200 |

### Esempi naming

- `Cart\CartItemController` (HTTP) chiama `CartService` (logica) chiama `Cart` (model).
- `Checkout\StripeCheckoutController` chiama `StripePaymentService` + `OrderCreationService`.
- `Auth\AuthUiCookie` (Support) emette/legge cookie sessione: nessuno stato, nessun DB.

### Regola DB::table() — quando si può barare

`DB::table()` aggira Eloquent. È **autorizzato solo** in 5 casi:

1. **Pivot pure senza modello dedicato**: `cart_user`, `package_order`, `saved_shipments`.
   Eloquent richiederebbe un modello pivot vuoto. `DB::table()` con `where`/`pluck`/`insert`
   è più chiaro e più veloce.
2. **Tabelle Laravel framework**: `password_reset_tokens`, `sessions`, `cache`, `jobs`,
   `cookie_consents`. Sono gestite da Laravel internals — wrapparle in modelli sarebbe
   ridondante.
3. **Bulk import / counter atomico**: `locations` (import GeoNames con `insert([...])` da
   100k righe), `invoice_counters` (counter atomico con `updateOrInsert` + `lockForUpdate`).
4. **Lock pessimistico esplicito**: `DB::table('users')->where('id', $userId)->lockForUpdate()->first()`
   per Stripe payment + wallet (evita double-spend). Un Eloquent `User::lockForUpdate()`
   funziona, ma `DB::table()` rende esplicito che è un pattern critico.
5. **Cleanup massivo / report aggregato**: comandi `CleanupOrders`, `SendAbandonedCartEmails`
   con `delete()` batch e aggregati `SUM/COUNT`.

Tutti gli altri accessi DB usano **Eloquent** (`Model::query()`, relazioni, scope).

### Regola helper duplicati

Quando un blocco di logica statica appare 3+ volte, estrai in `app/Support/`. Esempi:

- `App\Support\AuthUiCookie` — payload + emit + forget cookie sessione UI.

Se appare in un solo dominio, vive nel relativo service (`Cart/`, `Brt/`, ...).

## Motivazioni

- **Junior-friendly**: nuovo dev guarda `app/Services/` e capisce cosa fa il sistema in 5 minuti.
- **Testabile**: service iniettabili (`__construct(StripePaymentService $stripe)`) con mock facili.
- **Modular monolith**: cartelle = bounded context. Domani estrai un dominio in un microservizio
  spostando una cartella, senza rincorrere import ovunque.
- **Niente over-engineering**: no Repository pattern (Eloquent è già un Active Record), no UseCase
  layer (i Service fanno il loro lavoro), no DTO ovunque (FormRequest + array nativi bastano).

## File critici (>limite LOC, motivati)

Vivono sopra il budget perché toccarli senza E2E rompe pagamenti o stampa borderò.
Lista aggiornata post-refactor agency-grade (commit `a944261`):

| File | LOC | Perché intoccabile |
|---|---|---|
| `Checkout/StripeCheckoutController` | ~333 | Stripe-critical, vedi ADR 005. Già ridotto dal monolite originale (517) ma resta sopra budget per coerenza con webhook/idempotency. |
| `Checkout/StripeWebhookController` | ~113 | Stripe-critical, ora thin (handler-pattern per event-type) |
| `Brt/BrtBordereauGenerator` | ~250 | Già splittato. Resto è layout fisso PDF borderò BRT |
| `InvoicePdfService` | ~330 | Già ridotto. Counter atomico + render PDF contesto unico |
| `CartService` | ~267 | Già ridotto a facade. Sub-service in `Cart/` per item/totals/discount |

**Pattern post-refactor (Round 4 backend agency-grade):**

Tutti i controller domain (Cart, Order, Shipping, Gdpr, Catalog, Admin) sono ora **thin** (<200 LOC)
con dependency injection del rispettivo service:

| Controller | LOC pre | LOC post | Service estratto |
|---|---|---|---|
| `Cart/CartItemController` | 306 | 93 | `Cart/CartItemService` |
| `Order/OrderActionsController` | 265 | 131 | `Order/OrderActionsService` |
| `Order/OrderDetailController` | 416 | 70 | `Traits/HandlesOrderSubmissionContext` |
| `Catalog/PriceBandController` | 274 | 89 | `Pricing/PriceBandService` + `Requests/BulkUpdatePriceBandsRequest` |
| `Catalog/LocationController` | 364 | (split) | `Catalog/LocationLookupService` + 2 sub-controller |
| `Gdpr/GdprController` | 271 | 43 | `Gdpr/GdprService` |
| `Shipping/SavedShipmentController` | 269 | 82 | `Shipping/SavedShipmentService` |
| `Shipping/ShipmentExecutionController` | 299 | 161 | `Shipping/ShipmentExecutionService` (helper) |
| `Shipping/SessionDataController` | 362 | 167 | `Shipping/SessionDataService` |
| `Admin/OrderManagementController` | 256 | 92 | `Admin/OrderManagementService` |
| `Auth/GoogleController` | 371 | 161 | `Auth/GoogleOAuthService` |

## Conseguenze

- Nuovi service nascono `≤ 400 LOC`. Se crescono, si estraggono helper in sub-namespace
  (`Pricing/`, `Invoice/`, `Brt/`, `Stripe/`).
- Nuovi controller nascono `≤ 200 LOC`. Se crescono, business logic estratta in service.
- `DB::table()` nuovi richiedono giustificazione in commit message + aggiunta in `CLAUDE.md`.
- ADR 006 è la fonte verità — quando un dev junior chiede "dove metto questo metodo?",
  questo file risponde.

## Riferimenti

- `app/Services/` — 30+ service attivi (post Round 4 backend: +7 nuovi service per dominio)
- `app/Http/Controllers/` — 9 domini × ~3-5 controller, ora tutti thin <200 LOC
- `app/Http/Requests/` — FormRequest classes per validation centralizzata
- `app/Support/AuthUiCookie.php` — helper statico riusato
- ADR 002 — moneyphp cents (precisione monetaria)
- ADR 005 — Stripe Elements + idempotency
- Validato 2026 con: [Modular Monolith Laravel — Sevalla](https://sevalla.com/blog/building-modular-systems-laravel/), [Modularizing Monolith — Mateus Guimaraes](https://mateusguimaraes.com/posts/modularizing-the-monolith-a-real-world-experience), [Clean Architecture Laravel — Shazeedul](https://blog.shazeedul.dev/modular-monolith-with-clean-architecture)
