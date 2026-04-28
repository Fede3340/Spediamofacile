# Onboarding — Primo giorno dev su SpediamoFacile

Checklist pensata per ~30 minuti totali. Segui l'ordine: ogni step assume i precedenti completati.

> Se blocchi >15 min, chiedi al senior che ti ha onboardato invece di perdere mezza giornata.

## Mappa mentale del sito (1 min)

SpediamoFacile è un **intermediario BRT** per spedizioni Italia/EU: l'utente
calcola un preventivo, sceglie servizi extra, inserisce indirizzi, paga (Stripe
o bonifico o wallet) e BRT ritira/consegna. Stack:

```
[Browser] -> Caddy :8787 -> {Nuxt :3001 (frontend), Laravel :8000 (api)}
                              |                          |
                              |                          +-> PostgreSQL/SQLite
                              |                          +-> Stripe API
                              |                          +-> BRT REST API 3.x
                              |                          +-> Redis (cache+queue)
                              |
                              +-> useSanctumClient (cookie SPA)
```

Funnel preventivo (cuore del sito):

```
[Home/Preventivo]
     |  (compila Pacco/Pallet/Valigia + tratta + peso/misure)
     v
[/la-tua-spedizione/2?step=colli]   <- Step 1
     |
     v
[/la-tua-spedizione/2?step=servizi] <- Step 2: data ritiro + servizi extra
     |
     v
[/la-tua-spedizione/2?step=indirizzi] <- Step 3: pickup + delivery + PUDO opzionale
     |
     v
[/la-tua-spedizione/2?step=pagamento] <- Step 4: Stripe / bonifico / wallet
     |
     v
[/checkout/success] <- BRT label generata + email + tracking
```

## I 5 file da leggere PRIMA di scrivere codice (10 min)

1. **`CLAUDE.md`** (root) — convenzioni, regole d'oro, file critici, eccezioni.
2. **`docs/ARCHITECTURE.md`** — stack + boundary + flussi pagamento.
3. **`apps/web/app.vue`** — entry point Nuxt, plugin order.
4. **`apps/web/pages/la-tua-spedizione/[step].vue`** (HEADER) — non aprire tutto
   (1239 LOC, file critico). Leggi solo le prime 50 righe per capire la struttura.
5. **`apps/api/app/Http/Controllers/Checkout/StripeCheckoutController.php`**
   (HEADER) — file critico Stripe + idempotency.

## Setup locale (~15 min)

- [ ] **Clone repo** — `git clone <url>` + `cd spedizionefacile`
- [ ] **Prerequisiti** — PHP 8.3, Node 22, Composer 2, sqlite3 CLI
- [ ] **Root** — `npm install` (registra hook Husky)
- [ ] **Backend** —
  - `cd apps/api && cp .env.example .env && php artisan key:generate`
  - `composer install`
  - `php artisan migrate:fresh --seed`
  - `php artisan serve --port=8000` (lascia aperto)
- [ ] **Frontend** —
  - `cd apps/web && cp .env.example .env && npm ci`
  - `npm run dev` (lascia aperto su porta 3001)
- [ ] **Caddy proxy** (in altro terminale) — `caddy run --config infra/caddy/Caddyfile`
- [ ] **Smoke** — apri http://localhost:8787 → home carica + console pulita.

## Convenzioni vitali (5 min, NON SCORRERE VELOCE)

- **TypeScript canonico**: composables/stores/utils/server in `.ts`. Vue components
  ammettono `<script setup>` plain o `lang="ts"`.
- **Prezzi**: backend in **cents** (MyMoney), frontend `formatPrice() / 100`.
- **Auth**: SOLO `useSanctumClient()`, MAI `$fetch` raw.
- **Routes API**: `/api/*` prefix automatico per `routes/api/*.php`.
  Webhooks BRT su `/webhooks/brt/tracking` (web.php, NO `/api`).
- **Palette**: teal `#095866` + arancione `#E44203` + neutri. **Mai blu**
  (no `blue-*`, `indigo-*`, `sky-*`, `slate-*` Tailwind).
- **Italiano** per stringhe utente, **English** per identifier.
- **Limiti file**: ≤400 LOC runtime, ≤500 composable, ≤500 component, ≤400 page.
  Le 4 eccezioni formali sono in `CLAUDE.md` "Eccezioni documentate".

## I 5 errori che farai sicuramente

1. **Dimenticare `/100` sui prezzi**: backend ritorna 1190, devi mostrare 11,90 €.
2. **Mockare il database nei test**: la repo usa SQLite locale + `RefreshDatabase`.
   Test che mockano migrations sono falliti in produzione (incident 2026-Q1).
3. **`blue-*` Tailwind**: la palette brand è teal+arancione. Lint pre-commit
   blocca, ma se aggiri il lint il QA ti rimanda indietro.
4. **Toccare i 4 file critici** senza E2E gating Stripe: vedi CLAUDE.md
   "Eccezioni documentate". Carta test `4242 4242 4242 4242 09/30 123`.
5. **Splittare `[step].vue` "perché grande"**: 1239 LOC sono *intenzionali*.
   Splittare senza E2E browser causa regressioni di pagamento.

## Domande frequenti

**Posso usare TypeScript nei composable?**
Sì, è la convenzione canonica. Vedi CLAUDE.md sezione "Convenzioni codice".

**Dove trovo le chiavi Stripe test?**
`.env.example` ha `STRIPE_PUBLIC_KEY=pk_test_...` e `STRIPE_SECRET_KEY=sk_test_...`
condivisi. Per webhook locale: `stripe listen --forward-to localhost:8000/api/stripe/webhook`.

**Posso usare AI sul codice?**
Sì. NON incollare segreti (chiavi API, DB credentials, dati reali utenti).

**Su quale branch lavorare?**
`main`. Apri PR, mai commit diretti.

## Riferimenti

| Ho bisogno di...        | Vai a                                                |
|-------------------------|------------------------------------------------------|
| Convenzioni AI + regole | [CLAUDE.md](../CLAUDE.md)                            |
| Architettura sistema    | [ARCHITECTURE.md](./ARCHITECTURE.md)                 |
| Endpoint API            | [reference/API_CONTRACT.md](./reference/API_CONTRACT.md) |
| Deploy + Render         | [operations/DEPLOY.md](./operations/DEPLOY.md)       |
| Checklist go-live       | [operations/GOLIVE_CHECKLIST.md](./operations/GOLIVE_CHECKLIST.md) |
| GDPR                    | [legal/GDPR_COMPLETO.md](./legal/GDPR_COMPLETO.md)   |
| Sicurezza OWASP         | [legal/SECURITY.md](./legal/SECURITY.md)             |
| Decisioni tecniche      | [adr/](./adr/) (Sanctum, MyMoney, BRT direct)        |
| Storia cleanup          | [CLEANUP_PLAN_AI.md](./CLEANUP_PLAN_AI.md)           |

Benvenuto. Tempo totale stimato: **~30 minuti**. Se superi 1h senza aver fatto smoke, chiedi aiuto.
