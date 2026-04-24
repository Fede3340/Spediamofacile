<!--
  Sticky payment footer: summary, pay button, terms, status messages.
-->
<script setup>
defineProps({
  finalTotalFormatted: { type: String, required: true },
  paymentMethod:       { type: String, required: true },
  paymentActionLabel:  { type: String, required: true },
  payButtonTooltip:    { type: String, default: '' },
  canPay:              { type: Boolean, default: false },
  isProcessing:        { type: Boolean, default: false },
  paymentError:        { type: String,  default: '' },
  paymentStep:         { type: String,  default: '' },
  termsAccepted:       { type: Boolean, default: false },
})

const emit = defineEmits(['confirm-payment', 'update:termsAccepted'])
</script>

<template>
  <div class="checkout-payment-footer checkout-motion-card [--checkout-delay:200ms]">
    <div class="checkout-payment-footer__summary">
      <div class="checkout-payment-footer__summary-copy">
        <p class="checkout-payment-footer__summary-label">Totale da pagare</p>
        <p class="checkout-payment-footer__summary-value">{{ finalTotalFormatted }}</p>
      </div>
      <div class="checkout-payment-footer__summary-side">
        <span class="checkout-payment-footer__summary-chip">{{ paymentMethod === 'bonifico' ? 'Pagamento differito' : 'IVA inclusa' }}</span>
        <span class="checkout-security-badge">
          <svg class="checkout-security-badge__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          Pagamento sicuro
        </span>
      </div>
    </div>

    <div class="checkout-payment-footer__body">
      <div class="checkout-payment-footer__support">
        <label class="checkout-payment-footer__terms">
          <input type="checkbox" :checked="termsAccepted" @change="emit('update:termsAccepted', $event.target.checked)" class="checkout-payment-footer__checkbox" />
          <span class="checkout-payment-footer__terms-text">
            Ho letto e accetto i
            <NuxtLink to="/termini-e-condizioni" class="checkout-payment-footer__terms-link">Termini e condizioni</NuxtLink>
            e la
            <NuxtLink to="/privacy-policy" class="checkout-payment-footer__terms-link">privacy policy</NuxtLink>
          </span>
        </label>
        <p v-if="payButtonTooltip && !canPay" class="checkout-payment-footer__hint">{{ payButtonTooltip }}</p>
      </div>

      <div class="checkout-payment-footer__cta">
        <button
          type="button"
          @click="emit('confirm-payment')"
          :disabled="!canPay"
          :class="['sf-flow-cta', 'sf-flow-cta--primary', 'checkout-payment-submit', canPay ? 'checkout-payment-submit--active' : 'checkout-payment-submit--disabled']">
          <span>{{ isProcessing ? 'Apertura...' : paymentActionLabel }}</span>
          <span class="sf-flow-cta__arrow">
            <svg v-if="isProcessing" class="spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10" opacity="0.25"/>
              <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
            </svg>
            <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M12 5l7 7-7 7" />
            </svg>
          </span>
        </button>
      </div>
    </div>
  </div>

  <!-- Status messages -->
  <div class="checkout-payment-status">
    <p v-if="paymentError" class="checkout-payment-status__error">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span>{{ paymentError }}</span>
    </p>

    <div v-if="isProcessing && paymentStep" class="checkout-payment-status__progress">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 animate-spin"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg>
      <span>{{ paymentStep }}</span>
    </div>
  </div>
</template>
