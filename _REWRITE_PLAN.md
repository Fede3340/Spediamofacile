# REWRITE V2 — Piano di esecuzione autonoma per Claude Code

**Data inizio piano**: 2026-04-28
**Branch target**: `rewrite/v2-inertia-2026-04-28`
**Tag backup**: `backup/pre-rewrite-2026-04-28`
**Esecutore**: Claude Code (controllo completo PC, modalità loop autonomo)
**Durata stimata**: 28 giorni AI continui (40 con buffer 30%)

---

## OBIETTIVO MISURABILE

| Metrica | Pre | Post | Δ |
|---|---|---|---|
| LOC totali | ~90.000 | ~28.000 | **-69%** |
| File totali | 22.014 | ~5.500 | -75% |
| Composables | 60+ | 8 | -87% |
| Services BE | 42 | 14 | -67% |
| Controller BE | 57 | 22 | -61% |
| CSS custom LOC | 21.000 | 500 | -98% |
| Apps separate | 2 | 1 (monolite Inertia) | -50% |
| Repo size git | 717 MB | ~80 MB | -89% |
| Build time CI | ~6 min | ~2 min | -66% |
| Onboarding junior | 2-3 sett | 3-4 giorni | -80% |
| Lighthouse mobile | non misurato | ≥ 95 | gating |
| Test E2E | 38 | 38+ | invariato/+ |
| Test BE PHPUnit | 63 | 63+ | invariato/+ |
| axe violations | non misurato | 0 critical/serious | gating |

---

## STACK FINALE

- **Backend**: Laravel 11 + Sanctum 4 + Inertia 2 + Stripe 18 + Socialite (Google) + moneyphp
- **Frontend**: Vue 3.5 + Tailwind 4 (no Nuxt, no UI lib esterna pesante)
- **DB**: Postgres 15 (dev+prod parity) + SQLite (test fast unit)
- **Cache/Queue**: Redis 7
- **Build**: Vite 5 (no Nitro, no SSR Nuxt)
- **Test**: PHPUnit (BE) + Playwright (E2E) + Vitest (unit FE)
- **Quality**: Pint + PHPStan livello 9 + ESLint + Prettier + ts-prune + knip

---

## REGOLE OPERATIVE GLOBALI

1. **Branch dedicato**: `rewrite/v2-inertia-2026-04-28`. Mai toccare `main` fino a swap finale (Fase 9).
2. **Tag backup pre-fase**: `backup/pre-fase-N-YYYYMMDD-HHMM` prima di ogni fase distruttiva.
3. **Commit atomico**: ogni delete/refactor è 1 commit isolato e revertabile.
4. **Test gating obbligatorio**: ogni fase termina con TUTTI verdi:
   - `php artisan test`
   - `npm run test:e2e`
   - `npm run test:unit`
   - `npm run build`
   - `composer audit && npm audit` (no high/critical)
5. **Rollback automatico**: rosso → reset al tag pre-fase, root cause analysis, fix, retry. Mai forzare avanzamento con test rossi.
6. **Snapshot DB pre/post** per fasi critiche (3, 5, 6): `sqlite3 database.sqlite ".dump" > _data/snapshot_pre_fase_N.sql`.
7. **No mock in test integrazione**: Stripe test mode reale (`4242 4242 4242 4242 09/30 123`), BRT sandbox reale.
8. **File runtime ≤ 400 LOC** (eccezioni documentate inline come oggi).
9. **Italiano** in commit, commenti, doc, output utente.
10. **Mai blu** nella nuova UI Tailwind (palette `#095866` teal + `#E44203` arancione + neutri).
11. **Loop perfezione**: non si ferma finché score qualità ≥ 95/100 (metriche misurate, non auto-valutate).
12. **Report solo a fine fase**, non during (niente noise nel terminale).

---

## STRUMENTI INSTALLATI

### Backend
- `composer require inertiajs/inertia-laravel` (Fase 2)
- `composer require --dev rector/rector` (refactor automatico PHP)
- `larastan/larastan ^3.9` (già presente)
- `laravel/pint ^1.13` (già presente)
- `phpunit/phpunit ^10.5` (già presente)

