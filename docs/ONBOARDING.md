# Onboarding — Primo giorno dev su SpediamoFacile

Checklist sequenziale pensata per ~90 minuti totali. Segui l'ordine: ogni step assume i precedenti completati.

> Se blocchi, chiedi subito in `#dev` (o apri issue) invece di perdere mezza giornata.

## Fase 1 — Setup ambiente (~30 min)

- [ ] **Clone repo** — `git clone https://github.com/Boop91/spedizionefacile.git`
- [ ] **Verifica prerequisiti** — PHP 8.3, Node 22, Composer 2, PostgreSQL 15, Redis 7 installati (vedi [QUICKSTART.md](./QUICKSTART.md))
- [ ] **Root setup** — `cd spedizionefacile && npm install` (hook Husky si registrano)
- [ ] **Backend** —
  - [ ] `cd apps/api`
  - [ ] `cp .env.example .env` + `php artisan key:generate`
  - [ ] `composer install`
  - [ ] `php artisan migrate:fresh --seed`
  - [ ] `php artisan serve --port=8000` (lascia aperto)
- [ ] **Frontend** —
  - [ ] `cd apps/web`
  - [ ] `cp .env.example .env` + `npm ci`
  - [ ] `npm run dev` (lascia aperto su porta 8787)
- [ ] **Smoke locale** —
  - [ ] Apri [http://localhost:8787](http://localhost:8787) -> home carica
  - [ ] `curl http://localhost:8000/api/health` -> `{"status":"ok"}`
- [ ] **Login test** — `cliente@spediamofacile.it` / `Cliente2026!` -> accedi e arriva dashboard

**Criterio di riuscita**: tre utenti (admin/client/pro) funzionano, home renderizza, no errori in console.

## Fase 2 — Lettura docs (~35 min)

- [ ] [`ARCHITECTURE.md`](./ARCHITECTURE.md) — 20 minuti. Alla fine dovresti saper rispondere:
  - Che cookie viene settato dopo login?
  - Che succede quando Stripe invia `payment_intent.succeeded`?
  - Dove finiscono i job lenti (invio email, create BRT shipment)?
- [ ] [`FRONTEND_STRUCTURE.md`](./FRONTEND_STRUCTURE.md) — 8 minuti. Nota: prefisso componenti, autoimport composable, regola "un stato = un composable".
- [ ] [`BACKEND_STRUCTURE.md`](./BACKEND_STRUCTURE.md) — 7 minuti. Nota: Controller thin, Service fat, FormRequest obbligatorio su endpoint sensibili.
- [ ] [`DESIGN_SYSTEM.md`](./DESIGN_SYSTEM.md) — scorri token colore (MAI blu), spacing, tipografia.
- [ ] [`CONTRIBUTING.md`](./CONTRIBUTING.md) — Conventional Commits (type + scope).

## Fase 3 — Esplorazione guidata (~15 min)

- [ ] Apri `pages/la-tua-spedizione/[step].vue` e `utils/shipment.js`.
- [ ] Verifica quali sono le route canoniche del funnel:
  - [ ] `/la-tua-spedizione/2?step=colli`
  - [ ] `/la-tua-spedizione/2?step=servizi`
  - [ ] `/la-tua-spedizione/2?step=indirizzi`
  - [ ] `/la-tua-spedizione/2?step=pagamento`
- [ ] Nota che `pages/preventivo.vue`, `pages/checkout.vue` e `pages/riepilogo.vue` sono route di compatibilita, non entrypoint canonici.
- [ ] Apri `pages/account/spedizioni/index.vue` e `pages/account/spedizioni/[id].vue` come superfici canoniche cliente per lista + dettaglio.
- [ ] Tratta `pages/account/ordini/[id].vue` come legacy/non canonica finche' non viene ripulita.
- [ ] Apri `app/Services/OrderCreationService.php` e leggi `createFromCart()`.
- [ ] Apri `app/Http/Controllers/StripeWebhookController.php` -> verifica come chiama `OrderCreationService`.
- [ ] Apri DevTools -> Network mentre fai login: osserva:
  - [ ] `GET /sanctum/csrf-cookie` (cookie XSRF settato)
  - [ ] `POST /api/custom-login` con header `X-XSRF-TOKEN`
  - [ ] `GET /api/user` dopo login (userStore hydrate)

## Fase 4 — Test smoke (~5 min)

- [ ] `cd apps/web`
- [ ] `npm run test:e2e -- --grep @smoke` -> tutti pass
- [ ] `cd ../apps/api`
- [ ] `php artisan test --testsuite=Unit --stop-on-failure` -> tutti pass

Se uno fallisce: non ignorare, apri issue con log output.

## Fase 5 — Primo PR "hello world" (~15 min)

Piccola modifica a basso rischio per validare l'intero workflow end-to-end.

- [ ] `git checkout -b docs/<tuo-nome>-onboarding`
- [ ] Aggiungi il tuo nome in `docs/TEAM.md` (se non esiste, crealo con lista semplice).
- [ ] `git add docs/TEAM.md`
- [ ] `git commit -m "docs(onboarding): aggiungi <nome> al team"` — pre-commit hook gira Pint/lint.
- [ ] `git push -u origin docs/<tuo-nome>-onboarding` — pre-push lancia typecheck + unit test.
- [ ] Apri PR su GitHub:
  - [ ] Titolo segue conventional commits
  - [ ] Descrizione: 2 righe ("primo PR onboarding")
  - [ ] CI deve diventare verde (lint + typecheck + unit + e2e smoke)
- [ ] Richiedi review a chi ti ha fatto onboarding.

**Criterio di riuscita**: PR verde, merged entro fine giornata.

## Fase 6 — Tooling consigliato (quando hai 10 min liberi)

- [ ] **VS Code / Cursor** con estensioni: Vue (Volar), PHP Intelephense, Prettier, ESLint, Tailwind CSS IntelliSense, Laravel Extension Pack
- [ ] **TablePlus / DBeaver** per ispezionare PostgreSQL locale
- [ ] **RedisInsight** per ispezionare queue e cache
- [ ] **Stripe CLI** (`stripe listen --forward-to localhost:8000/api/stripe/webhook`) per test webhook locali
- [ ] **Postman / Bruno** con collection `docs/api/postman.json` (se presente)
- [ ] **Sentry** — chiedi accesso progetto `spediamofacile`
- [ ] **Plausible** — chiedi accesso dashboard analytics

## Cosa NON fare il primo giorno

- [ ] Non committare su `main` (protetto). Usa branch.
- [ ] Non usare `git commit --no-verify` se non e' un incident reale.
- [ ] Non toccare `.env` prod, Render dashboard, o segreti Sentry.
- [ ] Non fare `git push --force` nemmeno su branch feature.
- [ ] Non mergiare PR tue senza review.

## Domande frequenti

**"Posso usare TypeScript nei composable o nei componenti?"**
No: il frontend e' tutto JavaScript per essere accessibile a junior. Usa JSDoc (`@param`, `@returns`, `@typedef`) per type hints. Vue components in `<script setup>` (no `lang="ts"`).

**"Come aggiungo uno scope commitlint nuovo?"**
Aggiorna `commitlint.config.js` nello stesso PR che introduce lo scope. Non usare scope generici.

**"Dove trovo le chiavi Stripe test?"**
`.env.example` ha `STRIPE_PUBLIC_KEY=pk_test_...` e `STRIPE_SECRET_KEY=sk_test_...` condivisi. Per Stripe webhook: lancia `stripe listen` e copia il `whsec_...` in `STRIPE_WEBHOOK_SECRET`.

**"Posso usare ChatGPT / Claude sul codice?"**
Si. Non incollare segreti (chiavi API, DB credentials, dati reali utenti) nei prompt esterni.

**"Che branch devo forkare?"**
`develop` se esiste, altrimenti `main`. Chiedi se non chiaro.

## Riferimenti rapidi

| Ho bisogno di...                | Vai a                                |
|---------------------------------|--------------------------------------|
| Setup locale                    | [QUICKSTART.md](./QUICKSTART.md)     |
| Capire come funziona il sistema | [ARCHITECTURE.md](./ARCHITECTURE.md) |
| Trovare un componente Vue       | [FRONTEND_STRUCTURE.md](./FRONTEND_STRUCTURE.md) |
| Trovare un Service Laravel      | [BACKEND_STRUCTURE.md](./BACKEND_STRUCTURE.md) |
| Elenco endpoint                 | [API_CONTRACT.md](./API_CONTRACT.md) |
| Design tokens / stile           | [DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md) |
| Convenzioni commit              | [CONTRIBUTING.md](./CONTRIBUTING.md) |
| Errore strano                   | [DEBUGGING.md](./DEBUGGING.md)       |
| Glossario termini business      | [GLOSSARIO-SEMPLICE.md](./GLOSSARIO-SEMPLICE.md) |
| FAQ dev                         | `guide/` e `spiegazioni/` in docs    |

Benvenuto nel team. Tempo totale stimato seguendo la checklist: **~90 minuti**. Se superi 3 ore senza aver chiuso Fase 2, chiedi aiuto.
