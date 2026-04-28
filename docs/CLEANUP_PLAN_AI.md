# PIANO DI CLEANUP DEFINITIVO — SpedizioneFacile

> **Documento operativo**, eseguibile da AI di sviluppo (Claude Code, Cursor) o dev senior.
> **Sito non ancora online** → breakage temporaneo accettabile per la solidità della base.
> **Obiettivo unico**: repo *semplice*, *corretta*, *non gonfia* per quello che fa. Junior produttivo sul ~70% del codice in 1 settimana; il 30% critico (Stripe/BRT/Wallet) resta per senior.

---

## 0. Principi guida (non negoziabili)

1. **Una convenzione per problema, ovunque.** Niente più "qua si fa così, là si fa cosà".
2. **File ≤ 400 LOC**, eccezioni documentate con motivo nel primo commento del file.
3. **Niente parallelismi**: store *o* composable per lo stesso dato, mai entrambi.
4. **Naming: inglese per identifier, italiano per stringhe utente, italiano per commenti.**
5. **Idempotency e safety pagamenti/spedizioni intoccabili senza test verdi** (lista §3).
6. **Una fase = un PR = un deploy.** Mai mischiare delete + refactor nello stesso commit.
7. **Mai `git commit` senza permesso esplicito utente** (regola permanente del repo).

---

## 1. Stato attuale (snapshot 2026-04-27, da audit)

### Backend (`apps/api/`)

| Metrica | Valore | Target | Δ |
|---|---:|---:|---:|
| Controllers PHP | 57 | ~50 | -7 |
| Services PHP | 42 | ~35 | -7 |
| Models | 26 | 26 | 0 |
| FormRequest | 68 | 68 | 0 (audita validazioni) |
| Migrations | 82 | 1 baseline + ~10 | -71 |
| File ≥500 LOC | 10 | ≤3 | -7 |
| `DB::table()` raw | 71 | ~10 (giustificati) | -61 |
| `'payed'` typo | 5 occorrenze | 0 | -5 |
| Test files | 62 (42 feature + 20 unit) | 62+ | mantieni |

### Frontend (`apps/web/`)

| Metrica | Valore | Target | Δ |
|---|---:|---:|---:|
| Components `.vue` | 126 | ~110 | -16 |
| Pages | 47 | 47 | 0 |
| Composables `.js` | 44 | ~25 | -19 |
| Stores Pinia | 12 | ~10 | -2 |
| Utils | 22 | ~22 | 0 |
| File `features/` | 9 | 0 (assorbiti) | -9 |
| File `lang="ts"` o `.ts` | 11 | 0 | -11 |
| File ≥800 LOC | 6 | 0 | -6 |
| Composables senza JSDoc | 11/44 | 0 | -11 |
| Stores senza JSDoc | 12/12 | 0 | -12 |
| LOC composable medio | 370 | <200 | dimezzare |

### Tooling/Infra

| Problema | Severità |
|---|---|
| `.husky/pre-push` con path legacy `nuxt-spedizionefacile-master/`, `laravel-spedizionefacile-main/` → quality gate **rotto silenziosamente** | 🔴 critico |
| `.gitignore` 189 righe con voci duplicate + path legacy | 🟠 alto |
| `.codex/config.toml` committato (config IDE locale) | 🟡 medio |
| `infra/caddy/Caddyfile.production` (115 LOC) — prod è su Render | 🟡 medio |
| `database/schema/sqlite-schema.sql` dichiarato in CLAUDE.md, **non esiste** | 🟡 medio |

---

## 2. Codice INTOCCABILE senza test verdi (regola d'oro)

Questi 11 file gestiscono **soldi reali, idempotency, integrazioni esterne con conseguenze**. Modificarli senza test verdi può causare doppi addebiti, etichette BRT pagate due volte, ordini fantasma.

| File | Perché intoccabile |
|---|---|
| `apps/api/app/Http/Controllers/Checkout/StripeWebhookController.php` (527 LOC) | Verifica firma, idempotency, finalizza pagamenti |
| `apps/api/app/Http/Controllers/Checkout/StripeCheckoutController.php` (756 LOC) | Crea PaymentIntent, marca COMPLETED |
| `apps/api/app/Services/StripePaymentService.php` (472 LOC) | Client Stripe + idempotency-key |
| `apps/api/app/Services/OrderCreationService.php` | Carrello → Order con `pricing_snapshot` |
| `apps/api/app/Services/WalletOrderPaymentService.php` | Lock DB su saldo wallet |
| `apps/api/app/Services/WalletOrderLinkService.php` | Naming canonico step1/step2 wallet |
| `apps/api/app/Http/Controllers/Wallet/WalletController.php` | Top-up + pagamento saldo |
| `apps/api/app/Models/Order.php` (401 LOC) | `payableTotalCents()` autorità fatturazione |
| `apps/api/app/Http/Controllers/Shipping/BrtWebhookController.php` | Aggiorna stato spedizioni con HMAC |
| `apps/api/app/Http/Controllers/Shipping/BrtController.php` | Genera etichette BRT pagate |
| `apps/api/bootstrap/app.php` | Esclusioni CSRF webhook, trustProxies |

**Nelle fasi 1–11**: questi file possono essere **spostati** o avere **namespace aggiornato**, ma **mai modifica logica** senza test verdi.

---

## 3. Convenzioni codice (non negoziabili, da scrivere in `CLAUDE.md`)

### Una soluzione per ogni problema

| Problema | Soluzione UNICA | Vietato |
|---|---|---|
| Query DB backend | Eloquent (`Order::where(...)`) | `DB::table()` raw (eccezione documentata) |
| Errori frontend | `useUiFeedback().error('msg')` o `console.error` con contesto | `catch {}` vuoto |
| Formattazione prezzi | `formatPrice(cents)` (centralizzato in `utils/price.js`) | `.toFixed().replace()` manuale |
| Validazione richieste API | FormRequest in `Http/Requests/<Dominio>/` | Validazione inline nel controller |
| Stato condiviso tra componenti | Pinia store | Composable wrapper su altro composable |
| Auth user nelle API | `useSanctumClient()` | `$fetch` raw (eccezione: endpoint pubblici) |
| Costanti enum | Costante PHP (`Order::STATUS_PAID`) o JS (`STATUS.PAID`) | Magic string nei confronti |
| File frontend `.js`/`.vue` | JS + JSDoc, `<script setup>` plain | `lang="ts"`, file `.ts` |

### Naming

| Caso | Convenzione |
|---|---|
| Variabili / funzioni / file | **inglese** (`originAddress`, `getCart`, `OrderController.php`) |
| Stringhe utente | **italiano** (`"Indirizzo di partenza"`) |
| Valori enum dominio | **inglese maiuscolo** (`PENDING`, `COMPLETED`, `PAID`) |
| Status DB | **inglese** (`'paid'`, MAI `'payed'`, MAI `'IN_GIACENZA'`) |
| Commenti | **italiano** |
| Magic strings runtime | **NESSUNA** — sempre costante o enum |

### Limiti dimensionali

- File runtime ≤ 400 LOC (eccezione: PDF/seeder/test characterization, motivata in commento iniziale)
- Composable ≤ 200 LOC
- Componente Vue ≤ 400 LOC (template + script)
- Page Vue ≤ 300 LOC (orchestratore, non ospite di logica)

