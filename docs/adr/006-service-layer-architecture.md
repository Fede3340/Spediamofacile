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

Vivono sopra il budget perché toccarli senza E2E rompe pagamenti o stampa borderò:

| File | LOC | Perché intoccabile |
|---|---|---|
| `Checkout/StripeCheckoutController` | 517 | Stripe-critical, vedi ADR 005 |
| `Checkout/StripeWebhookController` | 436 | Stripe-critical, vedi ADR 005 |
| `Brt/BrtBordereauGenerator` | 527 | Generatore PDF borderò BRT con layout fisso, splittarlo non aiuta lettura |
| `InvoicePdfService` | 424 | Generatore PDF fattura con counter atomico, contesto unico |
| `CartService` | 438 | Logica carrello con cleanup duplicati, pivot, surcharge — splittarlo introdurrebbe N service che si chiamano fra loro |
| `CheckoutSubmissionContextService` | 402 | Snapshot context checkout (Stripe-critical input building) |

## Conseguenze

- Nuovi service nascono `≤ 400 LOC`. Se crescono, si estraggono helper in sub-namespace
  (`Pricing/`, `Invoice/`, `Brt/`, `Stripe/`).
- Nuovi controller nascono `≤ 200 LOC`. Se crescono, business logic estratta in service.
- `DB::table()` nuovi richiedono giustificazione in commit message + aggiunta in `CLAUDE.md`.
- ADR 006 è la fonte verità — quando un dev junior chiede "dove metto questo metodo?",
  questo file risponde.

## Riferimenti

- `app/Services/` — 30 service attivi
- `app/Http/Controllers/` — 9 domini × ~3-5 controller
- `app/Support/AuthUiCookie.php` — helper statico riusato
- ADR 002 — moneyphp cents (precisione monetaria)
- ADR 005 — Stripe Elements + idempotency
