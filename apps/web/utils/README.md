# Utils

Funzioni **pure** (no reattività, no side-effect). Auto-importati.

## Convenzioni

- Naming: `<dominio>.ts` (kebab-case quando serve).
- Niente `ref()`, `reactive()`, `useState()`. Se serve reattività, va in `~/composables/`.
- Test unitari in `tests/unit/utils/`.

## Util principali

- `price.ts` — `formatPrice(cents)`, parsing prezzi
- `date.ts` — formatting date IT
- `shipment.ts` — derivazione stato funnel
- `auth.ts` — helpers redirect overlay login
- `discountPreview.ts` — calcolo preview coupon/referral
- `shipmentFlowPresentation.ts` — formatting label step funnel
- `shipmentStepHelpers.ts` — helper puri pagina `/la-tua-spedizione/[step]`
- `pudoHelpers.ts` — coordinate, distanze, normalizzazione PUDO