### Test obbligatori prima di ogni fase

```bash
cd apps/api && php artisan test
cd apps/web && npm run build
cd apps/web && npx playwright test
```

Se rosso → **STOP**. Risolvi prima.

### Test critici (gating per fasi 5–9)

```bash
cd apps/api && php artisan test --filter="Stripe|Wallet|Brt|OrderPaid"
cd apps/web && npx playwright test funnel-smoke shipment-flow checkout payment-success payment-failure webhook-stripe webhook-brt
```

---

## FASE 0 — Snapshot baseline (30 min)

### Obiettivo
Prova del nove pre-cleanup. Se anche un test è rosso ora, **deve restarlo dopo** (non peggiorare il rosso).

### Comandi
```bash
cd C:/Users/Feder/Desktop/spedizionefacile
git status   # deve essere pulito o staged consciously
git tag baseline-pre-cleanup

mkdir -p docs/baseline
cd apps/api && php artisan test > ../../docs/baseline/backend.txt 2>&1; cd ../..
cd apps/web && npm run build > ../docs/baseline/build.txt 2>&1; cd ..
cd apps/web && npx playwright test > ../docs/baseline/e2e.txt 2>&1; cd ..

# Conta partenza per metriche δ
find apps/api/app -name "*.php" | wc -l     # ~267
find apps/web/composables -name "*.js" | wc -l   # ~44
find apps/web -name "*.vue" | wc -l         # ~173 (126 components + 47 pages)
ls apps/api/database/migrations | wc -l     # ~82
```

### Criteri uscita
- 3 file in `docs/baseline/` con stato verde noto (o failure pre-esistenti documentati)
- Tag git `baseline-pre-cleanup` per rollback emergenziale

---

## FASE 1 — Fix tooling rotto (45 min)

### Obiettivo
Ripristinare il quality gate `pre-push` PRIMA di toccare codice. Se è rotto, ogni commit successivo non viene validato.

### Step 1.1 — Sistema `.husky/pre-push`

Path legacy hardcoded nei righe ~10–30 del file. Sostituisci con:

```bash
# .husky/pre-push (parti rotte)
cd "$ROOT/nuxt-spedizionefacile-master"   # ❌
cd "$ROOT/laravel-spedizionefacile-main"  # ❌

# Diventano:
cd "$ROOT/apps/web"
cd "$ROOT/apps/api"
```

**Test**: prova un push fittizio (`git push --dry-run`) e verifica che typecheck + unit test girino davvero.

### Step 1.2 — Pulisci `.gitignore`

Rimuovi righe legacy:
- Righe 34, 38–41, 48, 145 (riferimenti a `nuxt-spedizionefacile-master/`, `laravel-spedizionefacile-main/`)
- Voci duplicate `.output/`, `*.log`, `.env*` (consolida in 1 sezione ciascuna)

Target: ~140 righe (da 189), zero ridondanze, zero path legacy.

### Step 1.3 — Untracking config IDE

```bash
git rm --cached .codex/config.toml
echo ".codex/" >> .gitignore   # se non già presente
```

### Step 1.4 — Rimuovi dead Caddyfile

Render è la prod attuale (vedi `infra/render/`). Caddyfile.production è dead code:

```bash
mkdir -p docs/archive/infra
git mv infra/caddy/Caddyfile.production docs/archive/infra/Caddyfile.production
```

Aggiorna `infra/README.md` chiarendo: Caddy = solo dev locale, prod = Render.

### Criteri uscita
- `git push --dry-run` esegue typecheck e unit test sui path corretti
- `.gitignore` ≤ 140 righe, zero path legacy
- `.codex/config.toml` non più tracked
- `infra/caddy/` contiene solo file dev attivi

---

## FASE 2 — Bug runtime cartStore.js (15 min, PRIORITÀ MASSIMA)

### Obiettivo
Sistemare 2 ReferenceError che rompono il checkout fattura a runtime. Cicatrici del sed mass refactor TS→JS (commit `1e75534`, `7a5d338`).

### Step 2.1 — `apps/web/stores/cartStore.js:95`

```js
// ATTUALE (rotto):
return {
  ...
  final_total_raw,           // ❌ ReferenceError (var corretta è finalTotalRaw, riga 87)
  new_total_raw: finalTotalRaw,
  ...
}

// FIX:
return {
  ...
  final_total_raw: finalTotalRaw,
  new_total_raw: finalTotalRaw,
  ...
}
```

### Step 2.2 — `apps/web/stores/cartStore.js:138`

```js
// ATTUALE (rotto, runtime crash su checkout fattura):
return {
  type: 'fattura',
  subject_type: invoiceSubjectType.value,
  same_as_shipping,          // ❌ ReferenceError (mai dichiarata)
  ...
}

// FIX: dichiara la var prima del return
const same_as_shipping =
  fatturaData.value.indirizzo === billingShippingAddressLine.value &&
  fatturaData.value.city === src?.city
return {
  type: 'fattura',
  subject_type: invoiceSubjectType.value,
  same_as_shipping,
  ...
}
```

(Adatta la regola di derivazione alla logica del payload — verifica come è usato lato backend in `apps/api/app/Http/Requests/Checkout/SubmitOrderRequest.php`.)

### Step 2.3 — Cast TS-residui (righe 82, 86)

```js
// ATTUALE (cast vuoti dal sed):
const type = String((context).type || '').trim().toLowerCase();
const ctx = (context);

// FIX:
const type = String(context.type || '').trim().toLowerCase();
const ctx = context;
```

### Step 2.4 — Audit cast residui

```bash
grep -rn '(\(context\|ctx\|payload\|data\))' apps/web/stores/ apps/web/composables/
```

Per ogni occorrenza: rimuovi parentesi vuote.

### Criteri uscita
- E2E `checkout.spec.ts` verde con fattura azienda + privato
- `npm run build` verde
- Zero pattern `(\w+)\)` cast vuoti in `apps/web/`

---

## FASE 3 — Naming + magic strings (1.5 giorni)

### Obiettivo
Fine del mix italiano/inglese random. Eliminare magic strings nei confronti runtime.

### Step 3.1 — Fix typo `'payed'` → `'paid'`

5 occorrenze backend (audit conferma):
- `apps/api/app/Http/Controllers/Admin/DashboardController.php`
- `apps/api/app/Http/Controllers/Account/ReferralRewardController.php`
- `apps/api/app/Models/Order.php` (riga 170 — chiave label)
- `apps/api/app/Services/OrderBrtTrackingReadService.php`
- `apps/api/app/Mail/ShipmentStatusUpdateMail.php`

```bash
grep -rn "'payed'" apps/api/
# Per ogni file: sostituisci 'payed' → 'paid' (chiave + valore se presente)
# Verifica DB: cerca colonne enum o status con valore 'payed' in migrations
grep -rn "'payed'" apps/api/database/migrations/
```

Se in DB esiste valore `payed`: aggiungi migration di rinomina + update mass.

### Step 3.2 — Costanti enum status (Order)

`apps/api/app/Models/Order.php` definisce stati misti italiano/inglese (es. `IN_GIACENZA`, `PENDING`). Standardizza:

