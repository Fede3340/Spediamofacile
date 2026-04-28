<!--
  Payment-success screen con micro-interazioni Awwwards-grade.
  Props: successOrderId, paymentMethod.
-->
<script setup>
import '~/assets/css/shipment-flow.css';

const props = defineProps({
  successOrderId: { type: [String, Number], required: true },
  paymentMethod: { type: String, default: '' },
  totalAmount: { type: String, default: '' },
})

const isMultiOrder = computed(() => String(props.successOrderId || '').includes(','))
const isBankTransfer = computed(() => props.paymentMethod === 'bonifico')
</script>

<template>
  <div class="checkout-success">
    <!-- Icona circolare 64px teal con anello -->
    <div class="checkout-success__badge" aria-hidden="true">
      <span class="checkout-success__badge-ring"></span>
      <span class="checkout-success__badge-inner">
        <svg class="checkout-success__check" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12" />
        </svg>
      </span>
    </div>

    <!-- Chip stato -->
    <span class="checkout-success__chip">
      <span class="checkout-success__chip-dot" aria-hidden="true"></span>
      Ordine confermato
    </span>

    <!-- Titolo 32px -->
    <h1 class="checkout-success__title">
      <template v-if="String(successOrderId).includes(',')">
        I tuoi ordini <span class="checkout-success__title-id">#{{ successOrderId }}</span> sono stati creati
      </template>
      <template v-else>
        Ordine <span class="checkout-success__title-id">#{{ successOrderId }}</span> confermato
      </template>
    </h1>

    <!-- Descrizione -->
    <p v-if="paymentMethod === 'bonifico'" class="checkout-success__lead">
      Riceverai le coordinate bancarie via email. Il ritiro sara pianificato appena confermato il bonifico.
    </p>
    <p v-else class="checkout-success__lead">
      Pagamento elaborato correttamente. Riceverai una email di conferma con tutti i dettagli.
    </p>

    <!-- F05 — Istruzioni bonifico bancario -->
    <BankTransferInstructions
      v-if="isBankTransfer"
      :order-id="successOrderId"
      :amount="totalAmount" />

    <!-- Info card con 3 pillar: email conferma, tracking, ritiro -->
    <div class="checkout-success__info-grid">
      <div class="checkout-success__info-card">
        <span class="checkout-success__info-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </span>
        <p class="checkout-success__info-label">Email di conferma</p>
        <p class="checkout-success__info-value">Inviata ora</p>
      </div>
      <div class="checkout-success__info-card">
        <span class="checkout-success__info-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        </span>
        <p class="checkout-success__info-label">Tracking BRT</p>
        <p class="checkout-success__info-value">Disponibile in 24h</p>
      </div>
      <div class="checkout-success__info-card">
        <span class="checkout-success__info-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </span>
        <p class="checkout-success__info-label">Ritiro</p>
        <p class="checkout-success__info-value">Nella data scelta</p>
      </div>
    </div>

    <!-- CTA: traccia + torna account -->
    <div class="checkout-success__actions">
      <SfButton to="/account/spedizioni" size="lg">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        Traccia spedizione
      </SfButton>
      <SfButton to="/account" variant="secondary" size="lg">
        Vai al tuo account
      </SfButton>
    </div>
  </div>
</template>

