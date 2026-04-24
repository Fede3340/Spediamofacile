# _archive/ — Backup tecnico

Directory di backup. NON è `docs/archive/` (quella è storico documentale).

Regole:
- Se qualcosa serve al prodotto attuale, NON deve stare qui
- Se qualcosa serve solo come memoria tecnica, può stare qui

## Retention policy

- **≤ 2 mesi**: file `cleanup-*` eliminabili definitivamente se nessuno li ha reimportati
- **≤ 3 mesi**: refactor (`frontend-simplification-*`, `motion-system-refactor-*`) restano per reference
- **Permanenti**: feature intenzionalmente rimosse con README di riattivazione (`2026-04-20-features-rimosse/`)

## Inventario

| Cartella | Contenuto | Scadenza |
|---|---|---|
| `2026-04-20-features-rimosse/` | Blog, API Pro, Bulk CSV, Swagger, PWA+push, Scanner QR, SDI, SMS in-app | Permanente |
| `apple-google-pay-2026-04-20/` | Stub wallet express disattivati pre-launch | Permanente |
| `cleanup-2026-04-24-v2/` | OAuth Facebook+Apple, SMS backend, Claims, Plausible plugin, landing servizio | 2026-06-24 |
| `cleanup-2026-04-24-v3/` | Mail orfana, ExampleTest dummy | 2026-06-24 |
| `cleanup-features-2026-04-20/` | Componenti orfani + TypeScript composables originali | 2026-07-20 |
| `dev-preview-pages/` | Pagine DEV preview (home-hero sandbox) | 2026-07-24 |
| `frontend-simplification-2026-04-20/` | Backup pre-refactor frontend (componenti orfani, CSS superseded) | 2026-07-20 |
| `motion-system-refactor-2026-04-20/` | Animation tokens precedenti pre-`.sf-interactive` | 2026-07-20 |
| `payment-old-2026-04-20/` | Vecchia payment logic pre-Stripe hardened | 2026-07-20 |

## Riattivazione

Per ripristinare:
1. `git mv _archive/<cartella>/<file> <percorso-originale>`
2. Verifica import/reference + cache Nuxt
3. Test end-to-end

## Prossime date di purge definitivo

- **Dopo 2026-06-24**: eliminare `cleanup-2026-04-24-v2/` e `v3/` se zero reimport
- **Dopo 2026-07-20**: rivedere `cleanup-features-2026-04-20/composables-typescript/`, `frontend-simplification-*`

`git bundle` su `G:\` mantiene history completa per recovery definitivo.
