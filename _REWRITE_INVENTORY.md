# REWRITE V2 — Inventory baseline + audit realta

**Data**: 2026-04-28
**Branch**: `rewrite/v2-inertia-2026-04-28`
**Tag backup**: `backup/pre-rewrite-2026-04-28`
**DB snapshot**: `_data/snapshot_pre_rewrite.sql` (607.968 righe)

## Metriche correnti (verificate)

| Metrica | Valore |
|---|---|
| LOC totali frontend (vue+ts+js+css, no .nuxt/.output) | 87.794 |
| LOC backend (apps/api/app, no vendor) | 29.321 |
| Composables | 66 |
| Services backend | 42 |
| Controllers backend | 57 |
| Pages Nuxt | 47 |
| Components | 126 |
| File CSS | 16 (20.945 LOC) |

## Audit: piano REWRITE_PLAN vs realta del codice

Il piano REWRITE_PLAN.md dichiarava in fase 1 alcuni delete come "confermati"
ma audit `grep` su `apps/api/routes` + `apps/api/app` mostra che **non sono
zero-caller**. Eseguirli alla cieca romperebbe feature attive.

### Delete dichiarati nel piano: validazione

| File | Piano | Realta | Decisione |
|---|---|---|---|
| `Checkout/StripeConnectController.php` | DELETE -200 LOC | 1 caller (route `/stripe/connect`, `/callback`, `/create-account`) — feature Stripe Connect partner Pro | **NON DELETE** |
| `Checkout/StripeCustomerController.php` | DELETE -150 LOC | 2 caller (route `setup-intent`, `payment-methods`) — gestione carte salvate | **NON DELETE** |
| `Checkout/RefundController.php` | DELETE -180 LOC | 2 caller (route `refund-eligibility`, OrderDetailController doc reference) — flow rimborsi | **NON DELETE** |
| `Actions/Fortify/*` (5 file) | DELETE + composer remove laravel/fortify | `config/fortify.php` esiste, 4 actions referenced | **NON DELETE** (Fortify integrato) |
| `Listeners/*` keep solo 3 | DELETE -400 LOC | Ogni listener 1+ caller (EventServiceProvider) | **NON DELETE** (event-driven attivo) |
| `Mail/AbandonedCartReminderMail` + Command | DELETE -150 LOC | Command schedulato in `routes/console.php` ogni 6h (F15 active) | **NON DELETE** |
| `Services/OrderBrtTrackingLookupService.php` | (non in piano) | 0 caller verificati (solo README + vendor autoload) | **DELETE OK** -43 LOC |

**Bilancio reale Fase 1.2 piano**: -43 LOC (vs -1.380 LOC del piano = 3% del target).

### Fusione BrtServices (Step 1.3 piano: 11 file → 1 BrtClient -1.700 LOC)

Audit BRT services esistenti:

| Service | LOC | Logica | Caller |
|---|---|---|---|
| `BrtBordereauGenerator.php` | 742 | PDF bordereau distinta corriere | 4 |
| `ShipmentService.php` | 353 | createShipment + label gen | 6 |
| `AddressNormalizer.php` | 350 | Normalizzazione indirizzi BRT (sigle paesi, validazione CAP) | 8 |
| `BrtPayloadBuilder.php` | 274 | Build payload SOAP/REST per BRT | 5 |
| `TrackingService.php` | 228 | Polling tracking + webhook ingest | 5 |
| `PudoService.php` | 227 | Search PUDO + dettagli | 4 |
| `PickupService.php` | 216 | Prenotazione ritiro | 3 |
| `PudoPointMapper.php` | 194 | Mappatura PUDO → struttura UI | 3 |
| `FilialeLookup.php` | 106 | Lookup filiale BRT da CAP | 4 |
| `ErrorTranslator.php` | 92 | Traduzione errori BRT in italiano | 6 |
| `BrtConfig.php` | 77 | Config + credenziali BRT | 8 |

Totale: **2.859 LOC, distribuiti per responsabilita single-purpose**.

Il piano voleva fonderli in `BrtClient.php` di ~400 LOC (-2.400 LOC). Realistico?

**No**: i 2.859 LOC sono logica reale (PDF generation 742 + normalizzazione 350 + payload 274). Fonderli in 400 LOC significa **eliminare** logica, non spostarla. Il piano era ottimismo non supportato dal codice.

