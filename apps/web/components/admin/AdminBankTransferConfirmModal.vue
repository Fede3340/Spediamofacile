<script setup>
/**
 * AdminBankTransferConfirmModal — modal conferma ricezione bonifico admin.
 */
const props = defineProps({
	order: { type: Object, default: null },
	confirming: { type: Boolean, default: false },
	formatAmount: { type: Function, required: true },
});

const emit = defineEmits(['close', 'confirm']);

const reference = ref('');

watch(() => props.order, (val) => {
	if (val) reference.value = '';
});

const handleConfirm = () => emit('confirm', reference.value);
const handleClose = () => emit('close');
</script>

<template>
	<Teleport to="body">
		<div
			v-if="order"
			class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 px-4"
			@click.self="handleClose">
			<div class="relative max-h-[90vh] w-full max-w-[480px] overflow-y-auto rounded-card bg-brand-card shadow-2xl p-5">
				<div class="flex items-center gap-3 mb-4">
					<div class="w-11 h-11 rounded-full bg-brand-soft-bg flex items-center justify-center shrink-0">
						<UIcon name="mdi:bank-transfer-in" class="w-6 h-6 text-brand-primary" />
					</div>
					<div>
						<h3 class="font-display text-lg font-extrabold text-brand-text">Conferma ricezione bonifico</h3>
						<p class="text-sm text-brand-text-secondary">
							Ordine #{{ order.id }} — {{ formatAmount(order.payable_total_cents ?? order.subtotal_cents ?? (order.subtotal?.amount ? Number(order.subtotal.amount) * 100 : null)) }}
						</p>
					</div>
				</div>

				<div class="space-y-3">
					<div class="bg-brand-bg-alt rounded-card px-3.5 py-2.5 text-sm text-brand-text-secondary">
						<p>Causale attesa: <strong class="font-mono text-brand-text">ORD-{{ order.id }}</strong></p>
						<p v-if="order.user">Cliente: {{ order.user.name }} {{ order.user.surname }}</p>
					</div>

					<div>
						<label class="block text-xs text-brand-text-secondary uppercase font-medium mb-1">
							Riferimento contabile (opzionale)
						</label>
						<input
							v-model="reference"
							type="text"
							maxlength="128"
							placeholder="Es. CRO bonifico o numero estratto conto"
							class="w-full bg-brand-bg-alt border border-brand-border rounded-control px-3 py-2.5 text-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20">
					</div>

					<p class="text-xs text-brand-text-muted">
						Confermando, l'ordine passa a "Completato" e parte la generazione automatica dell'etichetta BRT. Il cliente riceverà una email di conferma.
					</p>
				</div>

				<div class="mt-5 flex flex-col gap-2 tablet:flex-row tablet:justify-end">
					<SfButton variant="secondary" size="sm" :disabled="confirming" @click="handleClose">Annulla</SfButton>
					<SfButton variant="primary" size="sm" :loading="confirming" loading-text="Conferma in corso..." @click="handleConfirm">Conferma ricezione</SfButton>
				</div>
			</div>
		</div>
	</Teleport>
</template>
