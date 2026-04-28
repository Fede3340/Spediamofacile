<script setup>
const props = defineProps({
  defaultPaymentMethod: { type: Object, default: null },
  stripeConfigured: { type: Boolean, default: false },
});
const emit = defineEmits(["topUpSuccess", "paymentMethodUpdated"]);

const {
  topUpAmount, isLoading, message, messageType, presetAmounts,
  showNewCardForm, isPreparingNewCardForm, cardHolderName, cardError,
  canSubmitTopUp, topUpButtonLabel,
  selectPreset, handleTopUp, openNewCardForm, closeNewCardForm,
} = useWalletTopUp(props, emit);
</script>

<template>
	<div class="sf-account-panel rounded-[16px] p-[18px] desktop:sticky desktop:top-[108px] desktop:p-[20px]">
		<div class="flex items-start gap-[10px]">
			<div class="flex h-[36px] w-[36px] items-center justify-center rounded-[50px] bg-[#edf7f8]">
				<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[var(--color-brand-primary)]">
					<circle cx="12" cy="12" r="10" />
					<path d="M12 8v8M8 12h8" />
				</svg>
			</div>
			<div>
				<h2 class="text-[1rem] font-bold text-[#252B42]">Ricarica portafoglio</h2>
				<p class="mt-[4px] text-[0.75rem] leading-[1.45] text-[#737373]">Scegli un importo e conferma con la carta giusta, senza passaggi inutili.</p>
			</div>
		</div>

		<div class="mt-[16px] grid grid-cols-2 gap-[8px] sm:grid-cols-3 tablet:grid-cols-4 desktop:grid-cols-5">
			<button type="button"
				v-for="amount in presetAmounts"
				:key="amount"
				@click="selectPreset(amount)"
				:class="[
					'h-[38px] rounded-[12px] border-2 text-[13px] font-semibold cursor-pointer transition-all duration-[200ms]',
					topUpAmount == amount
						? 'border-[var(--color-brand-primary)] bg-[var(--color-brand-primary)] text-white shadow-[0_2px_8px_rgba(9,88,102,0.28)] scale-[1.03]'
						: 'border-[#E9EBEC] bg-white text-[#252B42] hover:border-[var(--color-brand-primary)] hover:bg-[rgba(9,88,102,0.05)] hover:scale-[1.05] active:scale-[0.97]',
				]">
				&euro;{{ amount }}
			</button>
		</div>

		<div class="mt-[14px]">
			<label class="mb-[6px] block text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-[var(--color-brand-primary)]">Importo personalizzato</label>
			<div class="relative">
				<span class="absolute left-[16px] top-1/2 -translate-y-1/2 text-[1rem] font-medium text-[#737373]">&euro;</span>
				<input
					v-model="topUpAmount"
					type="number"
					min="1"
					step="0.01"
					placeholder="Inserisci importo"
					class="w-full rounded-[12px] border border-[#E9EBEC] bg-[#F5F6F9] py-[12px] pl-[38px] pr-[16px] text-[0.9375rem] transition-all focus:border-[var(--color-brand-primary)] focus:bg-white focus:outline-none focus:shadow-[0_0_0_3px_rgba(9,88,102,0.1)]" />
			</div>
		</div>

		<div class="mt-[14px] rounded-[16px] border border-[#E9EBEC] bg-[#FAFCFD] p-[14px]">
			<div v-if="defaultPaymentMethod?.card && !showNewCardForm" class="flex flex-col gap-[10px] sm:flex-row sm:items-center sm:justify-between">
				<div class="flex items-center gap-[10px]">
					<div class="flex h-[32px] min-w-[52px] items-center justify-center rounded-[8px] bg-white px-[10px] text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-[var(--color-brand-primary)]">
						{{ defaultPaymentMethod.card.brand?.slice(0, 4) }}
					</div>
					<div class="min-w-0">
						<p class="text-[0.9375rem] font-medium text-[#252B42]">•••• {{ defaultPaymentMethod.card.last4 }}</p>
						<p class="mt-[2px] text-[0.75rem] text-[#737373]">Scad. {{ defaultPaymentMethod.card.exp_month }}/{{ defaultPaymentMethod.card.exp_year }}</p>
					</div>
				</div>
				<div class="flex flex-wrap items-center gap-[8px]">
					<NuxtLink to="/account/carte" class="text-[0.8125rem] font-medium text-[var(--color-brand-primary)] hover:opacity-80 transition-opacity">Cambia</NuxtLink>
					<button type="button" @click="openNewCardForm" class="text-[0.8125rem] font-medium text-[var(--color-brand-primary)] hover:opacity-80 transition-opacity cursor-pointer">
						Usa una nuova carta
					</button>
				</div>
			</div>

			<AccountWalletNewCardForm
				v-else-if="showNewCardForm"
				:is-preparing-new-card-form="isPreparingNewCardForm"
				v-model:card-holder-name="cardHolderName"
				:card-error="cardError"
				:has-saved-card="Boolean(defaultPaymentMethod?.card)"
				@close="closeNewCardForm" />

			<div v-else class="flex flex-col gap-[10px] rounded-[12px] border border-amber-200 bg-amber-50/80 px-[12px] py-[12px] text-[0.8125rem] text-amber-800">
				<div class="flex items-start gap-[10px]">
					<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-[1px] shrink-0 text-amber-600">
						<circle cx="12" cy="12" r="10" />
						<line x1="12" y1="8" x2="12" y2="12" />
						<line x1="12" y1="16" x2="12.01" y2="16" />
					</svg>
					<p class="leading-[1.5]">
						<template v-if="stripeConfigured">
							Nessuna carta salvata.
							<NuxtLink to="/account/carte" class="font-semibold text-amber-900 underline">Apri carte e pagamenti</NuxtLink>
							oppure aggiungila qui sotto.
						</template>
						<template v-else>
							Le ricariche con carta non sono ancora attive su questo sito. Quando Stripe sara configurato, qui potrai usare la tua carta salvata.
						</template>
					</p>
				</div>
				<div v-if="stripeConfigured" class="flex flex-wrap items-center gap-[8px]">
					<SfButton variant="secondary" size="sm" @click="openNewCardForm">
						<template #leading>
							<svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<line x1="12" y1="5" x2="12" y2="19" />
								<line x1="5" y1="12" x2="19" y2="12" />
							</svg>
						</template>
						Aggiungi carta
					</SfButton>
					<NuxtLink to="/account/carte" class="text-[0.8125rem] font-semibold text-amber-900 underline">Gestisci carte e pagamenti</NuxtLink>
				</div>
			</div>
		</div>

		<div class="mt-[14px] grid gap-[12px] desktop:grid-cols-[minmax(0,1fr)_240px] desktop:items-end">
			<div class="rounded-[16px] bg-[#FAFCFD] px-[14px] py-[12px]">
				<p class="text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-[#6B7280]">Ricarica pronta</p>
				<p class="mt-[4px] text-[0.9375rem] font-semibold text-[#252B42]">
					{{ topUpAmount ? `Importo selezionato: \u20AC${formatEuro(topUpAmount || 0)}` : 'Scegli un importo o inseriscilo manualmente' }}
				</p>
				<p class="mt-[4px] text-[0.75rem] leading-[1.45] text-[#737373]">
					{{ defaultPaymentMethod?.card || showNewCardForm ? 'Il pagamento usera la carta mostrata sopra.' : 'Serve una carta salvata o una nuova carta per procedere.' }}
				</p>
			</div>

			<button type="button"
				@click="handleTopUp"
				:disabled="!canSubmitTopUp"
				:class="[
					'btn-primary flex min-h-[38px] w-full items-center justify-center gap-[8px] text-[13px]',
					!canSubmitTopUp ? 'cursor-not-allowed bg-gray-200 text-gray-400' : 'cursor-pointer',
				]">
				<svg aria-hidden="true" v-if="!isLoading" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21 18v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1" />
					<path d="M14 11h4m-2-2v4" />
				</svg>
				<span>{{ topUpButtonLabel }}</span>
			</button>
		</div>

		<div
			v-if="message"
			:class="[
				'mt-[14px] flex items-center gap-[8px] rounded-[16px] px-[12px] py-[11px] text-[0.8125rem] font-medium',
				messageType === 'success' ? 'bg-[#f0fdf4] text-[#166534]' : 'bg-[#FFF5F2] text-[var(--color-brand-accent)]',
			]">
			<svg aria-hidden="true" v-if="messageType === 'success'" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
				<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
				<polyline points="22 4 12 14.01 9 11.01" />
			</svg>
			<svg aria-hidden="true" v-else width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
				<circle cx="12" cy="12" r="10" />
				<line x1="12" y1="8" x2="12" y2="12" />
				<line x1="12" y1="16" x2="12.01" y2="16" />
			</svg>
			{{ message }}
		</div>
	</div>
</template>

