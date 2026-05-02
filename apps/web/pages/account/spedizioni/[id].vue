<!-- FILE: pages/account/spedizioni/[id].vue -->
<script setup>
import { formatDateTimeIt } from '~/utils/date.js';

definePageMeta({ middleware: ['app-auth'] });

const route = useRoute();
const orderId = route.params.id;

const {
	order, orderStatus, orderData,
	orderSubtotalLabel, orderRouteLabel, orderPackageCountLabel,
	isPendingPayment, isCancellable, isCancelledOrRefunded,
	formatDate, formatPrice, paymentMethodLabel,
	showAddPackageForm, addingPackage, addPackageError, addPackageSuccess, newPackage, submitAddPackage,
	regenerating, regenerateError, regenerateSuccess, downloadLabel, regenerateLabel,
	showCancelModal, refundEligibility, loadingEligibility,
	cancelling, cancelError, cancelSuccess, cancelReason, openCancelModal, confirmCancellation,
	executionData, pickupBusy, borderoBusy, documentsBusy, downloadBorderoBusy,
	executionError, executionSuccess,
	requestPickup, createBordero, sendDocuments, downloadBordero, openBordero,
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
const RESCHEDULE_BLOCKED = ['in_transit', 'out_for_delivery', 'delivered', 'in_giacenza', 'returned', 'refused', 'cancelled', 'refunded', 'payment_failed'];
const canRescheduleFinal = computed(() => !!orderData.value && !RESCHEDULE_BLOCKED.includes(orderData.value?.raw_status));
const onPickupRescheduled = async () => {
	if (order?.refresh) { try { await order.refresh(); } catch { /* ignore */ } }
};

// Pillole meta header (status + packages + total) — palette unificata via useStatusBadge.
const orderMetaPillStyle = (kind, status = '') => {
	if (kind === 'packages') return { backgroundColor: '#F0F6F7', color: 'var(--color-brand-primary)' };
	if (kind === 'total') return { backgroundColor: 'rgba(9,88,102,0.06)', color: 'var(--color-brand-primary)' };
	const s = useStatusBadgeStyle(status);
	return { backgroundColor: s.background, color: s.color };
};

const META_PILL_BASE = 'inline-flex items-center gap-1 rounded-full border border-brand-primary/15 bg-brand-primary/[0.06] px-2.5 py-1.5 text-xs font-bold leading-none text-brand-primary';
const DT_CLASS = 'text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-[var(--color-brand-text-muted)]';
const DD_CLASS = 'mt-[2px] text-[0.875rem] font-semibold leading-[1.3] text-[var(--color-brand-text)]';
</script>

<template>
	<section class="w-full min-h-[600px] py-5 tablet:py-6 desktop:py-7">
		<div class="my-container max-w-7xl">
			<!-- Loading -->
			<div v-if="orderStatus === 'pending'" class="space-y-[16px]">
				<div class="bg-white rounded-[16px] p-[16px] border border-[var(--color-brand-border)] animate-pulse">
					<div class="h-[24px] bg-gray-200 rounded w-[40%] mb-[16px]"/>
					<div class="h-[16px] bg-gray-200 rounded w-[60%] mb-[8px]"/>
					<div class="h-[16px] bg-gray-200 rounded w-[50%]"/>
				</div>
			</div>

			<template v-else-if="orderData">
				<AccountPageHeader
					eyebrow="Dettaglio ordine"
					:title="`Ordine #${orderData.id}`"
					description="Controlla stato, colli, tracking BRT ed eventuali azioni ancora disponibili per questa spedizione."
					:crumbs="[
						{ label: 'Account', to: '/account' },
						{ label: 'Spedizioni', to: '/account/spedizioni' },
						{ label: `Ordine #${orderData.id}` },
					]"
					back-to="/account/spedizioni"
					back-label="Torna alle spedizioni">
					<template #meta>
						<div class="flex flex-wrap gap-2">
							<span :class="META_PILL_BASE" :style="orderMetaPillStyle('status', orderData.status)">{{ orderData.status }}</span>
							<span class="inline-flex items-center gap-1 rounded-full border border-brand-border bg-brand-bg-alt px-2.5 py-1.5 text-xs font-bold leading-none text-brand-text-muted" :style="orderMetaPillStyle('packages')">{{ orderPackageCountLabel }}</span>
							<span :class="META_PILL_BASE" :style="orderMetaPillStyle('total')">{{ orderSubtotalLabel }}</span>
						</div>
					</template>
				</AccountPageHeader>

				<!-- Cancel feedback -->
				<SfAlert v-if="cancelSuccess" tone="success" class="mb-[16px]">{{ cancelSuccess }}</SfAlert>
				<SfAlert v-if="cancelError && !showCancelModal" tone="danger" class="mb-[16px]">{{ cancelError }}</SfAlert>

				<!-- Refund info -->
				<SfAlert
					v-if="isCancelledOrRefunded && orderData.refund_status === 'completed'"
					tone="warning"
					title="Ordine rimborsato"
					icon="mdi:cash-refund"
					class="mb-[16px]">
					<div class="grid grid-cols-2 gap-[8px] text-[0.8125rem]">
						<div><span class="font-medium">Importo rimborsato:</span> {{ orderData.refund_amount }}</div>
						<div v-if="orderData.cancellation_fee"><span class="font-medium">Commissione:</span> {{ orderData.cancellation_fee }}</div>
						<div><span class="font-medium">Metodo rimborso:</span> {{ paymentMethodLabel(orderData.refund_method) }}</div>
						<div v-if="orderData.refunded_at"><span class="font-medium">Data rimborso:</span> {{ orderData.refunded_at }}</div>
					</div>
					<p v-if="orderData.refund_reason" class="mt-[6px] text-[0.8125rem]"><span class="font-medium">Motivo:</span> {{ orderData.refund_reason }}</p>
				</SfAlert>

				<!-- P14: Status & Summary - card flat unica, no matryoshka. -->
				<div class="mb-[16px] rounded-[16px] border border-[var(--color-brand-border)] bg-white p-[16px] tablet:p-[18px]">
					<dl class="grid grid-cols-2 gap-x-[16px] gap-y-[10px] tablet:grid-cols-4">
						<div><dt :class="DT_CLASS">Tratta</dt><dd :class="DD_CLASS">{{ orderRouteLabel }}</dd></div>
						<div><dt :class="DT_CLASS">Creato</dt><dd :class="DD_CLASS">{{ formatDateTimeIt(orderData.created_at, '—') }}</dd></div>
						<div><dt :class="DT_CLASS">Totale</dt><dd class="mt-[2px] text-[0.9375rem] font-bold leading-[1.2] text-[var(--color-brand-primary)]">{{ orderSubtotalLabel }}</dd></div>
						<div><dt :class="DT_CLASS">Pagamento</dt><dd :class="DD_CLASS">{{ paymentMethodLabel(orderData.payment_method) }}</dd></div>
					</dl>
					<div
						v-if="isCancellable && !isCancelledOrRefunded"
						class="mt-[16px] flex flex-col gap-[10px] border-t border-[var(--color-brand-border)] pt-[16px] desktop:flex-row desktop:items-center desktop:justify-between">
						<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
							Per richiedere un rimborso, contatta l'<NuxtLink to="/account/assistenza" class="text-[var(--color-brand-primary)] font-semibold underline">assistenza</NuxtLink>.
						</p>
						<div class="flex flex-wrap gap-[8px]">
							<button
								v-if="canRescheduleFinal"
								type="button"
								class="btn btn-secondary btn-sm inline-flex items-center gap-[6px]"
								style="color:var(--color-brand-primary);border-color:rgba(9,88,102,0.22);"
								@click="showRescheduleModal = true">
								<UIcon name="mdi:calendar-edit" class="w-[14px] h-[14px]" aria-hidden="true" />
								Cambia data ritiro
							</button>
							<NuxtLink
								to="/account/assistenza"
								class="btn btn-secondary btn-sm inline-flex items-center gap-[6px]"
								style="color:var(--color-brand-primary);border-color:rgba(9,88,102,0.22);">
								<UIcon name="mdi:help-circle" class="w-[14px] h-[14px]" aria-hidden="true" />
								Assistenza
							</NuxtLink>
							<button type="button" class="btn btn-secondary btn-sm inline-flex items-center gap-[6px]" style="color:#B91C1C;border-color:rgba(185,28,28,0.22);" @click="openCancelModal">
								<UIcon name="mdi:close-circle" class="w-[14px] h-[14px]" aria-hidden="true" />
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
						<NuxtLink
							to="/account/assistenza"
							class="btn btn-secondary btn-sm inline-flex items-center gap-[6px]"
							style="color:var(--color-brand-primary);border-color:rgba(9,88,102,0.22);">
							<UIcon name="mdi:help-circle" class="w-[14px] h-[14px]" aria-hidden="true" />
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
					@update:show-form="showAddPackageForm = $event; if ($event) addPackageSuccess = false;"
					@update:new-package="newPackage = $event"
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
						<UIcon name="mdi:arrow-left" class="w-[18px] h-[18px]" aria-hidden="true" />
						Torna alle spedizioni
					</SfButton>
				</div>
			</template>

			<!-- Not found -->
			<div v-else class="bg-white rounded-[16px] p-[20px] border border-[var(--color-brand-border)] text-center">
				<p class="text-[1rem] text-[var(--color-brand-text-secondary)]">Ordine non trovato.</p>
				<NuxtLink to="/account/spedizioni" class="btn btn-secondary btn-sm mt-[16px] inline-flex items-center gap-[6px]">
					<UIcon name="mdi:arrow-left" class="w-[18px] h-[18px]" aria-hidden="true" />
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
