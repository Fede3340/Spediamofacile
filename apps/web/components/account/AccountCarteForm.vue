<script setup>
defineProps({
	cardHolderName: { type: String, default: '' },
	errorMessage: { type: [String, null], default: null },
});

const emit = defineEmits(['update:cardHolderName', 'save', 'cancel']);
</script>

<template>
	<div class="max-w-[880px] rounded-card border border-brand-primary/[0.08] bg-white p-[18px] shadow-sf-sm tablet:p-[22px] desktop:p-6">
		<div class="mb-[18px]">
			<p class="text-[0.7rem] font-semibold uppercase tracking-[1px] text-brand-primary">Dettagli carta</p>
			<h2 class="mt-1 font-display text-base font-extrabold text-brand-text">Salva un metodo sicuro per i prossimi pagamenti</h2>
			<p class="mt-1 text-[0.8125rem] leading-snug text-brand-text-secondary">
				I dati sono gestiti da Stripe e restano pronti per checkout, wallet e ordini futuri.
			</p>
		</div>

		<div class="mb-4">
			<label class="mb-1.5 block text-xs font-semibold text-brand-text">Numero carta</label>
			<div id="card-number" class="account-carte-stripe-field" />
		</div>

		<div class="mb-4">
			<label class="mb-1.5 block text-xs font-semibold text-brand-text">Titolare carta</label>
			<input
				type="text"
				:value="cardHolderName"
				class="w-full rounded-card border border-brand-border bg-brand-bg-alt px-3.5 py-[11px] text-sm text-brand-text transition-colors placeholder:text-brand-text-muted focus:border-brand-primary focus:outline-none"
				placeholder="Mario Rossi"
				required
				@input="emit('update:cardHolderName', $event.target.value)">
		</div>

		<div class="mb-4 grid grid-cols-1 gap-3 tablet:grid-cols-[minmax(0,1fr)_132px]">
			<div class="min-w-0">
				<label class="mb-1.5 block text-xs font-semibold text-brand-text">Scadenza</label>
				<div id="card-expiry" class="account-carte-stripe-field" />
			</div>
			<div class="min-w-0 tablet:w-[132px]">
				<label class="mb-1.5 block text-xs font-semibold text-brand-text">CVC</label>
				<div id="card-cvc" class="account-carte-stripe-field" />
			</div>
		</div>

		<p v-if="errorMessage" class="mb-4 rounded-card border border-brand-error/30 bg-brand-error/[0.08] p-2.5 text-xs text-brand-error">
			{{ errorMessage }}
		</p>

		<div class="flex flex-col gap-2.5 sm:flex-row">
			<SfButton variant="secondary" size="sm" block @click.prevent="emit('cancel')">
				<template #leading>
					<svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
				</template>
				Annulla
			</SfButton>
			<SfButton variant="primary" size="sm" block @click="emit('save')">
				<template #leading>
					<svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" /><polyline points="17 21 17 13 7 13 7 21" /><polyline points="7 3 7 8 15 8" /></svg>
				</template>
				Salva carta
			</SfButton>
		</div>

		<div class="mt-3.5 flex items-center justify-center gap-1.5 text-[0.6875rem] text-brand-text-muted">
			<svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg>
			<span>Connessione sicura SSL</span>
		</div>
	</div>
</template>
