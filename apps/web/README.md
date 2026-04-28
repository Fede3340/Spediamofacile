# SpediamoFacile Frontend (Nuxt)

Questa cartella contiene il frontend vivo di SpediamoFacile.

Non e' un progetto Nuxt generico: qui vivono le superfici core del prodotto:

- home e quick quote
- funnel canonico `/la-tua-spedizione/[step]`
- carrello e pagamento
- account cliente
- console admin
- tracking, guide, servizi e contenuti pubblici

## Da dove partire

Se stai entrando ora nel frontend, i riferimenti canonici sono:

- [../docs/FRONTEND_STRUCTURE.md](../docs/FRONTEND_STRUCTURE.md)
- [../docs/FEATURE_BOUNDARIES.md](../docs/FEATURE_BOUNDARIES.md)
- [../docs/DESIGN_SYSTEM.md](../docs/DESIGN_SYSTEM.md)

Entry point utili:

- `pages/index.vue` -> home + preventivo rapido
- `pages/la-tua-spedizione/[step].vue` -> funnel canonico
- `pages/account/**` -> account cliente e admin
- `composables/useCart.js` -> checkout/cart boundary
- `composables/usePayment.js` -> payment boundary
- `stores/shipmentFlowStore.ts` -> stato condiviso del funnel

## Regole importanti

- una sola route canonica per il funnel: `/la-tua-spedizione/[step]`
- `pages/preventivo.vue`, `pages/checkout.vue` e `pages/riepilogo.vue` sono compat legacy, non superfici concorrenti
- i file grandi del funnel e del pagamento vanno spezzati solo dopo avere fissato il comportamento corretto
- niente nuova complessita accidentale: evitare route duplicate, page-controller enormi e CSS monolitico nuovo

## Setup rapido

Da root workspace:

```bash
cd nuxt-spedizionefacile-master
npm ci
npm run dev
```

Dev locale canonico: `http://127.0.0.1:8787`

## Build

```bash
npm run build
```

## Cosa non mettere qui

- log locali
- screenshot
- note temporanee di audit
- documentazione storica o handoff

Quelli stanno nella workspace root, in aree separate dal runtime vivo.
