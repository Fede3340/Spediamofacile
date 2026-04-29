<script setup>
// CRITICAL: vedi CLAUDE.md "Eccezioni documentate" — non splittare senza E2E gating Stripe.
// File 716 LOC: selezione metodo pagamento (Stripe/wallet/bonifico) + 3DS + idempotency.
import { computed } from 'vue';
import CheckoutBilling from '~/components/checkout/Billing.vue';
import CheckoutSuccess from '~/components/checkout/Success.vue';
import CheckoutPaymentMethods from '~/components/checkout/PaymentMethods.vue';
import CheckoutPaymentFooter from '~/components/checkout/PaymentFooter.vue';
import CheckoutConfirmModal from '~/components/checkout/ConfirmModal.vue';

/**
 * Template estratto da pages/la-tua-spedizione/[step].vue.
 * Step 4 del funnel: riepilogo espandibile + metodi di pagamento + billing + footer.
 * Tutta la logica (useCheckout, stripe, coupon, confirm modal) resta nel parent.
 */
const props = defineProps({
	isOpen: { type: Boolean, required: true },
	paymentSuccess: { type: Boolean, default: false },
	trattaLabel: { type: String, default: '' },
	finalTotalFormatted: { type: String, default: '' },
	summaryTotalPrice: { type: String, default: '' },
	// Subtotale (EURO numerico, prima dello sconto coupon). Il parent passa
	// getNumberTotal da useCheckout. Usato per riga "Subtotale" quando c'e' coupon.
	subtotal: { type: [Number, String], default: null },
	colloLabel: { type: String, default: '' },
	confirmationPickupDate: { type: String, default: '' },
	paymentDeliveryLabel: { type: String, default: '' },
	paymentMethodLabel: { type: String, default: '' },
	paymentSummaryExpanded: { type: Boolean, default: false },
	summaryPackageLabel: { type: String, default: '' },
	summaryDimensionsLabel: { type: String, default: '' },
	confirmationOriginContact: { type: String, default: '' },
	confirmationDestinationContact: { type: String, default: '' },
	originAddress: { type: Object, required: true },
	destinationAddress: { type: Object, required: true },
	deliveryMode: { type: String, required: true },
	paymentSummaryServicesLabel: { type: String, default: '' },
	resolvedContentDescription: { type: String, default: '' },
	paymentBootstrapPending: { type: Boolean, default: false },
	visiblePaymentBootstrapError: { type: String, default: '' },
	successOrderId: { type: [String, Number, null], default: null },
	paymentMethod: { type: String, default: '' },
	checkoutPageReady: { type: Boolean, default: false },
	couponPanelOpen: { type: Boolean, default: false },
	couponApplied: { type: [Object, null], default: null },
	couponCode: { type: String, default: '' },
	couponLoading: { type: Boolean, default: false },
	couponError: { type: String, default: '' },
	paymentMethodOptions: { type: Array, default: () => [] },
	cardPaymentsUnavailable: { type: Boolean, default: false },
	cardPaymentsNotice: { type: String, default: '' },
	hasSavedCard: { type: Boolean, default: false },
	defaultPayment: { type: [Object, null], default: null },
	useNewCard: { type: Boolean, default: false },
	shouldShowCardForm: { type: Boolean, default: false },
	stripeLoading: { type: Boolean, default: false },
	cardError: { type: String, default: '' },
	saveCardForFuture: { type: Boolean, default: false },
	setCheckoutCardRef: { type: Function, required: true },
	walletFormatted: { type: String, default: '' },
	walletLoaded: { type: Boolean, default: false },
	walletSufficient: { type: Boolean, default: false },
	/* Wallet express — Apple Pay / Google Pay (Stripe PaymentRequestButton) */
	canMakePayment: { type: Boolean, default: false },
	isAppleAvailable: { type: Boolean, default: false },
	isGoogleAvailable: { type: Boolean, default: false },
	paymentRequestError: { type: String, default: '' },
	paymentRequestRefCallback: { type: Function, default: null },
	onPaymentRequestReady: { type: Function, default: null },
	fatturazioneType: { type: String, default: '' },
	invoiceSubjectType: { type: String, default: '' },
	fatturaData: { type: Object, default: () => ({}) },
	billingShippingFullAddress: { type: String, default: '' },
	paymentActionLabel: { type: String, default: '' },
	payButtonTooltip: { type: String, default: '' },
	canPay: { type: Boolean, default: false },
	checkoutIsProcessing: { type: Boolean, default: false },
	visiblePaymentError: { type: String, default: '' },
	paymentStep: { type: String, default: '' },
	checkoutTermsAccepted: { type: Boolean, default: false },
	showConfirmModal: { type: Boolean, default: false },
	totalPackages: { type: Number, default: 0 },
	accordionTransitions: { type: Object, required: true },
	isAuthenticated: { type: Boolean, default: false },
	validateCoupon: { type: Function, required: true },
	removeCoupon: { type: Function, required: true },
	selectPaymentMethod: { type: Function, required: true },
	confirmPayment: { type: Function, required: true },
	proceedWithPayment: { type: Function, required: true },
});

