<script setup>
defineProps({
	payments: { type: Object, default: null },
	status: { type: String, default: '' },
	cardsFeatureAvailable: { type: Boolean, default: false },
	isAdmin: { type: Boolean, default: false },
	deleteConfirmId: { type: [String, null], default: null },
});

const emit = defineEmits([
	'toggle-form', 'set-default', 'delete',
	'ask-delete', 'cancel-delete', 'open-admin-settings',
]);

const getBrandIcon = (brand) => {
	const brands = { visa: 'Visa', mastercard: 'Mastercard', amex: 'Amex', discover: 'Discover' };
	return brands[brand?.toLowerCase()] || brand || 'Carta';
};
</script>

<template>
	<div v-if="status === 'pending'">
		<div v-for="n in 2" :key="n" class="mb-2 rounded-card bg-white p-3.5 shadow-sf-sm">
			<div class="flex animate-pulse items-center gap-3">
				<div class="h-[34px] w-[50px] rounded-lg bg-gray-200" />
				<div class="flex-1 space-y-1.5">
					<div class="h-3 w-2/5 rounded-full bg-gray-200" />
					<div class="h-2.5 w-1/4 rounded-full bg-gray-200" />
				</div>
			</div>
		</div>
	</div>

	<template v-else-if="payments && payments.data">
		<div v-if="payments.data.length === 0" class="rounded-card border border-transparent bg-white p-5 text-center shadow-sf-sm">
			<div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-brand-bg-alt">
				<svg aria-hidden="true" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#095866" opacity="0.4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2" /><line x1="1" y1="10" x2="23" y2="10" /></svg>
			</div>
			<h2 class="mb-2 font-display text-lg font-extrabold text-brand-text">
				{{ cardsFeatureAvailable ? 'Nessuna carta salvata' : 'Pagamenti con carta non ancora attivi' }}
			</h2>
			<p class="mx-auto mb-5 max-w-[460px] text-sm leading-relaxed text-brand-text-secondary">
				<span v-if="cardsFeatureAvailable">Aggiungi una carta per pagare più in fretta.</span>
				<span v-else-if="isAdmin">Configura Stripe per attivare carte e wallet.</span>
				<span v-else>Le carte saranno disponibili appena Stripe sarà attivo.</span>
			</p>
			<SfButton v-if="cardsFeatureAvailable" variant="primary" size="sm" @click="emit('toggle-form')">Aggiungi la tua prima carta</SfButton>
			<SfButton v-else-if="isAdmin" variant="primary" size="sm" @click="emit('open-admin-settings')">Apri impostazioni Stripe</SfButton>
			<p v-else class="text-sm font-medium text-brand-text-secondary">Quando Stripe sarà attivo, qui comparirà il pulsante per aggiungere la tua prima carta.</p>
		</div>

		<div v-else class="space-y-3.5">
			<div
				v-for="(payment, index) in payments.data" :key="index"
				:class="[
					'rounded-card border bg-white p-4 shadow-sf-sm transition-colors lg:p-[18px]',
					payment.default ? 'border-brand-primary' : 'border-transparent hover:bg-brand-primary/[0.03]',
				]">
				<div class="flex flex-col gap-3 tablet:flex-row tablet:items-center tablet:gap-3.5">
					<div :class="[
						'flex h-[34px] w-[50px] shrink-0 items-center justify-center rounded-lg text-[0.7rem] font-bold uppercase tracking-wide',
						payment.default ? 'bg-gradient-to-br from-brand-primary to-brand-primary-hover text-white' : 'bg-brand-bg-alt text-brand-text',
					]">
						{{ getBrandIcon(payment.brand)?.slice(0, 4) }}
					</div>
					<div class="w-full min-w-0 flex-1">
						<div class="flex flex-wrap items-center gap-2">
							<span class="text-[0.9375rem] font-semibold text-brand-text">{{ getBrandIcon(payment.brand) }} •••• {{ payment.last4 }}</span>
							<span v-if="payment.default" class="inline-block rounded-full bg-brand-primary/[0.10] px-2.5 py-[3px] text-[0.6875rem] font-semibold text-brand-primary">Predefinita</span>
						</div>
						<div class="mt-1 flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
							<span class="text-[0.8125rem] text-brand-text-secondary">{{ payment.holder_name }}</span>
							<span class="text-xs text-brand-text-muted">Scad. {{ payment.exp_month }}/{{ payment.exp_year }}</span>
						</div>
					</div>
					<div class="flex w-full flex-wrap items-center gap-2 tablet:w-auto tablet:justify-end">
						<SfButton v-if="!payment.default" variant="secondary" size="sm" @click="emit('set-default', payment.id)">
							<template #leading>
								<svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg>
							</template>
							Imposta predefinita
						</SfButton>
						<template v-if="deleteConfirmId !== payment.id">
							<SfButton variant="danger" size="sm" @click="emit('ask-delete', payment.id)">
								<template #leading>
									<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /></svg>
								</template>
								Elimina
							</SfButton>
						</template>
						<template v-else>
							<div class="flex flex-wrap items-center gap-1.5">
								<SfButton variant="primary" size="sm" @click="emit('delete', payment.id)">
									<template #leading>
										<svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
									</template>
									Conferma
								</SfButton>
								<SfButton variant="secondary" size="sm" @click="emit('cancel-delete')">
									<template #leading>
										<svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
									</template>
									Annulla
								</SfButton>
							</div>
						</template>
					</div>
				</div>
			</div>
		</div>
	</template>

	<div class="mt-3.5 flex items-start gap-2.5 rounded-[14px] bg-brand-bg-alt p-3">
		<svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-text-secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-px shrink-0"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg>
		<p class="text-xs leading-snug text-brand-text-secondary">
			I dati delle carte sono gestiti in modo sicuro da Stripe. Non conserviamo mai i numeri completi delle tue carte.
		</p>
	</div>
</template>
