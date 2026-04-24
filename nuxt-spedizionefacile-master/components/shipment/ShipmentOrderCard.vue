<!--
  Componente: ShipmentOrderCard
  Card singolo ordine nella lista spedizioni.
  Versione riallineata al prototipo: struttura piu lineare, meno box interni,
  priorita a riferimento, tratta, tracking e azioni davvero utili.
-->
<script setup>
const props = defineProps({
	order: { type: Object, required: true },
	statusColor: { type: Function, required: true },
	statusRaw: { type: Function, required: true },
	getOrderPackageLabel: { type: Function, required: true },
	getServiceLabel: { type: Function, required: true },
	getTrackingLabel: { type: Function, required: true },
	getOrderReferenceLabel: { type: Function, required: true },
	getOrderDateLabel: { type: Function, required: true },
	getOrderSubtotalLabel: { type: Function, required: true },
	getRouteLabel: { type: Function, required: true },
	getSenderName: { type: Function, required: true },
	getRecipientName: { type: Function, required: true },
	isPendingPayment: { type: Function, required: true },
	getPendingReason: { type: Function, required: true },
	// -- ARCHIVIATO 2026-04-20: prop legati a "Salva configurata" (_archive/frontend-simplification-2026-04-20/features/spedizioni-configurate) --
	// isAlreadySaved: { type: Function, required: true },
	// savingToConfigured: { type: Object, default: () => ({}) },
	saveError: { type: Object, default: () => ({}) },
});

// -- ARCHIVIATO 2026-04-20: emit saveToConfigured (_archive/frontend-simplification-2026-04-20/features/spedizioni-configurate) --
// const emit = defineEmits(['saveToConfigured']);
</script>

