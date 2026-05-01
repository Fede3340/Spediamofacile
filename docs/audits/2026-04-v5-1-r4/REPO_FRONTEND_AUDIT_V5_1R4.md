# Repo e Frontend Audit V5.1R4

Data audit: 2026-04-29

Scope: repo `spedizionefacile`, con focus su frontend Nuxt, struttura repo, tooling, documentazione, coerenza UI, payment/BRT/PUDO e leggibilita per sviluppatori junior.

Questo documento consolida l'analisi fatta in lettura. Non sostituisce i test: serve come mappa pratica di cosa correggere, in quale ordine e perche.

## Verdetto sintetico

La repo contiene un prodotto reale e molte funzioni core gia presenti: preventivo, funnel spedizione, carrello, checkout, account cliente, admin, wallet, coupon/referral, PUDO, tracking, BRT, documenti e fatture.

Il problema principale non e il peso in MB. Il problema e la complessita accidentale:

- documentazione non allineata al codice vivo
- CI/Husky puntati a path legacy
- frontend con errori TypeScript reali
- CSS troppo centrale e importato piu volte
- design system non completamente unificato
- file-hub troppo grandi nel funnel, pagamento, indirizzi e admin
- naming e metadata ancora generici o storici

Voto complessivo stimato: 47/100.

## Scorecard

| Area | Voto | Motivo breve |
| --- | ---: | --- |
| Volume codice/cartelle | 56/100 | Non enorme per MB, ma 829 file sorgente e 124k LOC creano molto carico cognitivo. |
| Struttura cartelle | 50/100 | Base Laravel root + Nuxt in `apps/web` sensata, ma docs/CI/Husky puntano ancora a `apps/api` e Inertia. |
| Leggibilita | 47/100 | Troppi file-hub critici e commenti "non splittare" su aree core. |
| Sintassi/gate | 46/100 | Backend PHP lint ok, ma frontend type-check fallisce e ESLint segnala molti errori. |
| Complessita | 42/100 | Dominio complesso, ma accoppiamento e centralizzazione aumentano rischio bug. |
| Lessico/naming | 54/100 | Termini dominio presenti, ma metadata e path vecchi sono incoerenti. |
| Coerenza | 38/100 | Docs, CI, design token, bottoni e payment scope raccontano cose diverse. |

## Metriche raccolte

Escludendo `node_modules`, `vendor`, `.git`, `.nuxt`, `.output`, coverage/report/cache/log e lockfile:

- File sorgente circa: 829
- LOC circa: 124.724
- Dimensione sorgente circa: 5.09 MB
- Frontend: circa 387 file
- Backend PHP: circa 403 file

File ad alto carico cognitivo:

- `apps/web/assets/css/shipment-flow.css`: 5320 righe
- `apps/web/assets/css/admin.css`: 3419 righe
- `apps/web/assets/css/account.css`: 2122 righe
- `apps/web/assets/css/main.css`: 2036 righe
- `apps/web/pages/la-tua-spedizione/[step].vue`: oltre 1100 righe
- `apps/web/components/shipment/ShipmentStepPagamento.vue`: circa 677/716 righe
- `apps/web/components/shipment/AddressFormFields.vue`: circa 683/737 righe
- `apps/web/composables/usePayment.ts`: circa 627/682 righe

## Prove principali

### 1. Frontend TypeScript rotto

File: `apps/web/stores/admin/pricingBandsStore.ts`

Righe osservate:

```ts
return {
    from_step: idx === 0 || !prev ? 1 : (prev.to_step ?? prev.from_step) + 1,
    to_step
} === normalized.length - 1 ? null : (row.to_step ?? row.from_step),
    increment_cents;
row.increment_cents,
;
```

Problema: non e TypeScript valido. La funzione sembra voler restituire un oggetto normalizzato, ma il codice e stato spezzato.

Fix sicuro:

```ts
return {
    from_step: idx === 0 || !prev ? 1 : (prev.to_step ?? prev.from_step) + 1,
    to_step: idx === normalized.length - 1 ? null : (row.to_step ?? row.from_step),
    increment_cents: row.increment_cents,
};
```

Effetto atteso: `vue-tsc` smette di fallire su questo store e admin pricing torna analizzabile.

### 2. Store preventivo con annotazioni spezzate

File: `apps/web/stores/preventivoStore.ts`

Righe osservate:

```ts
let autoQuoteTimer;
 | null;
null;
let pendingQuotePromise;
 | null;
null;
```

Problema: frammenti di tipo TypeScript separati dal binding. Questo blocca il type-check.

Fix sicuro:

```ts
let autoQuoteTimer: ReturnType<typeof setTimeout> | null = null;
let pendingQuotePromise: Promise<unknown> | null = null;
```

Effetto atteso: preventivo rapido piu leggibile e type-safe.

