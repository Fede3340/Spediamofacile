<script setup>
defineProps({
	isPreparingNewCardForm: { type: Boolean, default: false },
	cardHolderName: { type: String, default: '' },
	cardError: { type: String, default: null },
	hasSavedCard: { type: Boolean, default: false },
});
defineEmits(['update:cardHolderName', 'close']);
</script>

<template>
	<div class="space-y-3 rounded-card border border-brand-border bg-brand-card p-4">
		<div class="flex items-start justify-between gap-2.5">
			<div>
				<p class="text-sm font-semibold text-brand-text">Nuova carta per la ricarica</p>
				<p class="mt-1 text-xs leading-relaxed text-brand-text-secondary">
					La useremo per questa operazione e la salveremo come carta predefinita per checkout e wallet.
				</p>
			</div>
			<button
				type="button"
				class="whitespace-nowrap text-xs font-medium text-brand-primary hover:opacity-80 transition-opacity cursor-pointer"
				@click="$emit('close')"
			>
				{{ hasSavedCard ? 'Usa carta salvata' : 'Chiudi' }}
			</button>
		</div>

		<div
			v-if="isPreparingNewCardForm"
			class="flex items-center gap-2.5 rounded-card border border-brand-border bg-brand-bg-alt px-3.5 py-3 text-xs text-brand-text-secondary"
		>
			<div class="h-5 w-5 animate-spin rounded-full border-2 border-brand-border border-t-brand-primary" />
			Preparazione modulo carta in corso...
		</div>

		<div v-else class="space-y-3">
			<div>
				<label class="mb-1.5 block text-xs font-semibold text-brand-text" for="wallet-card-holder">
					Titolare carta
				</label>
				<input
					id="wallet-card-holder"
					:value="cardHolderName"
					type="text"
					placeholder="Mario Rossi"
					class="w-full rounded-control border border-brand-border bg-brand-card px-3.5 py-2.5 text-sm text-brand-text placeholder:text-brand-text-muted transition focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/20"
					@input="$emit('update:cardHolderName', $event.target.value)"
				>
			</div>

			<div>
				<label class="mb-1.5 block text-xs font-semibold text-brand-text">Numero carta</label>
				<div id="wallet-card-number" class="stripe-field" />
			</div>

			<div class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_132px]">
				<div>
					<label class="mb-1.5 block text-xs font-semibold text-brand-text">Scadenza</label>
					<div id="wallet-card-expiry" class="stripe-field" />
				</div>
				<div class="min-w-0 sm:w-[132px]">
					<label class="mb-1.5 block text-xs font-semibold text-brand-text">CVC</label>
					<div id="wallet-card-cvc" class="stripe-field" />
				</div>
			</div>

			<SfAlert v-if="cardError" tone="danger">
				{{ cardError }}
			</SfAlert>
		</div>
	</div>
</template>

<style scoped>
/* Stripe Elements iframe target: container styling deve restare CSS perché
   Stripe inietta iframe esterno e Tailwind non puo' targettarlo. */
.stripe-field {
	width: 100%;
	border: 1px solid var(--color-brand-border);
	border-radius: 14px;
	background-color: var(--color-brand-card);
	padding: 12px 16px;
	transition: border-color 200ms ease, box-shadow 200ms ease;
}
.stripe-field:focus-within {
	border-color: var(--color-brand-primary);
	box-shadow: 0 0 0 3px rgba(9, 88, 102, 0.1);
}
</style>
