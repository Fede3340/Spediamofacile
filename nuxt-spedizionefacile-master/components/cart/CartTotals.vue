<script setup>
import '~/assets/css/components/sf-cart-totals.css';

defineProps({
  cartMeta: { type: Object, default: () => ({}) },
  couponApplied: { type: Boolean, default: false },
  couponDiscount: { type: [Number, String], default: null },
  appliedTotal: { type: [Number, String], default: null },
  displayTotal: { type: [Number, String], default: null },
  displayEntries: { type: Array, default: () => [] },
  couponCode: { type: String, default: '' },
  couponMessage: { type: Object, default: null },
  showCouponField: { type: Boolean, default: false },
  showCouponPanel: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle-coupon', 'apply-coupon', 'remove-coupon', 'update:coupon-code']);
</script>

<template>
  <div class="cart-totals-card">

    <!-- Eyebrow + title -->
    <div class="cart-totals-card__head">
      <span class="cart-totals-card__eyebrow">Riepilogo carrello</span>
      <h3 class="cart-totals-card__title">Totale da pagare</h3>
    </div>

    <!-- Total price hero: 32px teal accent -->
    <div class="cart-totals-card__hero">
      <span class="cart-totals-card__hero-value">{{ displayTotal }}</span>
      <span class="cart-totals-card__hero-chip">IVA inclusa</span>
    </div>

    <!-- Line items -->
    <div class="cart-totals-card__lines">
      <!-- Subtotale -->
      <div class="cart-totals-card__line">
        <span class="cart-totals-card__line-label">Spedizioni ({{ cartMeta?.count || displayEntries?.length || 0 }})</span>
        <span class="cart-totals-card__line-value" :class="{ 'cart-totals-card__line-value--crossed': couponApplied }">{{ cartMeta?.total }}</span>
      </div>

      <!-- Sconto coupon -->
      <div v-if="couponApplied" class="cart-totals-card__line cart-totals-card__line--discount">
        <span>Sconto ({{ couponDiscount }}%)</span>
        <span>-{{ appliedTotal }}</span>
      </div>
    </div>

    <!-- Promo code toggle -->
    <button
      type="button"
      @click="emit('toggle-coupon')"
      :aria-expanded="showCouponPanel"
      class="cart-totals-card__coupon-toggle"
    >
      <span class="cart-totals-card__coupon-label">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        Codice sconto
      </span>
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
        class="cart-totals-card__coupon-chevron" :class="showCouponPanel ? 'is-open' : ''"><polyline points="6 9 12 15 18 9"/></svg>
    </button>

    <!-- Promo code form (collapsible) -->
    <div v-if="showCouponPanel" class="cart-totals-card__coupon-panel">
      <div class="cart-totals-card__coupon-row">
        <!-- Applied state -->
        <div v-if="couponApplied" class="cart-totals-card__coupon-applied">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
          <span class="cart-totals-card__coupon-applied-code">{{ couponCode.toUpperCase() }}</span>
          <span class="cart-totals-card__coupon-applied-badge">-{{ couponDiscount }}%</span>
          <button
            type="button"
            @click="emit('remove-coupon')"
            class="cart-totals-card__coupon-applied-remove"
            aria-label="Rimuovi coupon"
          >Rimuovi</button>
        </div>

        <!-- Input state -->
        <template v-else>
          <input
            type="text"
            :value="couponCode"
            @input="emit('update:coupon-code', $event.target.value)"
            placeholder="Inserisci codice"
            class="cart-totals-card__coupon-input"
            aria-label="Codice sconto"
          />
          <button
            type="button"
            @click="emit('apply-coupon')"
            class="btn btn-cta btn-compact cart-totals-card__coupon-apply"
          >Applica</button>
        </template>
      </div>
      <!-- Coupon feedback message -->
      <p v-if="couponMessage" class="cart-totals-card__coupon-feedback" :class="couponMessage.type === 'success' ? 'is-success' : 'is-error'">
        <svg v-if="couponMessage.type === 'success'" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ couponMessage.text }}</span>
      </p>
    </div>
  </div>
</template>