### Frontend
- `npm install @inertiajs/vue3` (Fase 2)
- `npm install -D ts-prune knip` (dead code FE)
- `@axe-core/playwright ^4.11` (già presente, hardening)
- `vite ^5` (sostituisce Nuxt build)

### CI tool
- GitHub Actions invariato struttura, aggiornati step

---

## PRE-FLIGHT (Giorno 0, ~2 ore)

```bash
git checkout main
git pull
git checkout -b rewrite/v2-inertia-2026-04-28
git tag backup/pre-rewrite-2026-04-28
git push origin rewrite/v2-inertia-2026-04-28 --tags

# Snapshot DB
mkdir -p _data
sqlite3 apps/api/database/database.sqlite ".dump" > _data/snapshot_pre_rewrite.sql

# Inventory baseline (Claude esegue tool: Grep + Read + Bash)
# Output: _REWRITE_INVENTORY.md
```

**Audit baseline genera `_REWRITE_INVENTORY.md` con**:
- Lista 60 composables + caller count (grep `useShipmentStep` per ognuno).
- Lista 42 services + caller count.
- Lista 128 endpoint + uso reale (server log + test coverage).
- Lista CSS class `.sf-*` + uso (regex su template Vue).
- Dead code analysis: PHPStan + ts-prune + knip.
- Output atteso: 8-10 file da eliminare con confidenza ≥ 99%, 30+ file da fondere.

---

## FASE 1 — Pulizia codice morto (Giorni 1-3) → -8.500 LOC

**Obiettivo**: eliminare con certezza chirurgica tutto il non-usato.

### Step 1.1 — Dead code analysis automatico (4h)

```bash
vendor/bin/phpstan analyse --level=9 app/ > _DEAD_CODE_PHP.txt
npx ts-prune > _DEAD_CODE_TS.txt
npx knip > _DEAD_CODE_FE.txt
```

Output: `_DEAD_CODE_REPORT.md` con per ogni candidato: callers count, ultima modifica, raccomandazione delete/refactor/keep.
Validazione manuale via `Grep` per ogni candidato (zero falsi positivi prima di delete).

### Step 1.2 — Eliminazione controller/service/listener inutilizzati (1g)

**Delete confermati (Claude esegue dopo grep zero-caller):**

```
DELETE app/Http/Controllers/Checkout/StripeConnectController.php          (-200 LOC)
DELETE app/Http/Controllers/Checkout/StripeCustomerController.php         (-150 LOC)
DELETE app/Http/Controllers/Checkout/RefundController.php                 (-180 LOC, refund inline in Webhook)
DELETE app/Actions/Fortify/* (5 file)                                     (-300 LOC)
COMPOSER REMOVE laravel/fortify
DELETE app/Listeners/* (audit; tieni solo SendOrderConfirmation, GenerateBrtLabel, MarkOrderProcessing)  (-400 LOC)
DELETE app/Events/* (tieni OrderPaid, ShipmentStatusChanged)              (-200 LOC)
DELETE app/Mail/AbandonedCartReminderMail + relativo Command              (-150 LOC, feature non MVP)
```

### Step 1.3 — Fusione 11 BrtServices → 1 BrtClient (1g)

```
NUOVO: app/Services/Brt/BrtClient.php (~400 LOC)
  metodi: createShipment(), getTracking(), searchPudo(), printLabel(), createPickup()
  helpers privati: normalizeAddress(), buildPayload(), translateError(), lookupFiliale()

DELETE: 11 file Brt/* esistenti                                           (-2.100 LOC, +400 = -1.700 netto)
```

Test: `tests/Feature/Brt/BrtClientTest.php` esistenti devono passare invariati.

### Step 1.4 — Fusione 4 OrderBrtTracking* → 1 (4h)

```
NUOVO: app/Services/OrderBrtTrackingService.php (~250 LOC)
DELETE: 4 service esistenti                                               (-600 LOC)
```

### Step 1.5 — Fusione composables shipment funnel (1g)

```
NUOVO: composables/useShipmentFunnel.ts (~600 LOC)
  state, validation, navigation, persistence, submit
DELETE: 16 useShipmentStep*.ts                                            (-3.500 LOC)
```

### Step 1.6 — Fusione composables location/quote (4h)

