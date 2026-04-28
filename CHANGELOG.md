# CHANGELOG

Tutte le modifiche rilevanti al progetto sono documentate qui.
Formato: [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased] — Risanamento V5.1R4 in corso

> Questa sezione rappresenta lo stato REALE della repo al 2026-04-24.
> La versione 1.0.0 originariamente dichiarata "Go-live Production" non è ancora completa.
> Vedi piano `C:\Users\Feder\.claude\plans\smooth-meandering-quokka.md` per dettagli.

### Stato attuale (dati verificati filesystem)

**Frontend**: 73.156 LOC Nuxt 3 (da 85.645 → −14,6%).
**Backend**: 58.437 LOC Laravel 11 (include test + services aggiunti).
**Docs canonici**: 29 file attivi in `docs/` (93 file legacy in `docs/archive/`).
**Branch di sviluppo**: `risanamento-v5-1r4` (da `codex-work-2026-04-24-snapshot`).
**Bundle backup**: `G:\spedizionefacile-2026-04-24.bundle` (32 MB, 44 refs).

### Added (lavoro Codex 21-24 apr — stato REALE)

- Stripe integration con retry logic webhook e idempotency
- BRT integration come intermediario diretto (no aggregatori), payload sanitizzato per errore -68 (whitelist `senderCustomerCode`)
- Sentry FE+BE error tracking + performance monitoring
- Web Vitals tracking (LCP/INP/CLS/FCP/TTFB)
- Plausible + GA4 Consent Mode v2 analytics
- Health check endpoint `/api/health` (4 services)
- CI/CD GitHub Actions (workflow ci.yml + deploy.yml)
- Husky pre-commit + commit-msg Conventional Commits
- Visual regression Playwright (baseline in `apps/web/tests/visual/`)
- Unit tests backend (75 test / 353 assertion passati sui servizi pagamento/wallet/coupon/referral/BRT/pickup/documenti)
- E2E tests frontend Playwright (~10 spec, 20 passati / 5 falliti al 24/4 per label mismatch funnel)
- **3 componenti Sf\* canonici** in `components/sf/`: `SfConfirmDialog`, `SfModal`, `SfSkeleton`. Gli altri 7 (SfButton, SfCard, SfInput, SfBadge, SfIcon, SfEmptyState, SfToast, SfTooltip) sono stati creati e successivamente archiviati come orfani in `_archive/frontend-simplification-2026-04-20/components-orfani/sf/`.
- **178 design tokens** in `apps/web/assets/css/main.css:root`
- TrustBadges + NewsletterSignup components in `components/layout/`
- 4 error pages Laravel custom (404/500/503/403)
- Feedback widget beta (FeedbackWidget.vue)
- Beta whitelist middleware
- **29 docs canonici attivi**: README, QUICKSTART, ARCHITECTURE, ARCHITECTURE_MAP, FEATURE_BOUNDARIES, FRONTEND_STRUCTURE, BACKEND_STRUCTURE, API_CONTRACT, CONTRIBUTING, ONBOARDING, DEBUGGING, DESIGN_SYSTEM, GLOSSARIO, FAQ_DEV, ROADMAP, SECURITY, GDPR_COMPLETO, LEGAL_GOLIVE_CHECKLIST, GOLIVE_CHECKLIST, STRIPE_LIVE_SETUP, BRT_PRODUCTION_SETUP, PUDO_FALLBACK_SETUP, DEPLOY, VISUAL_REGRESSION + 3 ADR (001-sanctum, 002-moneyphp-cents, 003-brt-direct)
- Print CSS ordini/fatture/bordero
- Layer di dominio `apps/web/features/`:
  - `shipment-flow/` (7 file, usati da `composables/useShipmentStepPageOrchestration.js`)
  - `wallet-referral/` (2 file, usati da `composables/useCart.js`)

### Changed

