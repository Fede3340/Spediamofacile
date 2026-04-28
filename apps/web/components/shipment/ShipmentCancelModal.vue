<!--
  Componente: ShipmentCancelModal
  Modale di conferma annullamento ordine con controllo idoneita' rimborso.
-->
<script setup>
const props = defineProps({
	show: { type: Boolean, required: true },
	loadingEligibility: { type: Boolean, default: false },
	refundEligibility: { type: Object, default: null },
	cancelling: { type: Boolean, default: false },
	cancelError: { type: String, default: null },
	cancelReason: { type: String, default: '' },
	orderSubtotal: { type: String, default: '' },
	paymentMethodLabel: { type: Function, required: true },
});

const emit = defineEmits(['update:show', 'update:cancelReason', 'confirm']);

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
	if (e.key === 'Escape') { emit('update:show', false) }
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
		<div v-if="show" class="fixed inset-0 z-[9999] flex items-center justify-center">
			<div class="absolute inset-0 bg-black/50" @click="emit('update:show', false)"></div>
			<div ref="dialogRef" role="dialog" aria-modal="true" aria-labelledby="cancel-modal-title" class="relative bg-white rounded-[16px] shadow-lg max-w-[520px] w-full mx-[16px] p-[20px] z-[1]">
				<!-- Header -->
				<div class="flex items-center gap-[12px] mb-[20px]">
					<div class="w-[44px] h-[44px] rounded-full bg-red-100 flex items-center justify-center shrink-0" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
					</div>
					<div>
						<h2 id="cancel-modal-title" class="font-montserrat text-[1.125rem] font-[800] text-[var(--color-brand-text)]">Bloccare questo pacco?</h2>
						<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)]">Il pacco verra' bloccato e non potra' più essere consegnato.</p>
					</div>
				</div>

				<!-- Loading -->
				<div v-if="loadingEligibility" class="py-[20px] text-center">
					<div class="inline-block w-[24px] h-[24px] border-[3px] border-[var(--color-brand-primary)] border-t-transparent rounded-full animate-spin"></div>
					<p class="mt-[10px] text-[0.8125rem] text-[var(--color-brand-text-secondary)]">Controllo in corso...</p>
				</div>

				<!-- Eligibility loaded -->
				<template v-else-if="refundEligibility">
					<!-- Not eligible -->
					<div v-if="!refundEligibility.eligible" class="bg-red-50 border border-red-200 rounded-[50px] px-[16px] py-[12px] mb-[16px]" role="alert">
						<p class="text-[0.875rem] text-red-700">{{ refundEligibility.reason }}</p>
					</div>

					<!-- Eligible -->
					<template v-else>
						<div class="bg-[#F8F9FB] rounded-[16px] p-[16px] mb-[16px]">
							<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)] mb-[10px]">{{ refundEligibility.reason }}</p>
							<div v-if="refundEligibility.refund_amount_cents > 0" class="space-y-[8px]">
								<div class="flex items-center justify-between text-[0.875rem]">
									<span class="text-[var(--color-brand-text-secondary)]">Totale ordine:</span>
									<span class="font-semibold text-[var(--color-brand-text)]">{{ orderSubtotal }}</span>
								</div>
								<div class="flex items-center justify-between text-[0.875rem]">
									<span class="text-[var(--color-brand-text-secondary)]">Commissione annullamento:</span>
									<span class="font-semibold text-red-600">- {{ refundEligibility.commission_eur }} EUR</span>
								</div>
								<div class="border-t border-[var(--color-brand-border)] pt-[8px] flex items-center justify-between text-[0.9375rem]">
									<span class="font-semibold text-[var(--color-brand-text)]">Rimborso:</span>
									<span class="font-bold text-[#0a8a7a]">{{ refundEligibility.refund_amount_eur }} EUR</span>
								</div>
								<div class="flex items-center justify-between text-[0.8125rem]">
									<span class="text-[var(--color-brand-text-secondary)]">Metodo rimborso:</span>
									<span class="font-medium text-[var(--color-brand-text)]">{{ paymentMethodLabel(refundEligibility.payment_method) }}</span>
								</div>
							</div>
						</div>

						<!-- Motivo -->
						<div class="mb-[16px]">
							<label class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] uppercase font-medium mb-[4px]">Motivo (opzionale)</label>
							<textarea :value="cancelReason" @input="emit('update:cancelReason', $event.target.value)"
								placeholder="Perché vuoi annullare questa spedizione?" maxlength="500" rows="2"
								class="w-full bg-[#F8F9FB] border border-[var(--color-brand-border)] rounded-[16px] p-[10px] text-[0.875rem] resize-none"></textarea>
						</div>

						<!-- Errore -->
						<div v-if="cancelError" class="bg-red-50 border border-red-200 rounded-[50px] px-[14px] py-[10px] text-red-600 text-[0.8125rem] mb-[12px]" role="alert">{{ cancelError }}</div>

						<!-- Azioni -->
						<div class="flex gap-[10px]">
							<button type="button" @click="emit('confirm')" :disabled="cancelling"
								class="flex-1 inline-flex items-center justify-center gap-[6px] px-[16px] py-[12px] bg-red-600 text-white rounded-[50px] text-[0.875rem] font-semibold hover:bg-red-700 transition disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer">
								<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
								{{ cancelling ? 'Blocco in corso...' : 'Conferma blocco pacco' }}
							</button>
							<button type="button" @click="emit('update:show', false)" :disabled="cancelling"
								class="px-[20px] py-[12px] bg-[var(--color-brand-border)] text-[var(--color-brand-text)] rounded-[50px] text-[0.875rem] font-semibold hover:bg-[#D0D0D0] transition disabled:opacity-60 cursor-pointer">
								Indietro
							</button>
						</div>
					</template>
				</template>

				<!-- Error loading eligibility -->
				<template v-else>
					<div v-if="cancelError" class="bg-red-50 border border-red-200 rounded-[50px] px-[14px] py-[10px] text-red-600 text-[0.8125rem] mb-[12px]" role="alert">{{ cancelError }}</div>
					<button type="button" @click="emit('update:show', false)"
						class="w-full px-[16px] py-[12px] bg-[var(--color-brand-border)] text-[var(--color-brand-text)] rounded-[50px] text-[0.875rem] font-semibold hover:bg-[#D0D0D0] transition cursor-pointer">
						Chiudi
					</button>
				</template>
			</div>
		</div>
	</Teleport>
</template>