```
useAddressAutocomplete + useShipmentLocationAutocomplete → useAddressLookup (-800 LOC)
useQuickQuote* (3 file) → inline in useQuote                              (-700 LOC)
```

### Gate Fase 1

```bash
php artisan test            # TUTTI verdi
npm run test:e2e            # TUTTI verdi (38 spec)
npm run test:unit           # TUTTI verdi
npm run build               # OK
composer audit && npm audit # 0 high/critical
git tag fase-1-completed
```

**Bilancio fase 1**: -8.500 LOC, zero feature persa, test verdi.

---

## FASE 2 — Migrazione a Inertia (Giorni 4-13) → -25.000 LOC

**La fase critica**. Esecuzione **incrementale per dominio**, NON big-bang.

### Step 2.1 — Setup Inertia + scaffolding (1g)

```bash
composer require inertiajs/inertia-laravel
php artisan inertia:middleware                # crea HandleInertiaRequests
npm install @inertiajs/vue3
```

- Config: `resources/views/app.blade.php` come root (rimpiazza Nuxt).
- `vite.config.js`: aggiunge `@inertiajs/vue3` plugin.
- Layout default: `resources/js/Layouts/AppLayout.vue` (port da `apps/web/layouts/default.vue`).
- Auth shared via `HandleInertiaRequests::share()` → niente più `useAuth` composable.
- Flash messages, errors validation, user → tutto in shared props Inertia.

### Step 2.2 — Migrazione progressiva dominio per dominio (8g)

**Ordine eseguito da Claude (priorità: bassa criticità → alta criticità):**

| Giorno | Dominio | Pages migrate | LOC eliminate |
|---|---|---|---|
| 4 | Pagine statiche | chi-siamo, contatti, faq, cookie, privacy, termini | -2.000 |
| 5 | Auth | login, registrazione, recupera-password, verifica-email, aggiorna-password | -2.500 |
| 6 | Account utente | account/dashboard, ordini, indirizzi, carte, wallet | -3.500 |
| 7 | Catalogo + preventivo | index, preventivo, servizi, traccia | -2.000 |
| 8-9 | Funnel spedizione | la-tua-spedizione/[step] (1241 LOC → 5 controller + 5 component split) | -8.000 |
| 10 | Carrello + Checkout | carrello, checkout (nuova versione hosted Fase 4) | -3.000 |
| 11 | Admin | admin/dashboard, prezzi, ordini, utenti, contenuti | -3.500 |
| 12 | PUDO + tracking | pudo, traccia/[tracking] | -1.000 |

**Pattern per ogni page (Claude applica meccanico):**

```
PRIMA (Nuxt):                          DOPO (Inertia):
apps/web/pages/account/ordini.vue      app/Http/Controllers/Account/OrderListController.php
  + composables/useOrdersList.ts          → return Inertia::render('Account/Orders', [
  + chiamata $fetch GET /api/orders          'orders' => $user->orders()->paginate(20)
  + Pinia store ordersStore             ])
  + middleware app-auth                resources/js/Pages/Account/Orders.vue
                                          props: { orders: Object }
                                          (zero composable, zero $fetch, zero store)
```

### Step 2.3 — Eliminazione layer Nuxt + BFF (1g)

Una volta migrate tutte le pages:

```
DELETE apps/web/server/                  # niente più Nitro BFF
DELETE apps/web/composables/useSanctum*  # auth via Inertia shared
DELETE apps/web/stores/* (eccetto cartStore se persiste guest cart)
DELETE apps/web/middleware/*             # Laravel middleware
DELETE apps/web/plugins/*                # Inertia gestisce
DELETE apps/web/nuxt.config.ts
DELETE apps/web/.nuxt, .output

MOVE apps/web/components/* → resources/js/Components/
MOVE apps/web/utils/* → resources/js/utils/ (pure functions)
DELETE apps/web/ (cartella intera)
DELETE apps/api/routes/api.php (~50 route → diventano web.php Inertia)
TIENI apps/api/routes/api.php SOLO per webhook BRT/Stripe (~10 route)

MOVE apps/api/* → root (collasso monorepo)
DELETE apps/ (cartella intera)
```