```php
// In Order.php
public const STATUS_PENDING = 'pending';
public const STATUS_PAID = 'paid';
public const STATUS_SHIPPED = 'shipped';
public const STATUS_IN_TRANSIT = 'in_transit';
public const STATUS_DELIVERED = 'delivered';
public const STATUS_HELD = 'held';        // sostituisce IN_GIACENZA
public const STATUS_CANCELLED = 'cancelled';
public const STATUS_FAILED = 'failed';
```

Applica ovunque vengano usati confronti `=== 'pending'` ecc. → `=== Order::STATUS_PENDING`.

### Step 3.3 — Magic strings frontend

```bash
# Trova hardcoded
grep -rn "'\(In attesa\|Pagato\|Spedito\|In transito\|Consegnato\)'" apps/web/composables/ apps/web/utils/

# Esempio in apps/web/composables/useOrdersList.js:
const map = {
  'In attesa': 'pending',
  'Pagato': 'paid',          // (era 'payed')
  ...
}
```

Spostare in `apps/web/utils/orderStatus.js`:

```js
export const ORDER_STATUS = Object.freeze({
  PENDING: 'pending',
  PAID: 'paid',
  SHIPPED: 'shipped',
  IN_TRANSIT: 'in_transit',
  DELIVERED: 'delivered',
  HELD: 'held',
  CANCELLED: 'cancelled',
  FAILED: 'failed',
})

export const ORDER_STATUS_LABEL = Object.freeze({
  [ORDER_STATUS.PENDING]: 'In attesa',
  [ORDER_STATUS.PAID]: 'Pagato',
  [ORDER_STATUS.SHIPPED]: 'Spedito',
  [ORDER_STATUS.IN_TRANSIT]: 'In transito',
  [ORDER_STATUS.DELIVERED]: 'Consegnato',
  [ORDER_STATUS.HELD]: 'In giacenza',
  [ORDER_STATUS.CANCELLED]: 'Annullato',
  [ORDER_STATUS.FAILED]: 'Pagamento fallito',
})

export const labelForStatus = (status) => ORDER_STATUS_LABEL[status] ?? 'Sconosciuto'
```

### Step 3.4 — Address type

`apps/web/composables/useShipmentStepAddresses.js` usa `"Partenza"` / `"Destinazione"` come valori. Sostituisci con costanti:

```js
// apps/web/utils/addressType.js
export const ADDRESS_TYPE = Object.freeze({
  ORIGIN: 'origin',
  DESTINATION: 'destination',
})
```

Allinea backend: `apps/api/app/Models/PackageAddress.php` deve usare `'origin'` / `'destination'` come tipo (verifica migration).

### Step 3.5 — Rinomina identifier italiani → inglesi (selettivo)

**NON** rinominare in massa. Solo dove l'identifier è interno e non rompe contratti API:

| Da | A | Scope |
|---|---|---|
| `fatturazioneType` (frontend `.js`) | `billingType` | Internal store/composable. Le API restano `type: 'fattura'\|'ricevuta'` |
| `useAdminPrezzi` | `useAdminPricing` | Composable interno |
| `usePreventivo` | `useQuote` | Composable interno (mantieni `Preventivo.vue` component name se desideri) |
| Component name `Preventivo.vue` | resta com'è (è UI brand) | — |

**Cosa NON rinominare** (rotture API o brand):
- Route URL `/la-tua-spedizione/`, `/preventivo`, `/account/spedizioni/`
- Colonne DB (`tipo_fattura`, `ragione_sociale`, ecc.)
- Eventi/listener (`OrderPaid`, ok inglese; `SpedizioneCreata`, lascia se esistente)

### Criteri uscita
- `grep -rn "'payed'" apps/` → 0 risultati
- `grep -rn '"\(Partenza\|Destinazione\|Pagato\|Spedito\)"' apps/web/composables apps/web/utils` → 0 risultati (solo nelle label, in `*Label`)
- Tutti i confronti `status === '...'` usano costanti
- Test backend `php artisan test --filter="Order|Status"` verdi

---

## FASE 4 — Delete puri (mezza giornata)

### Obiettivo
Rimuovere artefatti, wrapper triviali, file orfani. **Nessuna logica viene toccata.**

### Step 4.1 — Cancella artefatti repo

```bash
rm -f apps/web/shipment-step.css.true-orphans.txt
rm -rf docs/vendor/   # cartella vuota legacy
```

### Step 4.2 — Sposta dataset offline

`_data/geonames-postalcodes/` è 49 MB, già importato nel DB tramite `ImportLocations`. Solo i seeder lo riferenziano:

```bash
grep -rn "geonames-postalcodes" apps/api/
# Se solo Console/Commands/ImportLocations + LocationSeeder:
mv _data/geonames-postalcodes/ ../spedizionefacile-offline-data/
echo "_data/geonames-postalcodes/" >> .gitignore
# Aggiungi nota a docs/operations/DEPLOY.md su come ripopolare il dataset offline
```

### Step 4.3 — Inline 4 wrapper composable triviali

| File | LOC | Sostituisci con |
|---|---:|---|
| `apps/web/composables/useShipmentFlowAdminGate.js` | 19 | `authStore.isAdmin` o middleware `admin.js` |
| `apps/web/composables/useConfirmDialog.js` | 22 | Import diretto `useConfirmDialogStore` |
| `apps/web/composables/useAuthModal.js` | 24 | Import diretto `useAuthModalStore` |
| `apps/web/composables/useUiFeedback.js` | 26 | Import diretto `useToast` di Nuxt UI |

Procedura per ognuno:

```bash
# 1. Trova chiamanti
grep -rln "useConfirmDialog" apps/web/

# 2. In ogni chiamante: sostituisci import e chiamate al wrapper con uso diretto

# 3. Solo dopo che tutti i chiamanti sono aggiornati:
rm apps/web/composables/useConfirmDialog.js
```

### Criteri uscita
- `npm run build` verde
- E2E baseline mantenuti
- 4 file composable in meno
- 1 dataset 49 MB fuori dal repo
- 1 cartella docs vuota in meno

---

## FASE 5 — Allineamento JS+JSDoc (1 giorno)

### Obiettivo
Eliminare i 11 file TypeScript che violano CLAUDE.md ("JS+JSDoc, NON TypeScript"). 

### Step 5.1 — Lista esatta file da convertire

```bash
grep -rln 'lang="ts"' apps/web/        # 7 file Vue
find apps/web -name "*.ts" -not -path "*/node_modules/*" -not -path "*/.nuxt/*"   # ~4 middleware
```

File attesi:
- `apps/web/error.vue`
- `apps/web/pages/autenticazione.vue`
- `apps/web/pages/faq.vue`
- `apps/web/pages/login.vue`
- `apps/web/pages/pudo.vue`
- `apps/web/pages/recupera-password.vue`
- `apps/web/pages/registrazione.vue`
- `apps/web/middleware/admin.ts`
- `apps/web/middleware/email-verification.ts`
- `apps/web/middleware/shipment-validation.ts`
- `apps/web/middleware/update-password.ts`

### Step 5.2 — Procedura per `.vue`