defineEmits([
	'open',
	'edit-packages',
	'edit-addresses',
	'edit-services',
	'request-login',
	'update:paymentSummaryExpanded',
	'update:couponPanelOpen',
	'update:couponCode',
	'update:useNewCard',
	'update:saveCardForFuture',
	'update:fatturazioneType',
	'update:invoiceSubjectType',
	'update:checkoutTermsAccepted',
	'update:showConfirmModal',
]);

// Formatter coerente con finalTotalFormatted di useCartOperations:
// "20,00 €" con non-breaking space (\u00A0). Input: euro numerico.
const formatEuroAmount = (value) => {
	const num = Number(value);
	if (!Number.isFinite(num)) return '';
	return num.toFixed(2).replace('.', ',') + '\u00A0€';
};

// Subtotale prima dello sconto (riga "Subtotale" quando c'e' coupon)
const subtotalFormatted = computed(() => formatEuroAmount(props.subtotal));

// Sconto coupon (discount_amount e' in EURO, stesso piano di finalTotal)
const discountFormatted = computed(() => {
	const discount = props.couponApplied?.discount_amount;
	return discount ? formatEuroAmount(discount) : '';
});

// Defensive UI guard: some legacy order/session strings may still arrive with
// mojibake sequences from older persisted snapshots. We normalize only the
// visible checkout strings here so the payment step stays clean while we keep
// simplifying the upstream order context.
const repairVisibleText = (value) => String(value ?? '')
	.replace(/â‚¬/g, '\u20AC')
	.replace(/Â·/g, '\u00B7')
	.replace(/Ã¨/g, 'è')
	.replace(/Ã /g, 'à')
	.replace(/Ã²/g, 'ò')
	.replace(/â€”/g, '\u2014');

// Estrae l'importo numerico da una stringa formato "11,90 €" / "0,00 €" / "0,00  €"
// per decidere se ci si puo' fidare di `finalTotalFormatted` (carrello/ordine reale)
// oppure occorre fallback su `summaryTotalPrice` (preventivo in corso, mai aggiunto al carrello).
const parseFormattedAmount = (raw) => {
	const n = Number.parseFloat(String(raw ?? '').replace(/[^\d,.-]/g, '').replace(',', '.'));
	return Number.isFinite(n) ? n : 0;
};
const displayTotalText = computed(() => {
	const finalAmount = parseFormattedAmount(props.finalTotalFormatted);
	if (finalAmount > 0 && props.finalTotalFormatted) {
		return repairVisibleText(props.finalTotalFormatted);
	}
	if (props.summaryTotalPrice && parseFormattedAmount(props.summaryTotalPrice) > 0) {
		return repairVisibleText(props.summaryTotalPrice);
	}
	return repairVisibleText(props.finalTotalFormatted || props.summaryTotalPrice || 'Da definire');
});