Repo monolite: niente più `apps/`, tutto sotto root Laravel standard.

### Gate Fase 2

- 47 pages funzionanti via Inertia
- Stripe E2E con test card `4242 4242 4242 4242 09/30 123` end-to-end OK
- BRT integration test OK
- 63 PHPUnit + 38 Playwright (rifattorizzati per Inertia, stesso DOM) tutti verdi
- `npm run build` produce 1 bundle, non 2

**Bilancio fase 2**: -25.000 LOC, monorepo collassato a monolite.

---

## FASE 3 — CSS Tailwind-only (Giorni 14-18) → -19.500 LOC CSS

### Step 3.1 — Tool conversione automatica (1g)

Claude scrive `scripts/css-to-tailwind.mjs`:

- Parsa ogni file CSS → estrae class `.sf-*` con regole.
- Genera mapping `class → tailwind utilities` (es. `.sf-btn-primary { padding: 12px 24px; bg: #095866; ... }` → `px-6 py-3 bg-brand-teal ...`).
- Per ogni `<template>` sostituisce `class="sf-btn-primary"` con utilities.
- Lascia in CSS solo: design tokens (custom properties), animazioni, regole impossibili in Tailwind.

### Step 3.2 — Conversione per file (3g)

```
shipment-flow.css   5.974 LOC → conversione automatica + manuale review → 0 LOC
admin.css           4.068 LOC → conversione → 0 LOC
account.css         3.153 LOC → conversione → 0 LOC
layout.css          1.162 LOC → conversione → 0 LOC
content.css           867 LOC → conversione → 0 LOC
autenticazione.css    813 LOC → conversione → 0 LOC
contatti.css          478 LOC → conversione → 0 LOC
tracking.css          364 LOC → conversione → 0 LOC
pages/home.css        591 LOC → conversione → 0 LOC

main.css            2.297 LOC → riduce a soli design tokens → 200 LOC
```

### Step 3.3 — Verifica visiva regression (1g)

- `npm run test:e2e -- visual-regression.spec.ts` con snapshot pre/post
- Claude usa `mcp__Claude_Preview__preview_screenshot` per confronto pixel su 15 pagine chiave
- Differenze > 2% → fix manuale prima di chiudere

### Gate Fase 3

- CSS totale ≤ 600 LOC
- Visual regression < 2% diff su tutte le 15 pagine chiave
- Lighthouse score CSS-related invariato o migliore

**Bilancio fase 3**: -20.500 LOC, sistema design unificato.

---

## FASE 4 — Stripe Checkout hosted (Giorni 19-20) → -1.500 LOC

### Step 4.1 — Sostituzione Elements → Checkout Session (1g)

```
NUOVO: app/Services/StripeCheckoutSession.php (~80 LOC)
  createSession(Order $order): redirect URL

NUOVO: app/Http/Controllers/Checkout/StripeController.php (~120 LOC)
  POST /checkout/session → crea session → redirect
  GET  /checkout/return  → verifica session_id → mostra esito

RIUSI: StripeWebhookController.php (idempotency già presente, semplificato)

DELETE: ShipmentStepPagamento.vue (716 LOC)
NUOVO:  Pages/Checkout/Method.vue (~150 LOC, solo selezione: stripe/wallet/bonifico)
DELETE: usePayment.ts (687 LOC)
DELETE: StripeCheckoutController.php (760 LOC) → sostituito con 120
```

### Step 4.2 — Test E2E pagamento (1g)

- Test card `4242 4242 4242 4242 09/30 123` → success
- Test card 3DS challenge → success
- Test card decline → fallback corretto
- Webhook retry simulation → idempotency tiene

### Gate Fase 4

- Pagamento E2E completo verde su Stripe test mode
- Webhook idempotency test verde (replay 3x)
- PCI scope ridotto (no card data passa per server)

**Bilancio fase 4**: -1.500 LOC, security migliorata.

---

## FASE 5 — Database standardizzato (Giorni 21-22)

### Step 5.1 — Estrazione migrations da schema-dump (1g)

- Claude legge `database/schema/sqlite-schema.sql` → genera 1 file migration per tabella.
- Output: `database/migrations/2026_05_01_000001_create_users_table.php` ... (25 tabelle).
- Test: `php artisan migrate:fresh --seed` ricrea identico DB.

