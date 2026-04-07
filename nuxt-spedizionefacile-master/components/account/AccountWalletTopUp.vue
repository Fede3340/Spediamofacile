<!--
  FILE: components/account/AccountWalletTopUp.vue
  SCOPO: Sezione ricarica portafoglio — importi preimpostati, Stripe card form inline, pulsante ricarica.
  PROPS: defaultPaymentMethod (Object|null), stripeConfigured (Boolean).
  EVENTS: topUpSuccess — emesso dopo una ricarica riuscita (il parent aggiorna saldo/movimenti).
  LOGIC: composables/useWalletTopUp.js
-->
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
	<div class="rounded-[20px] border border-[#E9EBEC] bg-white p-[18px] shadow-sm desktop:sticky desktop:top-[108px] desktop:p-[20px]">
		<div class="flex items-start gap-[10px]">
			<div class="flex h-[36px] w-[36px] items-center justify-center rounded-[50px] bg-[#edf7f8]">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#095866]">
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
					'h-[46px] rounded-[12px] border-2 text-[0.875rem] font-semibold transition-all',
					topUpAmount == amount
						? 'border-[#095866] bg-[#095866] text-white shadow-[0_2px_8px_rgba(9,88,102,0.28)]'
						: 'border-[#E9EBEC] bg-white text-[#252B42] hover:border-[#095866] hover:bg-[#f0fafb]',
				]">
				&euro;{{ amount }}
			</button>
		</div>

		<div class="mt-[14px]">
			<label class="mb-[6px] block text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-[#095866]">Importo personalizzato</label>
			<div class="relative">
				<span class="absolute left-[16px] top-1/2 -translate-y-1/2 text-[1rem] font-medium text-[#737373]">&euro;</span>
				<input
					v-model="topUpAmount"
					type="number"
					min="1"
					step="0.01"
					placeholder="Inserisci importo"
					class="w-full rounded-[12px] border border-[#E9EBEC] bg-[#F5F6F9] py-[12px] pl-[38px] pr-[16px] text-[0.9375rem] transition-all focus:border-[#095866] focus:bg-white focus:outline-none focus:shadow-[0_0_0_3px_rgba(9,88,102,0.1)]" />
			</div>
		</div>

		<div class="mt-[14px] rounded-[16px] border border-[#E9EBEC] bg-[#FAFCFD] p-[14px]">
			<div v-if="defaultPaymentMethod?.card && !showNewCardForm" class="flex flex-col gap-[10px] sm:flex-row sm:items-center sm:justify-between">
				<div class="flex items-center gap-[10px]">
					<div class="flex h-[32px] min-w-[52px] items-center justify-center rounded-[8px] bg-white px-[10px] text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-[#095866]">
						{{ defaultPaymentMethod.card.brand?.slice(0, 4) }}
					</div>
					<div class="min-w-0">
						<p class="text-[0.9375rem] font-medium text-[#252B42]">&bull;&bull;&bull;&bull; {{ defaultPaymentMethod.card.last4 }}</p>
						<p class="mt-[2px] text-[0.75rem] text-[#737373]">Scad. {{ defaultPaymentMethod.card.exp_month }}/{{ defaultPaymentMethod.card.exp_year }}</p>
					</div>
				</div>
				<div class="flex flex-wrap items-center gap-[8px]">
					<NuxtLink to="/account/carte" class="text-[0.8125rem] font-medium text-[#095866] hover:underline">Cambia</NuxtLink>
					<button type="button" @click="openNewCardForm" class="text-[0.8125rem] font-medium text-[#095866] hover:underline cursor-pointer">
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
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-[1px] shrink-0 text-amber-600">
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
					<button type="button" @click="openNewCardForm" class="btn-secondary btn-compact inline-flex items-center gap-[6px]">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<line x1="12" y1="5" x2="12" y2="19" />
							<line x1="5" y1="12" x2="19" y2="12" />
						</svg>
						Aggiungi carta
					</button>
					<NuxtLink to="/account/carte" class="text-[0.8125rem] font-semibold text-amber-900 underline">Gestisci carte e pagamenti</NuxtLink>
				</div>
			</div>
		</div>

		<div class="mt-[14px] grid gap-[12px] desktop:grid-cols-[minmax(0,1fr)_240px] desktop:items-end">
			<div class="rounded-[14px] bg-[#FAFCFD] px-[14px] py-[12px]">
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
					'btn-cta flex min-h-[54px] w-full items-center justify-center gap-[8px] text-[0.9375rem]',
					!canSubmitTopUp ? 'cursor-not-allowed bg-gray-200 text-gray-400' : 'cursor-pointer',
				]">
				<svg v-if="!isLoading" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21 18v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1" />
					<path d="M14 11h4m-2-2v4" />
				</svg>
				<span>{{ topUpButtonLabel }}</span>
			</button>
		</div>

		<div
			v-if="message"
			:class="[
				'mt-[14px] flex items-center gap-[8px] rounded-[14px] px-[12px] py-[11px] text-[0.8125rem] font-medium',
				messageType === 'success' ? 'bg-[#f0fdf4] text-[#166534]' : 'bg-[#FFF5F2] text-[#E44203]',
			]">
			<svg v-if="messageType === 'success'" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
				<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
				<polyline points="22 4 12 14.01 9 11.01" />
			</svg>
			<svg v-else width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
				<circle cx="12" cy="12" r="10" />
				<line x1="12" y1="8" x2="12" y2="12" />
				<line x1="12" y1="16" x2="12.01" y2="16" />
			</svg>
			{{ message }}
		</div>
	</div>
</template>
