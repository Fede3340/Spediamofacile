/**
 * @file useShipmentStepPageOrchestration — Composable useShipmentStepPageOrchestration.
 */
// Canonical shipment-flow entrypoint.
//
// Questo file espone:
// - useShipmentStepPageOrchestration: orchestrazione UI del ventaglio
//
// I subcomposable del funnel restano importabili dai propri file dedicati:
// questo evita auto-import duplicati Nuxt e rende chiaro dove intervenire.

import { computed, onMounted, watch, type Ref } from 'vue';
import type { AuthModalTab } from '~/stores/authModalStore';
import {
	buildSecondStepPayload,
	type ServicesState,
	type ShipmentDraftStore,
	type StepAddressDraft,
} from '~/utils/shipmentDraftPayload';
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
} from '~/utils/shipmentFlowPresentation';

type ServiceSummaryItem = { name?: string; isSelected?: boolean; [key: string]: unknown };
type StepPageDeps = {
	isAuthenticated: Ref<boolean>;
	openAuthModal: (options: { redirect: string; tab: AuthModalTab }) => unknown;
	isBusinessProfile: Ref<boolean>;
	activeAccordionStep: Ref<string>;
	showAddressFields: Ref<boolean>;
	deliveryMode: Ref<string>;
	submitError: Ref<string | null>;
	dateError: Ref<string | null>;
	contentError: Ref<string | null>;
	paymentBootstrapError: Ref<string>;
	paymentBootstrapPending: Ref<boolean>;
	paymentSummaryExpanded: Ref<boolean>;
	isProceedingToPayment: Ref<boolean>;
	packagesStageRef: unknown;
	servicesStageRef: unknown;
	addressStageRef: unknown;
	paymentStageRef: unknown;
	scrollAccordionStageIntoView: (target: unknown, selector?: string) => unknown;
	openPackagesStage: () => Promise<unknown>;
	openPaymentStage: () => Promise<boolean | undefined> | boolean | undefined;
	goBackToServices: () => Promise<boolean | undefined> | boolean | undefined;
	goBackToAddresses: () => Promise<unknown>;
	openAddressFields: () => Promise<boolean | undefined> | boolean | undefined;
	validatePackagesStep: () => boolean;
	validateInlineServiceDetails: () => boolean;
	focusFirstInvalidServiceField: () => unknown;
	clearServiceCardErrors: () => unknown;
	expandedServiceKey: Ref<string>;
	editablePackages: Ref<Record<string, unknown>[]>;
	addPackageInline: () => unknown;
	ensurePackagesIdentity: () => unknown;
	loadQuickQuotePriceBands: () => Promise<unknown>;
	initOnMounted: () => Promise<unknown>;
	session: Ref<{ data?: { content_description?: string; packages?: Record<string, unknown>[]; pickup_date?: string } } | null | undefined>;
	services: Ref<ServicesState>;
	smsEmailNotification: Ref<boolean>;
	featuredService: Ref<ServiceSummaryItem | null | undefined>;
	regularServices: Ref<ServiceSummaryItem[]>;
	notificationPriceLabel: Ref<string>;
	addressReadinessItems: Ref<Array<{ done: boolean }>>;
	summaryOriginCity: Ref<string>;
	summaryDestinationCity: Ref<string>;
	summaryPackageLabel: Ref<string>;
	summaryDimensionsLabel: Ref<string>;
	originAddress: Ref<StepAddressDraft>;
	destinationAddress: Ref<StepAddressDraft>;
	existingOrderId: Ref<string | number | null | undefined>;
	editCartId?: string | number | null;
	paymentSuccess: Ref<boolean>;
	paymentError: Ref<string>;
	paymentMethod: Ref<string>;
	checkoutPageReady: Ref<boolean>;
	initCheckoutPage: () => Promise<boolean> | boolean;
	initStripe: () => Promise<unknown> | unknown;
	loadPriceBands: () => unknown;
	autoApplyReferral: () => Promise<unknown> | unknown;
};

const isThrottleLikeFunnelError = (message: unknown) =>
	typeof message === 'string' && /throttl|rate[ -]?limit|troppo (?:rapido|veloce|frequente)/i.test(message);
const stripFunnelThrottleMessage = (message: string) => (isThrottleLikeFunnelError(message) ? '' : message);

export const resolveFunnelErrorMessage = (error: unknown, fallback: string) => {
	const source = error && typeof error === 'object' ? error as { data?: { message?: unknown }; message?: unknown } : {};
	const raw = source.data?.message || source.message || '';
	return typeof raw === 'string' && raw.trim() ? raw : fallback;
};

export function useShipmentStepPageOrchestration(deps: StepPageDeps) {
	const route = useRoute();
	const shipmentFlowStore = useShipmentStore();
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

	const openShipmentAuthModal = (tab: AuthModalTab = 'login') => openAuthModal({ redirect: route.fullPath, tab });

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
			shipmentFlowStore: shipmentFlowStore as ShipmentDraftStore,
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
			shipmentFlowStore: shipmentFlowStore as { pendingShipment?: object | null; editingCartItemId?: unknown },
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
