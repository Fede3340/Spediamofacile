# Utils

Funzioni **pure** (no reattività, no side-effect). Auto-importati.

## Convenzioni

- Naming: `<dominio>.js` o `<dominio>/<file>.js` per gruppi.
- Niente `ref()`, `reactive()`, `useState()`. Se serve reattività, va in `~/composables/`.
- Test unitari in `tests/unit/utils/`.

## Util principali

- `price.js` — `formatPrice(cents)`, parsing prezzi
- `date.js` — formatting date IT
- `shipment.js` — derivazione stato funnel
- `auth.js` — helpers redirect overlay login
- `discountPreview.js` — calcolo preview coupon/referral
- `shipmentFlow/presentation.js` — formatting label step funnel