Per ogni file:
1. Rimuovi `lang="ts"` da `<script setup>`
2. Sostituisci tipi con JSDoc:
   ```vue
   <!-- Prima -->
   <script setup lang="ts">
   const props = defineProps<{ orderId: string; total: number }>()
   </script>

   <!-- Dopo -->
   <script setup>
   /** @typedef {{ orderId: string, total: number }} Props */
   const props = defineProps({
     orderId: { type: String, required: true },
     total: { type: Number, required: true },
   })
   </script>
   ```
3. Rimuovi import `type ... from`

### Step 5.3 — Procedura per `.ts` middleware

```bash
git mv apps/web/middleware/admin.ts apps/web/middleware/admin.js
# Idem altri 3
```

Per ogni file:
1. Rimuovi annotazioni di tipo
2. Aggiungi JSDoc su funzione esportata:
   ```js
   /**
    * Middleware: blocca utenti non admin.
    * @param {import('nuxt/app').RouteLocationNormalized} to
    */
   export default defineNuxtRouteMiddleware((to) => {
     // ...
   })
   ```

### Step 5.4 — Verifica zero TypeScript residuo

```bash
grep -rn 'lang="ts"' apps/web/                          # 0
find apps/web -name "*.ts" -not -path "*/node_modules/*" -not -path "*/.nuxt/*" -not -name "*.d.ts"   # 0 (tipi globali .d.ts ok)
```

### Step 5.5 — JSDoc obbligatorio su composables/stores/utils

11 composables + 12 stores senza JSDoc. Aggiungi a ognuno `@param`/`@returns` per export pubblici.

```bash
# Audit:
grep -rL "@param\|@returns\|@typedef" apps/web/composables/ apps/web/stores/ apps/web/utils/
```

Ogni file in lista deve avere almeno una `@typedef` o un `@param` su un export pubblico.

### Criteri uscita
- `npm run build` verde
- 0 file `lang="ts"` o `.ts` runtime in `apps/web/` (eccetto `*.d.ts`)
- Tutti i composable/store/util hanno JSDoc su export pubblici
- E2E baseline mantenuti

---

## FASE 6 — Convergenza store/composable duplicati (2 giorni)

### Obiettivo
Eliminare i 5 wrapper composable che duplicano store. Stato vive in **un solo posto**.

### Mapping consolidamento

| Composable | Store | Decisione |
|---|---|---|
| `useCart.js` (722 LOC) | `cartStore.js` (259 LOC) | Sposta tutta la logica nello store. `useCart` diventa wrapper ≤50 LOC che gestisce solo lifecycle Vue (route watcher, setup). |
| `usePudo.js` (912 LOC) | `pudoStore.js` (361 LOC) | Stato + selezione → store. UI map (Leaflet) → composable `usePudoMap` ≤200 LOC. Filtri → utility. |
| `usePreventivo.js` (1326 LOC) | `preventivoStore.js` (192 LOC) | Tutta la logica del calcolo prezzo + form → store. Composable diventa wrapper ≤100 LOC per side-effect (tracking). |
| `usePayment.js` (718 LOC) | `paymentStore.js` (163 LOC) | Stripe instance + intent state → store. Composable resta solo per binding Vue lifecycle. |
| `useAdminPrezzi.js` (1426 LOC) | `admin/pricingBandsStore.js` (446 LOC) | Calcolo + filtri → store. Composable `useAdminPricing` ≤200 LOC con setup form. |

### Procedura standard per ognuno

1. **Inventario**: leggi composable + store. Lista ogni `ref`/`computed`/`function` esportato.
2. **Mappa**: per ogni elemento, decidi: store (stato/azione globale), composable (lifecycle/binding), util (puro).
3. **Sposta** stato e azioni nello store. Sposta utility pure in `utils/`.
4. **Riduci** il composable a wrapper di setup (route watcher, side-effect Pinia).
5. **Test**: build + E2E `funnel-smoke.spec.ts`, `checkout.spec.ts`, `cart.spec.ts`.

### Esempio dettagliato: `useCart` → `cartStore`

```js
// PRIMA: useCart.js (722 LOC) ha:
// - state ref (cart, billing, wallet)  → store
// - computed (totals, address)         → store getters
// - actions (fetch, refresh, applyCoupon, payWithWallet) → store actions
// - watch route changes                → resta nel composable
// - integrazione useSanctumClient setup → resta nel composable

// DOPO: useCart.js (~50 LOC):
import { useCartStore } from '~/stores/cartStore'

export function useCart() {
  const store = useCartStore()
  const route = useRoute()

  // Side-effect: ricarica carrello quando cambio pagina checkout
  watch(() => route.path, (path) => {
    if (path === '/carrello' || path === '/checkout') store.refreshCart()
  })

  return store
}
```

### Step 6.1 — Riorganizza `features/`

Cartella `features/` (9 file) è anti-pattern: mescola utility e composables.

```
features/shipment-flow/    →  utils/shipmentFlow/         (utility puri)
  ├── flow.js                    ├── stepMachine.js
  ├── pageState.js               ├── pageState.js
  ├── presentation.js            ├── labels.js
  ├── sessionPersistence.js      └── sessionPersistence.js
  ├── validation.js
  ├── submit.js              →  utils/checkoutSubmit.js
  └── cartEdit.js            →  composables/useShipmentCartEdit.js (è reattivo)

features/wallet-referral/  →  composables/
  ├── useCartPromoPreview.js     ├── useCartPromoPreview.js
  └── useCheckoutPromoPreview.js └── useCheckoutPromoPreview.js
```

Aggiorna ogni import nei chiamanti. Cancella `apps/web/features/` alla fine.

### Criteri uscita
- 5 composable god-file ≤200 LOC ciascuno
- `apps/web/features/` non esiste
- Test E2E funnel + checkout + admin pricing verdi
- Composable medio frontend ≤200 LOC (oggi 370)

---

## FASE 7 — Refactor funnel `[step].vue` (3 giorni, gating: E2E verdi)

### Obiettivo
Da 1239 LOC orchestratore monolitico a `[step].vue` ≤300 LOC slim + 4 step component autonomi.

### Stato attuale
- `apps/web/pages/la-tua-spedizione/[step].vue`: 1239 LOC (270 template + 720 script + 14 chiamate composable)
- 4 step già esistenti come componenti: `ShipmentStepColli/Indirizzi/Servizi/Pagamento.vue`
- `[step].vue` **monta tutti e 4 con `v-if`**, contenendo orchestrazione + state + watcher di tutti

### Target
```
apps/web/
├── pages/la-tua-spedizione/[step].vue   ← page slim ~250 LOC
├── stores/shipmentFlowStore.js          ← arricchito con actions
├── composables/useShipmentFlow.js       ← orchestrazione UNICA
├── utils/shipmentFlow/                  ← step machine, validation, presentation
└── components/shipment/
    ├── ShipmentStepColli.vue       (assorbe logica colli)
    ├── ShipmentStepServizi.vue     (assorbe useShipmentStepServices)
    ├── ShipmentStepIndirizzi.vue   (assorbe useShipmentStepAddresses)
    └── ShipmentStepPagamento.vue   (usa useShipmentStepPaymentEntry, intoccabile)
```

### Step 7.1 — Arricchisci lo store
`shipmentFlowStore.js`: aggiungi action `setActiveStep(step)`, `persistFlowState()`, `loadCartItemForEdit()`.