### Step 5.2 — Locations da CDN (4h)

```
DELETE database/comuni.json (1.9 MB)
DELETE database/IT.zip (252 KB)
DELETE database/GR.zip (196 B)

NUOVO LocationSeeder.php:
  Http::get('https://download.geonames.org/export/zip/IT.zip')
    ->throw()
    ->save(storage_path('app/temp/IT.zip'))
  ZipArchive → estrae IT.txt → bulk insert → cleanup

Fallback: storage/app/seed-cache/IT.zip (cached, gitignored)
```

### Step 5.3 — DB SQLite → Postgres dev parity (4h)

- `docker-compose.yml` con Postgres 15 per dev (parity con prod)
- Mantiene SQLite come opzione test fast (PHPUnit `--testsuite=Unit`)
- Aggiorna `.env.example` con stringhe connessione Postgres

### Gate Fase 5

- `php artisan migrate:fresh --seed` riproducibile su SQLite e Postgres
- Repo size git -2.2 MB
- Test `RefreshDatabase` verdi (era rotto, ora va)

---

## FASE 6 — Auth semplificata (Giorno 23) → -800 LOC

```
COMPOSER REMOVE laravel/fortify  (già fatto fase 1, conferma)
TIENI: laravel/sanctum + laravel/socialite

DELETE 6 controller Auth → SOSTITUITI con:
  app/Http/Controllers/Auth/AuthController.php (~150 LOC: login, register, logout, verify)
  app/Http/Controllers/Auth/PasswordController.php (~80 LOC: forgot, reset, change)
  app/Http/Controllers/Auth/SocialController.php (~100 LOC: Google OAuth)

DELETE FE: 4 composables auth (useAuth, useAuthOverlay, useSession, useTurnstile usato solo qui)
NUOVO FE: shared via Inertia HandleInertiaRequests (zero composable)
```

### Gate Fase 6

- Login email + Google OAuth verdi
- Password reset flow E2E verde
- Email verification verde
- 2FA backlog (non MVP)

---

## FASE 7 — Hardening + Polish (Giorni 24-26)

### Step 7.1 — Performance (1g)

- Lighthouse audit 15 pagine: target ≥ 95 mobile
- Bundle analyzer Vite → tree-shake aggressivo, lazy components admin
- Image optimization: tutte le PNG/JPG → AVIF/WebP via Vite plugin
- Font subset (solo glyph latin)
- Preload only critical fonts (4 max)

### Step 7.2 — Accessibility (1g)

- `@axe-core/playwright` su tutte le 47 pages → zero violation gravi
- Tastiera-only navigation E2E
- Screen reader test (NVDA su Windows) su flussi critici (registrazione, preventivo, checkout)
- Contrast ratio AA su tutti i testi (Tailwind plugin)

### Step 7.3 — Security (1g)

- `composer audit` + `npm audit` → zero vuln high/critical
- OWASP Top 10 checklist (in `docs/legal/SECURITY.md`)
- CSP header strict, Subresource Integrity, Permissions-Policy
- Rate limiting endpoint sensibili (login, password reset, contact)
- Secret scan: `gitleaks detect` → zero hit

### Gate Fase 7

- Lighthouse mobile ≥ 95 su 15 pages
- axe violations: 0 critical, 0 serious
- Security audit: 0 high/critical
- Gitleaks: 0 secret esposti

---

## FASE 8 — Documentazione minima + Onboarding (Giorno 27)

```
RIDUCO:
  CLAUDE.md           240 → 30 righe (solo convenzioni essenziali)
  ARCHITECTURE.md     230 → 60 righe (1 diagramma, 4 sezioni)
  ONBOARDING.md       lunghissimo → 25 righe (3 comandi setup + 1 screenshot)
  README.md           80 → 40 righe

ELIMINO:
  docs/baseline/* (snapshot vecchi)
  docs/adr/* (mantengo solo se ancora rilevanti, max 3)

TENGO:
  docs/legal/SECURITY.md, GDPR_COMPLETO.md (compliance obbligatoria)
  docs/operations/DEPLOY.md, GOLIVE_CHECKLIST.md
  docs/reference/API_CONTRACT.md (solo webhook BRT/Stripe ora)
```

