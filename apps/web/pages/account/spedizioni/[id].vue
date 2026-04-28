<!-- FILE: pages/account/spedizioni/[id].vue -->
<script setup>
definePageMeta({ middleware: ['app-auth'] });
import { formatDateTimeIt } from '~/utils/date.js';

const route = useRoute();
const orderId = route.params.id;

const {
	order,
	orderStatus,
	orderData,
	orderSubtotalLabel,
	orderRouteLabel,
	orderPackageCountLabel,
	isPendingPayment,
	isCancellable,
	isCancelledOrRefunded,
	formatDate,
	formatPrice,
	paymentMethodLabel,
	showAddPackageForm,
	addingPackage,
	addPackageError,
	addPackageSuccess,
	newPackage,
	submitAddPackage,
	regenerating,
	regenerateError,
	regenerateSuccess,
	downloadLabel,
	regenerateLabel,
	showCancelModal,
	refundEligibility,
	loadingEligibility,
	cancelling,
	cancelError,
	cancelSuccess,
	cancelReason,
	openCancelModal,
	confirmCancellation,
	executionData,
	pickupBusy,
	borderoBusy,
	documentsBusy,
	downloadBorderoBusy,
	executionError,
	executionSuccess,
	requestPickup,
	createBordero,
	sendDocuments,
	downloadBordero,
	openBordero,
} = useOrderDetail(orderId);

useSeoMeta({
	title: () => (orderData.value?.id ? `Ordine #${orderData.value.id}` : 'Dettaglio spedizione'),
	ogTitle: () => (orderData.value?.id ? `Ordine #${orderData.value.id}` : 'Dettaglio spedizione'),
	description: 'Consulta stato, colli, tracking e documenti della tua spedizione su SpediamoFacile.',
	ogDescription: 'Dettaglio ordine con stato, colli, tracking e documenti su SpediamoFacile.',
	robots: 'noindex, nofollow',
});

// F04 — Modale riprogrammazione data ritiro
const showRescheduleModal = ref(false);
const canRescheduleFinal = computed(() => {
	const raw = orderData.value?.raw_status;
	const blockedStatuses = ['in_transit', 'out_for_delivery', 'delivered', 'in_giacenza', 'returned', 'refused', 'cancelled', 'refunded', 'payment_failed'];
	return !!orderData.value && !blockedStatuses.includes(raw);
});
const onPickupRescheduled = async () => {
	// Ricarica i dati ordine dopo riprogrammazione
	if (order?.refresh) {
		try { await order.refresh(); } catch (_) { /* ignore */ }
	}
};

