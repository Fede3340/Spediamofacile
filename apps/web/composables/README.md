# Composables

Logica reattiva riusabile + binding lifecycle Vue. Auto-importati da Nuxt — niente import esplicito nei componenti.

## Convenzioni

- Naming: `use<Dominio><Azione>.js` (es. `useCart`, `useShipmentStepFlow`).
- Solo JavaScript + JSDoc (no TypeScript). Vue components in `<script setup>` plain.
- Stato globale → Pinia store in `~/stores/`. I composables consumano lo store.
- File ≤ 1000 LOC. Sopra: splitta o crea utility puri in `~/utils/`.

## Composable principali

- `useCart` — checkout, billing, wallet, coupon
- `usePreventivo` — calcolo prezzi funnel
- `useFunnel` — stepper "la-tua-spedizione"
- `usePudo` — punti BRT (mappa + lista)
- `usePayment` — Stripe instance + intent
- `useAuth` — sessione Sanctum
- `useShipmentStep*` — orchestrazione 4 step funnel
