<script setup>
const props = defineProps({
	defaultPaymentMethod: { type: Object, default: null },
	stripeConfigured: { type: Boolean, default: false },
});
const emit = defineEmits(['topUpSuccess', 'paymentMethodUpdated']);

const {
	topUpAmount, isLoading, message, messageType, presetAmounts,
	showNewCardForm, isPreparingNewCardForm, cardHolderName, cardError,
	canSubmitTopUp, topUpButtonLabel,
	selectPreset, handleTopUp, openNewCardForm, closeNewCardForm,
} = useWalletTopUp(props, emit);
</script>

<template>
	<div class="rounded-card border border-brand-border bg-brand-card p-[18px] shadow-sf desktop:sticky desktop:top-[108px] desktop:p-5">
		<div class="flex items-start gap-2.5">
			<div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-primary/10">
				<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-brand-primary">
					<circle cx="12" cy="12" r="10" />
					<path d="M12 8v8M8 12h8" />
				</svg>
			</div>
			<div>
				<h2 class="text-base font-bold text-brand-text">Ricarica portafoglio</h2>
				<p class="mt-1 text-xs leading-snug text-brand-text-muted">Scegli un importo e conferma con la carta giusta, senza passaggi inutili.</p>
			</div>
		</div>

		<div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 tablet:grid-cols-4 desktop:grid-cols-5">
			<button
				v-for="amount in presetAmounts"
				:key="amount"
				type="button"
				:class="[
					'h-[38px] cursor-pointer rounded-xl border-2 text-[13px] font-semibold transition-all duration-200',
					topUpAmount == amount
						? 'scale-[1.03] border-brand-primary bg-brand-primary text-white shadow-[0_2px_8px_rgba(9,88,102,0.28)]'
						: 'border-brand-border bg-white text-brand-text hover:scale-[1.05] hover:border-brand-primary hover:bg-brand-primary/[0.05] active:scale-[0.97]',
				]"
				@click="selectPreset(amount)">
				&euro;{{ amount }}
			</button>
		</div>

		<div class="mt-3.5">
			<label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.08em] text-brand-primary">Importo personalizzato</label>
			<div class="relative">
				<span class="absolute left-4 top-1/2 -translate-y-1/2 text-base font-medium text-brand-text-muted">&euro;</span>
				<input
					v-model="topUpAmount"
					type="number"
					min="1"
					step="0.01"
					placeholder="Inserisci importo"
					class="w-full rounded-xl border border-brand-border bg-brand-bg-alt py-3 pl-9 pr-4 text-[0.9375rem] transition-all focus:border-brand-primary focus:bg-white focus:shadow-[0_0_0_3px_rgba(9,88,102,0.1)] focus:outline-none">
			</div>
		</div>

		<div class="mt-3.5 rounded-card border border-brand-border bg-brand-bg-alt p-3.5">
			<div v-if="defaultPaymentMethod?.card && !showNewCardForm" class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between">
				<div class="flex items-center gap-2.5">
					<div class="flex h-8 min-w-[52px] items-center justify-center rounded-lg bg-white px-2.5 text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-brand-primary">
						{{ defaultPaymentMethod.card.brand?.slice(0, 4) }}
					</div>
					<div class="min-w-0">
						<p class="text-[0.9375rem] font-medium text-brand-text">•••• {{ defaultPaymentMethod.card.last4 }}</p>
						<p class="mt-0.5 text-xs text-brand-text-muted">Scad. {{ defaultPaymentMethod.card.exp_month }}/{{ defaultPaymentMethod.card.exp_year }}</p>
					</div>
				</div>
				<div class="flex flex-wrap items-center gap-2">
					<NuxtLink to="/account/carte" class="text-[0.8125rem] font-medium text-brand-primary transition-opacity hover:opacity-80">Cambia</NuxtLink>
					<button type="button" class="cursor-pointer text-[0.8125rem] font-medium text-brand-primary transition-opacity hover:opacity-80" @click="openNewCardForm">
						Usa una nuova carta
					</button>
				</div>
			</div>

			<AccountWalletNewCardForm
				v-else-if="showNewCardForm"
				v-model:card-holder-name="cardHolderName"
				:is-preparing-new-card-form="isPreparingNewCardForm"
				:card-error="cardError"
				:has-saved-card="Boolean(defaultPaymentMethod?.card)"
				@close="closeNewCardForm" />

			<div v-else class="flex flex-col gap-2.5 rounded-xl border border-status-pending-fg/30 bg-status-pending-bg px-3 py-3 text-[0.8125rem] text-status-pending-fg">
				<div class="flex items-start gap-2.5">
					<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-px shrink-0">
						<circle cx="12" cy="12" r="10" />
						<line x1="12" y1="8" x2="12" y2="12" />
						<line x1="12" y1="16" x2="12.01" y2="16" />
					</svg>
					<p class="leading-snug">
						<template v-if="stripeConfigured">
							Nessuna carta salvata.
							<NuxtLink to="/account/carte" class="font-semibold underline">Apri carte e pagamenti</NuxtLink>
							oppure aggiungila qui sotto.
						</template>
						<template v-else>
							Le ricariche con carta non sono ancora attive su questo sito. Quando Stripe sarà configurato, qui potrai usare la tua carta salvata.
						</template>
					</p>
				</div>
				<div v-if="stripeConfigured" class="flex flex-wrap items-center gap-2">
					<SfButton variant="secondary" size="sm" @click="openNewCardForm">
						<template #leading>
							<svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<line x1="12" y1="5" x2="12" y2="19" />
								<line x1="5" y1="12" x2="19" y2="12" />
							</svg>
						</template>
						Aggiungi carta
					</SfButton>
					<NuxtLink to="/account/carte" class="text-[0.8125rem] font-semibold underline">Gestisci carte e pagamenti</NuxtLink>
				</div>
			</div>
		</div>

		<div class="mt-3.5 grid gap-3 desktop:grid-cols-[minmax(0,1fr)_240px] desktop:items-end">
			<div class="rounded-card bg-brand-bg-alt px-3.5 py-3">
				<p class="text-xs font-semibold uppercase tracking-[0.08em] text-brand-text-secondary">Ricarica pronta</p>
				<p class="mt-1 text-[0.9375rem] font-semibold text-brand-text">
					{{ topUpAmount ? `Importo selezionato: €${formatEuro(topUpAmount || 0)}` : 'Scegli un importo o inseriscilo manualmente' }}
				</p>
				<p class="mt-1 text-xs leading-snug text-brand-text-muted">
					{{ defaultPaymentMethod?.card || showNewCardForm ? 'Il pagamento userà la carta mostrata sopra.' : 'Serve una carta salvata o una nuova carta per procedere.' }}
				</p>
			</div>

			<button
				type="button"
				:disabled="!canSubmitTopUp"
				:class="[
					'btn-primary flex min-h-[38px] w-full items-center justify-center gap-2 text-[13px]',
					!canSubmitTopUp ? 'cursor-not-allowed bg-gray-200 text-gray-400' : 'cursor-pointer',
				]"
				@click="handleTopUp">
				<svg v-if="!isLoading" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21 18v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1" />
					<path d="M14 11h4m-2-2v4" />
				</svg>
				<span>{{ topUpButtonLabel }}</span>
			</button>
		</div>

		<div
			v-if="message"
			:class="[
				'mt-3.5 flex items-center gap-2 rounded-card px-3 py-2.5 text-[0.8125rem] font-medium',
				messageType === 'success' ? 'bg-brand-success-bg text-brand-success-fg' : 'bg-brand-accent/10 text-brand-accent',
			]">
			<svg v-if="messageType === 'success'" aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
				<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
				<polyline points="22 4 12 14.01 9 11.01" />
			</svg>
			<svg v-else aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
				<circle cx="12" cy="12" r="10" />
				<line x1="12" y1="8" x2="12" y2="12" />
				<line x1="12" y1="16" x2="12.01" y2="16" />
			</svg>
			{{ message }}
		</div>
	</div>
</template>
