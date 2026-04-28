<!--
  Confirm-payment modal (teleported to body).
-->
<script setup>
const props = defineProps({
  show:               { type: Boolean, required: true },
  finalTotalFormatted:{ type: String,  required: true },
  paymentMethod:      { type: String,  required: true },
  totalPackages:      { type: Number,  required: true },
})

const emit = defineEmits(['close', 'confirm'])

const dialogRef = ref(null)
const previousFocus = ref(null)

const trapFocus = (e) => {
  if (!dialogRef.value) return
  const focusable = dialogRef.value.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
  if (!focusable.length) return
  const first = focusable[0]
  const last = focusable[focusable.length - 1]
  if (e.key === 'Tab') {
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus() }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus() }
  }
  if (e.key === 'Escape') { emit('close') }
}

watch(() => props.show, (open) => {
  if (open) {
    previousFocus.value = document.activeElement
    nextTick(() => {
      dialogRef.value?.querySelector('button')?.focus()
      document.addEventListener('keydown', trapFocus)
    })
  } else {
    document.removeEventListener('keydown', trapFocus)
    nextTick(() => previousFocus.value?.focus?.())
  }
})

onUnmounted(() => { document.removeEventListener('keydown', trapFocus) })
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[9999] flex items-center justify-center p-[20px]"
      style="background: rgba(9,19,28,0.36); backdrop-filter: blur(6px)"
      @click.self="emit('close')">
      <div ref="dialogRef" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title" class="w-full max-w-[440px] bg-white rounded-[16px] p-[24px] animate-[scale-in_0.2s_ease-out]"
        style="outline:1px solid #DFE2E7; outline-offset:-1px; box-shadow: 0 8px 32px rgba(0,0,0,0.12)">
        <!-- Header -->
        <div class="flex items-start gap-[12px] mb-[16px]">
          <div class="w-[40px] h-[40px] rounded-[12px] flex items-center justify-center shrink-0" aria-hidden="true"
            style="background:rgba(9,88,102,0.06); outline:1px solid rgba(9,88,102,0.12); outline-offset:-1px">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <div>
            <h3 id="confirm-modal-title" class="text-[var(--color-brand-text)] text-[17px]" style="font-weight:700">Conferma pagamento</h3>
            <p class="text-[var(--color-brand-text-secondary)] text-[14px] mt-[4px] leading-[1.55]" style="font-weight:400">
              Stai per pagare <span class="text-[var(--color-brand-text)]" style="font-weight:700">{{ finalTotalFormatted }}</span>
              <template v-if="paymentMethod === 'carta'">con carta di credito</template>
              <template v-else-if="paymentMethod === 'bonifico'">tramite bonifico bancario</template>
              <template v-else-if="paymentMethod === 'wallet'">con il tuo wallet</template>
              per <span class="text-[var(--color-brand-text)]" style="font-weight:700">{{ totalPackages }} {{ totalPackages === 1 ? 'spedizione' : 'spedizioni' }}</span>.
            </p>
          </div>
        </div>

        <!-- Divider -->
        <div class="h-[1px] bg-[#EEF0F3] mb-[16px]"></div>

        <!-- Actions -->
        <div class="flex gap-[10px]">
          <button type="button" @click="emit('close')"
            class="flex-1 h-[44px] rounded-full bg-white text-[14px] text-[var(--color-brand-text-secondary)] cursor-pointer"
            style="font-weight:600; outline: 1.5px solid #DFE2E7; outline-offset:-1.5px">
            Annulla
          </button>
          <button type="button" @click="emit('confirm')"
            class="btn-cta-filled flex-1 h-[44px] rounded-full text-[14px] cursor-pointer">
            Conferma
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
@keyframes scale-in {
  from { opacity: 0; transform: scale(0.95); }
  to   { opacity: 1; transform: scale(1); }
}
</style>
