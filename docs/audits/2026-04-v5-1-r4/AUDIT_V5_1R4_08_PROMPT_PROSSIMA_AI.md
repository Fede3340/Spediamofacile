# Prompt per prossima AI

Usa questo prompt in un'altra AI per riprendere l'analisi e il lavoro.

```text
Lavori nella repo:
C:\Users\Feder\Desktop\spedizionefacile

Devi continuare un risanamento professionale della repo SpediamoFacile.

Tipo di prodotto:
Sito/intermediario spedizioni BRT con quick quote, funnel spedizione, carrello, pagamento, account cliente, admin, wallet, coupon/referral, PUDO, tracking, BRT, documenti/etichette/fatture.

Stack reale:
- Backend Laravel 11 alla root repo.
- Frontend Nuxt in apps/web.
- API Laravel modulari in routes/api/*.php.
- Webhook Stripe/BRT in routes/web.php.

Problema principale:
La repo contiene prodotto reale, ma e troppo complessa cognitivamente e non ancora professionale per handoff/junior. Non va riscritta da zero e non vanno tolte funzioni core. Va ridotta la complessita accidentale.

Documenti di audit da leggere prima:
- docs/reference/AUDIT_V5_1R4_00_INDEX.md
- docs/reference/AUDIT_V5_1R4_01_SCORECARD_E_METRICHE.md
- docs/reference/AUDIT_V5_1R4_02_PROVE_RIGHE_CRITICHE.md
- docs/reference/AUDIT_V5_1R4_03_FRONTEND_COMPLESSITA.md
- docs/reference/AUDIT_V5_1R4_04_REPO_TOOLING_DOCS.md
- docs/reference/AUDIT_V5_1R4_05_UI_DESIGN_SYSTEM.md
- docs/reference/AUDIT_V5_1R4_06_BACKEND_BRT_PAYMENT.md
- docs/reference/AUDIT_V5_1R4_07_PIANO_OPERATIVO.md
- docs/reference/REPO_FRONTEND_AUDIT_V5_1R4.md

Voto attuale stimato:
47/100.

Metriche:
- circa 829 file sorgente
- circa 124.724 LOC
- frontend circa 387 file
- backend PHP circa 403 file
- file grandi: shipment-flow.css 5320 righe, admin.css 3419, account.css 2122, main.css 2036, pages/la-tua-spedizione/[step].vue oltre 1100, usePayment.ts oltre 600

Problemi certi P0:
1. apps/web/stores/admin/pricingBandsStore.ts righe circa 168-172:
   TypeScript rotto con to_step/increment_cents spezzati.
2. apps/web/stores/preventivoStore.ts righe circa 23-28:
   annotazioni TypeScript spezzate.
3. apps/web/stores/pudoStore.ts righe circa 73-80:
   filter predicate corrotto e condizione fuori blocco.

Problemi P1:
1. README root falso: parla di Inertia/apps/api.
2. docs/ONBOARDING.md falso: dice "Niente Nuxt".
3. docs/ARCHITECTURE.md obsoleto: Inertia/apps/api.
4. CI e Husky puntano apps/api invece della root Laravel.
5. BRT webhook fail-open in produzione.
6. CSS shipment-flow importato piu volte.
7. Design system incoerente: #005961 vs #095866, primary vs cta.
8. composer/package naming generic: laravel/laravel, nuxt-app.
9. PayPal citato ma non realmente implementato come scope chiaro.
10. File locali/AI tracciati da decidere.

Regola di lavoro:
Non fare big bang.
Non cancellare funzioni core.
Non pulire database/cache utili.
Non stravolgere UI gia corretta.
Prima correggi cio che e oggettivamente rotto, poi semplifica.

Ordine operativo:
1. Correggere i tre TypeScript rotti.
2. Eseguire cd apps/web && npm run typecheck.
3. Correggere BRT webhook fail-closed in produzione.
4. Aggiornare README/ONBOARDING/ARCHITECTURE/CI/Husky ai path reali.
5. Rimuovere import CSS duplicati con screenshot baseline.
6. Unificare token colore e semantica bottoni.
7. Solo dopo iniziare refactor frontend:
   - helper puri da [step].vue
   - CSS funnel per sezioni
   - sotto-componenti indirizzi
   - pagamento solo con test E2E

Gate minimi:
- php -l
- composer validate
- composer lint
- php artisan test
- cd apps/web && npm run typecheck
- cd apps/web && npm run lint
- cd apps/web && npm run test:unit
- cd apps/web && npm run build

Manuale:
Testare home quick quote -> funnel -> colli -> servizi -> indirizzi/PUDO -> pagamento -> ordine -> account cliente/admin.

Obiettivo finale:
Repo piu semplice, leggibile, stabile, coerente e junior-friendly, mantenendo tutto il business core.
```