### Validazione finale onboarding

Claude simula junior dev:

1. Clone repo
2. `composer install && npm install`
3. `cp .env.example .env && php artisan key:generate`
4. `docker-compose up -d` (postgres + redis)
5. `php artisan migrate:fresh --seed`
6. `npm run dev` + `php artisan serve`
7. Apre browser → home funzionante

**Target**: ≤ 20 minuti dal clone al primo render funzionante.

---

## FASE 9 — Swap finale a main (Giorno 28)

```bash
# Pre-swap checks
git checkout rewrite/v2-inertia-2026-04-28
php artisan test                    # 100% verde
npm run test:e2e                    # 100% verde
npm run test:unit                   # 100% verde
npm run build                       # OK
composer audit && npm audit         # 0 high/critical

# Snapshot finale
sqlite3 database/database.sqlite ".dump" > _data/snapshot_post_rewrite.sql

# Tag release
git tag v2.0.0-rewrite-complete
git push --tags

# Swap (utente conferma)
git checkout main
git merge rewrite/v2-inertia-2026-04-28 --no-ff
# (oppure reset hard se preferisci storia pulita)
```

---

## PIANO DI ROLLBACK

Per ogni fase:

- **Tag pre-fase** sempre disponibile → `git reset --hard backup/pre-fase-N-YYYYMMDD`
- **DB snapshot** → `sqlite3 database.sqlite < snapshot_pre_fase_N.sql`
- **Branch isolato** → `main` mai toccato fino allo swap finale di Fase 9
- **Storia commit atomica** → `git revert <hash>` puntuale per fix mirato

Se 3 retry consecutivi falliscono su uno step → STOP, escalation, attesa input utente.

---

## TEMPISTICA

| Fase | Giorni | Cumulati | Output |
|---|---|---|---|
| 0 — Pre-flight | 0.5 | 0.5 | Branch + tag + inventory |
| 1 — Pulizia codice morto | 3 | 3.5 | -8.500 LOC |
| 2 — Inertia migration | 10 | 13.5 | -25.000 LOC, monolite |
| 3 — CSS Tailwind | 5 | 18.5 | -20.500 LOC CSS |
| 4 — Stripe hosted | 2 | 20.5 | -1.500 LOC, PCI ridotto |
| 5 — DB standard | 2 | 22.5 | Migrations + Postgres |
| 6 — Auth semplificata | 1 | 23.5 | -800 LOC |
| 7 — Hardening | 3 | 26.5 | Lighthouse 95+, axe 0 |
| 8 — Docs minime | 1 | 27.5 | Onboarding 20 min |
| 9 — Swap main | 0.5 | 28 | v2.0.0 release |

**Totale**: 28 giorni AI continui. **Buffer +30%** → 35-40 giorni calendario.

---

## CHECKLIST PRE-AVVIO

- [ ] Conferma utente: "Vai"
- [ ] Repo pulita (git status clean su main)
- [ ] Disco con almeno 5 GB liberi
- [ ] Stripe test API key in `.env` valida
- [ ] BRT sandbox API key valida
- [ ] Postgres 15 disponibile (Docker o locale)
- [ ] Node 20+ e PHP 8.3+ installati

---

## DOPO IL "VAI"

Sequenza immediata:

1. `git checkout -b rewrite/v2-inertia-2026-04-28`
2. `git tag backup/pre-rewrite-2026-04-28 && git push --tags`
3. Snapshot DB in `_data/snapshot_pre_rewrite.sql`
4. Genera `_REWRITE_INVENTORY.md` con audit completo (~2 ore)
5. Inizio **Fase 1 Step 1.1** (dead code analysis)
6. Loop autonomo fino a gate Fase 1 (3 giorni)
7. Report a fine Fase 1 con bilancio reale: LOC eliminate, test verdi, screenshot diff zero
8. Procedo con Fase 2 (la più lunga, 10 giorni)
9. Continuo loop perfezione fino a Fase 9 swap
10. Consegna repo da manuale agency top-tier

**Niente conferme intermedie. Solo report a fine fase.**