### 3. Store PUDO con predicate corrotto

File: `apps/web/stores/pudoStore.ts`

Righe osservate:

```ts
const coords = points
    .map((p) => ({ latitude: parseCoordinate(p?.latitude), longitude: parseCoordinate(p?.longitude) }))
    .filter((c) => { latitude: number, longitude; number; });
}
});
Number.isFinite(c.latitude) && Number.isFinite(c.longitude),
;
```

Problema: il filtro e rotto, la condizione e finita fuori contesto e la funzione non puo essere affidabile.

Fix sicuro:

```ts
const coords = points
    .map((p) => ({
        latitude: parseCoordinate(p?.latitude),
        longitude: parseCoordinate(p?.longitude),
    }))
    .filter((c) => Number.isFinite(c.latitude) && Number.isFinite(c.longitude));
```

Effetto atteso: PUDO/localizzazione torna gestibile senza sintassi invalida.

### 4. Documentazione architetturale falsa

File: `README.md`

Prove:

- riga 7: dichiara `Laravel 11 + Inertia 2 + Vue 3.5 + Tailwind 4 + Vite 5`
- riga 17: comanda `cd spedizionefacile/apps/api`
- riga 44: parla di `Auth: sessione Laravel + Inertia shared props`

Problema: il codice vivo usa frontend Nuxt in `apps/web`, mentre il backend Laravel e alla root.

Fix sicuro:

- riscrivere quickstart root Laravel + Nuxt `apps/web`
- spostare eventuali riferimenti Inertia in archivio storico
- indicare chiaramente quali comandi avviare per locale e preview

Effetto atteso: un junior non segue istruzioni false.

### 5. Onboarding contrario alla realta

File: `docs/ONBOARDING.md`

Prove:

- riga 23: `Niente Nuxt, niente SSR esterno`
- riga 29: `cd apps/api`
- righe 52-57: flusso Inertia controller/page

Problema: il documento e per un'architettura precedente.

Fix sicuro:

- riscrivere onboarding come "Laravel API root + Nuxt app"
- aggiungere mappa feature: shipment-flow, checkout, wallet, coupon/referral, BRT/PUDO, account/admin

Effetto atteso: onboarding realmente utile.

### 6. CI/Husky puntati a path legacy

File: `.github/workflows/ci.yml`

Prove:

- riga 57: `working-directory: apps/api`
- righe 77-78: cache composer in `apps/api`
- riga 132: coverage in `apps/api/coverage.xml`

File: `.husky/pre-commit`

Prove:

- riga 22: controlla solo `^apps/api/.*\.php$`
- riga 25: `cd "$ROOT/apps/api"`

Problema: i gate automatici non rappresentano il codice reale.

Fix sicuro:

- backend job alla root
- frontend job in `apps/web`
- pre-commit PHP sulla root Laravel
- pre-commit frontend in `apps/web`

Effetto atteso: CI e hooks diventano affidabili.

### 7. Webhook BRT fail-open in produzione

File: `app/Http/Controllers/Shipping/BrtWebhookController.php`

Righe osservate:

```php
if (app()->isProduction()) {
    Log::warning('BRT webhook: nessuna protezione configurata in produzione. Configurare BRT_WEBHOOK_SECRET o BRT_WEBHOOK_ALLOWED_IPS.');
}

return null;
```

Problema: se in produzione mancano secret e allowed IP, il webhook viene comunque accettato.

Fix sicuro:

```php
if (app()->isProduction()) {
    Log::critical('BRT webhook non protetto in produzione.');
    return 'Webhook BRT non configurato in modo sicuro.';
}

return null;
```

Effetto atteso: boundary BRT fail-closed in produzione.

### 8. CSS funnel importato piu volte

Prove:

- `apps/web/assets/css/main.css:4`: importa `shipment-flow.css`
- `apps/web/components/shipment/Preventivo.vue:10`: importa `shipment-flow.css`
- `apps/web/pages/la-tua-spedizione/[step].vue:969-970`: include due volte `shipment-flow.css`

Problema: rischio cascata duplicata, sovrascritture imprevedibili e debugging visuale difficile.

Fix sicuro:

- scegliere un solo import canonico
- prima fare screenshot baseline su funnel
- rimuovere duplicati uno alla volta

Effetto atteso: meno CSS globale e meno regressioni casuali.

### 9. Design system incoerente

Prove:

- `apps/web/app.config.ts`: usa `#005961`
- `apps/web/assets/css/main.css`: definisce `--color-brand-primary: #095866`
- `apps/web/tailwind.config.js`: definisce `brand.teal: '#095866'`
- `apps/web/components/sf/SfButton.vue`: `primary` mappa a `btn-cta`
- `main.css`: contiene sia `.btn-cta` sia `.btn-primary`

Problema: non e chiaro se primary sia arancione o teal.

Standard consigliato:

- `cta`: arancione, conversione commerciale
- `primary`: teal, azione operativa/sistema
- `secondary`: neutro
- `danger`: distruttivo
- `ghost`: azione leggera

Fix sicuro:

- aggiungere alias semantici senza cambiare subito tutte le classi
- migrare pagina per pagina dopo screenshot

Effetto atteso: bottoni coerenti in funnel, account, admin e pagine pubbliche.

### 10. Naming/metadata generici o vecchi

Prove:

- `composer.json`: `"name": "laravel/laravel"`
- `apps/web/package.json`: `"name": "nuxt-app"`
- `apps/web/README.md`: cita `nuxt-spedizionefacile-master`
- `.github/CODEOWNERS`: path storici `nuxt-spedizionefacile-master`

Problema: primo impatto poco professionale e ownership non chiara.

Fix sicuro:

- aggiornare metadata e docs
- non rinominare cartelle runtime prima di avere CI verde

Effetto atteso: repo piu leggibile e professionale.

### 11. Scope PayPal incoerente

Prove:

- `apps/web/composables/useFaqs.ts`: dice che PayPal non e gestito
- `app/Models/Transaction.php`: commenti/mapping citano `paypal`

Problema: scope launch non chiaro.

Decisione necessaria:

- se PayPal e launch, implementare flusso reale
- se PayPal e post-launch, togliere riferimenti dal modello UI/docs o lasciarlo come enum legacy documentato

Effetto atteso: meno ambiguita business.

### 12. File locali/AI tracciati

File tracciati osservati:

- `.claude/launch.json`
- `.claude/preview-proxy.js`
- `.claude/scheduled_tasks.lock`
- `.claude/settings.local.json`
- `.codex/config.toml`
- `.local/qa/playwright/design-audit/*.png`

Problema: se non sono strumenti canonici del team, aumentano rumore e confusione.

Fix sicuro:

- decidere cosa e tooling ufficiale
- spostare il resto in `.gitignore` o archivio non runtime

Effetto atteso: root piu pulita e piu leggibile.

## Fonti tecniche usate come riferimento

- Nuxt directory structure: struttura standard `assets`, `components`, `composables`, `pages`, `plugins`, `utils`, `app.vue`, `app.config.ts`.
- Vue style guide: componenti chiari, props definite, naming coerente.
- Vue performance: code splitting e componenti piu piccoli aiutano caricamento e manutenibilita.
- Laravel migrations/schema dump: schema dump valido, ma va documentato bene.
- GitHub Actions workflow syntax: `working-directory` deve puntare alle cartelle reali.
- Stripe security: webhook e pagamenti devono essere verificati e fail-safe.

## Piano di miglioramento sicuro

### Fase 1: mettere in sicurezza

1. Correggere i tre file TypeScript rotti.
2. Eseguire `npm run typecheck`.
3. Correggere README, ONBOARDING, ARCHITECTURE.
4. Correggere CI/Husky sui path reali.
5. Rendere BRT webhook fail-closed in produzione.

### Fase 2: stabilizzare UI e tooling

1. Rimuovere import CSS duplicati.
2. Unificare token colore.
3. Chiarire semantica `cta` vs `primary`.
4. Aggiornare metadata package/composer.
5. Decidere cosa fare dei file locali tracciati.

### Fase 3: semplificare frontend senza rompere

1. Estrarre helper puri da `la-tua-spedizione/[step].vue`.
2. Spezzare `shipment-flow.css` per sezioni o feature.
3. Rendere `AddressFormFields.vue` piu piccolo con sotto-componenti.
4. Ridurre `usePayment.ts` solo dopo test E2E pagamento.
5. Documentare boundary feature.

### Fase 4: junior-friendly

Creare mappe brevi per:

- shipment-flow
- checkout/payment
- wallet
- coupon/referral
- BRT/PUDO
- account/admin

Ogni mappa deve dire:

- dove entra
- chi decide
- dove persiste
- dove si vede
- cosa non toccare senza test

## Gate consigliati

Minimo per chiudere un blocco:

```bash
php -l
composer validate
composer lint
php artisan test
cd apps/web && npm run typecheck
cd apps/web && npm run lint
cd apps/web && npm run test:unit
cd apps/web && npm run build
```

Manuale:

- home quick quote -> funnel
- colli singoli/multipli
- servizi
- indirizzi domicilio/PUDO
- pagamento carta/bonifico/wallet
- ordine creato
- account cliente
- admin ordini/spedizioni
- BRT/tracking/documenti quando disponibili

## Regola finale

Non semplificare togliendo dominio.

Semplificare significa:

- meno file-hub
- meno duplicazioni
- meno documenti falsi
- meno CSS globale
- meno naming storico
- gate verdi
- feature boundary chiari
- codice che un junior puo leggere senza paura nella maggior parte delle aree

