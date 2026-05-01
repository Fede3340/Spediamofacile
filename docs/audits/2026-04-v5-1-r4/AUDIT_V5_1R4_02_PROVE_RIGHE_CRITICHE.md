# Audit V5.1R4 - Prove e righe critiche

Data: 2026-04-29

Questo documento elenca prove concrete con file, righe, perche sono problematiche, come cambiarle e cosa succede dopo la modifica.

## P0 - TypeScript rotto in admin pricing

File: `apps/web/stores/admin/pricingBandsStore.ts`

Righe osservate: 168-172 circa.

Codice incriminato:

```ts
to_step
} === normalized.length - 1 ? null : (row.to_step ?? row.from_step),
    increment_cents;
row.increment_cents,
;
```

Problema: codice non valido. Sembra un oggetto spezzato accidentalmente.

Come cambiarlo:

```ts
return {
    from_step: idx === 0 || !prev ? 1 : (prev.to_step ?? prev.from_step) + 1,
    to_step: idx === normalized.length - 1 ? null : (row.to_step ?? row.from_step),
    increment_cents: row.increment_cents,
};
```

Effetto: ripristina syntax store admin pricing e riduce un errore `vue-tsc`.

Rischio: basso se si verifica admin prezzi e type-check.

## P0 - TypeScript rotto in preventivo store

File: `apps/web/stores/preventivoStore.ts`

Righe osservate: 23-28 circa.

Codice incriminato:

```ts
let autoQuoteTimer;
 | null;
null;
let pendingQuotePromise;
 | null;
null;
```

Problema: annotazioni di tipo spezzate. Il codice non e TypeScript valido.

Come cambiarlo:

```ts
let autoQuoteTimer: ReturnType<typeof setTimeout> | null = null;
let pendingQuotePromise: Promise<unknown> | null = null;
```

Effetto: store preventivo piu leggibile e type-safe.

Rischio: medio-basso. Verificare quick quote home e avanzamento verso funnel.

## P0 - PUDO store corrotto

File: `apps/web/stores/pudoStore.ts`

Righe osservate: 73-80 circa.

Codice incriminato:

```ts
.filter((c) => { latitude: number, longitude; number; });
}
});
Number.isFinite(c.latitude) && Number.isFinite(c.longitude),
;
```

Problema: predicate non valido e condizione fuori dal blocco corretto.

Come cambiarlo:

```ts
const coords = points
    .map((p) => ({
        latitude: parseCoordinate(p?.latitude),
        longitude: parseCoordinate(p?.longitude),
    }))
    .filter((c) => Number.isFinite(c.latitude) && Number.isFinite(c.longitude));
```

Effetto: ripristina funzione di inferenza coordinate PUDO.

Rischio: medio. Testare ricerca PUDO nel funnel e pagina PUDO.

## P1 - README falso

File: `README.md`

Prove:

- riga 7: dichiara Laravel + Inertia + Vue + Tailwind + Vite
- riga 17: `cd spedizionefacile/apps/api`
- riga 44: Auth via Inertia shared props

Problema: stack e path non sono quelli reali.

Come cambiarlo:

- backend Laravel alla root
- frontend Nuxt in `apps/web`
- quickstart con comandi reali
- link ai documenti aggiornati

Effetto: onboarding reale.

Rischio: nullo runtime, alto valore operativo.

## P1 - Onboarding falso

File: `docs/ONBOARDING.md`

Prove:

- riga 23: `Niente Nuxt`
- riga 29: `cd apps/api`
- righe 52-57: flusso Inertia

Problema: un junior seguirebbe una mappa non piu vera.

Come cambiarlo:

- riscrivere per Nuxt + Laravel API
- aggiungere mappa feature
- spostare la parte Inertia in storico se serve

Effetto: meno confusione e meno errori iniziali.

## P1 - CI/Husky legacy

File: `.github/workflows/ci.yml`

Prove:

- riga 57: `working-directory: apps/api`
- righe 77-78: cache composer in `apps/api`
- riga 132: coverage in `apps/api/coverage.xml`

File: `.husky/pre-commit`

Prove:

- riga 22: cerca `^apps/api/.*\.php$`
- riga 25: `cd "$ROOT/apps/api"`

Problema: i controlli non controllano la repo viva.

Come cambiarlo:

- backend root
- frontend `apps/web`
- separare job PHP e Nuxt

Effetto: gate affidabili.

## P1 - BRT webhook fail-open

File: `app/Http/Controllers/Shipping/BrtWebhookController.php`

Righe osservate: 196-200 circa.

Codice incriminato:

```php
if (app()->isProduction()) {
    Log::warning('BRT webhook: nessuna protezione configurata in produzione. Configurare BRT_WEBHOOK_SECRET o BRT_WEBHOOK_ALLOWED_IPS.');
}

return null;
```

Problema: in produzione accetta anche senza secret/IP allowlist.

Come cambiarlo:

```php
if (app()->isProduction()) {
    Log::critical('BRT webhook non protetto in produzione.');
    return 'Webhook BRT non configurato in modo sicuro.';
}

return null;
```

Effetto: sicurezza piu forte sul boundary BRT.

Rischio: medio. Serve configurare env prima del deploy.

## P1 - CSS duplicato

Prove:

- `apps/web/assets/css/main.css:4` importa `shipment-flow.css`
- `apps/web/components/shipment/Preventivo.vue:10` importa `shipment-flow.css`
- `apps/web/pages/la-tua-spedizione/[step].vue:969-970` include due volte `shipment-flow.css`

Problema: cascade duplicata e rischio UI imprevedibile.

Come cambiarlo:

- scegliere un import canonico
- screenshot prima/dopo
- rimuovere duplicati uno alla volta

Effetto: meno caos CSS.

## P1 - Semantica bottoni incoerente

Prove:

- `apps/web/components/sf/SfButton.vue:49`: `primary` -> `btn-cta`
- `apps/web/assets/css/main.css`: esistono `.btn-cta` e `.btn-primary`

Problema: primary non ha significato stabile.

Come cambiarlo:

- `cta` arancione
- `primary` teal
- `secondary` neutro
- `danger` distruttivo
- `ghost` leggero

Effetto: azioni piu coerenti in tutto il sito.

## P2 - Naming generico

Prove:

- `composer.json`: `laravel/laravel`
- `apps/web/package.json`: `nuxt-app`
- `apps/web/README.md`: `nuxt-spedizionefacile-master`

Problema: repo sembra scaffold o recupero storico.

Come cambiarlo:

- aggiornare metadata
- aggiornare docs e CODEOWNERS

Effetto: maggiore professionalita e chiarezza.