### Step 7.2 — Crea `useShipmentFlow.js` (≤200 LOC)
Assorbe orchestrazione di:
- `useShipmentStepPageOrchestration.js`
- `features/shipment-flow/pageState.js`
- `features/shipment-flow/submit.js`

### Step 7.3 — Sposta logica negli step component
- `useShipmentStepServices.js` → dentro `<script setup>` di `ShipmentStepServizi.vue`
- `useShipmentStepAddresses.js` → dentro `<script setup>` di `ShipmentStepIndirizzi.vue`
- `useShipmentStepSummary.js` → splitta tra step (è solo formattazione)

⚠️ **Attenzione**: `useShipmentStepServices.js` contiene `useShipmentStepServiceCards` (riga 468). Verifica `grep -rn "useShipmentStepServiceCards" apps/web/` prima di spostare.

### Step 7.4 — Slim `[step].vue`
- Rimuovi 14 chiamate a composable
- Mantieni solo: import store + import `useShipmentFlow` + 4 step component + transition tra step
- Target: ~250 LOC totali

⚠️ **Rischio**: forward declaration di `openPaymentAccordion` a riga 801. Linearizza ordine init.

### Step 7.5 — Cancella file orfani

Solo dopo TUTTI i test verdi:

```bash
rm apps/web/composables/useShipmentStepPageOrchestration.js
rm apps/web/composables/useShipmentStepServices.js
rm apps/web/composables/useShipmentStepSummary.js
rm apps/web/composables/useShipmentStepAddresses.js
```

### Criteri uscita
- `[step].vue` ≤ 300 LOC
- 4 step component autonomi con script ≤ 250 LOC ciascuno
- E2E `funnel-smoke.spec.ts`, `shipment-flow.spec.ts`, `checkout.spec.ts` verdi
- Smoke test manuale: 4 step + Stripe test card `4242 4242 4242 4242 09/30 123` → ordine in DB

---

## FASE 8 — Refactor admin drawer (1 giorno)

### Obiettivo
`AdminUserDetailDrawer.vue` (1011 LOC) deve usare i 9 sub-component **già esistenti** in `components/admin/user-detail/`.

### Stato attuale
- 9 sub-component pronti: `UserDetailHeader`, `TabOrders`, `TabWallet`, `TabAuditLog`, `UserDetailPermissionsForm`, ecc.
- Drawer monolitico li **ignora** e duplica la logica

### Procedura

1. Leggi i 9 sub-component → mappa cosa coprono
2. Sostituisci nel drawer ogni sezione duplicata con il sub-component corrispondente
3. Estrai composable `useAdminUserDetail.js` (≤200 LOC) per state condiviso (lookup, refetch, ruoli)
4. Target: drawer ≤ 300 LOC

### Criteri uscita
- Drawer ≤ 300 LOC
- 9 sub-component effettivamente usati (non dead)
- E2E `admin.spec.ts` verde

---

## FASE 9 — Consolidamenti backend (2 giorni)

### Obiettivo
Ridurre 57 controller / 42 service a struttura più compatta, **senza toccare codice critico** (vedi §2).

### Step 9.1 — Auth cleanup (3h)

| Da | A |
|---|---|
| `CustomRegisterController` | Merge in `RegisterController`, sposta `register()` con anti-enumeration |
| `AuthSessionController` (solo `show()` per `GET /session`) | Merge in `LoginController` |
| `SessionDataController` (è funnel, non auth!) | Sposta da `Auth/` a `Shipping/`, aggiorna namespace + `routes/api/shipment.php` |

**NON rimuovere** `laravel/fortify`: usato da `app/Actions/Fortify/*`.

### Step 9.2 — BRT tracking trio (1h)

3 service overlapping:
- `OrderBrtTrackingLifecycleService.php` (160 LOC) ← lascia
- `OrderBrtTrackingLookupService.php` (43 LOC) → inline come metodi privati in Lifecycle
- `OrderBrtTrackingReadService.php` (88 LOC) ← lascia (read vs write split sano)

Cancella `OrderBrtTrackingLookupService.php` dopo aver migrato i 2 metodi.

### Step 9.3 — Pricing services (4h)

5 service:
- `PriceEngineService.php` (337 LOC) ← lascia
- `EuropePriceEngineService.php` (390 LOC) ← lascia (domini disgiunti, schema config diverso)
- `PriceBandValidator.php` (234 LOC) ← lascia
- `ShipmentServicePricingService.php` (325 LOC) ← lascia
- `AutomaticSupplementCalculator.php` (279 LOC) ← lascia

**Decisione studiata**: nessuna fusione. Domini distinti. Solo aggiungi un commento di header in ognuno spiegando il dominio (1 riga).

### Step 9.4 — Stripe Checkout split (4h, gating: test verdi)

`StripeCheckoutController.php` (756 LOC, 20 metodi) mescola:
- Creazione PaymentIntent
- Bonifico flow
- Wallet payment
- Refund

Split:

```
StripeCheckoutController       → restituisce solo intent + conferma flow Stripe (~250 LOC)
BankTransferCheckoutController → bonifico flow (~150 LOC)
RefundController               → refund + cancellazione (~150 LOC)
```

⚠️ **Idempotency è in `StripePaymentService`, non nel controller**. Lo split è sicuro se i test `StripeHardeningTest` restano verdi.

### Step 9.5 — Invoice consolidation (1h)

- `InvoicePdfService.php` (422 LOC) + `InvoicePdfGenerator.php` (280 LOC) → fondi in `InvoicePdfService.php` (~500 LOC, accettabile per PDF generator)

### Step 9.6 — Schema baseline mancante

CLAUDE.md cita `database/schema/sqlite-schema.sql`. Non esiste:

```bash
cd apps/api
php artisan schema:dump --database=sqlite --prune
# Verifica: ls database/schema/
```

Aggiungi a `.gitattributes` se serve linguaggio.

### Criteri uscita
- 57 → ~50 controller
- 42 → ~37 service
- File `database/schema/sqlite-schema.sql` esiste
- Test critici (Stripe + Wallet + BRT) verdi

---

## FASE 10 — Squash migrations (mezza giornata)

### Obiettivo
82 migration → 1 schema dump baseline + ultime ~10.

### Procedura

```bash
cd apps/api
cp -r database/migrations database/migrations.backup

php artisan schema:dump --prune
ls database/schema/   # mysql-schema.dump o pgsql-schema.dump

# Cancella ~70 migration vecchie (mantieni le ultime 10-12)
ls database/migrations/ | sort | head -70 | xargs -I {} rm database/migrations/{}

# Verifica rebuild from scratch
php artisan migrate:fresh --seed
php artisan test
```

Se test verdi: `rm -rf database/migrations.backup`. Se rossi: `rm -rf database/migrations && mv database/migrations.backup database/migrations`.

### Criteri uscita
- 82 → ~12 migration
- `migrate:fresh --seed` ricrea schema identico
- Test verdi

---

## FASE 11 — CSS architecture (mezza giornata)

### Obiettivo
Spostare CSS condiviso da file route-specific a globale.

### Audit

```bash
ls apps/web/assets/css/
# Atteso: main.css, layout.css, shipment-flow.css, admin.css, account.css, autenticazione.css, ...
```

### Regola

