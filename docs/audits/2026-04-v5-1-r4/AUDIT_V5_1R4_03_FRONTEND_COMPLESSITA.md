# Audit V5.1R4 - Frontend e complessita

Data: 2026-04-29

## Giudizio

Il frontend Nuxt come scelta tecnologica e sensato. La struttura standard `pages`, `components`, `composables`, `stores`, `utils`, `assets` e coerente con Nuxt.

Il problema non e Nuxt. Il problema e come la complessita e stata distribuita:

- pagine orchestratrici troppo grandi
- CSS troppo centrale
- composables con troppi side effect
- componenti form/payment troppo ampi
- design system non abbastanza vincolante

Per un junior il frontend oggi e difficile. Non perche il dominio spedizioni sia impossibile, ma perche le responsabilita sono troppo aggregate.

## Aree difficili per un junior

### Funnel spedizione

File: `apps/web/pages/la-tua-spedizione/[step].vue`

Problemi:

- pagina molto grande
- coordina step, sessione, validazione, UI, payment bridge e debug
- contiene commento `CRITICAL` e avviso di non splittare

Miglioria:

- lasciare la pagina come orchestratore sottile
- spostare logica in feature boundary:
  - `shipment-flow/packages`
  - `shipment-flow/services`
  - `shipment-flow/addresses`
  - `checkout/payment`
  - `shipment-flow/session`

### Payment

File: `apps/web/composables/usePayment.ts`

Problemi:

- molto lungo
- contiene Stripe, 3DS, idempotency, pending payment e piu endpoint
- commenti dicono di non splittare senza E2E

Miglioria:

- non splittare subito la parte reattiva
- prima estrarre helper puri:
  - costruzione payload
  - traduzione errori
  - gestione pending key
  - guardie idempotency

### Indirizzi

File: `apps/web/components/shipment/AddressFormFields.vue`

Problemi:

- form enorme
- gestisce origin/dest, autocomplete, errori, assist, business fields, PUDO/domicilio

Miglioria:

- creare sotto-componenti:
  - `AddressNameFields`
  - `AddressStreetFields`
  - `AddressGeoFields`
  - `AddressContactFields`
  - `AddressBusinessFields`

Prima estrarre markup, non logica.

### CSS funnel

File: `apps/web/assets/css/shipment-flow.css`

Problemi:

- 5320 righe
- contiene colli, servizi, indirizzi, payment, cart totals e responsive
- import duplicati

Miglioria:

- spezzare in sezioni:
  - `shipment-shell.css`
  - `shipment-packages.css`
  - `shipment-services.css`
  - `shipment-addresses.css`
  - `shipment-payment.css`
  - `shipment-summary.css`

Fare solo dopo screenshot baseline.

## Cosa non fare

- Non riscrivere tutto il funnel.
- Non cancellare funzioni core.
- Non spostare file critici senza test.
- Non cambiare UI gia corretta solo per uniformare.
- Non fare un mega-refactor insieme a fix payment/BRT.

## Cosa fare

1. Correggere sintassi TypeScript.
2. Mettere verdi typecheck e build.
3. Ridurre import CSS duplicati.
4. Estrarre helper puri.
5. Spezzare CSS per feature.
6. Creare mini docs feature.
7. Solo dopo ridurre componenti grandi.

## Forma target

Un junior dovrebbe poter aprire una feature e capire:

- entrypoint UI
- composable di stato
- API chiamate
- modelli dati
- CSS relativo
- test minimi

Esempio target:

```text
features/shipment-flow/
  packages/
  services/
  addresses/
  summary/
  session/

features/checkout/
  payment/
  billing/
  order-context/

features/pudo/
  search/
  map/
  selection/
```