const collapsedPaymentSummary = computed(() =>
	props.paymentSuccess
		? 'Pagamento completato'
		: `${repairVisibleText(resolvedTrattaLabel.value)} \u00B7 ${displayTotalText.value}`,
);

const mounted = ref(false);
onMounted(() => { mounted.value = true; });

// Guard: empty state solo quando il bootstrap checkout ha davvero concluso
// che non esiste un contesto pagabile. Prima questo ramo partiva troppo presto
// e mascherava loading/errori/checkout ready durante l'apertura da order_id.
const hasValidAmount = computed(() => {
	const raw = repairVisibleText(props.finalTotalFormatted || props.summaryTotalPrice || '').replace(/[^0-9,]/g, '');
	const num = Number(raw.replace(',', '.'));
	return Number.isFinite(num) && num > 0;
});
const showEmptyState = computed(() =>
	props.isOpen
	&& !props.paymentSuccess
	&& props.isAuthenticated
	&& !props.paymentBootstrapPending
	&& !props.visiblePaymentBootstrapError
	&& !props.checkoutPageReady
	&& !hasValidAmount.value,
);

const shouldShowPaymentSummary = computed(() =>
	props.paymentSuccess
	|| props.checkoutPageReady
	|| hasValidAmount.value,
);

const paymentSummaryToggleLabel = computed(() => {
	if (props.paymentSummaryExpanded) return 'Nascondi dettagli ordine';
	return props.paymentSuccess ? 'Vedi dettagli ordine' : 'Vedi dettagli ordine e modifica';
});

const sanitizeSummaryText = (value) => String(value ?? '').replace(/\s+/g, ' ').trim();

const isMeaningfulSummaryText = (value) => {
	const normalized = sanitizeSummaryText(value);
	if (!normalized) return false;

	const lowered = normalized.toLowerCase();
	return ![
		'n/d',
		'nd',
		'—',
		'-',
		'null',
		'undefined',
	].includes(lowered);
};

const pickFirstMeaningfulSummaryText = (...candidates) => {
	for (const candidate of candidates) {
		if (isMeaningfulSummaryText(candidate)) return sanitizeSummaryText(candidate);
	}
	return '';
};

const buildAddressStreetLine = (address, fallback) => {
	const line = [
		sanitizeSummaryText(address?.address),
		sanitizeSummaryText(address?.address_number),
	].filter(Boolean).join(' ').trim();
	return line || fallback;
};

const buildAddressLocalityLine = (address, fallback) => {
	const line = [
		sanitizeSummaryText(address?.postal_code),
		sanitizeSummaryText(address?.city),
		sanitizeSummaryText(address?.province),
	].filter(Boolean).join(' ').trim();
	return line || fallback;
};

const hasResolvedRouteLabel = computed(() => {
	const normalized = sanitizeSummaryText(props.trattaLabel);
	if (!isMeaningfulSummaryText(normalized)) return false;

	const lowered = normalized.toLowerCase();
	return (
		!lowered.includes('da definire')
		&& lowered !== 'mittente e destinatario'
		&& lowered !== 'mittente e punto brt'
		&& !lowered.includes('destinazione da completare')
	);
});

const resolvedOriginContact = computed(() => (
	pickFirstMeaningfulSummaryText(
		props.confirmationOriginContact,
		props.originAddress?.full_name,
		props.originAddress?.name,
	) || 'Mittente da completare'
));

const resolvedDestinationContact = computed(() => (
	pickFirstMeaningfulSummaryText(
		props.confirmationDestinationContact,
		props.destinationAddress?.full_name,
		props.destinationAddress?.name,
		props.deliveryMode === 'pudo' ? props.destinationAddress?.city : '',
	) || (props.deliveryMode === 'pudo' ? 'Punto BRT da selezionare' : 'Destinatario da completare')
));

