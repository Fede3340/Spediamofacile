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
	<!-- P14 RIDISEGNO: card compatta unica (era 2 sezioni separate con bg gradient + 2 padding 16-22).
	     Adesso 1 sola superficie, info inline, bottoni a destra. Da ~180px a ~90px. -->
	<div class="sf-order-card group/card overflow-hidden rounded-[12px] border border-[#E2E8EE] bg-white transition-colors duration-150 hover:bg-[#FBFCFD] focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[var(--color-brand-primary)]">
		<div class="px-[14px] py-[12px] tablet:px-[16px] tablet:py-[14px]">
			<div class="flex flex-col gap-[10px] tablet:flex-row tablet:items-center tablet:justify-between">
				<div class="min-w-0 flex-1">
					<!-- Riga 1: ID + prezzo + status -->
					<div class="flex flex-wrap items-center gap-[8px]">
						<span class="font-mono text-[0.875rem] font-bold text-[var(--color-brand-text)]">
							{{ getOrderReferenceLabel(order) }}
						</span>
						<span class="text-[0.875rem] font-bold text-[var(--color-brand-primary)]">
							{{ getOrderSubtotalLabel(order) }}
						</span>
						<span :class="statusColor(order.status)" class="inline-flex items-center rounded-full px-[8px] py-[2px] text-[0.6875rem] font-[700]">
							{{ order.status }}
						</span>
					</div>

					<!-- Riga 2: tratta -->
					<p class="mt-[4px] flex flex-wrap items-center gap-x-[6px] text-[0.8125rem] font-semibold text-[var(--color-brand-text)]">
						<span class="min-w-0 break-words">{{ getSenderName(order) }}</span>
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="13 6 19 12 13 18"/></svg>
						<span class="min-w-0 break-words">{{ getRecipientName(order) }}</span>
					</p>

					<!-- Riga 3: meta -->
					<div class="mt-[2px] flex flex-wrap items-center gap-x-[8px] gap-y-[2px] text-[0.6875rem] text-[var(--color-brand-text-muted)]">
						<span>{{ getOrderDateLabel(order) }}</span>
						<span aria-hidden="true">·</span>
						<span>{{ getOrderPackageLabel(order) }}</span>
						<template v-if="getServiceLabel(order)">
							<span aria-hidden="true">·</span>
							<span>{{ getServiceLabel(order) }}</span>
						</template>
						<span v-if="getTrackingLabel(order)" aria-hidden="true">·</span>
						<span v-if="getTrackingLabel(order)" class="font-mono text-[0.6875rem]">{{ getTrackingLabel(order) }}</span>
					</div>
				</div>

				<!-- Bottoni inline a destra -->
				<div class="shrink-0 flex flex-wrap gap-[6px]">
					<SfButton
						v-if="isPendingPayment(order)"
						size="sm"
						class="text-[0.75rem]"
						:to="{ path: '/la-tua-spedizione/2', query: { step: 'pagamento', order_id: order.id } }">
						Paga
					</SfButton>
					<SfButton
						v-if="getTrackingLabel(order)"
						size="sm"
						class="text-[0.75rem]"
						:to="`/traccia/${encodeURIComponent(getTrackingLabel(order))}`">
						Traccia
					</SfButton>
					<SfButton
						variant="secondary"
						size="sm"
						class="text-[0.75rem]"
						:to="`/account/spedizioni/${order.id}`">
						Dettagli
					</SfButton>
				</div>
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
