# Documentazione SpediamoFacile

Indice della documentazione attiva del monorepo.

## Struttura

- `docs/` — doc canonici (overview, onboarding)
- `docs/operations/` — deploy e checklist go-live
- `docs/reference/` — contratto API
- `docs/legal/` — security e GDPR
- `docs/adr/` — Architecture Decision Records

## Inizia qui

1. [`ARCHITECTURE.md`](ARCHITECTURE.md) — panoramica di sistema
2. [`ONBOARDING.md`](ONBOARDING.md) — setup locale e percorso per nuovi dev

## Operations (`operations/`)

- [`DEPLOY.md`](operations/DEPLOY.md) — deploy produzione
- [`GOLIVE_CHECKLIST.md`](operations/GOLIVE_CHECKLIST.md) — checklist go-live

## Reference (`reference/`)

- [`API_CONTRACT.md`](reference/API_CONTRACT.md) — contratto API applicativo

## Legal & Compliance (`legal/`)

- [`SECURITY.md`](legal/SECURITY.md) — policy sicurezza
- [`GDPR_COMPLETO.md`](legal/GDPR_COMPLETO.md) — registro trattamenti, export/delete, breach plan

## ADR (`adr/`)

- [001 — Sanctum SPA cookie-based auth](adr/001-sanctum-spa-auth.md)
- [002 — Prezzi in cents con moneyphp/money](adr/002-moneyphp-cents.md)
- [003 — Integrazione BRT diretta (no aggregator)](adr/003-brt-direct-integration.md)

## Cleanup plan

- [`CLEANUP_PLAN_AI.md`](CLEANUP_PLAN_AI.md) — piano di refactor agency-grade applicato (storico)

## Note

- `CLAUDE.md` (root) — convenzioni operative per agenti AI; non fonte documentale di prodotto
- Per recovery storico: `git log` / commit history
