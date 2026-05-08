<script setup>// Form verifica email OTP (codice 6 cifre).
// Handler di input/keydown/submit/resend vivono in useAuthOverlay — qui solo presentazione.
defineProps({
    email: { type: String, default: '' },
    code: { type: Array, required: true },
    isLoading: { type: Boolean, default: false },
    resendLoading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    success: { type: String, default: '' },
});
const emit = defineEmits(['input', 'keydown', 'submit', 'resend', 'back']);
const CTA_CLS = 'btn-cta-filled w-full h-[50px] rounded-full text-sm flex items-center justify-center gap-[10px] mt-[4px] cursor-pointer active:scale-[0.985] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[var(--color-brand-accent)]/25 disabled:cursor-wait';
</script>

<template>
  <div class="flex flex-col gap-[16px]">
    <p class="text-sm leading-[1.5] text-[#666] m-0">
      Inserisci il codice a 6 cifre inviato a <strong class="text-brand-text">{{ email }}</strong>.
    </p>

    <div class="flex items-center justify-center gap-[6px] sm:gap-[8px]">
      <input
        v-for="(digit, index) in code"
        :key="index"
        :value="digit"
        :data-verification-index="index"
        :aria-label="`Cifra ${index + 1} del codice di verifica`"
        type="text"
        inputmode="numeric"
        maxlength="1"
        class="w-[40px] h-[46px] sm:w-[44px] sm:h-[48px] rounded-control bg-[#F8F9FB] text-center text-[16px] font-bold ring-[1.5px] ring-brand-border focus:ring-[3px] focus:ring-[var(--color-brand-primary)]/60 focus:bg-white outline-none transition-all duration-200"
        @input="emit('input', index, $event)"
        @keydown="emit('keydown', index, $event)"
      >
    </div>

    <div v-if="error" class="flex items-center gap-[8px] bg-[#FFF5F2] ring-[1px] ring-[var(--color-brand-accent)]/10 rounded-control px-[14px] py-[11px]">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E44203" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>
      <span class="text-[var(--color-brand-accent)] text-[13px] font-semibold">{{ error }}</span>
    </div>
    <div v-if="success" class="flex items-center gap-[8px] bg-[#f0fdf4] ring-[1px] ring-[#166534]/10 rounded-control px-[14px] py-[11px]">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg>
      <span class="text-[#166534] text-[13px] font-semibold">{{ success }}</span>
    </div>

    <button
      type="button"
      :class="CTA_CLS"
      :disabled="isLoading"
      @click="emit('submit')"
    >
      <template v-if="isLoading">
        <svg class="animate-spin w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
      </template>
      <template v-else>Verifica e continua</template>
    </button>

    <div class="flex items-center justify-between gap-[12px] text-[13px]">
      <button
        type="button"
        class="text-[var(--color-brand-primary)] font-medium hover:opacity-80 cursor-pointer bg-transparent border-0 p-0 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="resendLoading"
        @click="emit('resend')"
      >
        {{ resendLoading ? 'Invio...' : 'Invia nuovo codice' }}
      </button>
      <button
        type="button"
        class="text-[#888] font-medium hover:text-[#555] hover:opacity-80 cursor-pointer bg-transparent border-0 p-0 transition-colors"
        @click="emit('back')"
      >
        Torna indietro
      </button>
    </div>
  </div>
</template>