const resolvedTrattaLabel = computed(() => {
	if (hasResolvedRouteLabel.value) return sanitizeSummaryText(props.trattaLabel);

	const originCity = pickFirstMeaningfulSummaryText(props.originAddress?.city);
	const destinationLabel = props.deliveryMode === 'pudo'
		? pickFirstMeaningfulSummaryText(props.destinationAddress?.name, props.destinationAddress?.city, resolvedDestinationContact.value)
		: pickFirstMeaningfulSummaryText(props.destinationAddress?.city, resolvedDestinationContact.value);

	if (originCity && destinationLabel) return `${originCity} -> ${destinationLabel}`;
	if (originCity) return `${originCity} -> Destinazione da completare`;
	return 'Tratta da definire';
});

const resolvedOriginStreetLine = computed(() => buildAddressStreetLine(props.originAddress, 'Indirizzo da completare'));
const resolvedOriginLocalityLine = computed(() => buildAddressLocalityLine(props.originAddress, 'Località da completare'));

const resolvedDestinationStreetLine = computed(() => buildAddressStreetLine(
	props.destinationAddress,
	props.deliveryMode === 'pudo' ? 'Consegna presso punto BRT' : 'Indirizzo da completare',
));
const resolvedDestinationLocalityLine = computed(() => buildAddressLocalityLine(
	props.destinationAddress,
	props.deliveryMode === 'pudo' ? 'Punto BRT da selezionare' : 'Località da completare',
));

const resolvedPaymentSummaryServicesLabel = computed(() => (
	pickFirstMeaningfulSummaryText(props.paymentSummaryServicesLabel) || 'Nessun extra selezionato'
));

const resolvedOriginLocalityDisplay = computed(() => buildAddressLocalityLine(
	props.originAddress,
	'Località da completare',
));

const resolvedDestinationLocalityDisplay = computed(() => buildAddressLocalityLine(
	props.destinationAddress,
	props.deliveryMode === 'pudo' ? 'Punto BRT da selezionare' : 'Località da completare',
));

const resolvedBillingShippingFullAddress = computed(() => {
	if (isMeaningfulSummaryText(props.billingShippingFullAddress)) {
		return sanitizeSummaryText(props.billingShippingFullAddress);
	}

	const street = buildAddressStreetLine(props.destinationAddress, '');
	const locality = buildAddressLocalityLine(props.destinationAddress, '');
	return [street, locality].filter(Boolean).join(', ');
});
</script>

