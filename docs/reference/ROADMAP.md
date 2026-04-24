# Roadmap SpediamoFacile

## Sprint attivi
- Sprint 10 (W4): Repo health + documentazione
- Sprint 11 (W5): Go-live prep

## Debito tecnico post-v1.0

### Rinomina `nuxt-spedizionefacile-master` -> `nuxt-spedizionefacile-main`
**Stato**: POSPOSTA post-go-live (decisione Sprint 10)

**Motivazione**: la rinomina della cartella frontend richiede aggiornamento coordinato di:
- `Caddyfile`, `Caddyfile.production`, `Caddyfile.trycloudflare`
- `docker-compose.yml` (volumes)
- `render.yaml` (paths deploy)
- `.github/workflows/*.yml` (working-directory CI/CD)
- `.github/CODEOWNERS`
- Scripts `scripts/*.sh` / `scripts/*.ps1`
- Tutti i path in `docs/*.md`
- `README.md`, `DEPLOY.md`, `.gitignore`

Eseguire la rinomina pre-go-live significa rischiare rottura di CI/deploy durante la finestra critica. La rinomina va pianificata come task separato post-v1.0 (finestra di manutenzione annunciata), con branch dedicato, build/CI verdi prima del merge, e smoke test production.

**Tracking**: target Sprint 12 o W6 post-go-live.

### Altri debiti noti
- ~~Rimozione definitiva cartella `BrtRestApi-PUDO-EN` dalla root~~ (fatto Sprint 10: spostato in `docs/vendor/brt-pudo/`)
- Consolidamento `docs/CHANGELOG.md` (legacy animazioni) dentro `CHANGELOG.md` root (Keep-a-Changelog)
- Audit finale `.codex/` folder (verificare se ancora necessaria post-dismissione workflow Codex)

## Refactor backlog post-MVP

File oltre 700 righe individuati in audit Sprint 10 (escluso `pages/la-tua-spedizione/[step].vue` gia' in refactor).

Regola generale: puntare a **<500 righe** per file Vue e **<400 righe** per composable/service. File grandi rallentano onboarding junior e aumentano merge-conflict.

### `components/admin/AdminConsoleAnalytics.vue` (754 righe)
**Posizione**: `nuxt-spedizionefacile-master/components/admin/AdminConsoleAnalytics.vue:1-754`

**Suggerimento split**:
- Estrarre le sezioni grafici in sottocomponenti dedicati:
  - `AdminAnalyticsRevenueChart.vue` (grafico ricavi)
  - `AdminAnalyticsOrdersChart.vue` (grafico ordini)
  - `AdminAnalyticsKPICards.vue` (card KPI in alto)
- Estrarre logica fetch/aggregazione in composable:
  - `composables/useAdminAnalytics.ts` — fetch dei dati + reactive filters (date range, servizio, ecc.)
- Mantenere `AdminConsoleAnalytics.vue` come **container** (<250 righe) che orchestri i sottocomponenti.

**Beneficio**: riduce accoppiamento UI/logica, rende testabili i chart in isolamento, facilita la navigazione per junior.

### Nota su `components/shipment/StepServicesGrid.vue` (931 righe)
Escluso da questo backlog: cartella `components/shipment/*` in refactor attivo da altro agent (Sprint 10).
