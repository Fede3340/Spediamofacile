# Audit V5.1R4 - Piano operativo sicuro

Data: 2026-04-29

## Obiettivo

Semplificare radicalmente la repo senza rompere il business core.

Core da mantenere:

- quick quote
- funnel spedizione
- colli
- servizi
- indirizzi
- PUDO
- carrello
- pagamento carta/bonifico/wallet
- account cliente
- admin
- wallet
- coupon/referral
- BRT
- tracking
- documenti/etichette/fatture

## Regola

Prima sicurezza e gate. Poi refactor.

Non si semplifica una repo con `vue-tsc` rotto e docs false. Prima la si mette in condizione di sapere quando si rompe.

## Fase 1 - Stabilizzazione immediata

1. Correggere `pricingBandsStore.ts`.
2. Correggere `preventivoStore.ts`.
3. Correggere `pudoStore.ts`.
4. Eseguire `cd apps/web && npm run typecheck`.
5. Correggere BRT webhook fail-open.
6. Eseguire test mirati PUDO/funnel/admin pricing.

Gate:

```bash
cd apps/web && npm run typecheck
php -l
composer validate
```

## Fase 2 - Docs e tooling veri

1. Riscrivere README.
2. Riscrivere ONBOARDING.
3. Riscrivere ARCHITECTURE.
4. Correggere CI.
5. Correggere Husky.
6. Correggere CODEOWNERS/dependabot se puntano path vecchi.
7. Aggiornare composer/package metadata.

Gate:

```bash
composer lint
cd apps/web && npm run lint
```

## Fase 3 - UI/design system

1. Definire token canonici.
2. Chiarire bottoni:
   - `cta`
   - `primary`
   - `secondary`
   - `danger`
   - `ghost`
3. Creare o consolidare:
   - `SfButton`
   - `SfInput`
   - `SfSegmentedControl`
   - `SfSwitch`
   - `SfSurface`
   - `SfPageHeader`
4. Rimuovere import CSS duplicati.

Gate:

- screenshot home
- screenshot funnel colli
- screenshot servizi
- screenshot indirizzi
- screenshot pagamento
- screenshot account/admin principali

## Fase 4 - Frontend feature refactor

Ordine:

1. Estrarre helper puri da `[step].vue`.
2. Spezzare CSS funnel per sezioni.
3. Ridurre `AddressFormFields.vue` via sotto-componenti markup-only.
4. Ridurre `ShipmentStepPagamento.vue` senza cambiare logica payment.
5. Ridurre `usePayment.ts` solo con E2E pagamento.

Regola:

- ogni estrazione deve avere comportamento identico
- niente nuovo design durante refactor logico
- niente refactor payment senza test carta/bonifico/wallet

## Fase 5 - Junior-friendly docs

Creare mini mappe:

- shipment-flow
- checkout/payment
- wallet
- coupon/referral
- BRT/PUDO
- account/admin

Ogni mappa:

- entrypoint
- store/composable
- API
- persistenza
- UI dove si vede
- test da lanciare
- cosa non toccare senza review

## Fase 6 - Verifica finale

Test automatici:

```bash
composer lint
php artisan test
cd apps/web && npm run typecheck
cd apps/web && npm run lint
cd apps/web && npm run test:unit
cd apps/web && npm run build
```

Test manuali:

- home quick quote -> funnel
- singolo collo
- multi-collo
- servizi extra
- domicilio
- PUDO
- pagamento carta
- bonifico
- wallet
- ordine in account cliente
- ordine in admin
- documenti/etichette se disponibili
- cookie banner
- breadcrumb
- pagine pubbliche

