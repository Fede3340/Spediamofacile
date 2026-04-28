/**
 * @file useShipmentStepPageOrchestration — Composable useShipmentStepPageOrchestration.
 */
// Canonical shipment-flow entrypoint.
//
// Questo file espone:
// - useShipmentStepPageOrchestration: orchestrazione UI del ventaglio
// - re-export canoniche dei sub-boundary shipment-flow
//
// I subcomposable del funnel vivono in `features/shipment-flow/` per
// mantenere l'API pubblica stabile e ridurre il monolite storico.

import { computed, onMounted, watch } from 'vue';
import { buildSecondStepPayload } from '~/composables/useShipmentStepDraftPayload';
import { useShipmentStepPaymentEntry } from '~/composables/useShipmentStepPaymentEntry';
import {
	collectSelectedServiceItems,
	formatAddressAccordionSummary,
	formatColloLabel,
	formatConfirmationContact,
	formatPackageAccordionSummary,
	formatPaymentDeliveryLabel,
	formatPaymentMethodLabel,
	formatPaymentSummaryServicesLabel,
	formatPickupDate,
	formatSelectedServiceSummary,
	formatServicesAccordionSummary,
	formatTrattaLabel,
	getShipmentFlowHeroDescription,
	getShipmentFlowHeroTitle,
	getVisiblePackageItems,
} from '~/utils/shipmentFlow/presentation';

const isThrottleLikeFunnelError = (message) =>
	typeof message === 'string' && /throttl|rate[ -]?limit|troppo (rapido|veloce|frequente)/i.test(message);
const stripFunnelThrottleMessage = (message) => (isThrottleLikeFunnelError(message) ? '' : message);

export const resolveFunnelErrorMessage = (error, fallback) => {
	const raw = error?.data?.message || error?.message || '';
	return typeof raw === 'string' && raw.trim() ? raw : fallback;
};

