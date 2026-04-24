# Documentazione SpediamoFacile

Indice unico della documentazione attiva del monorepo.

## Struttura

- `docs/` — **10 docs canonici** (overview, struttura, design)
- `docs/operations/` — deploy, setup production, checklist go-live
- `docs/reference/` — API contract, glossario, FAQ dev, roadmap
- `docs/legal/` — security, GDPR
- `docs/adr/` — Architecture Decision Records
- `docs/archive/` — storico documentale e handoff superseded
- `../_archive/` — snapshot tecnici e materiale repo archiviato
- `../_LOG/` — evidenze locali, screenshot e probe

## Inizia qui

1. [QUICKSTART.md](QUICKSTART.md) — setup locale in 15 min
2. [ARCHITECTURE.md](ARCHITECTURE.md) — panoramica di sistema
3. [FEATURE_BOUNDARIES.md](FEATURE_BOUNDARIES.md) — mappa feature core e boundary
4. [FRONTEND_STRUCTURE.md](FRONTEND_STRUCTURE.md) oppure [BACKEND_STRUCTURE.md](BACKEND_STRUCTURE.md)
5. [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md) — se tocchi UI

## Canonical (10 docs root)

### Overview
- [ARCHITECTURE.md](ARCHITECTURE.md) — panoramica attuale di sistema
- [FEATURE_BOUNDARIES.md](FEATURE_BOUNDARIES.md) — dove entrano e dove finiscono funnel, payment, wallet, coupon/referral, account/admin

### Struttura
- [FRONTEND_STRUCTURE.md](FRONTEND_STRUCTURE.md) — struttura frontend Nuxt
- [BACKEND_STRUCTURE.md](BACKEND_STRUCTURE.md) — struttura backend Laravel
- [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md) — palette, primitive, grammatica UI

### Onboarding
- [QUICKSTART.md](QUICKSTART.md) — setup locale rapido
- [ONBOARDING.md](ONBOARDING.md) — percorso guidato per nuovi dev
- [CONTRIBUTING.md](CONTRIBUTING.md) — workflow, hook, branch, PR
- [DEBUGGING.md](DEBUGGING.md) — troubleshooting e errori frequenti

## Operations (`operations/`)

- [DEPLOY.md](operations/DEPLOY.md) — deploy produzione
- [GOLIVE_CHECKLIST.md](operations/GOLIVE_CHECKLIST.md) — checklist go-live
- [LEGAL_GOLIVE_CHECKLIST.md](operations/LEGAL_GOLIVE_CHECKLIST.md) — blocker legali/GDPR
- [STRIPE_LIVE_SETUP.md](operations/STRIPE_LIVE_SETUP.md) — setup Stripe live
- [BRT_PRODUCTION_SETUP.md](operations/BRT_PRODUCTION_SETUP.md) — setup BRT production
- [PUDO_FALLBACK_SETUP.md](operations/PUDO_FALLBACK_SETUP.md) — fallback PUDO
- [VISUAL_REGRESSION.md](operations/VISUAL_REGRESSION.md) — baseline Playwright

## Reference (`reference/`)

- [API_CONTRACT.md](reference/API_CONTRACT.md) — contratto API applicativo
- [GLOSSARIO.md](reference/GLOSSARIO.md) — lessico di dominio
- [FAQ_DEV.md](reference/FAQ_DEV.md) — domande ricorrenti dev
- [ROADMAP.md](reference/ROADMAP.md) — direzione e backlog
- [ARCHITECTURE_MAP.md](reference/ARCHITECTURE_MAP.md) — audit architetturale esteso
- [TEST_PLAN_MANUALE.md](reference/TEST_PLAN_MANUALE.md) — scenari E2E manuali

## Legal & Compliance (`legal/`)

- [SECURITY.md](legal/SECURITY.md) — policy sicurezza
- [GDPR_COMPLETO.md](legal/GDPR_COMPLETO.md) — registro trattamenti, export/delete, breach plan

## ADR (`adr/`)

- [001 — Sanctum SPA cookie-based auth](adr/001-sanctum-spa-auth.md)
- [002 — Prezzi in cents con moneyphp/money](adr/002-moneyphp-cents.md)
- [003 — Integrazione BRT diretta (no aggregator)](adr/003-brt-direct-integration.md)

## Storico

- [archive/](archive/) — documenti storici, snapshot, handoff non più canonici (non fonte di verità codice)

## Note

- `CLAUDE.md` (root) — configurazione operativa agenti AI; non fonte documentale prodotto
- Per capire il prodotto attuale: parti da `docs/README.md` (questo file) + file in `docs/` root
- Per recovery storico: `docs/archive/` o `../_archive/`
