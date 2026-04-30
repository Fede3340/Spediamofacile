# Composables

Logica reattiva riusabile + binding lifecycle Vue. Auto-importati da Nuxt — niente import esplicito nei componenti.

## Convenzioni

- Naming: `use<Dominio><Azione>.ts` (es. `useCart`, `useShipmentStepFlow`).
- TypeScript con tipi reali sui parametri/ritorni (no `any`).
- Stato globale → Pinia store in `~/stores/`. I composables consumano lo store.
- File ≤ 1000 LOC. Sopra: splitta o crea utility puri in `~/utils/`.
- Se il composable non usa `ref/computed/onMounted/watch`, non è un composable: spostalo in `~/utils/`.

## Composable principali

- `useCart` — checkout, billing, wallet, coupon
- `useQuote` — calcolo prezzi funnel preventivo
- `useFunnel` — stepper "la-tua-spedizione"
- `usePudo` — punti BRT (mappa + lista)
- `usePayment` — Stripe instance + intent (CRITICAL Stripe-gated)
- `useAuth` — sessione Sanctum
- `useShipmentStep*` — orchestrazione 4 step funnel
