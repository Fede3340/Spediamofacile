# Stores Pinia

Stato globale ispezionabile in Vue DevTools. Auto-importati.

## Convenzioni

- Naming: `<dominio>Store.ts` (es. `cartStore`, `authModalStore`).
- Setup syntax (`defineStore('name', () => { ... })`), non options API.
- Solo state + getters + actions; niente lifecycle Vue (quello vive nei composables).
- Hydration SSR via `useState` o cookie sigillato (vedi `authStore`).

## Store principali

- `cartStore` — carrello + billing + wallet
- `authStore` / `authModalStore` — sessione + modal login
- `shipmentFlowStore` — stato step funnel
- `pudoStore` — selezione PUDO + filtri
- `confirmDialogStore` — dialog di conferma globale
- `admin/pricingBandsStore` — bande pricing admin
