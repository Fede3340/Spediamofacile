# SpediamoFacile

Monorepo della piattaforma **SpediamoFacile**: intermediazione spedizioni BRT con preventivo rapido, funnel ordine one-page, account cliente e console admin.

## Stack

- **Frontend**: Nuxt 4.1 + Vue 3.5 (JavaScript + JSDoc) + Pinia 3 + Tailwind CSS 4 + Nuxt UI 4
- **Backend**: Laravel 11 + Sanctum 4 + SQLite (dev) / Postgres (prod) + Redis
- **Pagamenti**: Stripe 18 (carta, 3DS), bonifico, wallet
- **Spedizioni**: integrazione BRT REST 3.x (etichette, tracking, PUDO)
- **Test**: Playwright + Vitest (frontend), PHPUnit (backend)

## Struttura repo

```
.
├── apps/web/   Frontend Nuxt 4.1
├── apps/api/  Backend Laravel 11
├── docs/                           Documentazione canonica
├── infra/                          Configurazioni infrastruttura
├── scripts/                        Tooling locale
├── .github/workflows/              CI/CD GitHub Actions
└── .husky/                         Pre-commit hooks
```

Tutto il resto (`_archive/`, `_LOG/`, `output/`, `_data/`, `docs/archive/`) è **gitignored**: contiene solo log/snapshot di lavoro locale, non parte del prodotto.

## Setup rapido

```bash
# 1. Clone + installa dipendenze root (husky + lint-staged)
git clone <repo-url>
cd spedizionefacile
npm install

# 2. Frontend
cd apps/web
npm install
cp .env.example .env
npm run dev   # http://localhost:3000

# 3. Backend (in altro terminale)
cd apps/api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve   # http://localhost:8000
```

## Documentazione

Partire da [`docs/README.md`](docs/README.md) per l'indice completo. Documenti chiave:

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — panoramica di sistema
- [`docs/ONBOARDING.md`](docs/ONBOARDING.md) — setup e percorso per nuovi dev
- [`docs/operations/DEPLOY.md`](docs/operations/DEPLOY.md) — procedure di deploy
- [`docs/operations/GOLIVE_CHECKLIST.md`](docs/operations/GOLIVE_CHECKLIST.md) — checklist go-live
- [`docs/reference/API_CONTRACT.md`](docs/reference/API_CONTRACT.md) — contratto API
- [`docs/legal/SECURITY.md`](docs/legal/SECURITY.md) — security baseline
- [`docs/legal/GDPR_COMPLETO.md`](docs/legal/GDPR_COMPLETO.md) — compliance GDPR
- [`docs/adr/`](docs/adr/) — Architecture Decision Records

## Quality gates

- **CI**: lint + typecheck + build + test E2E + security audit (`.github/workflows/ci.yml`)
- **Pre-commit**: lint-staged (frontend) + Pint (backend) via Husky
- **Commit messages**: convenzionali (`feat:`, `fix:`, `chore:`...) verificati con commitlint

## Convenzioni

- **Italiano** per commenti, doc, commit message, output utente
- **Palette**: teal `#095866` + arancione `#E44203` (mai blu)
- **Prezzi**: backend in cents, frontend `formatPrice()` divide per 100
- **Auth**: Sanctum SPA cookie, usare `useSanctumClient()` per chiamate API

Dettagli completi: [`CLAUDE.md`](CLAUDE.md) (anche per agenti AI).

## License

Proprietario — vedi [`LICENSE`](LICENSE).
