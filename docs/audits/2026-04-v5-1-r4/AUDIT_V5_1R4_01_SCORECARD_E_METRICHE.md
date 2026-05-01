# Audit V5.1R4 - Scorecard e metriche

Data: 2026-04-29

## Verdetto

La repo contiene molte funzioni reali e utili, ma oggi non e abbastanza pulita, coerente e leggibile per essere considerata una repo professionale "da web agency" pronta al passaggio a un junior.

Il problema principale non e il peso in MB. Il problema e il carico cognitivo:

- troppi file-hub
- docs obsolete
- CI/Husky non allineati
- frontend con errori TypeScript
- CSS troppo globale
- design system non pienamente unico
- naming storico/generico

## Voti

| Area | Voto | Motivazione |
| --- | ---: | --- |
| Volume codice/cartelle | 56/100 | 829 file sorgente e 124.724 LOC circa. Il peso e basso in MB, ma alto come carico mentale. |
| Struttura cartelle | 50/100 | Base Laravel root + Nuxt `apps/web` sensata, ma docs/CI/Husky puntano ancora a `apps/api`/Inertia. |
| Leggibilita | 47/100 | Troppi file grandi in aree core: funnel, payment, indirizzi, CSS, admin. |
| Sintassi/gate | 46/100 | PHP lint ok, ma `vue-tsc` fallisce e ESLint segnala 401 errori. |
| Complessita | 42/100 | Dominio complesso, ma esiste molta complessita accidentale evitabile. |
| Lessico/naming | 54/100 | Termini dominio presenti, ma metadata e path storici indeboliscono professionalita. |
| Coerenza | 38/100 | Docs, CI, design system, payment scope e CSS non raccontano la stessa realta. |

Voto complessivo stimato: 47/100.

## Metriche rilevate

Escludendo `node_modules`, `vendor`, `.git`, `.nuxt`, `.output`, coverage, report, cache, logs e lockfile:

- File sorgente: circa 829
- LOC: circa 124.724
- Dimensione sorgente: circa 5.09 MB
- Frontend: circa 387 file
- Backend PHP: circa 403 file

## File piu critici per volume

| File | Righe circa | Problema |
| --- | ---: | --- |
| `apps/web/assets/css/shipment-flow.css` | 5320 | CSS funnel troppo centrale e difficile da manutenere. |
| `apps/web/assets/css/admin.css` | 3419 | Admin styling molto ampio e non feature-based. |
| `apps/web/assets/css/account.css` | 2122 | Shell account e pagine account concentrate in CSS grande. |
| `apps/web/assets/css/main.css` | 2036 | Foundation e componenti base mescolati a regole globali. |
| `apps/web/pages/la-tua-spedizione/[step].vue` | 1179+ | Orchestratore funnel troppo grande. |
| `apps/web/components/shipment/ShipmentStepPagamento.vue` | 677+ | Payment UI e bridge critico molto grande. |
| `apps/web/components/shipment/AddressFormFields.vue` | 683+ | Form indirizzi troppo grande e pieno di responsabilita. |
| `apps/web/composables/usePayment.ts` | 627+ | Payment boundary molto sensibile e lungo. |

## Interpretazione oggettiva

Una repo di questo tipo puo avere tante funzioni. Non deve pero costringere chi legge a capire tutto insieme.

La forma target deve essere:

- feature boundary chiari
- pochi entrypoint per feature
- docs vere
- gate verdi
- design system unico
- file mediamente piccoli
- business core testato prima di ogni refactor