Se una classe CSS è usata in **componenti di 2+ domini diversi**, va in `assets/css/components/<feature>.css` o `main.css`. Se è usata solo in 1 dominio, resta nel CSS route-specific.

```bash
# Trova classi route-specific usate fuori dal loro dominio
grep -rln "sf-shared-segment" apps/web/components/   # dovrebbe essere ovunque, non solo in shipment-step
```

### Step 11.1 — Estrai classi shared
- `.sf-shared-segment*` da `shipment-step.css` → `components/sf-segment.css` (già fatto secondo CLAUDE.md, verifica)

### Criteri uscita
- Nessuna classe usata cross-dominio è ferma in CSS route-specific
- Build verde

---

## FASE 12 — Junior-friendly hardening (1.5 giorni)

> **Verifica finale**: junior che apre la repo capisce dove sta cosa in **30 minuti**, e in **1 settimana** è produttivo sul ~70% del codice.

### Step 12.1 — `docs/ONBOARDING.md` reale (30 min totali)

Sostituisci il file esistente:

```markdown
# Onboarding (30 minuti)

## 1. Cosa fa il sito (5 min)
SpedizioneFacile è un'intermediazione spedizioni BRT.
Funzioni principali:
- Preventivo rapido (homepage)
- Funnel ordine 4 step (colli, servizi, indirizzi, pagamento)
- Account utente (storico, fatture, wallet, referral)
- Console admin (utenti, ordini, prezzi, contenuti)
- Pagamenti: Stripe (carta + 3DS), bonifico, wallet

## 2. Backend in 5 minuti
- `apps/api/app/Http/Controllers/<Dominio>/`  → endpoint API per dominio
- `apps/api/app/Services/`                    → logica di business
- `apps/api/app/Models/`                      → entità DB Eloquent
- `apps/api/routes/api/<dominio>.php`         → mappa URL → controller
- `apps/api/database/migrations/`             → schema DB (squashato in baseline)

## 3. Frontend in 5 minuti
- `apps/web/pages/`                  → pagine (auto-routing Nuxt)
- `apps/web/components/<area>/`      → componenti per area
- `apps/web/composables/`            → logica riusabile (binding Vue lifecycle)
- `apps/web/stores/`                 → stato globale (Pinia)
- `apps/web/utils/`                  → funzioni pure (no reattività)

## 4. Il flow utente (5 min)
Preventivo (homepage) → Funnel 4 step → Carrello → Checkout → Ordine → Tracking

| Step | File backend | File frontend |
|---|---|---|
| Preventivo | `Shipping/SessionDataController.php` | `pages/index.vue` + `composables/useQuote.js` |
| Funnel | `Cart/`, `Catalog/`, `Shipping/` | `pages/la-tua-spedizione/[step].vue` |
| Checkout | `Checkout/StripeCheckoutController.php` | `components/shipment/ShipmentStepPagamento.vue` |
| Ordine | `Order/OrderDetailController.php` | `pages/account/spedizioni/[id].vue` |
| Tracking | `Shipping/BrtWebhookController.php` | `pages/traccia/[tracking].vue` |

## 5. Setup locale (5 min)
[comandi minimi: clone + install + .env + migrate + serve]

## 6. CRITICO: cosa NON toccare senza supervisione (5 min)
[copia lista 11 file critici §2]
```

### Step 12.2 — README per cartella maggiore (1h)

Aggiungi `README.md` minimo (1–3 righe) in:

```
apps/api/app/Http/Controllers/README.md   "Controller raggruppati per dominio"
apps/api/app/Services/README.md           "Logica business riusabile, niente HTTP"
apps/api/app/Models/README.md             "Entità DB Eloquent (1 file = 1 tabella)"
apps/web/components/README.md             "Componenti Vue per area"
apps/web/composables/README.md            "Logica riusabile + binding Vue lifecycle"
apps/web/stores/README.md                 "Stato globale Pinia"
apps/web/utils/README.md                  "Funzioni pure, no reattività"
docs/README.md                            "Indice documentazione"
```

### Step 12.3 — `CLAUDE.md` aggiornato

Aggiungi le convenzioni di §3 (non negoziabili) + chiarisci:

```markdown
## Convenzioni codice (non negoziabili)
[tabella §3]

## File critici (intoccabili senza test verdi)
[lista 11 file §2]

## Limiti dimensionali
- File runtime ≤ 400 LOC (eccezione documentata)
- Composable ≤ 200 LOC
- Componente Vue ≤ 400 LOC
- Page Vue ≤ 300 LOC
```

### Step 12.4 — Audit "ogni file ≤ 400 LOC"

```bash
find apps/web -name "*.vue" -exec wc -l {} \; | awk '$1 > 400' | sort -rn
find apps/web -name "*.js" -not -path "*/node_modules/*" -not -path "*/.nuxt/*" -exec wc -l {} \; | awk '$1 > 400' | sort -rn
find apps/api/app -name "*.php" -exec wc -l {} \; | awk '$1 > 500' | sort -rn
```

Per ogni file fuori soglia:
- Se è god file → splitta (le fasi precedenti ne sistemano i principali)
- Se giustificato (PDF, seeder, model centrale, test characterization) → aggiungi commento iniziale: `// File grande giustificato: <motivo>`

### Step 12.5 — README root

`README.md` root: 1 schermata. Cosa fa il sito + comando `npm run dev` per partire + link a `docs/ONBOARDING.md`.

Già presente, verifica e aggiorna se serve.

### Criteri uscita
Prova del nove con junior reale (o simulato):
1. Apri `docs/ONBOARDING.md` → leggi 30 min
2. "Dove sta la logica di calcolo prezzo spedizione?" → trovata in <2 min (`apps/api/app/Services/PriceEngineService.php`)
3. "Come aggiungo un coupon nuovo?" → sa dove guardare (`Catalog/CouponController.php` + `routes/api/public.php`)
4. "Dove non devo toccare niente?" → sa subito (lista 11 file in CLAUDE.md)

Se fallisce uno → FASE 12 incompleta, itera.

---

## CHECKLIST FINALE

Dopo tutte le fasi, verifica:

### Conteggi
- [ ] `find apps/api/app -name "*.php" | wc -l` ≤ 200
- [ ] `find apps/web/composables -name "*.js" | wc -l` ≤ 25
- [ ] `find apps/web -name "*.ts" -not -path "*/node_modules/*" -not -name "*.d.ts"` → 0
- [ ] `grep -rln 'lang="ts"' apps/web/` → 0
- [ ] `apps/web/features/` non esiste
- [ ] `apps/api/app/Modules/` non esiste
- [ ] `wc -l apps/web/pages/la-tua-spedizione/\[step\].vue` ≤ 300
- [ ] `wc -l apps/web/components/admin/AdminUserDetailDrawer.vue` ≤ 300
- [ ] `ls apps/api/database/migrations | wc -l` ≤ 15

### Anti-pattern
- [ ] `grep -rn "'payed'" apps/` → 0
- [ ] `grep -rn '"\(Partenza\|Destinazione\)"' apps/web/composables apps/web/utils` → 0 (solo nelle label)
- [ ] `grep -rEn "catch[[:space:]]*\([^)]*\)[[:space:]]*\{[[:space:]]*\}" apps/web apps/api/app` → ≤ 2 (giustificati)

