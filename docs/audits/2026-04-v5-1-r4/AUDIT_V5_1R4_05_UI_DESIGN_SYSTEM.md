# Audit V5.1R4 - UI e design system

Data: 2026-04-29

## Giudizio

La UI ha molte parti migliorate, ma il design system non e ancora abbastanza vincolante. Esistono primitive, ma non governano davvero tutto.

Il rischio e che ogni pagina risolva i problemi localmente: bottoni, switch, card, colori e spacing diventano simili ma non uguali.

## Prove

### Token colore non univoci

Prove:

- `apps/web/app.config.ts`: usa `#005961`
- `apps/web/assets/css/main.css`: `--color-brand-primary: #095866`
- `apps/web/tailwind.config.js`: `brand.teal: '#095866'`

Problema: due teal diversi vivono in layer diversi.

Fix:

- scegliere un token canonico
- mappare Nuxt UI, Tailwind e CSS variables allo stesso valore
- evitare hex hardcoded fuori token

### Bottoni semanticamente ambigui

Prove:

- `SfButton.vue`: `primary` mappa a `btn-cta`
- `main.css`: esistono `.btn-cta` e `.btn-primary`

Problema: "primary" puo voler dire arancione o teal.

Standard consigliato:

| Variante | Colore | Uso |
| --- | --- | --- |
| `cta` | arancione | Conversione: paga, calcola, compra, conferma commerciale. |
| `primary` | teal | Azione operativa principale dentro app. |
| `secondary` | neutro | Azione alternativa. |
| `danger` | rosso | Elimina, annulla distruttivo. |
| `ghost` | trasparente | Azione leggera o utility. |

### CSS duplicato

Prove:

- `main.css` importa `shipment-flow.css`
- `Preventivo.vue` importa `shipment-flow.css`
- `[step].vue` lo include due volte

Problema: cascade fragile e difficile da debuggare.

Fix:

- un solo import globale o route-level, non entrambi
- screenshot baseline prima di rimuovere

### Switch/toggle non uniformi

Prove:

- esiste `.sf-toggle`
- admin usa switch custom con classi inline e colori diretti

Problema: stesso pattern UI implementato piu volte.

Fix:

- creare `SfSegmentedControl` e `SfSwitch`
- usare gli stessi componenti in colli, domicilio/PUDO, pagamento, admin

### Card/surface frammentate

Prove:

- `sf-card`
- `admin-card`
- `shipment-stage-card`
- card funnel custom
- card account custom

Problema: layout simili ma non identici.

Fix:

- creare primitive:
  - `SfSurface`
  - `SfPanel`
  - `SfSectionHeader`
  - `SfEmptyState`
  - `SfDataList`

## Regola di migrazione

Non correggere pagina per pagina creando altre eccezioni.

Sequenza corretta:

1. definire token
2. definire primitive
3. mappare classi vecchie ad alias compatibili
4. migrare una feature alla volta
5. verificare screenshot/manuale

## Rischi se non si fa

- ogni nuova modifica grafica puo rompere altre pagine
- bottoni importanti non hanno gerarchia chiara
- switch e selezioni sembrano diversi tra funnel, account e admin
- un junior copia pattern sbagliati