- CSP production senza `unsafe-inline` script-src
- OAuth state da cookie a session + PKCE SHA256
- Email enumeration protection (response identico + timing normalization)
- File upload security (ImageSanitizer GD + FormRequest)
- Stripe `account_id` + `customer_id` encrypted at rest (migration `2026_04_20_000000_encrypt_existing_stripe_account_ids.php`)
- **Composables TypeScript → JavaScript puro** (REVERSE dell'ipotesi Codex iniziale): 71 file `.ts` originali archiviati in `_archive/frontend-simplification-2026-04-20/composables-typescript/71-ts-originali/`. Oggi **0 file `.ts` in `composables/`**, direzione allineata all'intenzione utente "meno linguaggi".
- SeoMeta coverage pagine pubbliche
- JSON-LD 6 schema (LocalBusiness, Organization, BreadcrumbList, Service, Article, FAQPage)
- Docker Apache → PHP-FPM alpine + nginx sidecar
- AuthOverlay modal rebuilt (fix bordo, overlay, chiusura inaspettata)
- Pagine Guide/Traccia/Contatti/Servizi rewrite allineate Prototipo
- Components top-level riorganizzati in sottocartelle: `components/layout/`, `components/shipment/`, `components/pudo/`
- Routes API Laravel splittate: `routes/api/{admin,auth,cart,claims,community,invoices,orders,payments,public,shipment}.php`

### Fixed (parziale)

- 5/5 security blockers chiusi (Stripe encryption, CSP, OAuth PKCE, email enum, file upload)
- BRT `ritiro_al_piano` non attiva più pickup automaticamente senza richiesta esplicita (fix grave)
- `single_order_only` anti-duplicazione ordini nel checkout
- BRT errore `-68` "Unrecognized field" **bypassato** con whitelist payload (sender dedotto da `senderCustomerCode`, `pParcelID` rimosso). **Fix reale rimandato post-launch con sandbox BRT** per testare candidati `parcels|parcelsData|parcelDetails`.

### Removed

- **Blu Tailwind da palette** (teal+arancione+neutri è la palette ferrea)
- **Hex hardcoded sostituiti con CSS variables** (vedi commit `4d93bd1` + `5f03184`)
- 5.984 righe CODEX_*/TEAM_LOG archiviati in `docs/archive/`
- 1.7 MB build logs (13 file + pw-last.log)
- Componenti orfani: useRiepilogo, AccountProSkeleton, Skeleton, OrderSummary, 20+ altri
- Feature non-core pianificate per archivio (Fase 3.2 risanamento): blog, API Pro, Bulk CSV, Swagger, PWA+push, Scanner QR, SDI fatturazione, SMS in-app

### Security

- 5/5 security blockers chiusi
- CSP production hardened (enforce dopo 48h report-only)
- OAuth PKCE SHA256 + state session-based timing-safe hash_equals
- Password reset generic response + timing jitter 200-300ms
- Image upload GD re-encoding strippa EXIF + magic byte check

### Discrepanze corrette in questo CHANGELOG (onestà)

Questa versione corregge claim inesatti di un CHANGELOG precedente:

| Claim precedente | Realtà verificata |
|---|---|
| "10 SF atomic components library" | **3 reali** in `components/sf/`; 7 orfani archiviati |
| "174 design tokens main.css" | **178 design tokens** (contati in `:root`) |
| "T3.6.5 preservato byte-identical durante split [step].vue" | **Falso**: `pages/la-tua-spedizione/[step].vue` è **1.263 LOC non splittato**. Split rinviato post-launch (rischio regressione funnel). |
| "Composables 20+ migrati .js → .ts con 21 interfaces" | **Invertito**: direzione corretta è `.ts → .js`. 71 `.ts` archiviati, 0 `.ts` in composables oggi. |
| "BRT production ready" | **Parziale**: integrazione BRT funziona con payload sanitizzato, errore -68 bypassato, fix reale post-launch. |

### Non ancora fatto (Risanamento V5.1R4 in corso)

Vedi piano `.claude/plans/smooth-meandering-quokka.md`:

- [ ] Fase 2A — Fix pagamento disconnect utente + perdita dati durante 3DS Stripe
- [ ] Fase 2B — Fix FAQ home che si chiudono al click (accordion state)
- [ ] Fase 2C — Unificare cookie banner variants `--dense`/`--flow` per coerenza sitewide
- [ ] Fase 2D — Unificare spaziatura pagine pubbliche con tokens `--spacing-page-*`
- [ ] Fase 3.1 — Eliminare 8 `LEGGERE-QUI.md` redirect-only (mantenere solo `app/Services/Brt/`)
- [ ] Fase 3.2 — Audit incrociato + archivio frontend+backend coordinato feature non-core
- [ ] Fase 3.3 — Dedupe CSS duplicati `.sf-segmented-control`, `.sf-card` + riduzione tokens 178 → 60-80 essenziali
- [ ] Fase 4 — Palette check `blue-|indigo-|sky-|slate-` + eliminazione AI-slop grafico + allowlist documentata
- [ ] Fase 5 — Test manuali sistematici 20+ scenari V5.1R4 (funnel multi-collo, PUDO, contrassegno, pagamenti carta/bonifico/wallet/PayPal, coupon/referral end-to-end, wallet top-up, post-pagamento, account/admin)
- [ ] Fase 6.1 — Docs finali ≤ 10 canonici + 3 ADR
- [ ] Fase 6.2 — Copia G:\ pennina senza riferimenti AI
- [ ] Fase 6.3 — Documento grafico interattivo HTML standalone BE+FE desktop+mobile
- [ ] Fase 6.4 — CHANGELOG finale con numeri post-risanamento + `CHANGELOG-VERIFY.md` riproducibile
- [ ] Fase 6.5 — Commit finale taggato `v1.0.0-real`

### Bug noti aperti (da sistemare in Fase 2)

| Bug | Stato |
|---|---|
| Pagamento Stripe disconnette utente + perde dati compilati | 🔴 Aperto — Fase 2A prioritaria |
| FAQ home si chiudono "in massa" al click | 🔴 Aperto — Fase 2B |
| Cookie banner percepito diverso home vs pagine interne | 🟡 Non-bug tecnico (stesso componente), varianti `--dense`/`--flow` da uniformare — Fase 2C |
| Spaziatura non uniforme pagine pubbliche | 🔴 Aperto — Fase 2D |
| BRT errore -68 | 🟠 Bypassato con whitelist payload — fix reale post-launch |
| Artefatti grafici durante caricamento | 🔴 Aperto — da investigare |
| `.tmp-repro-422.mjs` runtime debug | 🟡 File debug Playwright per errore 422 submit — investigazione aperta |

---

[Unreleased]: https://github.com/Boop91/spedizionefacile/compare/main...risanamento-v5-1r4