<template>
	<section
		class="shipment-stage-card"
		:class="{ 'shipment-stage-card--open': isOpen }">

		<div class="shipment-stage-card__accent" />

		<button
			type="button"
			class="shipment-stage-card__toggle"
			data-accordion-trigger="payment"
			:aria-expanded="isOpen ? 'true' : 'false'"
			@click="$emit('open')">

			<div class="shipment-stage-card__badge">
				4
			</div>

			<div class="shipment-stage-card__copy">
				<h2 class="shipment-stage-card__title">Pagamento</h2>
				<p v-if="!isOpen && mounted" class="shipment-stage-card__summary">
					{{ collapsedPaymentSummary }}
				</p>
			</div>

			<span
				class="shipment-stage-card__indicator"
				:class="{ 'shipment-stage-card__indicator--open': isOpen }"
				aria-hidden="true">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M6 9l6 6 6-6" />
				</svg>
			</span>
		</button>

		<Transition
			@before-enter="accordionTransitions.onBeforeEnter"
			@enter="accordionTransitions.onEnter"
			@after-enter="accordionTransitions.onAfterEnter"
			@before-leave="accordionTransitions.onBeforeLeave"
			@leave="accordionTransitions.onLeave"
			@after-leave="accordionTransitions.onAfterLeave">
			<div
				v-if="isOpen"
				class="shipment-stage-card__body">
				<div class="shipment-stage-card__body-inner">
					<!-- Empty state: nessun ordine valido da pagare (refresh post-success,
						 deep-link o cart stale). Evita il "ghost page" 0,00 € senza form. -->
					<div
						v-if="showEmptyState"
						class="flex flex-col items-center gap-[16px] py-[32px] px-[20px] text-center">
						<span class="inline-flex h-[48px] w-[48px] items-center justify-center rounded-full bg-[#FFF5EF] text-[#E44203]">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<circle cx="12" cy="12" r="10" />
								<line x1="12" y1="8" x2="12" y2="12" />
								<line x1="12" y1="16" x2="12.01" y2="16" />
							</svg>
						</span>
						<div class="max-w-[420px]">
							<h3 class="text-[16px] font-bold text-[#1d2738] mb-[6px]">Nessun preventivo attivo</h3>
							<p class="text-[14px] leading-[1.5] text-[#5C6473]">
								Il carrello è vuoto o il preventivo è scaduto. Torna al passo 1 per calcolare un nuovo prezzo e completare l'ordine.
							</p>
						</div>
						<NuxtLink to="/" class="sf-flow-cta sf-flow-cta--primary">
							Torna al preventivo
							<span class="sf-flow-cta__arrow" aria-hidden="true">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M5 12h14M13 5l7 7-7 7" />
								</svg>
							</span>
						</NuxtLink>
					</div>

					<template v-else>
					<!--
						Riepilogo espandibile/modificabile dentro il payment step.
					-->
					<div v-if="shouldShowPaymentSummary" class="payment-summary-card">
						<!-- Header compatto: tratta + meta + totale -->
						<div class="flex flex-col gap-[10px] lg:flex-row lg:items-start lg:justify-between">
							<div class="min-w-0 flex items-start gap-[12px]">
								<span class="inline-flex h-[40px] w-[40px] shrink-0 items-center justify-center rounded-[14px] bg-[#F3FAFB] text-[#095866] border border-[#D7E4E7]">
									<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path d="M3 7l9-4 9 4v10l-9 4-9-4z" />
										<path d="M3 7l9 4 9-4" />
										<path d="M12 11v10" />
									</svg>
								</span>
								<div class="min-w-0">
									<div class="flex flex-wrap items-center gap-[8px]">
										<p class="text-[11px] uppercase tracking-[0.16em] text-[#7C8594]" style="font-weight:800">Riepilogo ordine</p>
										<span class="inline-flex items-center rounded-full border border-[#D7E4E7] bg-[#F5F7FA] px-[10px] py-[5px] text-[11px] text-[#5C6473]" style="font-weight:800">
											{{ colloLabel }}
										</span>
									</div>
									<div class="mt-[6px] flex flex-wrap items-center gap-x-[10px] gap-y-[6px] text-[13px] text-[#1d2738]" style="font-weight:800">
										<span>{{ resolvedTrattaLabel }}</span>
										<span class="text-[#C0C5CC]">•</span>
										<span class="text-[#5C6473]" style="font-weight:700">Ritiro {{ confirmationPickupDate }}</span>
										<span class="text-[#C0C5CC]">•</span>
										<span class="text-[#5C6473]" style="font-weight:700">{{ paymentDeliveryLabel }}</span>
									</div>
								</div>
							</div>

							<div class="flex items-center gap-[10px] lg:justify-end">
								<span class="inline-flex items-center gap-[6px] rounded-full border border-[#D7E4E7] bg-[#F3FAFB] px-[10px] py-[6px] text-[11px] text-[#095866]" style="font-weight:800">
									{{ paymentMethodLabel }}
								</span>
								<p
									class="leading-none text-[#1d2738]"
									style="font-weight:800; font-size: clamp(28px, 4vw, 36px); letter-spacing: -0.02em;">
									{{ displayTotalText }}
								</p>
							</div>
						</div>

						<!-- Toggle espandibile: rivela 3 sezioni inline con link Modifica per ciascuna -->
						<button
							type="button"
							class="payment-summary-card__toggle"
							:aria-expanded="paymentSummaryExpanded ? 'true' : 'false'"
							@click="$emit('update:paymentSummaryExpanded', !paymentSummaryExpanded)">
							<span>{{ paymentSummaryToggleLabel }}</span>
							<span
								class="payment-summary-card__toggle-chevron"
								:class="{ 'payment-summary-card__toggle-chevron--open': paymentSummaryExpanded }"
								aria-hidden="true">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M6 9l6 6 6-6" />
								</svg>
							</span>
						</button>

						<Transition name="payment-panel">
							<div v-if="paymentSummaryExpanded" class="payment-summary-card__details">
								<!-- Sezione Colli -->
								<article class="payment-summary-section">
									<header class="payment-summary-section__header">
										<div class="min-w-0">
											<p class="payment-summary-section__eyebrow">Colli</p>
											<p class="payment-summary-section__title">{{ summaryPackageLabel || 'Tipo collo da scegliere' }}</p>
											<p class="payment-summary-section__body">{{ summaryDimensionsLabel || 'Misure da completare' }}</p>
										</div>
										<button
											v-if="!paymentSuccess"
											type="button"
											class="payment-summary-section__edit"
											@click="$emit('edit-packages')">
											<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<path d="M12 20h9" />
												<path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
											</svg>
											<span>Modifica</span>
										</button>
									</header>
								</article>

								<!-- Sezione Indirizzi -->
								<article class="payment-summary-section">
									<header class="payment-summary-section__header">
										<div class="min-w-0">
											<p class="payment-summary-section__eyebrow">Indirizzi</p>
											<div class="payment-summary-section__route">
												<div class="payment-summary-section__route-item">
													<span class="payment-summary-section__route-label">Partenza</span>
													<span class="payment-summary-section__title">{{ resolvedOriginContact }}</span>
													<span class="payment-summary-section__body">
														{{ resolvedOriginStreetLine }}
													</span>
													<span class="payment-summary-section__body">
														{{ resolvedOriginLocalityDisplay }}
													</span>
												</div>
												<div class="payment-summary-section__route-item">
													<span class="payment-summary-section__route-label">{{ deliveryMode === 'pudo' ? 'Destinazione (Punto BRT)' : 'Destinazione' }}</span>
													<span class="payment-summary-section__title">{{ resolvedDestinationContact }}</span>
													<span class="payment-summary-section__body">
														{{ resolvedDestinationStreetLine }}
													</span>
													<span class="payment-summary-section__body">
														{{ resolvedDestinationLocalityDisplay }}
													</span>
												</div>
											</div>
										</div>
										<button
											v-if="!paymentSuccess"
											type="button"
											class="payment-summary-section__edit"
											@click="$emit('edit-addresses')">
											<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<path d="M12 20h9" />
												<path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
											</svg>
											<span>Modifica</span>
										</button>
									</header>
								</article>

								<!-- Sezione Servizi attivi + contenuto -->
								<article class="payment-summary-section">
									<header class="payment-summary-section__header">
										<div class="min-w-0">
											<p class="payment-summary-section__eyebrow">Servizi</p>
											<p class="payment-summary-section__title">{{ resolvedPaymentSummaryServicesLabel }}</p>
											<p class="payment-summary-section__body">Contenuto: {{ resolvedContentDescription || 'non specificato' }}</p>
										</div>
										<button
											v-if="!paymentSuccess"
											type="button"
											class="payment-summary-section__edit"
											@click="$emit('edit-services')">
											<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<path d="M12 20h9" />
												<path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
											</svg>
											<span>Modifica</span>
										</button>
									</header>
								</article>

								<!--
									Breakdown: Subtotale / Sconto / Totale.
									Le prime due righe compaiono SOLO se c'e' un coupon applicato,
									cosi' l'utente vede chiaramente quanto sta risparmiando (trust + conversione).
								-->
								<div class="payment-summary-card__breakdown">
									<div v-if="couponApplied && subtotalFormatted" class="payment-summary-card__row">
										<span>Subtotale</span>
										<span>{{ subtotalFormatted }}</span>
									</div>
									<div v-if="couponApplied && discountFormatted" class="payment-summary-card__row payment-summary-card__row--discount">
										<span>Sconto{{ couponApplied.code ? ` (${couponApplied.code})` : '' }}</span>
										<span>-{{ discountFormatted }}</span>
									</div>
									<div class="payment-summary-card__row payment-summary-card__row--total">
										<span>Totale spedizione</span>
										<strong>{{ finalTotalFormatted || summaryTotalPrice || 'Da definire' }}</strong>
									</div>
								</div>
							</div>
						</Transition>
					</div>

					<div
						v-if="paymentBootstrapPending"
						class="grid grid-cols-1 gap-[14px] xl:grid-cols-[minmax(0,1.08fr)_minmax(320px,0.88fr)]">
						<div class="flex flex-col gap-[12px]">
							<div class="sf-skeleton-shimmer" style="height: 188px" />
							<div class="sf-skeleton-shimmer" style="height: 176px" />
							<div class="sf-skeleton-shimmer" style="height: 168px" />
						</div>
						<div class="flex flex-col gap-[12px]">
							<div class="sf-skeleton-shimmer" style="height: 220px" />
							<div class="sf-skeleton-shimmer" style="height: 132px" />
						</div>
					</div>

					<div
						v-else-if="visiblePaymentBootstrapError"
						class="shipment-stage-error"
						role="alert">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<circle cx="12" cy="12" r="10" />
							<line x1="12" y1="8" x2="12" y2="12" />
							<line x1="12" y1="16" x2="12.01" y2="16" />
						</svg>
						<span>{{ visiblePaymentBootstrapError }}</span>
					</div>

					<CheckoutSuccess
						v-else-if="paymentSuccess"
						:success-order-id="successOrderId"
						:payment-method="paymentMethod"
						:total-amount="finalTotalFormatted" />

					<div
						v-else-if="!isAuthenticated"
						class="payment-auth-required"
						role="region"
						aria-label="Accesso richiesto per completare il pagamento">
						<div class="payment-auth-required__icon" aria-hidden="true">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
								<path d="M7 11V7a5 5 0 0 1 10 0v4" />
							</svg>
						</div>
						<div class="payment-auth-required__copy">
							<p class="payment-auth-required__title">Accedi per completare il pagamento</p>
							<p class="payment-auth-required__sub">
								Serve un account SpediamoFacile per pagare in sicurezza, tracciare la spedizione e gestire resi, reclami e fatture.
							</p>
						</div>
						<div class="payment-auth-required__actions">
							<SfButton size="lg" @click="$emit('request-login', 'login')">Accedi</SfButton>
							<SfButton variant="secondary" size="lg" @click="$emit('request-login', 'register')">Crea account</SfButton>
						</div>
					</div>

					<div
						v-else-if="checkoutPageReady"
						class="flex flex-col gap-[12px]">
						<div class="rounded-[18px] border border-[#DFE2E7] bg-white px-[16px] py-[14px]" style="box-shadow: 0 8px 26px rgba(15,23,42,0.04)">
							<button
								type="button"
								class="w-full flex items-center justify-between gap-[12px] text-left"
								@click="$emit('update:couponPanelOpen', !couponPanelOpen)">
								<div>
									<p class="text-[11px] uppercase tracking-[0.14em] text-[#7C8594]" style="font-weight:800">Codice promozionale</p>
									<p class="mt-[6px] text-[14px] text-[#1d2738]" style="font-weight:700">
										{{ couponApplied ? 'Coupon applicato' : 'Hai un codice o un invito?' }}
									</p>
								</div>
								<span
									class="shrink-0 text-[#C0C5CC]"
									:style="{ transform: couponPanelOpen ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 0.3s cubic-bezier(0.22,1,0.36,1)' }"
									aria-hidden="true">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path d="M6 9l6 6 6-6" />
									</svg>
								</span>
							</button>

							<Transition name="payment-panel">
								<div v-if="couponPanelOpen" class="mt-[12px] flex flex-col gap-[8px]">
									<div class="flex flex-col gap-[10px] sm:flex-row">
										<input
											:value="couponCode"
											type="text"
											placeholder="Inserisci il codice"
											class="flex-1 h-[46px] rounded-[16px] border border-[#D9E1EA] bg-[#F8F9FB] px-[16px] text-[15px] text-[#1d2738] outline-none focus:border-[#0b7d92] focus:ring-[3px] focus:ring-[rgba(11,125,146,0.12)]"
											@input="$emit('update:couponCode', ($event.target).value)" />
										<SfButton
											v-if="!couponApplied"
											size="lg"
											:loading="couponLoading"
											@click="validateCoupon">
											{{ couponLoading ? 'Verifica...' : 'Applica' }}
										</SfButton>
										<SfButton
											v-else
											variant="secondary"
											size="lg"
											@click="removeCoupon">
											Rimuovi
										</SfButton>
									</div>
									<p v-if="couponError" class="text-[13px] leading-[1.55] text-[#A64016]" style="font-weight:700">{{ couponError }}</p>
									<p v-if="couponApplied" class="text-[13px] leading-[1.55] text-[#0f7a56]" style="font-weight:700">
										{{ couponApplied.code || couponCode }} attivo.
									</p>
								</div>
							</Transition>
						</div>

						<div class="flex flex-col gap-[12px]">
							<CheckoutPaymentMethods
								:payment-method="paymentMethod"
								:payment-method-options="paymentMethodOptions"
								:card-payments-unavailable="cardPaymentsUnavailable"
								:card-payments-notice="cardPaymentsNotice"
								:has-saved-card="hasSavedCard"
								:default-payment="defaultPayment"
								:use-new-card="useNewCard"
								:should-show-card-form="shouldShowCardForm"
								:stripe-loading="stripeLoading"
								:card-error="cardError"
								:save-card-for-future="saveCardForFuture"
								:card-ref-callback="setCheckoutCardRef"
								:wallet-formatted="walletFormatted"
								:wallet-loaded="walletLoaded"
								:wallet-sufficient="walletSufficient"
								:can-make-payment="canMakePayment"
								:is-apple-available="isAppleAvailable"
								:is-google-available="isGoogleAvailable"
								:payment-request-error="paymentRequestError"
								:payment-request-ref-callback="paymentRequestRefCallback"
								:on-payment-request-ready="onPaymentRequestReady"
								@select-payment-method="selectPaymentMethod"
								@update:use-new-card="$emit('update:useNewCard', $event)"
								@update:save-card-for-future="$emit('update:saveCardForFuture', $event)" />

							<CheckoutBilling
								:fatturazione-type="fatturazioneType"
								:invoice-subject-type="invoiceSubjectType"
								:fattura-data="fatturaData"
								:billing-shipping-full-address="resolvedBillingShippingFullAddress"
								:destination-address="destinationAddress"
								@update:fatturazione-type="$emit('update:fatturazioneType', $event)"
								@update:invoice-subject-type="$emit('update:invoiceSubjectType', $event)" />

							<CheckoutPaymentFooter
								:final-total-formatted="finalTotalFormatted"
								:payment-method="paymentMethod"
								:payment-action-label="paymentActionLabel"
								:pay-button-tooltip="payButtonTooltip"
								:can-pay="canPay"
								:is-processing="checkoutIsProcessing"
								:payment-error="visiblePaymentError"
								:payment-step="paymentStep"
								:terms-accepted="checkoutTermsAccepted"
								@confirm-payment="confirmPayment"
								@update:terms-accepted="$emit('update:checkoutTermsAccepted', $event)" />
						</div>
					</div>

					<CheckoutConfirmModal
						:show="showConfirmModal"
						:final-total-formatted="finalTotalFormatted"
						:payment-method="paymentMethod"
						:total-packages="totalPackages"
						@close="$emit('update:showConfirmModal', false)"
						@confirm="proceedWithPayment" />
					</template>
				</div>
			</div>
		</Transition>
	</section>
</template>