// Palette unificata via useStatusBadge composable (P5 design system).
// Convertiamo il { color, background } al formato Vue style { backgroundColor, color }.
const orderMetaPillStyle = (kind, status = '') => {
	if (kind === 'packages') return { backgroundColor: '#F0F6F7', color: 'var(--color-brand-primary)' };
	if (kind === 'total') return { backgroundColor: 'rgba(9,88,102,0.06)', color: 'var(--color-brand-primary)' };
	const s = useStatusBadgeStyle(status);
	return { backgroundColor: s.background, color: s.color };
};
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-[20px] tablet:py-[24px] desktop:py-[28px]">
		<div class="my-container max-w-[1280px]">
			<!-- Loading -->
			<div v-if="orderStatus === 'pending'" class="space-y-[16px]">
				<div class="bg-white rounded-[16px] p-[16px] border border-[var(--color-brand-border)] animate-pulse">
					<div class="h-[24px] bg-gray-200 rounded w-[40%] mb-[16px]"></div>
					<div class="h-[16px] bg-gray-200 rounded w-[60%] mb-[8px]"></div>
					<div class="h-[16px] bg-gray-200 rounded w-[50%]"></div>
				</div>
			</div>

			<template v-else-if="orderData">
				<AccountPageHeader
					eyebrow="Dettaglio ordine"
					:title="`Ordine #${orderData.id}`"
					:description="'Controlla stato, colli, tracking BRT ed eventuali azioni ancora disponibili per questa spedizione.'"
					:crumbs="[
						{ label: 'Account', to: '/account' },
						{ label: 'Spedizioni', to: '/account/spedizioni' },
						{ label: `Ordine #${orderData.id}` },
					]"
					back-to="/account/spedizioni"
					back-label="Torna alle spedizioni">
					<template #meta>
						<div class="flex flex-wrap gap-[8px]">
							<span class="sf-account-meta-pill" :style="orderMetaPillStyle('status', orderData.status)">{{ orderData.status }}</span>
							<span class="sf-account-meta-pill sf-account-meta-pill--muted" :style="orderMetaPillStyle('packages')">
								{{ orderPackageCountLabel }}
							</span>
							<span class="sf-account-meta-pill" :style="orderMetaPillStyle('total')">{{ orderSubtotalLabel }}</span>
						</div>
					</template>
				</AccountPageHeader>

				<!-- Cancel success/error messages -->
				<div
					v-if="cancelSuccess"
					class="bg-[#f0fdf4] border border-[#d1fae5] rounded-[16px] px-[16px] py-[14px] flex items-start gap-[12px] mb-[16px]">
					<svg aria-hidden="true"
						xmlns="http://www.w3.org/2000/svg"
						width="22"
						height="22"
						viewBox="0 0 24 24"
						fill="none"
						stroke="#059669"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
						class="shrink-0 mt-[1px]">
						<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
						<polyline points="22 4 12 14.01 9 11.01" />
					</svg>
					<p class="text-[0.875rem] text-[#0a8a7a] flex-1">{{ cancelSuccess }}</p>
				</div>
				<div
					v-if="cancelError && !showCancelModal"
					class="bg-red-50 border border-red-200 rounded-[16px] px-[16px] py-[14px] flex items-start gap-[12px] mb-[16px]">
					<svg aria-hidden="true"
						xmlns="http://www.w3.org/2000/svg"
						width="22"
						height="22"
						viewBox="0 0 24 24"
						fill="none"
						stroke="#EF4444"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
						class="shrink-0 mt-[1px]">
						<circle cx="12" cy="12" r="10" />
						<line x1="12" y1="8" x2="12" y2="12" />
						<line x1="12" y1="16" x2="12.01" y2="16" />
					</svg>
					<p class="text-[0.875rem] text-red-700 flex-1">{{ cancelError }}</p>
				</div>

				<!-- Refund info banner -->
				<div
					v-if="isCancelledOrRefunded && orderData.refund_status === 'completed'"
					class="bg-orange-50 border border-orange-200 rounded-[16px] px-[16px] py-[14px] mb-[16px]">
					<div class="flex items-start gap-[12px]">
						<svg aria-hidden="true"
							xmlns="http://www.w3.org/2000/svg"
							width="22"
							height="22"
							viewBox="0 0 24 24"
							fill="none"
							stroke="#EA580C"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
							class="shrink-0 mt-[1px]">
							<polyline points="23 4 23 10 17 10" />
							<path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
						</svg>
						<div class="flex-1">
							<p class="text-[0.875rem] font-semibold text-orange-800 mb-[6px]">Ordine rimborsato</p>
							<div class="grid grid-cols-2 gap-[8px] text-[0.8125rem] text-orange-700">
								<div>
									<span class="font-medium">Importo rimborsato:</span>
									{{ orderData.refund_amount }}
								</div>
								<div v-if="orderData.cancellation_fee">
									<span class="font-medium">Commissione:</span>
									{{ orderData.cancellation_fee }}
								</div>
								<div>
									<span class="font-medium">Metodo rimborso:</span>
									{{ paymentMethodLabel(orderData.refund_method) }}
								</div>
								<div v-if="orderData.refunded_at">
									<span class="font-medium">Data rimborso:</span>
									{{ orderData.refunded_at }}
								</div>
							</div>
							<p v-if="orderData.refund_reason" class="mt-[6px] text-[0.8125rem] text-orange-600">
								<span class="font-medium">Motivo:</span>
								{{ orderData.refund_reason }}
							</p>
						</div>
					</div>
				</div>

				<!-- P14: Status & Summary - matryoshka rimossa (era card-in-card-in-card).
				     Ora unica card con 4 colonne dl flat senza border interno. -->
				<div class="mb-[16px] rounded-[16px] border border-[var(--color-brand-border)] bg-white p-[16px] tablet:p-[18px]">
					<dl class="grid grid-cols-2 gap-x-[16px] gap-y-[10px] tablet:grid-cols-4">
						<div>
							<dt class="text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-[var(--color-brand-text-muted)]">Tratta</dt>
							<dd class="mt-[2px] text-[0.875rem] font-semibold leading-[1.3] text-[var(--color-brand-text)]">{{ orderRouteLabel }}</dd>
						</div>
						<div>
							<dt class="text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-[var(--color-brand-text-muted)]">Creato</dt>
							<dd class="mt-[2px] text-[0.875rem] font-semibold leading-[1.3] text-[var(--color-brand-text)]">{{ formatDateTimeIt(orderData.created_at, '—') }}</dd>
						</div>
						<div>
							<dt class="text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-[var(--color-brand-text-muted)]">Totale</dt>
							<dd class="mt-[2px] text-[0.9375rem] font-bold leading-[1.2] text-[var(--color-brand-primary)]">{{ orderSubtotalLabel }}</dd>
						</div>
						<div>
							<dt class="text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-[var(--color-brand-text-muted)]">Pagamento</dt>
							<dd class="mt-[2px] text-[0.875rem] font-semibold leading-[1.3] text-[var(--color-brand-text)]">{{ paymentMethodLabel(orderData.payment_method) }}</dd>
						</div>
					</dl>
					<div
						v-if="isCancellable && !isCancelledOrRefunded"
						class="mt-[16px] flex flex-col gap-[10px] border-t border-[var(--color-brand-border)] pt-[16px] desktop:flex-row desktop:items-center desktop:justify-between">
						<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
							Per richiedere un rimborso, contatta l'
							<NuxtLink to="/account/assistenza" class="text-[var(--color-brand-primary)] font-semibold underline">assistenza</NuxtLink>
							.
						</p>
						<div class="flex flex-wrap gap-[8px]">
							<button
								v-if="canRescheduleFinal"
								type="button"
								class="btn btn-secondary btn-sm inline-flex items-center gap-[6px]"
								style="color:var(--color-brand-primary);border-color:rgba(9,88,102,0.22);"
								@click="showRescheduleModal = true">
								<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[14px] h-[14px]" fill="currentColor">
									<path d="M19,3H18V1H16V3H8V1H6V3H5C3.89,3 3,3.9 3,5V21A2,2 0 0,0 5,23H19A2,2 0 0,0 21,21V5A2,2 0 0,0 19,3M19,21H5V8H19V21Z" />
								</svg>
								Cambia data ritiro
							</button>
							<!-- -- ARCHIVIATO 2026-04-20: CTA Reclamo dedicato (_archive/frontend-simplification-2026-04-20/features/reclami-dedicato) -- -->
							<NuxtLink
								to="/account/assistenza"
								class="btn btn-secondary btn-sm inline-flex items-center gap-[6px]"
								style="color:var(--color-brand-primary);border-color:rgba(9,88,102,0.22);">
								<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[14px] h-[14px]" fill="currentColor">
									<path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
								</svg>
								Assistenza
							</NuxtLink>
							<button type="button" @click="openCancelModal" class="btn btn-secondary btn-sm inline-flex items-center gap-[6px]" style="color:#B91C1C;border-color:rgba(185,28,28,0.22);">
								<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[14px] h-[14px]" fill="currentColor">
									<path
										d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M7,10L12,15L17,10H7Z" />
								</svg>
								Blocca il pacco
							</button>
						</div>
					</div>
					<div
						v-else-if="orderData?.brt_parcel_id && !isCancelledOrRefunded"
						class="mt-[16px] flex flex-col gap-[10px] border-t border-[var(--color-brand-border)] pt-[16px] desktop:flex-row desktop:items-center desktop:justify-between">
						<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
							Hai riscontrato un problema con questa spedizione? Contatta l'assistenza e il nostro team ti risponderà entro 48h.
						</p>
						<!-- -- ARCHIVIATO 2026-04-20: CTA Reclamo dedicato (_archive/frontend-simplification-2026-04-20/features/reclami-dedicato) -- -->
						<NuxtLink
							to="/account/assistenza"
							class="btn btn-secondary btn-sm inline-flex items-center gap-[6px]"
							style="color:var(--color-brand-primary);border-color:rgba(9,88,102,0.22);">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[14px] h-[14px]" fill="currentColor">
								<path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
							</svg>
							Assistenza
						</NuxtLink>
					</div>
				</div>

				<!-- Packages -->
				<div v-if="orderData.packages?.length" class="space-y-[12px]">
					<ShipmentOrderPackageCard
						v-for="(pkg, pkgIdx) in orderData.packages"
						:key="pkg.id || pkgIdx"
						:pkg="pkg"
						:index="pkgIdx"
						:has-pudo="!!order?.data?.brt_pudo_id"
						:format-price="formatPrice" />
				</div>

				<!-- Aggiungi collo -->
				<ShipmentAddPackageForm
					v-if="isPendingPayment"
					:show-form="showAddPackageForm"
					:adding-package="addingPackage"
					:add-package-error="addPackageError"
					:add-package-success="addPackageSuccess"
					:new-package="newPackage"
					@update:show-form="
						showAddPackageForm = $event;
						if ($event) addPackageSuccess = false;
					"
					@submit="submitAddPackage" />

				<!-- BRT Section -->
				<ShipmentBrtSection
					v-if="!isCancelledOrRefunded"
					:order-data="orderData"
					:regenerating="regenerating"
					:regenerate-error="regenerateError"
					:regenerate-success="regenerateSuccess"
					@download-label="downloadLabel"
					@regenerate-label="regenerateLabel" />

				<ShipmentExecutionSection
					v-if="!isCancelledOrRefunded"
					:order-data="orderData"
					:execution-data="executionData"
					:pickup-busy="pickupBusy"
					:bordero-busy="borderoBusy"
					:documents-busy="documentsBusy"
					:download-bordero-busy="downloadBorderoBusy"
					:execution-error="executionError"
					:execution-success="executionSuccess"
					:format-date="formatDate"
					@request-pickup="requestPickup"
					@create-bordero="createBordero"
					@send-documents="sendDocuments"
					@download-bordero="downloadBordero"
					@open-bordero="openBordero" />

				<!-- Back -->
				<div class="mt-[24px]">
					<SfButton variant="secondary" size="sm" to="/account/spedizioni">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<line x1="19" y1="12" x2="5" y2="12" />
							<polyline points="12 19 5 12 12 5" />
						</svg>
						Torna alle spedizioni
					</SfButton>
				</div>
			</template>

			<!-- Not found -->
			<div v-else class="bg-white rounded-[16px] p-[20px] border border-[var(--color-brand-border)] text-center">
				<p class="text-[1rem] text-[var(--color-brand-text-secondary)]">Ordine non trovato.</p>
				<NuxtLink to="/account/spedizioni" class="btn btn-secondary btn-sm mt-[16px] inline-flex items-center gap-[6px]">
					<svg aria-hidden="true"
						xmlns="http://www.w3.org/2000/svg"
						width="18"
						height="18"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round">
						<line x1="19" y1="12" x2="5" y2="12" />
						<polyline points="12 19 5 12 12 5" />
					</svg>
					Torna alle spedizioni
				</NuxtLink>
			</div>

			<!-- Cancel Modal -->
			<ShipmentCancelModal
				:show="showCancelModal"
				:loading-eligibility="loadingEligibility"
				:refund-eligibility="refundEligibility"
				:cancelling="cancelling"
				:cancel-error="cancelError"
				:cancel-reason="cancelReason"
				:order-subtotal="orderData?.subtotal || ''"
				:payment-method-label="paymentMethodLabel"
				@update:show="showCancelModal = $event"
				@update:cancel-reason="cancelReason = $event"
				@confirm="confirmCancellation" />

			<!-- F04 — Reschedule Pickup Modal -->
			<ReschedulePickupModal
				v-if="orderData"
				:show="showRescheduleModal"
				:order-id="orderData.id"
				:current-pickup-date="orderData.pickup_date"
				:current-time-slot="orderData.pickup_time_slot"
				:current-notes="orderData.pickup_notes"
				@update:show="showRescheduleModal = $event"
				@updated="onPickupRescheduled" />
		</div>
	</section>
</template>