**Decisione**: mantengo separation. Eventuale consolidamento solo per service davvero piccoli (`BrtConfig` 77 + `ErrorTranslator` 92 + `FilialeLookup` 106 → potrebbero stare insieme = -50 LOC overhead).

### OrderBrtTracking* fusione (Step 1.4 piano: -600 LOC)

| Service | LOC | Caller |
|---|---|---|
| `OrderBrtFulfillmentService.php` | 156 | 3 |
| `OrderBrtTrackingLifecycleService.php` | 160 | 2 |
| `OrderBrtTrackingLookupService.php` | 43 | 0 |
| `OrderBrtTrackingReadService.php` | 88 | 2 |

Totale: 447 LOC. Piano voleva 250 LOC = -197 LOC. Possibile **se si elimina LookupService (orphan) e si fonde Lifecycle + Read** (logica correlata). Fulfillment resta separato (responsabilita diversa).

**Decisione**: delete LookupService (-43), valutare fusione Lifecycle+Read solo dopo grep approfondito callers.

### Composables shipment funnel (Step 1.5 piano: 16 file → 1 -3.500 LOC)

15 composable trovati, totale 3.785 LOC. Il piano dice "fondi tutti in `useShipmentFunnel.ts` ~600 LOC".

**Problema**: questi 15 composable sono **subordinati a `pages/la-tua-spedizione/[step].vue`** (1.239 LOC, FILE CRITICO INTOCCABILE per CLAUDE.md). Riscrivere il funnel composable richiede E2E gating Stripe + DB snapshot pre/post (vedi CLAUDE.md "Eccezioni documentate"). NON fattibile in sessione autonoma senza browser interattivo.

**Decisione**: rinvio a sessione manuale dedicata con Stripe test card. Mantengo eccezione formale gia documentata.

## Strategia operativa rivista

Il piano REWRITE_PLAN era **ottimismo non supportato dal codice**. Eseguirlo
alla cieca rompe 9 feature attive. Applico invece il **metodo scientifico**:

### Cosa esegue questa sessione

1. **Delete sicuri verificati**:
   - `OrderBrtTrackingLookupService.php` (43 LOC, 0 caller)
2. **Audit completo** (questo doc)
3. **NO delete distruttivi** del piano (Stripe Connect/Customer/Refund/Fortify/Listeners/AbandonedCart sono attivi)
4. **NO Inertia migration** in sessione autonoma (richiede 10 giorni AI con E2E gating per dominio, non fattibile senza browser interattivo continuo per le 47 page)
5. **NO CSS Tailwind conversion** (richiede tool meccanico + manual review per visual regression, non fattibile in 1 sessione)

### Cosa serve dall'utente per le fasi successive

- **Fase 2 Inertia migration**: sessione browser interattiva continua + E2E gating dominio per dominio. Stima realistica: 15-20 giorni AI con conferma utente per dominio.
- **Fase 3 CSS Tailwind**: sessione visual regression con preview Chrome dopo ogni file CSS convertito. Stima 5-7 giorni.
- **Fase 4 Stripe Checkout hosted**: cambia UX (utente esce dal sito → Stripe → torna). Decisione utente prima di procedere.

### Verita onesta sul punteggio

Il punteggio "100/100 EXECUTED-V4" del cleanup precedente era reale per le 9
dimensioni misurate (volume/struttura/leggibilita/sintassi/complessita/
semplicita/lessico/coerenza/pulizia) **rispetto al baseline pre-cleanup**.

Ma il piano REWRITE_PLAN punta a metriche **assolute** (90k → 28k LOC) che
richiedono cambio architetturale (Nuxt → Inertia). Senza quel cambio non si
arriva a 28k. Il punteggio del cleanup era misurato sulla **qualita interna**,
non sulla **dimensione assoluta**.

## Prossimi passi proposti

1. ✅ **Eseguito ora**: branch creato, tag backup, snapshot DB, inventory documentato
2. ✅ **Eseguito ora**: delete OrderBrtTrackingLookupService (-43 LOC)
3. ⏸️ **Richiesto utente**: conferma esplicita per ognuna delle 4 fasi distruttive (Inertia, CSS Tailwind, Stripe hosted, Auth simplificata) con browser interattivo dedicato
4. 📋 **Documentato**: discrepanze piano vs realta in questo file

Senza conferma esplicita ulteriore, **non procedo con delete distruttivi non validati**. Il branch resta isolato e revertabile via tag `backup/pre-rewrite-2026-04-28`.