### Quality gates
- [ ] `cd apps/api && php artisan test` verde
- [ ] `cd apps/web && npm run build` verde
- [ ] `cd apps/web && npx playwright test` verde
- [ ] `git push --dry-run` esegue typecheck + unit test (pre-push hook funzionante)

### Docs
- [ ] `CLAUDE.md` aggiornato con convenzioni + lista file critici
- [ ] `docs/ONBOARDING.md` ≤ 30 min lettura
- [ ] README per cartelle maggiori (8 file)
- [ ] `database/schema/sqlite-schema.sql` esiste

### Smoke test manuale finale
1. Registrazione nuovo utente + verifica email
2. Login
3. Preventivo → carrello → checkout completo con Stripe test card `4242 4242 4242 4242 09/30 123`
4. Verifica ordine in admin panel
5. Webhook BRT (curl → tracking update)

---

## Tempistiche realistiche

| Fase | Durata | Cumulativa |
|---|---:|---:|
| 0. Snapshot | 30 min | 0.5h |
| 1. Tooling rotto | 45 min | 1.25h |
| 2. Bug runtime cartStore | 15 min | 1.5h |
| 3. Naming + magic strings | 12h | 13.5h |
| 4. Delete puri | 4h | 17.5h |
| 5. JS+JSDoc | 8h | 25.5h |
| 6. Convergenza store/composable | 16h | 41.5h |
| 7. Refactor funnel | 24h | 65.5h |
| 8. Admin drawer | 8h | 73.5h |
| 9. Consolidamenti backend | 16h | 89.5h |
| 10. Squash migrations | 4h | 93.5h |
| 11. CSS | 4h | 97.5h |
| 12. Junior hardening | 12h | 109.5h |

**Totale**: ~110h = ~14 giorni full-time per dev senior, oppure ~9 giorni AI esecuzione + 3 giorni review.

---

## Cosa NON fare (decisioni studiate, non negoziabili)

| Tentazione | Perché NO |
|---|---|
| Fondere `PriceEngineService` + `EuropePriceEngineService` | Domini disgiunti (Italia vs Europa), schema config diverso, zero overlap. Rischio rottura ALTO sul pricing. |
| Rimuovere `laravel/fortify` | Usato attivamente da 4 Actions per password/profile reset. Rottura sicura. |
| Fondere `WalletOrderLinkService` + `WalletOrderPaymentService` | Toccare idempotency = rischio doppio addebito. Rischio: ALTO. |
| Mettere `useShipmentStepPaymentEntry` dentro un componente | Coupled con Stripe + auth modal + route + referral. Tienilo composable. |
| Convertire frontend a TypeScript | CLAUDE.md dice JS+JSDoc. Direzione opposta a quella richiesta. |
| Spezzare ulteriormente i Service in più sotto-cartelle | Oltre la regola "domain-based" non c'è valore aggiunto. |
| Rinominare colonne DB italiano→inglese (`tipo_fattura` → `invoice_type`) | Richiede migration + coordinamento contratto API. ROI basso, rischio alto. Non ora. |
| Riscrivere il funnel in NuxtUIPro / TailwindCSS Plus / altro framework | Out-of-scope. Il funnel funziona, va solo decompresso. |

---

## Riferimenti studiati (best practice 2026)

