# ADR 007 — TypeScript Strategy (TS dove serve, JS dove non aggiunge valore)

Data: 2026-05-02
Status: Accepted

## Contesto

Il frontend Nuxt 4 contiene tre tipi di file rilevanti:

1. **Composables/utils/stores** (`*.ts`) — logica pura, riusata, testabile.
   Ogni composable è punto d'incontro fra n componenti e backend API: i tipi degli
   argomenti, dei ritorni e delle response API riducono drasticamente i bug di
   integrazione.
2. **Componenti single-file Vue** (`*.vue`) — template + script setup.
   I template Vue 3 sono templating string-based: il sistema di tipi non scende
   nel template. Aggiungere `lang="ts"` allo script setup tipizza la logica
   interna del componente, ma il vero contratto fra componenti passa tramite
   props/emits — già tipizzabili con `defineProps<{}>()` runtime.
3. **Config Nuxt/Vite** — dichiarazioni `defineNuxtConfig`, `defineConfig`
   già tipizzate via JSDoc o tipi inferiti dai loro factory.

## Decisione

| File | Linguaggio | Motivazione |
|---|---|---|
| `composables/*.ts` | **TypeScript** | Firma esplicita, tipi cross-domain, JSDoc minimo |
| `utils/*.ts` | **TypeScript** | Helper riusati 5+ posti, tipi entità (Order, Cart, Address) condivisi |
| `stores/*.ts` (Pinia) | **TypeScript** | State globale: tipo store = contratto applicativo |
| `components/**/*.vue` | **JavaScript** + `defineProps`/`defineEmits` runtime | Template non beneficia di TS, props già tipizzabili, scope locale |
| `pages/**/*.vue` | **JavaScript** | Idem componenti, page-level: orchestrazione bassa |
| `nuxt.config.ts` | **TypeScript** (factory) | Già tipizzato da Nuxt |
| `vite.config.ts` | **TypeScript** (factory) | Già tipizzato da Vite |

### Esempio: `composables/useCart.ts` (TS)

```ts
export interface CartItem {
  id: number
  name: string
  priceCents: number
  quantity: number
}

export function useCart(): {
  items: Ref<CartItem[]>
  totalCents: ComputedRef<number>
  addItem: (item: CartItem) => Promise<void>
} {
  // ...
}
```

I caller `.vue` usano `useCart()` con autocompletamento e safety, **senza
dover scrivere TS nel template**.

### Esempio: `components/cart/CartSummary.vue` (JS)

```vue
<script setup>
import { computed } from 'vue'

const props = defineProps({
  items: { type: Array, required: true },
  totalCents: { type: Number, required: true },
})

const totalEuro = computed(() => (props.totalCents / 100).toFixed(2))
</script>

<template>
  <div class="cart-summary">
    Totale: {{ totalEuro }} €
  </div>
</template>
```

Le props sono validate runtime, il composable `useCart()` resta tipizzato a
monte, e il template non deve sapere niente di TS.

## Conseguenze

### Positive

- **Tipi dove servono davvero**: bug di integrazione fra composable e API
  catturati a compile-time.
- **Niente boilerplate nei template**: `lang="ts"` su 200+ componenti
  aggiungerebbe ~30-40 LOC complessive (defineProps generic, type imports)
  senza catturare un solo bug in più rispetto al runtime validation.
- **Onboarding lineare**: chi conosce JS può aprire qualsiasi `.vue` e
  capire il template senza prerequisiti TS.
- **Build più veloci**: `vue-tsc` ha overhead non trascurabile su template;
  saltarlo per i `.vue` riduce tempo `typecheck` del 30-40%.

### Negative accettate

- Props complesse (oggetti annidati) nei `.vue` non hanno enforcement
  compile-time del tipo — mitigato da PropType<T> JSDoc dove serve davvero.
- Refactoring di interfacce condivise richiede grep manuale nei `.vue` per
  i siti d'uso — accettabile data la modesta dimensione del codebase.

## Eccezioni

Componenti che fanno orchestrazione intensiva (`ShipmentFlowPage.vue`,
`AccountDashboard.vue`) **possono** scegliere `lang="ts"` se il caso lo
giustifica. Decisione **non automatica**: serve commit message che spieghi
"questo componente coordina N composables tipizzati, lang=ts evita cast
manuali ripetuti".

## Cosa NON si fa

- ❌ Conversione automatica massiva `.vue` JS → TS
- ❌ Wrapper TS attorno a librerie già tipizzate solo per "uniformità"
- ❌ `// @ts-ignore` in produzione: se serve è bug nel tipo, fixare il tipo

## Riferimenti

- [Vue 3 + TypeScript guide](https://vuejs.org/guide/typescript/overview)
- [Nuxt 4 TypeScript handling](https://nuxt.com/docs/guide/concepts/typescript)
- [ADR 004 — Tailwind utility design system](./004-tailwind-utility-design-system.md)
- [ADR 006 — Service-Layer Architecture](./006-service-layer-architecture.md)
