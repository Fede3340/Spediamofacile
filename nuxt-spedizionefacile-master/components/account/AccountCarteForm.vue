<script setup>
defineProps({
  cardHolderName: { type: String, default: '' },
  errorMessage: { type: [String, null], default: null },
})

const emit = defineEmits(['update:cardHolderName', 'save', 'cancel'])
</script>

<template>
  <div class="bg-white rounded-[18px] p-[18px] tablet:p-[22px] desktop:p-[24px] border border-[rgba(9,88,102,0.08)] max-w-[880px]" style="box-shadow: 0 2px 8px rgba(9,88,102,0.06), 0 0 0 1px rgba(9,88,102,0.04);">
    <div class="mb-[18px]">
      <p class="text-[0.7rem] font-semibold uppercase tracking-[1px] text-[var(--color-brand-primary)]">Dettagli carta</p>
      <h2 class="mt-[4px] font-montserrat text-[1rem] font-[800] text-[var(--color-brand-text)]">Salva un metodo sicuro per i prossimi pagamenti</h2>
      <p class="mt-[4px] text-[0.8125rem] leading-[1.5] text-[var(--color-brand-text-secondary)]">
        I dati sono gestiti da Stripe e restano pronti per checkout, wallet e ordini futuri.
      </p>
    </div>

    <div class="mb-[16px]">
      <label class="block text-[0.75rem] font-semibold text-[var(--color-brand-text)] mb-[5px]">Numero carta</label>
      <div class="account-carte-stripe-field" id="card-number"></div>
    </div>

    <div class="mb-[16px]">
      <label class="block text-[0.75rem] font-semibold text-[var(--color-brand-text)] mb-[5px]">Titolare carta</label>
      <input
        type="text"
        :value="cardHolderName"
        @input="emit('update:cardHolderName', $event.target.value)"
        class="w-full px-[14px] py-[11px] bg-[#F5F6F9] border border-[var(--color-brand-border)] rounded-[16px] text-[0.875rem] text-[var(--color-brand-text)] placeholder:text-[var(--color-brand-text-muted)] focus:border-[var(--color-brand-primary)] focus:outline-none transition-colors"
        placeholder="Mario Rossi"
        required />
    </div>

    <div class="grid grid-cols-1 tablet:grid-cols-[minmax(0,1fr)_132px] gap-[12px] mb-[16px]">
      <div class="min-w-0">
        <label class="block text-[0.75rem] font-semibold text-[var(--color-brand-text)] mb-[5px]">Scadenza</label>
        <div class="account-carte-stripe-field" id="card-expiry"></div>
      </div>
      <div class="min-w-0 tablet:w-[132px]">
        <label class="block text-[0.75rem] font-semibold text-[var(--color-brand-text)] mb-[5px]">CVC</label>
        <div class="account-carte-stripe-field" id="card-cvc"></div>
      </div>
    </div>

    <p v-if="errorMessage" class="text-red-500 text-[0.75rem] mb-[16px] p-[10px] bg-red-50 rounded-[16px] border border-red-200">
      {{ errorMessage }}
    </p>

    <div class="flex flex-col sm:flex-row gap-[10px]">
      <SfButton variant="secondary" size="sm" block @click.prevent="emit('cancel')">
        <template #leading>
          <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </template>
        Annulla
      </SfButton>
      <SfButton variant="primary" size="sm" block @click="emit('save')">
        <template #leading>
          <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        </template>
        Salva carta
      </SfButton>
    </div>

    <div class="mt-[14px] flex items-center justify-center gap-[6px] text-[0.6875rem] text-[var(--color-brand-text-muted)]">
      <svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      <span>Connessione sicura SSL</span>
    </div>
  </div>
</template>

