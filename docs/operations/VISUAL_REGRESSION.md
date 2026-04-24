# Visual Regression — Sprint 9.5

Sistema di baseline visuale basato su **Playwright native snapshots** (zero costi, nessun servizio esterno).

## Perche' Playwright e non Percy/Chromatic

- Integrato con lo stack di test E2E gia' presente
- Baseline committati in repo (PNG) -> review visuale in PR diretta
- Aggiornamento con `--update-snapshots`
- Se il team cresce oltre ~50 pagine o serve review collaborativa, valutare Percy/Chromatic

## Pagine coperte (15 critiche)

| Route | Nome baseline | Auth |
|-------|---------------|------|
| `/` | homepage | - |
| `/preventivo` | preventivo-redirect | - |
| `/la-tua-spedizione/2` | funnel-colli | - |
| `/la-tua-spedizione/2?step=servizi` | funnel-servizi | - |
| `/la-tua-spedizione/2?step=indirizzi` | funnel-indirizzi | - |
| `/la-tua-spedizione/2?step=pagamento` | funnel-pagamento | - |
| `/servizi` | servizi-index | - |
| `/guide` | guide-index | - |
| `/contatti` | contatti | - |
| `/faq` | faq | - |
| `/chi-siamo` | chi-siamo | - |
| `/traccia-spedizione` | traccia-spedizione | - |
| `/account` | account-dashboard | customer |
| `/account/amministrazione` | admin-console | admin |
| `/account/amministrazione/utenti` | admin-utenti | admin |

Viewport matrix (vedi `playwright.config.ts`):

- `chromium` -> 1440x900 (desktop)
- `tablet` -> 768x1024
- `mobile-chrome` -> 375x812 (Pixel 5)

Totale: **15 pagine x 3 viewport = 45 snapshot**.

## Threshold

Configurato in `playwright.config.ts` -> `expect.toHaveScreenshot`:

- `threshold: 0.001` (0.1% tolerance per pixel)
- `maxDiffPixels: 100`
- `animations: 'disabled'`
- `caret: 'hide'`

Se un test diventa flaky, preferire uno di questi approcci (in ordine):

1. Freeze animation extra via `addStyleTag` (gia' presente nello spec)
2. `waitForLoadState('networkidle')` + timeout specifico
3. Mask di regioni dinamiche (es. timestamp, ads): `mask: [page.locator('.dynamic-banner')]`
4. Bump `maxDiffPixels` a 300 solo per quel test, mai globalmente

## Comandi

### Generare baseline (prima esecuzione o aggiornamento)

```bash
cd nuxt-spedizionefacile-master

# Baseline desktop
npx playwright test tests/e2e/visual-regression.spec.ts --project=chromium --update-snapshots

# Baseline mobile
npx playwright test tests/e2e/visual-regression.spec.ts --project=mobile-chrome --update-snapshots

# Baseline tablet
npx playwright test tests/e2e/visual-regression.spec.ts --project=tablet --update-snapshots

# Tutti i viewport insieme
npx playwright test tests/e2e/visual-regression.spec.ts --update-snapshots
```

Pre-requisito: storage state auth generati da `auth.setup.spec.ts`:

```bash
npx playwright test tests/e2e/auth.setup.spec.ts
```

Output: `output/playwright/auth/{customer,pro,admin}.json`.

### Verificare regressioni (CI mode)

```bash
npx playwright test tests/e2e/visual-regression.spec.ts
```

Exit code != 0 se diff > 0.1%. Diff images in `test-results/<test>/`:

- `*-expected.png` -> baseline
- `*-actual.png` -> screenshot corrente
- `*-diff.png` -> mappa differenze

### Baseline storage

Snapshot committati in:

```
tests/e2e/visual-regression.spec.ts-snapshots/
  homepage-chromium-win32.png
  homepage-mobile-chrome-win32.png
  homepage-tablet-win32.png
  ...
```

**NON** aggiungere a `.gitignore` — i baseline sono parte del repo.

## Workflow PR con UI change intenzionale

1. Fai la modifica UI
2. Esegui test: `npx playwright test tests/e2e/visual-regression.spec.ts`
3. Fallisce come atteso -> review dei diff in `test-results/`
4. Se il cambio e' corretto: `--update-snapshots` per rigenerare
5. Commit dei PNG aggiornati insieme alle modifiche UI
6. In PR: il reviewer vede sia il codice sia i PNG cambiati (binary diff)

## CI integration

Job Playwright in `.github/workflows/ci.yml`:

- Matrix `[chromium, mobile-chrome]`
- Run dopo nuxt-build (artefatto `.output` scaricato)
- Fail del job se regressione > threshold
- Upload automatico dei diff PNG come artefatto (`visual-regression-diffs-<project>`)
- Retention 14 giorni

## Troubleshooting

**Test flaky sul CI ma non in locale**
-> OS differente (snapshot sono platform-specific: `-win32`, `-linux`, `-darwin`). Generare su WSL/docker Linux prima di pushare, oppure lasciare che il primo run CI generi i baseline Linux.

**Pagina auth blocca il test**
-> `authStateFiles.customer|admin` non esiste. Eseguire `auth.setup.spec.ts` prima.

**Font rendering diverso**
-> I font di sistema possono cambiare tra OS. Usare `--workers=1` e fissare il rendering via `chromium` flag se necessario, oppure aumentare `maxDiffPixels` per quel test.