export function useShipmentStepPageOrchestration(deps) {
	const route = useRoute();
	const shipmentFlowStore = useShipmentFlowStore();
	const sanctumClient = useSanctumClient();
	const uiFeedback = useUiFeedback();
	const funnelAnalytics = useFunnelAnalytics();

	const {
		isAuthenticated,
		openAuthModal,
		isBusinessProfile,
		activeAccordionStep,
		showAddressFields,
		deliveryMode,
		submitError,
		dateError,
		contentError,
		paymentBootstrapError,
		paymentBootstrapPending,
		paymentSummaryExpanded,
		isProceedingToPayment,
		packagesStageRef,
		servicesStageRef,
		addressStageRef,
		paymentStageRef,
		scrollAccordionStageIntoView,
		openPackagesStage,
		openPaymentStage,
		goBackToServices,
		goBackToAddresses,
		openAddressFields,
		validatePackagesStep,
		validateInlineServiceDetails,
		focusFirstInvalidServiceField,
		clearServiceCardErrors,
		expandedServiceKey,
		editablePackages,
		addPackageInline,
		ensurePackagesIdentity,
		loadQuickQuotePriceBands,
		initOnMounted,
		session,
		services,
		smsEmailNotification,
		featuredService,
		regularServices,
		notificationPriceLabel,
		addressReadinessItems,
		summaryOriginCity,
		summaryDestinationCity,
		summaryTotalPrice,
		summaryPackageLabel,
		summaryDimensionsLabel,
		originAddress,
		destinationAddress,
		existingOrderId,
		editCartId,
		paymentSuccess,
		paymentError,
		paymentMethod,
		checkoutPageReady,
		initCheckoutPage,
		initStripe,
		loadPriceBands,
		autoApplyReferral,
	} = deps;

	const visiblePaymentError = computed(() => stripFunnelThrottleMessage(paymentError.value));
	watch(
		() => paymentError.value,
		(value) => {
			if (value && isThrottleLikeFunnelError(value)) paymentError.value = '';
		},
		{ flush: 'sync' },
	);

	const canAdvanceFromAddresses = computed(() => addressReadinessItems.value.every((item) => item.done));
	const isPackagesAccordionOpen = computed(() => activeAccordionStep.value === 'packages');
	const isServicesAccordionOpen = computed(() => activeAccordionStep.value === 'services');
	const isAddressAccordionOpen = computed(() => activeAccordionStep.value === 'addresses');
	const isPaymentAccordionOpen = computed(() => activeAccordionStep.value === 'payment');

	const quoteHeroTitle = computed(() => getShipmentFlowHeroTitle());
	const quoteHeroDescription = computed(() => getShipmentFlowHeroDescription());

	const resolvedContentDescription = computed(() =>
		String(shipmentFlowStore?.contentDescription || session.value?.data?.content_description || '').trim(),
	);

	const packageItems = computed(() =>
		getVisiblePackageItems({
			editablePackages: editablePackages.value,
			sessionPackages: session.value?.data?.packages,
		}),
	);
	const colloLabel = computed(() => formatColloLabel(packageItems.value));
	const trattaLabel = computed(() => formatTrattaLabel(summaryOriginCity.value, summaryDestinationCity.value));
	const packageAccordionSummary = computed(() => formatPackageAccordionSummary(summaryPackageLabel.value, summaryDimensionsLabel.value));

	const selectedServiceItems = computed(() =>
		collectSelectedServiceItems({
			featuredService: featuredService.value,
			regularServices: regularServices.value,
			smsEmailNotification: smsEmailNotification.value,
			notificationPriceLabel: notificationPriceLabel.value,
		}),
	);
	const selectedServiceSummary = computed(() => formatSelectedServiceSummary(selectedServiceItems.value));
	const servicesAccordionSummary = computed(() =>
		formatServicesAccordionSummary({
			pickupDate: services.value?.date,
			selectedServiceSummary: selectedServiceSummary.value,
			resolvedContentDescription: resolvedContentDescription.value,
		}),
	);
	const addressAccordionSummary = computed(() =>
		formatAddressAccordionSummary({
			deliveryMode: deliveryMode.value,
			summaryOriginCity: summaryOriginCity.value,
			summaryDestinationCity: summaryDestinationCity.value,
			pudoName: String(shipmentFlowStore?.selectedPudo?.name || '').trim(),
		}),
	);

	const confirmationPickupDate = computed(() => formatPickupDate(services.value?.date || session.value?.data?.pickup_date));
	const confirmationOriginContact = computed(() => formatConfirmationContact(originAddress.value?.full_name, 'Mittente da completare'));
	const confirmationDestinationContact = computed(() => {
		if (deliveryMode.value === 'pudo') {
			return formatConfirmationContact(shipmentFlowStore?.selectedPudo?.name, 'Punto BRT da selezionare');
		}
		return formatConfirmationContact(destinationAddress.value?.full_name, 'Destinatario da completare');
	});
	const paymentSummaryServicesLabel = computed(() => formatPaymentSummaryServicesLabel(selectedServiceItems.value));
	const paymentMethodLabel = computed(() => formatPaymentMethodLabel(paymentMethod.value));
	const paymentDeliveryLabel = computed(() => formatPaymentDeliveryLabel(deliveryMode.value));

	const openShipmentAuthModal = (tab = 'login') => openAuthModal({ redirect: route.fullPath, tab });

	const openPackagesAccordion = async () => {
		if (isPaymentAccordionOpen.value) await clearPaymentRouteContext();
		submitError.value = null;
		await openPackagesStage();
		scrollAccordionStageIntoView(packagesStageRef, '[data-accordion-trigger="packages"]');
	};

	const openServicesAccordion = async () => {
		if (isPaymentAccordionOpen.value) await clearPaymentRouteContext();
		if (isPackagesAccordionOpen.value) {
			submitError.value = null;
			if (!validatePackagesStep()) return;
		}
		if ((await goBackToServices()) === false) return;
		scrollAccordionStageIntoView(servicesStageRef, '[data-accordion-trigger="services"]');
	};

	const openAddressAccordion = async () => {
		if (isPaymentAccordionOpen.value) {
			await clearPaymentRouteContext();
			await goBackToAddresses();
			scrollAccordionStageIntoView(addressStageRef, '[data-accordion-trigger="addresses"]');
			return;
		}
		submitError.value = null;
		dateError.value = null;
		contentError.value = null;
		clearServiceCardErrors();
		if (!validateInlineServiceDetails()) {
			focusFirstInvalidServiceField();
			return;
		}
		expandedServiceKey.value = '';
		if ((await openAddressFields()) === false) return;
		scrollAccordionStageIntoView(addressStageRef);
	};

	const collapseAddressAccordion = () => openServicesAccordion();

	const buildCurrentShipmentPayload = () => ({
		...buildSecondStepPayload({
			shipmentFlowStore,
			services,
			smsEmailNotification,
			originAddress,
			destinationAddress,
			includeAddresses: true,
		}),
		packages: editablePackages.value,
	});

	const { clearPaymentRouteContext, openPaymentAccordion, proceedToPaymentFromConfirm, ensurePaymentStageReady } =
		useShipmentStepPaymentEntry({
			shipmentFlowStore,
			sanctumClient,
			uiFeedback,
			funnelAnalytics,
			isAuthenticated,
			showAddressFields,
			submitError,
			paymentBootstrapError,
			paymentBootstrapPending,
			paymentSummaryExpanded,
			isProceedingToPayment,
			paymentStageRef,
			scrollAccordionStageIntoView,
			openPaymentStage,
			existingOrderId,
			editCartId,
			paymentSuccess,
			checkoutPageReady,
			initCheckoutPage,
			initStripe,
			loadPriceBands,
			autoApplyReferral,
			openShipmentAuthModal,
			buildCurrentShipmentPayload,
			openAddressAccordion,
		});

	watch(
		() => activeAccordionStep.value,
		async (step) => {
			if (step === 'payment') await ensurePaymentStageReady();
		},
		{ flush: 'post', immediate: true },
	);

	watch(
		() => isAuthenticated.value,
		async (auth) => {
			if (auth && activeAccordionStep.value === 'payment') await ensurePaymentStageReady();
		},
		{ flush: 'post', immediate: true },
	);

	onMounted(async () => {
		// Non bloccare il restore iniziale del funnel su una fetch non critica:
		// i listini possono arrivare in background, mentre edit/resume e shell
		// devono stabilizzarsi subito per ridurre loader e jump iniziali.
		loadQuickQuotePriceBands().catch(() => {});
		ensurePackagesIdentity();
		await initOnMounted();
		if (activeAccordionStep.value === 'payment') {
			await ensurePaymentStageReady();
		}
		if (!Array.isArray(shipmentFlowStore?.packages) || shipmentFlowStore.packages.length === 0) {
			addPackageInline();
			ensurePackagesIdentity();
		}
		try {
			const quoteType = isBusinessProfile.value ? 'business' : deliveryMode.value === 'pudo' ? 'pudo' : 'privato';
			funnelAnalytics.trackPreventivoStart(quoteType);
		} catch {
			// no-op
		}
	});

	return {
		packageAccordionSummary,
		servicesAccordionSummary,
		addressAccordionSummary,
		resolvedContentDescription,
		colloLabel,
		trattaLabel,
		confirmationPickupDate,
		confirmationOriginContact,
		confirmationDestinationContact,
		paymentSummaryServicesLabel,
		paymentMethodLabel,
		paymentDeliveryLabel,
		isPackagesAccordionOpen,
		isServicesAccordionOpen,
		isAddressAccordionOpen,
		isPaymentAccordionOpen,
		canAdvanceFromAddresses,
		quoteHeroTitle,
		quoteHeroDescription,
		visiblePaymentError,
		openPackagesAccordion,
		openServicesAccordion,
		openAddressAccordion,
		collapseAddressAccordion,
		openPaymentAccordion,
		proceedToPaymentFromConfirm,
		openShipmentAuthModal,
	};
}

export { useShipmentStepSessionPersistence } from '~/composables/useShipmentStepSessionPersistence';
export { useShipmentStepFlow } from '~/composables/useShipmentStepFlow';
export { useShipmentStepSubmit } from '~/composables/useShipmentStepSubmit';
export { useShipmentStepPageState } from '~/composables/useShipmentStepPageState';
export { useShipmentStepCartEdit } from '~/composables/useShipmentStepCartEdit';
export { useShipmentStepValidation } from '~/composables/useShipmentStepValidation';
