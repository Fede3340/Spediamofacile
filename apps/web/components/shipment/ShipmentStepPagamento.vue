<script setup>
import { computed, onMounted, ref } from 'vue';
import CheckoutBilling from '~/components/checkout/Billing.vue';
import CheckoutSuccess from '~/components/checkout/Success.vue';
import CheckoutPaymentMethods from '~/components/checkout/PaymentMethods.vue';
import CheckoutPaymentFooter from '~/components/checkout/PaymentFooter.vue';
import CheckoutConfirmModal from '~/components/checkout/ConfirmModal.vue';

const props = defineProps({
	isOpen: { type: Boolean, required: true },
	paymentSuccess: { type: Boolean, default: false },
	trattaLabel: { type: String, default: '' },
	finalTotalFormatted: { type: String, default: '' },
	summaryTotalPrice: { type: String, default: '' },
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
	'open', 'edit-packages', 'edit-addresses', 'edit-services', 'request-login',
	'update:paymentSummaryExpanded', 'update:couponPanelOpen', 'update:couponCode',
	'update:useNewCard', 'update:saveCardForFuture',
	'update:fatturazioneType', 'update:invoiceSubjectType', 'update:fatturaData',
	'update:checkoutTermsAccepted', 'update:showConfirmModal',
]);

const formatEuroAmount = (value) => {
	const num = Number(value);
	if (!Number.isFinite(num)) return '';
	return num.toFixed(2).replace('.', ',') + ' €';
};

const subtotalFormatted = computed(() => formatEuroAmount(props.subtotal));
const discountFormatted = computed(() => {
	const discount = props.couponApplied?.discount_amount;
	return discount ? formatEuroAmount(discount) : '';
});

const repairVisibleText = (value) => [
	['â‚¬', '€'],
	['Â·', '·'],
	['Ã¨', 'è'],
	['Ã ', 'à'],
	['Ã²', 'ò'],
	['â€”', '—'],
].reduce((text, [search, replacement]) => text.replaceAll(search, replacement), String(value ?? ''));

const parseFormattedAmount = (raw) => {
	const n = Number.parseFloat(String(raw ?? '').replace(/[^\d,.-]/g, '').replace(',', '.'));
	return Number.isFinite(n) ? n : 0;
};

const displayTotalText = computed(() => {
	const finalAmount = parseFormattedAmount(props.finalTotalFormatted);
	if (finalAmount > 0 && props.finalTotalFormatted) return repairVisibleText(props.finalTotalFormatted);
	if (props.summaryTotalPrice && parseFormattedAmount(props.summaryTotalPrice) > 0) {
		return repairVisibleText(props.summaryTotalPrice);
	}
	return repairVisibleText(props.finalTotalFormatted || props.summaryTotalPrice || 'Da definire');
});

const collapsedPaymentSummary = computed(() => {
	if (props.paymentSuccess) return 'Pagamento completato';
	const tratta = repairVisibleText(props.trattaLabel || 'Tratta da definire');
	return `${tratta} · ${displayTotalText.value}`;
});

const mounted = ref(false);
onMounted(() => { mounted.value = true; });

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
	props.paymentSuccess || props.checkoutPageReady || hasValidAmount.value,
);

const sanitize = (value) => String(value ?? '').replace(/\s+/g, ' ').trim();
const isMeaningful = (value) => {
	const n = sanitize(value);
	if (!n) return false;
	return !['n/d', 'nd', '—', '-', 'null', 'undefined'].includes(n.toLowerCase());
};

const resolvedBillingShippingFullAddress = computed(() => {
	if (isMeaningful(props.billingShippingFullAddress)) return sanitize(props.billingShippingFullAddress);
	const street = [sanitize(props.destinationAddress?.address), sanitize(props.destinationAddress?.address_number)].filter(Boolean).join(' ').trim();
	const locality = [sanitize(props.destinationAddress?.postal_code), sanitize(props.destinationAddress?.city), sanitize(props.destinationAddress?.province)].filter(Boolean).join(' ').trim();
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
			<div class="shipment-stage-card__badge">4</div>
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
			<div v-if="isOpen" class="shipment-stage-card__body">
				<div class="shipment-stage-card__body-inner">
					<ShipmentPaymentEmptyState v-if="showEmptyState" />

					<template v-else>
						<ShipmentPaymentSummaryCard
							v-if="shouldShowPaymentSummary"
							:payment-success="paymentSuccess"
							:payment-summary-expanded="paymentSummaryExpanded"
							:tratta-label="trattaLabel"
							:collo-label="colloLabel"
							:confirmation-pickup-date="confirmationPickupDate"
							:payment-delivery-label="paymentDeliveryLabel"
							:display-total-text="displayTotalText"
							:final-total-formatted="finalTotalFormatted"
							:summary-total-price="summaryTotalPrice"
							:summary-package-label="summaryPackageLabel"
							:summary-dimensions-label="summaryDimensionsLabel"
							:confirmation-origin-contact="confirmationOriginContact"
							:confirmation-destination-contact="confirmationDestinationContact"
							:origin-address="originAddress"
							:destination-address="destinationAddress"
							:delivery-mode="deliveryMode"
							:payment-summary-services-label="paymentSummaryServicesLabel"
							:resolved-content-description="resolvedContentDescription"
							:subtotal-formatted="subtotalFormatted"
							:discount-formatted="discountFormatted"
							:coupon-applied="couponApplied"
							@update:payment-summary-expanded="$emit('update:paymentSummaryExpanded', $event)"
							@edit-packages="$emit('edit-packages')"
							@edit-addresses="$emit('edit-addresses')"
							@edit-services="$emit('edit-services')" />

						<ShipmentPaymentBootstrapSkeleton v-if="paymentBootstrapPending" />

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

						<ShipmentPaymentAuthRequired
							v-else-if="!isAuthenticated"
							@request-login="$emit('request-login', $event)" />

						<div v-else-if="checkoutPageReady" class="flex flex-col gap-[12px]">
							<ShipmentPaymentCouponPanel
								:coupon-panel-open="couponPanelOpen"
								:coupon-applied="couponApplied"
								:coupon-code="couponCode"
								:coupon-loading="couponLoading"
								:coupon-error="couponError"
								:validate-coupon="validateCoupon"
								:remove-coupon="removeCoupon"
								@update:coupon-panel-open="$emit('update:couponPanelOpen', $event)"
								@update:coupon-code="$emit('update:couponCode', $event)" />

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
									@update:invoice-subject-type="$emit('update:invoiceSubjectType', $event)"
									@update:fattura-data="$emit('update:fatturaData', $event)" />

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
