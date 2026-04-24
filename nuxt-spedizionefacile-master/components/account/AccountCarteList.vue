<script setup>
defineProps({
  payments: { type: Object, default: null },
  status: { type: String, default: '' },
  cardsFeatureAvailable: { type: Boolean, default: false },
  isAdmin: { type: Boolean, default: false },
  deleteConfirmId: { type: [String, null], default: null },
})

const emit = defineEmits([
  'toggle-form', 'set-default', 'delete',
  'ask-delete', 'cancel-delete', 'open-admin-settings',
])

const getBrandIcon = (brand) => {
  const brands = { visa: 'Visa', mastercard: 'Mastercard', amex: 'Amex', discover: 'Discover' }
  return brands[brand?.toLowerCase()] || brand || 'Carta'
}
</script>

<template>
  <!-- Loading skeleton -->
  <div v-if="status === 'pending'">
    <div v-for="n in 2" :key="n" class="bg-white rounded-[16px] p-[14px] mb-[8px]" style="box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
      <div class="flex animate-pulse items-center gap-[12px]">
        <div class="w-[50px] h-[34px] rounded-[8px] bg-gray-200"></div>
        <div class="flex-1 space-y-[6px]">
          <div class="h-[13px] rounded-full bg-gray-200 w-[40%]"></div>
          <div class="h-[11px] rounded-full bg-gray-200 w-[25%]"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Cards loaded -->
  <template v-else-if="payments && payments.data">
    <!-- Empty state -->
    <div v-if="payments.data.length === 0" class="bg-white rounded-[16px] p-[20px] shadow-[0_1px_3px_rgba(0,0,0,0.05)] border border-transparent text-center">
      <div class="w-[64px] h-[64px] mx-auto mb-[16px] bg-[#F5F6F9] rounded-full flex items-center justify-center">
        <svg aria-hidden="true" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#095866" opacity="0.4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
      </div>
      <h2 class="font-montserrat text-[1.125rem] font-[800] text-[var(--color-brand-text)] mb-[8px]">
        {{ cardsFeatureAvailable ? 'Nessuna carta salvata' : 'Pagamenti con carta non ancora attivi' }}
      </h2>
      <p class="text-[var(--color-brand-text-secondary)] text-[0.875rem] max-w-[460px] mx-auto mb-[20px] leading-[1.55]">
        <span v-if="cardsFeatureAvailable">Aggiungi una carta per pagare più in fretta.</span>
        <span v-else-if="isAdmin">Configura Stripe per attivare carte e wallet.</span>
        <span v-else>Le carte saranno disponibili appena Stripe sarÁ  attivo.</span>
      </p>
      <button v-if="cardsFeatureAvailable" @click="emit('toggle-form')" class="btn-primary btn-compact font-semibold text-[0.875rem]">Aggiungi la tua prima carta</button>
      <button v-else-if="isAdmin" @click="emit('open-admin-settings')" class="btn-primary btn-compact font-semibold text-[0.875rem]">Apri impostazioni Stripe</button>
      <p v-else class="text-[var(--color-brand-text-secondary)] text-[0.875rem] font-medium">Quando Stripe sarÁ  attivo, qui comparirÁ  il pulsante per aggiungere la tua prima carta.</p>
    </div>

    <!-- Card items -->
    <div v-else class="space-y-[14px]">
      <div v-for="(payment, index) in payments.data" :key="index"
        :class="['bg-white rounded-[16px] p-[16px] desktop:p-[18px] border transition-all', payment.default ? 'border-[var(--color-brand-primary)]' : 'border-transparent hover:bg-[rgba(9,88,102,0.03)]']"
        style="box-shadow: 0 2px 8px rgba(9,88,102,0.06), 0 0 0 1px rgba(9,88,102,0.04);">
        <div class="flex flex-col gap-[12px] tablet:flex-row tablet:items-center tablet:gap-[14px]">
          <!-- Brand icon -->
          <div :class="['w-[50px] h-[34px] rounded-[8px] flex items-center justify-center text-[0.7rem] font-bold uppercase tracking-wide shrink-0', payment.default ? 'bg-gradient-to-br from-[var(--color-brand-primary)] to-[var(--color-brand-primary-hover)] text-white' : 'bg-[#F0F4F5] text-[var(--color-brand-text)]']">
            {{ getBrandIcon(payment.brand)?.slice(0, 4) }}
          </div>
          <!-- Info -->
          <div class="min-w-0 w-full flex-1">
            <div class="flex flex-wrap items-center gap-[8px]">
              <span class="text-[0.9375rem] font-semibold text-[var(--color-brand-text)]">{{ getBrandIcon(payment.brand) }} •••• {{ payment.last4 }}</span>
              <span v-if="payment.default" class="inline-block px-[10px] py-[3px] rounded-full text-[0.6875rem] font-semibold bg-[#EDF7F8] text-[var(--color-brand-primary)]">Predefinita</span>
            </div>
            <div class="mt-[4px] flex flex-col gap-[4px] sm:flex-row sm:items-center sm:gap-[12px]">
              <span class="text-[0.8125rem] text-[var(--color-brand-text-secondary)]">{{ payment.holder_name }}</span>
              <span class="text-[0.75rem] text-[var(--color-brand-text-muted)]">Scad. {{ payment.exp_month }}/{{ payment.exp_year }}</span>
            </div>
          </div>
          <!-- Actions -->
          <div class="flex w-full flex-wrap items-center gap-[8px] tablet:w-auto tablet:justify-end">
            <button v-if="!payment.default" @click="emit('set-default', payment.id)"
              class="btn-secondary btn-compact inline-flex items-center gap-[6px]">
              <svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              Imposta predefinita
            </button>
            <template v-if="deleteConfirmId !== payment.id">
              <button @click="emit('ask-delete', payment.id)"
                class="btn-secondary btn-compact inline-flex items-center gap-[6px] text-red-600 hover:!border-red-200 hover:!bg-red-50">
                <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                Elimina
              </button>
            </template>
            <template v-else>
              <div class="flex flex-wrap items-center gap-[6px]">
                <button @click="emit('delete', payment.id)"
                  class="btn-primary btn-compact">
                  <svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  Conferma
                </button>
                <button @click="emit('cancel-delete')"
                  class="btn-secondary btn-compact">
                  <svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  Annulla
                </button>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </template>

  <!-- Security note -->
  <div class="mt-[14px] flex items-start gap-[10px] p-[12px] bg-[#F5F6F9] rounded-[14px]">
    <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-text-secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 mt-[1px]"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    <p class="text-[0.75rem] text-[var(--color-brand-text-secondary)] leading-[1.5]">
      I dati delle carte sono gestiti in modo sicuro da Stripe. Non conserviamo mai i numeri completi delle tue carte.
    </p>
  </div>
</template>

