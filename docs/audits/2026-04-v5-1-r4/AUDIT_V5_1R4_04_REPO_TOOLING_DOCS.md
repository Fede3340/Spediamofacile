# Audit V5.1R4 - Repo, tooling e documentazione

Data: 2026-04-29

## Giudizio

La repo ha una base potenzialmente professionale, ma oggi si presenta male perche la documentazione e il tooling non corrispondono al codice reale.

Questo e molto grave per onboarding e manutenzione: il codice puo anche funzionare localmente, ma se README, CI e Husky puntano a una struttura vecchia, un nuovo sviluppatore perde fiducia e tempo.

## Struttura reale

Struttura osservata:

- backend Laravel alla root
- frontend Nuxt in `apps/web`
- routes API modulari in `routes/api/*.php`
- webhook in `routes/web.php`
- docs in `docs`

Questa struttura puo funzionare bene.

## Struttura dichiarata ma falsa

Documenti e tooling citano:

- `apps/api`
- Inertia
- Vite come frontend Laravel
- `resources/js/Pages`
- `nuxt-spedizionefacile-master`

Questi riferimenti sono legacy.

## File da correggere

### README root

File: `README.md`

Problemi:

- stack falso
- path falso
- auth Inertia non reale

Azioni:

- sostituire con quickstart reale
- aggiungere comandi root Laravel
- aggiungere comandi `apps/web`
- linkare audit e docs aggiornate

### ONBOARDING

File: `docs/ONBOARDING.md`

Problemi:

- nega Nuxt
- parla di Inertia
- usa `apps/api`

Azioni:

- riscrivere per Nuxt + Laravel API
- tenere storico solo in archive se serve

### ARCHITECTURE

File: `docs/ARCHITECTURE.md`

Problemi:

- diagramma Inertia
- `apps/api`
- `web.php 100% Inertia`

Azioni:

- mappa attuale:
  - Browser -> Nuxt
  - Nuxt -> Laravel API
  - Laravel -> DB/BRT/Stripe
  - Webhooks -> Laravel

### CI

File: `.github/workflows/ci.yml`

Problemi:

- backend job su `apps/api`
- cache path legacy
- coverage path legacy

Azioni:

- backend job root:
  - `composer install`
  - `composer lint`
  - `php artisan test`
- frontend job `apps/web`:
  - `npm ci`
  - `npm run typecheck`
  - `npm run lint`
  - `npm run test:unit`
  - `npm run build`

### Husky

File: `.husky/pre-commit`, `.husky/pre-push`

Problemi:

- path `apps/api`

Azioni:

- aggiornare path
- separare controlli PHP/FE

### Metadata

File:

- `composer.json`
- `apps/web/package.json`

Problemi:

- nomi skeleton `laravel/laravel` e `nuxt-app`

Azioni:

- usare nomi progetto reali

## File locali tracciati

File osservati:

- `.claude/launch.json`
- `.claude/preview-proxy.js`
- `.claude/scheduled_tasks.lock`
- `.claude/settings.local.json`
- `.codex/config.toml`
- `.local/qa/playwright/design-audit/*.png`

Decisione:

- se sono tooling ufficiale, documentarli
- se sono personali, rimuoverli dal tracking
- se sono audit output, spostarli in docs/reference o artifact non runtime

## Standard target

Root chiara:

```text
app/
routes/
config/
database/
tests/
apps/web/
docs/
infra/
scripts/
```

No miscuglio tra:

- runtime vivo
- output locali
- screenshot temporanei
- config personali
- docs legacy

