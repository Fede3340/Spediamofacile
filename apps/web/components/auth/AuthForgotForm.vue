<script setup>// Form "Password dimenticata" (richiesta link di recupero via email).
// Stato e submit vivono nel wrapper AuthOverlayModal (è logica locale al modal).
defineProps({
    email: { type: String, required: true },
    isLoading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    success: { type: String, default: '' },
});
const emit = defineEmits(['update:email', 'submit', 'back']);
const INPUT_CLS = 'w-full h-[46px] rounded-control px-[14px] text-sm font-medium text-brand-text bg-white ring-[1.5px] ring-brand-border focus:ring-[2.5px] focus:ring-[var(--color-brand-primary)]/50 placeholder:text-[#aaa] outline-none transition-all duration-200';
const LABEL_CLS = 'text-[#777] text-[11px] uppercase tracking-[0.4px] font-bold block';
const CTA_CLS = 'btn-cta-filled w-full h-[50px] rounded-full text-sm flex items-center justify-center gap-[10px] mt-[4px] cursor-pointer active:scale-[0.985] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[var(--color-brand-accent)]/25 disabled:cursor-wait';
</script>

<template>
  <div class="flex flex-col gap-[16px]">
    <div v-if="error" class="flex items-center gap-[8px] bg-[#FFF5F2] ring-[1px] ring-[var(--color-brand-accent)]/10 rounded-control px-[14px] py-[11px]">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E44203" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>
      <span class="text-[var(--color-brand-accent)] text-[13px] font-semibold">{{ error }}</span>
    </div>
    <div v-if="success" class="flex items-center gap-[8px] bg-[#f0fdf4] ring-[1px] ring-[#166534]/10 rounded-control px-[14px] py-[11px]">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg>
      <span class="text-[#166534] text-[13px] font-semibold">{{ success }}</span>
    </div>

    <div class="flex flex-col gap-[5px]">
      <label :class="LABEL_CLS" for="auth-forgot-email">Email</label>
      <input
        id="auth-forgot-email"
        :value="email"
        :class="INPUT_CLS"
        type="email"
        autocomplete="email"
        placeholder="nome@email.com"
        @input="emit('update:email', $event.target.value)"
      >
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
      <template v-else>Invia link di recupero</template>
    </button>

    <button
      type="button"
      class="inline-flex items-center justify-center gap-[8px] text-sm font-medium text-[var(--color-brand-primary)] hover:text-[#0a7489] hover:opacity-80 cursor-pointer transition-colors bg-transparent border-0 p-0"
      @click="emit('back')"
    >
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7" /></svg>
      Torna al login
    </button>
  </div>
</template>