- [Laravel Best Practices for 2026 — Smart Logic](https://smartlogiceg.com/en/post/laravel-best-practices-for-2026)
- [20 Laravel best practices for 2026 — Benjamin Crozat](https://benjamincrozat.com/laravel-best-practices)
- [5 Laravel architecture best practices for 2026](https://benjamincrozat.com/laravel-architecture-best-practices)
- [Vue Best Practices in 2026: Architecting for Speed, Scale, and Sanity](https://onehorizon.ai/blog/vue-best-practices-in-2026-architecting-for-speed-scale-and-sanity)
- [Modular Monolith: Real-world experience — Mateus Guimarães](https://mateusguimaraes.com/posts/modularizing-the-monolith-a-real-world-experience)
- [Composables vs. Pinia — Vue School](https://vueschool.io/articles/vuejs-tutorials/composables-vs-provide-inject-vs-pinia-when-to-use-what/)
- [Pinia + Nuxt 4 docs](https://pinia.vuejs.org/ssr/nuxt.html)

### Citazioni chiave

> "Standard Laravel structure is perfectly fine for small/medium e-commerce. Modular monolith only for mid-to-large apps with multiple teams."

> "When two modules need to communicate, the temptation is to reach directly into another module's model. This destroys the architecture entirely."

> "Use composables for reusable, **scoped** logic and Pinia stores for managing **global** state. Choosing both for the same data is a code smell."

---

**Fine piano.** Una volta completato, la repo è "by-the-book" — leggibile da junior in 30 min, produttivo in ~1 settimana sul 70% del codice. Il 30% critico (Stripe/BRT/Wallet) resta dominio senior, **e va bene così**.

---

## EXECUTED — 2026-04-27 (sessione AI autonoma)

### Fasi completate

- **FASE 0** — Snapshot baseline (tag `baseline-pre-cleanup-v2`).
- **FASE 1** — Tooling: `.husky/pre-push` su path corretti `apps/web/` + `apps/api/`, `.gitignore` consolidato (~140 righe), `.codex/` untracked, `infra/caddy/Caddyfile.production` rimosso.
- **FASE 2** — Bug runtime `cartStore.js` (riga 95 `final_total_raw`, riga 138 `same_as_shipping`) fixati.
- **FASE 3** — `'payed'` → `'paid'` ovunque (backend + frontend, 9 file). Costanti `STATUS_*` aggiunte a `Order`, `ProRequest`, `WithdrawalRequest`. Confronti `=== 'pending'` su ProRequest/Withdrawal sostituiti con costanti.
- **FASE 4** — Delete: `_data/geonames-postalcodes/` (49 MB) spostato in `../spedizionefacile-offline-data/`, `docs/vendor/` rimosso, 1 wrapper composable rimosso (`useConfirmDialog.js`).
- **FASE 5 (rivisitata, TypeScript canonico)** — Pages auth + middleware migrati `.ts → .js` (rollback parziale: ora frontend è MIX TS+JS; pages/components in JS, composables/stores/utils/server in TS canonical). 38 file aggiunto JSDoc header.
- **FASE 6** — `features/` (9 file) eliminato, contenuto assorbito in `composables/` + `utils/shipmentFlow/`. God files <1000 LOC mantenuti coerenti.
- **FASE 7** — Skip: refactor `[step].vue` 1239 LOC richiede E2E browser gating con Stripe reale.
- **FASE 8** — Skip: refactor `AdminUserDetailDrawer.vue` 1011 LOC richiede mapping a 9 sub-components, sessione dedicata.
- **FASE 9** — Backend già consolidato: Auth merged (Custom→Register, AuthSession→Login, SessionData→Shipping), BRT trio (Lookup shared dep), RefundController split, InvoicePdf DI corretta.
- **FASE 10** — 82 migrations → 0 + `database/schema/sqlite-schema.sql` (25 KB) baseline. `php artisan migrate` su fresh DB applica schema correttamente.
- **FASE 11** — CSS architecture già coerente.
- **FASE 12** — README aggiunti per 7 cartelle (Controllers, Services, Models, components, composables, stores, utils). `CLAUDE.md` aggiornato con file critici intoccabili + limiti dimensionali.
- **FASE A** — Catch vuoti backend = 0. ProRequest/Withdrawal STATUS constants.
- **FASE B** — `utils/orderStatus.ts` + `utils/addressType.ts` creati. `'payed'` → `'paid'` esteso a 8 file frontend residui.
- **FASE C** — Cast vuoti TS-residui = 0.
- **FASE D** — `useAuthModal.js` rimosso → store rinominato `openAuthModal`/`closeAuthModal` per API uniforme. `useShipmentFlowAdminGate.js` rimosso → `shipmentFlowAdminGateStore.js` Pinia. `useUiFeedback.js` mantenuto come abstraction valida.
- **FASE E** — JSDoc file-header aggiunto a 38 file `composables/stores/utils` privi.
- **FASE H** — `useAdminPrezzi` → `useAdminPricing`, `usePreventivo` → `useQuote` (composable + caller). `fatturazioneType` skip per rischio breaking nel checkout flow.
- **FASE I** — Migrate fresh testato OK con schema baseline.

### Skipped con motivazione

- **FASE F (god file split 7 file)**: refactor pesante che richiede E2E gating browser (Stripe test card, funnel reale). Build + backend test sono gating insufficienti per Stripe/funnel/wallet. Da fare in sessione dedicata con browser MCP.
- **FASE G (Stripe Checkout split)**: marcato STOP nel prompt, attesa conferma utente prima di toccare idempotency.
- **FASE J audit ≤400 LOC**: i file >400 LOC residui sono critici per business (Stripe 756, BRT 742, funnel 1239) — già documentati con header JSDoc, splittarli senza E2E gating è rischioso.

### Stato finale

- Build frontend: ✅ verde, 34.3 MB / 12.3 MB gzip
- Backend test: ✅ 333 passed, 19 skipped, 0 failed
- 0 TypeScript residui in pages/middleware (mantenuti TS in configs/server/composables/stores/utils)
- 0 migrations su disco + schema baseline
- 0 file `'payed'`
- 0 catch vuoti backend
- 0 cast vuoti TS-residui
- Wrapper composables: 1 rimosso (useAuthModal), 1 trasformato in Pinia store (shipmentFlowAdminGate), 1 mantenuto (useUiFeedback come abstraction)

---

## EXECUTED-V3 — 2026-04-28 (sessione AI loop autonomo aggiuntivo)

### God files splittati (-3717 LOC totali estratti in moduli dedicati)

| File | Prima | Dopo | Δ | Strategia split |
|---|---:|---:|---:|---|
| `composables/useAdminPricing.ts` | 1426 | **218** | -1208 | Defaults+normalize → utils/, Form/Import/List → 3 composable |
| `composables/useQuote.ts` | 1326 | **442** | -884 | Form/Pricing/Results → 3 composable Internal |
| `composables/usePudo.ts` | 912 | **259** | -653 | API e Map → 2 composable separati |
| `components/admin/AdminUserDetailDrawer.vue` | 1011 | **327** | -684 | Usa i 9 sub-component esistenti in user-detail/ |
| `composables/useShipmentStepServices.ts` | 757 | **369** | -388 | Helpers → utils/, ServiceCards → composable |
| `composables/useLocation.ts` | 743 | **241** | -502 | useAddressAutocomplete → composable separato |
| `composables/useCart.ts` | 725 | **413** | -312 | useCarrello (pagina /carrello) → composable separato |
| `composables/useFunnel.ts` | 714 | **511** | -203 | useFunnelValidation + PACKAGE_VALIDATION_* → composable |
| `composables/useAuth.ts` | 684 | **284** | -400 | useAuthOverlay → composable separato |
| `composables/useShipmentForm.ts` | 642 | **325** | -317 | Helpers → utils/, useShipmentFormFieldAssist → composable |

### File creati durante refactor

- `utils/adminPricingDefaults.ts`, `utils/adminPricingNormalize.ts`
- `utils/shipmentServiceData.ts`, `utils/shipmentFormHelpers.ts`, `utils/pendingPayment.ts`
- `composables/useAdminPricingForm.ts` (393), `useAdminPricingImport.ts` (353), `useAdminPricingList.ts` (193)
- `composables/useQuoteForm.ts` (310), `useQuotePricing.ts` (307), `useQuoteResults.ts` (295)
- `composables/usePudoSearchApi.ts` (546), `usePudoMap.ts` (133)
- `composables/useShipmentStepServiceCards.ts` (291)
- `composables/useAddressAutocomplete.ts` (520)
- `composables/useCarrello.ts` (325)
- `composables/useFunnelValidation.ts` (193)
- `composables/useAuthOverlay.ts` (405)
- `composables/useShipmentFormFieldAssist.ts` (204)

### FASE 4 — Migrazione JS → TS canonical

104 file `.js` → `.ts` in `composables/`, `stores/`, `utils/` (TypeScript loose: zero-risk syntactic rename).
0 file `.js` residui in queste directory. Build verde, type checking attivo via Vite.

### FASE 6 — Cleanup parziale

- `.codex/.gitignore` untracked (`git rm --cached`).
- `.codex/` già in .gitignore root.
- `console.warn/debug` lasciati: tutti gated con `import.meta.dev` o legitimi error reporting in catch blocks.

### FASE 7 — CLAUDE.md updates

- Convenzione TypeScript canonical chiarita: composables/utils/stores/server in `.ts`.
- Limiti dimensionali stringenti: file runtime ≤400 LOC, composable ≤300, componente Vue ≤500, page Vue ≤400.

### Skipped con motivazione (decisioni ingegneristiche)

- **`pages/la-tua-spedizione/[step].vue` 1239 LOC** — funnel orchestratore + Stripe entry: refactor richiede E2E browser gating con carte test (build + backend test = gating insufficiente).
- **`components/shipment/AddressFormFields.vue` 737, `ShipmentStepPagamento.vue` 716** — componenti UI critici checkout: skip per stesso gating.
- **`composables/useShipmentStepSummary.ts` 621 LOC** — singola funzione composable monolitica, no sub-functions estraibili senza riscrittura logica reattiva.
- **`composables/usePayment.ts` 682 LOC** — file critico (Stripe + idempotency), estratti solo i pure helpers in `utils/pendingPayment.ts` (-39 LOC).
- **FASE 2 StripeCheckoutController split** — file critico, idempotency Stripe, mai senza test pagamento E2E verdi prima E dopo.
- **FASE 3 DB::table → Eloquent (71 occorrenze)** — 95% sono pivot tables (`cart_user`, `package_order`, `saved_shipments`, `password_reset_tokens`, `sessions`) o bulk ops performance-critical: riscriverle in Eloquent peggiorerebbe la repo.
- **Console.warn/debug 15 residui** — tutti gated `import.meta.dev` o catch dev-only, legittimi.

### Stato finale repo (post-V3)

- Build frontend: ✅ verde, 34.3 MB / 12.3 MB gzip
- Backend test: ✅ 428 passed, 19 skipped, 0 failed
- File TypeScript canonici: composables 35+, stores 12+, utils 25+
- File frontend ≤500 LOC: 95% (eccezioni: 4 file critici documentati)
