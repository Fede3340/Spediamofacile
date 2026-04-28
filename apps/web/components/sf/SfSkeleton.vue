<!-- COMPONENTE: SfSkeleton (atom) -->
<script setup>import { computed } from 'vue';
const props = defineProps({
  variant: { type: Object, default: () => ({}) },
  width: { type: String, default: '100%' },
  height: { type: String, default: '14px' },
  rounded: { type: String, default: '' },
  count: { type: Number, default: 1 },
});
// Preset single-shape (una barra). text-block è gestito a parte nel template.
const PRESETS = {
    line: { width: '100%', height: '14px', rounded: '6px' },
    text: { width: '100%', height: '14px', rounded: '6px' }, // alias legacy di "line"
    title: { width: '70%', height: '22px', rounded: '8px' },
    circle: { width: '40px', height: '40px', rounded: '9999px' },
    avatar: { width: '40px', height: '40px', rounded: '9999px' }, // alias legacy
    card: { width: '100%', height: '120px', rounded: '16px' },
    button: { width: '120px', height: '40px', rounded: '10px' },
    custom: { width: '100%', height: '14px', rounded: '6px' },
};
const isTextBlock = computed(() => props.variant === 'text-block');
const resolved = computed(() => {
    if (props.variant && props.variant !== 'custom' && props.variant !== 'text-block') {
        return PRESETS[props.variant];
    }
    return {
        width: props.width,
        height: props.height,
        rounded: props.rounded ?? (props.variant === 'custom' ? '6px' : 'var(--radius-sm, 6px)'),
    };
});
const itemStyle = computed(() => ({
    width: resolved.value.width,
    height: resolved.value.height,
    borderRadius: resolved.value.rounded,
}));
const items = computed(() => Array.from({ length: Math.max(1, props.count) }));
</script>

<template>
  <!-- text-block: layout preconfezionato (titolo + 2 righe testo) -->
  <div v-if="isTextBlock" class="sf-skeleton-wrap sf-skeleton-wrap--block" aria-busy="true" aria-live="polite">
    <span class="sf-skeleton" style="width: 70%; height: 18px; border-radius: 8px;" aria-hidden="true" />
    <span class="sf-skeleton" style="width: 100%; height: 12px; border-radius: 6px;" aria-hidden="true" />
    <span class="sf-skeleton" style="width: 85%; height: 12px; border-radius: 6px;" aria-hidden="true" />
  </div>
  <div v-else class="sf-skeleton-wrap" aria-busy="true" aria-live="polite">
    <span
      v-for="(_, i) in items"
      :key="i"
      class="sf-skeleton"
      :style="itemStyle"
      aria-hidden="true"
    />
  </div>
</template>

<style scoped>
.sf-skeleton-wrap {
  display: flex;
  flex-direction: column;
  gap: var(--gap-2, 8px);
}
.sf-skeleton-wrap--block {
  gap: 10px;
}

.sf-skeleton {
  display: block;
  /* Pulse uniforme: neutro → neutro più scuro. Token con fallback grigi. */
  background: linear-gradient(
    90deg,
    var(--color-neutral-100, #F3F4F6) 0%,
    var(--color-neutral-200, #E5E7EB) 50%,
    var(--color-neutral-100, #F3F4F6) 100%
  );
  background-size: 200% 100%;
  animation: sf-skeleton-pulse 1.5s ease-in-out infinite;
}

@keyframes sf-skeleton-pulse {
  0%   { background-position: 200% 0; opacity: 1; }
  50%  { opacity: 0.75; }
  100% { background-position: -200% 0; opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
  .sf-skeleton {
    animation: none;
    background: var(--color-neutral-100, #F3F4F6);
    opacity: 0.8;
  }
}
</style>