<template>
	<div class="sf-order-card group/card overflow-hidden rounded-[16px] border border-[#E2E8EE] bg-white shadow-[0_3px_14px_rgba(15,23,42,0.04)] transition-[transform,box-shadow] duration-200 ease-out hover:-translate-y-[2px] hover:shadow-[0_10px_28px_rgba(15,23,42,0.08)] focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[var(--color-brand-primary)]">
		<div class="border-b border-[rgba(9,88,102,0.08)] bg-[linear-gradient(180deg,#FBFCFD_0%,#F8FAFB_100%)] px-[18px] py-[16px] tablet:px-[22px]">
			<div class="flex flex-col gap-[12px] tablet:flex-row tablet:items-start tablet:justify-between">
				<div class="min-w-0 flex-1 order-2 tablet:order-1">
					<div class="flex flex-wrap items-center gap-[10px]">
						<span class="text-[18px] font-bold leading-[1.1] text-[var(--color-brand-text)] font-mono tracking-tight">
							{{ getOrderReferenceLabel(order) }}
						</span>
						<span class="inline-flex items-center rounded-full bg-[#F2F6F8] px-[10px] py-[3px] text-[0.75rem] font-semibold text-[var(--color-brand-primary)]">
							{{ getOrderSubtotalLabel(order) }}
						</span>
					</div>

					<p class="mt-[4px] text-[13px] font-medium leading-[1.3] text-[var(--color-brand-text-secondary)]">
						{{ getOrderDateLabel(order) }}
					</p>

					<p class="mt-[10px] flex flex-wrap items-center gap-x-[8px] gap-y-[4px] text-[1rem] font-bold leading-[1.25] text-[var(--color-brand-text)] tablet:text-[1.06rem]">
						<span class="min-w-0 break-words">{{ getSenderName(order) }}</span>
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="13 6 19 12 13 18"/></svg>
						<span class="min-w-0 break-words">{{ getRecipientName(order) }}</span>
					</p>

					<div class="mt-[8px] flex flex-wrap items-center gap-x-[10px] gap-y-[6px] text-[0.82rem] text-[var(--color-brand-text-secondary)]">
						<span>{{ getOrderPackageLabel(order) }}</span>
						<span aria-hidden="true" class="text-[#D5DCE2]">•</span>
						<span>BRT {{ getServiceLabel(order) }}</span>
						<span v-if="getTrackingLabel(order)" class="inline-flex items-center rounded-full border border-[#E2E8EE] bg-[#F5F7F9] px-[10px] py-[3px] font-mono text-[0.78rem] font-semibold tracking-tight text-[var(--color-brand-text)]">
							{{ getTrackingLabel(order) }}
						</span>
					</div>
				</div>

				<div class="order-1 flex items-start justify-start tablet:order-2 tablet:justify-end">
					<span :class="statusColor(order.status)" class="inline-flex items-center rounded-full px-[12px] py-[5px] text-[0.7rem] font-[700] uppercase tracking-[0.08em]">
						{{ order.status }}
					</span>
				</div>
			</div>
		</div>

		<div class="px-[18px] py-[16px] tablet:px-[22px]">
			<div class="flex flex-col gap-[12px] tablet:flex-row tablet:flex-wrap tablet:items-center tablet:justify-end">
				<NuxtLink
					v-if="isPendingPayment(order)"
					:to="{ path: '/la-tua-spedizione/2', query: { step: 'pagamento', order_id: order.id } }"
					class="btn btn-cta inline-flex items-center justify-center gap-[8px] w-full min-h-[40px] tablet:w-auto focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-brand-primary)]">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20,8H4V6H20M20,18H4V12H20M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z"/></svg>
					Paga ora
				</NuxtLink>

				<!-- -- ARCHIVIATO 2026-04-20: bottone "Salva configurata" + pill "Salvata" (_archive/frontend-simplification-2026-04-20/features/spedizioni-configurate) -- -->

				<NuxtLink
					v-if="getTrackingLabel(order)"
					:to="`/traccia/${encodeURIComponent(getTrackingLabel(order))}`"
					title="Traccia spedizione"
					class="btn btn-primary inline-flex items-center justify-center gap-[8px] w-full min-h-[40px] tablet:w-auto focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-brand-primary)]">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
					<span>Traccia</span>
				</NuxtLink>

				<NuxtLink
					:to="`/account/spedizioni/${order.id}`"
					title="Vedi dettagli"
					class="btn btn-secondary inline-flex items-center justify-center gap-[8px] w-full min-h-[40px] tablet:w-auto focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-brand-primary)]">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>
					<span>Dettagli</span>
				</NuxtLink>
			</div>
		</div>

		<div v-if="isPendingPayment(order)" class="mx-[22px] mb-[12px] flex items-center gap-[12px] rounded-[16px] border border-amber-200 bg-amber-50 px-[16px] py-[12px]">
			<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#F59E0B" class="shrink-0"><path d="M12,2L1,21H23M12,6L19.53,19H4.47M11,10V14H13V10M11,16V18H13V16"/></svg>
			<p class="text-[0.8125rem] text-amber-800 flex-1">{{ getPendingReason(order) }}</p>
		</div>

		<div v-if="saveError[order.id]" class="mx-[22px] mb-[10px] flex items-center gap-[10px] rounded-[16px] border border-red-200 bg-red-50 px-[16px] py-[10px]">
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#EF4444" class="shrink-0"><path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/></svg>
			<p class="text-red-600 text-[0.8125rem] font-medium">{{ saveError[order.id] }}</p>
		</div>

		<div v-if="statusRaw(order.status) === 'refunded' && order.refund_amount" class="mx-[22px] mb-[16px] flex items-center gap-[10px] rounded-[16px] border border-orange-200 bg-orange-50 px-[16px] py-[10px]">
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#EA580C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
			<p class="text-orange-700 text-[0.8125rem]">Rimborso di <span class="font-semibold">{{ order.refund_amount }}</span> effettuato<span v-if="order.refunded_at"> il {{ order.refunded_at }}</span></p>
		</div>
	</div>
</template>
