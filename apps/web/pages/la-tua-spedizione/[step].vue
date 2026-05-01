<script setup>
// CRITICAL — Eccezione documentata 1207 LOC.
// Orchestratore funnel 5 step + entry point Stripe checkout. Composto da:
//   - Setup di 14 composable (services, addresses, validation, flow, summary, ecc.)
//   - 35+ computed di summary derivati da multipla sorgenti (cart/session/store)
//   - 3 provide() per dependency injection ai sub-componenti
//   - Handler ventaglio finale (add-to-cart, save-configured)
//   - Wrapper continueToCart con analytics
// SPLITTING richiede E2E Stripe gating con carta `4242 4242 4242 4242 09/30 123`.
// Le sezioni splittabili senza E2E (helper puri) sono GIA' state estratte in
// utils/shipmentStepHelpers.ts (8 funzioni), utils/shipmentSummaryHelpers.ts (8
// funzioni), utils/shipmentFlowPresentation.ts (formatting label).
import ShipmentStepColli from '~/components/shipment/ShipmentStepColli.vue';
import ShipmentStepServizi from '~/components/shipment/ShipmentStepServizi.vue';
import ShipmentStepIndirizzi from '~/components/shipment/ShipmentStepIndirizzi.vue';
import ShipmentStepPagamento from '~/components/shipment/ShipmentStepPagamento.vue';
import PublicPageHeader from '~/components/layout/PublicPageHeader.vue';
import {
	buildEmptyPaymentAddress,
	cleanPaymentSummaryText,
	formatExistingOrderDate,
	getExistingOrderPackageDimensions,
	getExistingOrderPackageQuantity,
	getExistingOrderPackageType,
	normalizeExistingOrderAddress,
} from '~/utils/shipmentStepHelpers';
// resolveApiError ora usato solo dentro useShipmentVentaglioActions.

const debugCheckpoint = (label) => {
	if (!import.meta.client) return;
	if (localStorage.getItem('sf_debug_shipment') !== '1') return;
	// eslint-disable-next-line no-console -- debug runtime opt-in via localStorage
	console.info(`[shipment-step-debug] ${label}`);
};
const summaryHydrationReady = ref(false);
onMounted(() => {
	nextTick(() => {
		summaryHydrationReady.value = true;
	});
});

const shipmentFlowStore = useShipmentStore();
const route = useRoute();
const { openAuthModal } = useAuthStore();
const authUi = useAuthUiState();
const isAuthenticated = authUi.isAuthenticatedForUi;
definePageMeta({ middleware: ['shipment-validation'] });
const DEBUG_ONLY_PACKAGES = false;
const DEBUG_DISABLE_QUICK_QUOTE_PACKAGES = false;

useSeoMeta({
	title: 'Preventivo',
	description: 'Configura colli, servizi, indirizzi e pagamento in un flusso unico, chiaro e modificabile.',
	ogTitle: 'Preventivo - SpedizioneFacile',
	ogDescription: 'Configura colli, servizi, indirizzi e pagamento in un flusso unico, chiaro e modificabile.',
});
const { session, status, refresh } = useSession({ server: true });
const sanctumClient = useSanctumClient();
debugCheckpoint('core deps ready');

// --- Navigation primitives (scroll + focus + transitions) ---------------
const {
	scrollAccordionStageIntoView,
	focusPickupDateSection: focusPickupDateSectionHelper,
	onAccordionPanelBeforeEnter,
	onAccordionPanelEnter,
	onAccordionPanelAfterEnter,
	onAccordionPanelBeforeLeave,
	onAccordionPanelLeave,
	onAccordionPanelAfterLeave,
} = useFunnelNavigation();

// Oggetto handler transitions passato come prop ai sub-componenti step.
const accordionTransitions = {
	onBeforeEnter: onAccordionPanelBeforeEnter,
	onEnter: onAccordionPanelEnter,
	onAfterEnter: onAccordionPanelAfterEnter,
	onBeforeLeave: onAccordionPanelBeforeLeave,
	onLeave: onAccordionPanelLeave,
	onAfterLeave: onAccordionPanelAfterLeave,
};
debugCheckpoint('navigation ready');

const deliveryMode = computed({
	get: () => shipmentFlowStore.deliveryMode,
	set: (v) => {
		shipmentFlowStore.deliveryMode = v;
	},
});
const isBusinessProfile = computed(() => {
	const rawType = String(authUi.uiSnapshot.value?.userType || '')
		.trim()
		.toLowerCase();
	return ['commerciante', 'azienda', 'business'].includes(rawType);
});

// --- Funnel state (errors + template refs + ui flags + icon filters) ----
const funnelState = useFunnelState();
const { dateError, submitError, contentError, paymentBootstrapError } = funnelState.errors;
const { formRef, stepsRef, pickupDateSectionRef, packagesStageRef, servicesStageRef, addressStageRef, paymentStageRef } =
	funnelState.templateRefs;
const { paymentBootstrapPending, paymentSummaryExpanded, isProceedingToPayment } = funnelState.ui;
const { SERVICE_ICON_FILTER_IDLE, SERVICE_ICON_FILTER_ACTIVE } = funnelState.iconFilters;
const visibleSubmitError = funnelState.visibleSubmitError;
const visiblePaymentBootstrapError = funnelState.visiblePaymentBootstrapError;
debugCheckpoint('funnel state ready');

// Dismiss manuale della pill di errore submit (bottone × nelle 3 pill step).
// Resetta il ref condiviso in useFunnelState così la pill sparisce da tutti gli step.
const clearVisibleSubmitError = () => {
	if (submitError.value) submitError.value = null;
};

const focusPickupDateSection = () => focusPickupDateSectionHelper(pickupDateSectionRef);
// Callback ref: il sub-componente ShipmentStepServizi inietta qui
// il template-ref del ShipmentStepPickupDate nel pickupDateSectionRef centrale.
const setPickupDateSectionRef = (el) => {
	pickupDateSectionRef.value = el ?? null;
};

const {
	chooseDate,
	chooseService,
	daysInMonth,
	ensureServiceSelected,
	expandedServiceKey,
	featuredService,
	regularServices,
	removeService,
	resetServicesState,
	serviceData,
	services,
	servicesList,
	smsEmailNotification,
	notificationPriceLabel,
	syncSelectedServicesVisual,
	toggleServiceDetails,
	toggleServiceSelection,
} = useShipmentStepServices({ shipmentFlowStore, dateError });
debugCheckpoint('shipment services ready');

const { editCartId, editablePackages, loadCartItemForEdit, loadingEditData } = useShipmentStepCartEdit({
	sanctumClient,
	session,
	syncSelectedServicesVisual,
	shipmentFlowStore,
});
debugCheckpoint('cart edit ready');

const {
	serviceCardErrors,
	clearServiceCardErrors,
	updateContrassegnoField,
	updateAssicurazioneValue,
	clearContrassegnoError,
	clearAssicurazioneError,
	normalizeCurrencyInput,
	contrassegnoIncassoOptions,
	contrassegnoRimborsoOptions,
	requiresContrassegnoDettaglio,
	insurancePackages,
	validateInlineServiceDetails,
	isServiceExpanded,
	canConfigureService,
	getServiceConfigureLabel,
	handleServicePrimaryAction,
	removeConfiguredService,
	toggleRegularService,
	toggleFeaturedService,
	activateConfiguredService,
} = useShipmentStepServiceCards({
	editablePackages,
	ensureServiceSelected,
	expandedServiceKey,
	featuredService,
	chooseService,
	removeService,
	resetServicesState,
	serviceData,
	servicesList,
	smsEmailNotification,
	submitError,
	toggleServiceDetails,
	toggleServiceSelection,
	shipmentFlowStore,
});
debugCheckpoint('service cards ready');

const {
	getWeightPrice,
	getVolumePrice,
	getCapSupplement,
	getEuropeQuote,
	priceBands: quickQuotePriceBands,
	loadPriceBands: loadQuickQuotePriceBands,
} = usePriceBands();
debugCheckpoint('price bands ready');

const quickQuotePackagesApi = DEBUG_DISABLE_QUICK_QUOTE_PACKAGES
	? {
			addPackageInline: () => {},
			calcPriceWithVolume: () => {},
			calcPriceWithWeight: () => {},
			calcQuantity: () => {},
			decrementQuantity: () => {},
			deletePack: () => {},
			ensurePackagesIdentity: () => {},
			europeRestrictionMessage: computed(() => ''),
			incrementQuantity: () => {},
			isEuropeMonocollo: computed(() => false),
			packageTypeList: [
				{ text: 'Pacco', img: 'pack.png', width: 43, height: 47 },
				{ text: 'Pallet', img: 'pallet.png', width: 43, height: 42 },
				{ text: 'Valigia', img: 'suitcase.png', width: 30, height: 52 },
			],
			updatePackageType: () => {},
		}
	: useQuickQuotePackages({
			shipmentFlowStore,
			getWeightPrice,
			getVolumePrice,
			getCapSupplement,
			getEuropeQuote,
			priceBands: quickQuotePriceBands,
		});

const {
	addPackageInline,
	calcPriceWithVolume,
	calcPriceWithWeight,
	calcQuantity: recalcPackageQuantity,
	decrementQuantity,
	deletePack,
	ensurePackagesIdentity,
	europeRestrictionMessage,
	incrementQuantity,
	isEuropeMonocollo,
	packageTypeList,
	updatePackageType,
} = quickQuotePackagesApi;
debugCheckpoint('quick quote packages ready');

const {
	applySavedAddress,
	destSelectorRef,
	destinationAddress,
	loadingSavedAddresses,
	originAddress,
	originSelectorRef,
	saveAddressToBook,
	savedAddresses,
	showDestAddressSelector,
	showOriginAddressSelector,
	shouldAutoShowAddressFields,
	toggleAddressSelector,
	canSaveOriginAddress,
	canSaveDestAddress,
	savingOriginAddress,
	savingDestAddress,
} = useShipmentStepAddresses({ shipmentFlowStore, session, route, isAuthenticated, sanctumClient, deliveryMode, submitError });
debugCheckpoint('addresses ready');

const { persistShipmentFlowState } = useShipmentStepSessionPersistence({
	sanctumClient,
	refresh,
	session,
	submitError,
	shipmentFlowStore,
	services,
	smsEmailNotification,
	originAddress,
	destinationAddress,
});
debugCheckpoint('session persistence ready');

// Auto-dismiss pill submitError quando l'utente modifica i campi
// rilevanti (colli, indirizzo mittente/destinatario). Debounce 300ms per evitare
// reset a ogni keystroke. {deep: true, flush: 'post'} per intercettare anche le
// mutazioni nested (es. pack.weight, address.city). Attivo SOLO se errore presente.
let _autoDismissErrorTimer = null;
watch(
	[() => editablePackages.value, () => originAddress, () => destinationAddress],
	() => {
		if (!submitError.value) return;
		if (_autoDismissErrorTimer) clearTimeout(_autoDismissErrorTimer);
		_autoDismissErrorTimer = setTimeout(() => {
			submitError.value = null;
			_autoDismissErrorTimer = null;
		}, 300);
	},
	{ deep: true, flush: 'post' },
);
onBeforeUnmount(() => {
	if (_autoDismissErrorTimer) clearTimeout(_autoDismissErrorTimer);
});

const {
	applyFieldAssist,
	contentFieldHint,
	destCapSuggestions,
	destCitySuggestions,
	destProvinceSuggestions,
	fieldClass,
	fieldErrorText,
	focusContentDescriptionField,
	focusFirstFormError,
	formatCapSuggestionLabel,
	formatCitySuggestionLabel,
	getFieldAssist,
	getFieldError,
	normalizeLocationText,
	onCapFocus,
	onCapInput,
	onCityFocus,
	onCityInput,
	onNameInput,
	onProvinciaInput,
	onProvinceFocus,
	onTelefonoInput,
	originCapSuggestions,
	originCitySuggestions,
	originProvinceSuggestions,
	selectCap,
	selectCity,
	selectProvincia,
	smartBlur,
	sv,
	validateForm,
} = useShipmentStepValidation({
	contentError,
	dateError,
	deliveryMode,
	destinationAddress,
	originAddress,
	sanctumClient,
	services,
	shipmentFlowStore,
});
debugCheckpoint('validation ready');

const {
	activeAccordionStep,
	addressReadinessItems,
	goBackToAddresses,
	goBackToServices,
	openPackagesStage,
	onPudoDeselected,
	onPudoSelected,
	openAddressFields,
	openPaymentStage,
	showAddressFields,
} = useShipmentStepFlow({
	contentError,
	dateError,
	deliveryMode,
	destinationAddress,
	focusContentDescriptionField,
	focusPickupDateSection,
	normalizeLocationText,
	persistServicesStep: () => persistShipmentFlowState({ includeAddresses: false }),
	session,
	services,
	shouldAutoShowAddressFields,
	sv,
	shipmentFlowStore,
});
debugCheckpoint('step flow ready');

const { currentStep, initOnMounted, showInitialStepLoading } = useShipmentStepPageState({
	destinationAddress,
	editCartId,
	isAuthenticated,
	loadCartItemForEdit,
	loadingEditData,
	ensurePackagesIdentity,
	originAddress,
	refresh,
	resetServicesState,
	services,
	session,
	showAddressFields,
	smsEmailNotification,
	status,
	shipmentFlowStore,
});
debugCheckpoint('page state ready');

const {
	routeConsistencyState,
	summaryDimensionsLabel,
	summaryDestinationCity,
	summaryOriginCity,
	summaryPackageLabel,
	summaryTotalPrice,
} = useShipmentStepSummary({
	activeAccordionStep,
	destinationAddress,
	editablePackages,
	normalizeLocationText,
	originAddress,
	session,
	showAddressFields,
	status,
	stepsRef,
	shipmentFlowStore,
});
debugCheckpoint('summary ready');
const ssrSafePackageAccordionSummary = computed(() => (summaryHydrationReady.value ? packageAccordionSummary.value : '0 colli · —'));
const ssrSafeAddressAccordionSummary = computed(() => (summaryHydrationReady.value ? addressAccordionSummary.value : '— -> —'));
const ssrSafeTrattaLabel = computed(() => (summaryHydrationReady.value ? trattaLabel.value : 'Tratta da definire'));
const ssrSafeSummaryPackageLabel = computed(() => (summaryHydrationReady.value ? summaryPackageLabel.value : '0 colli'));
const ssrSafeSummaryDimensionsLabel = computed(() => (summaryHydrationReady.value ? summaryDimensionsLabel.value : '—'));
const ssrSafeConfirmationOriginContact = computed(() =>
	summaryHydrationReady.value ? confirmationOriginContact.value : 'Mittente da completare',
);
const ssrSafeConfirmationDestinationContact = computed(() =>
	summaryHydrationReady.value
		? confirmationDestinationContact.value
		: deliveryMode.value === 'pudo'
			? 'Punto BRT da selezionare'
			: 'Destinatario da completare',
);
const ssrSafePaymentOriginAddress = computed(() => (summaryHydrationReady.value ? originAddress.value : buildEmptyPaymentAddress()));
const ssrSafePaymentDestinationAddress = computed(() =>
	summaryHydrationReady.value ? destinationAddress.value : buildEmptyPaymentAddress(),
);

// --- Packages/services validation (step 1 & 2) --------------------------
const funnelValidation = useFunnelValidation({
	sv,
	editablePackages,
	calcPriceWithWeight,
	calcPriceWithVolume,
	recalcPackageQuantity,
	validateInlineServiceDetails,
});
const {
	packagesError,
	getPackageMetricError,
	getPackageMetricClass,
	focusFirstInvalidServiceField,
	onPackageQuantityInput,
	onPackageWeightInput,
	onPackageWeightBlur,
	onPackageDimensionInput,
	onPackageDimensionBlur,
	validatePackagesStep,
} = funnelValidation;
debugCheckpoint('funnel validation ready');

const handleAddPackage = () => {
	packagesError.value = '';
	addPackageInline();
};
const handleDeletePackage = (targetPackId) => {
	packagesError.value = '';
	deletePack(targetPackId);
};
const handleUpdatePackageType = (pack, packageType) => {
	packagesError.value = '';
	updatePackageType(pack, packageType);
};

const cart = useCart();
const pay = usePayment(cart);
debugCheckpoint('cart + payment ready');

// Destructure da cart (nomi identici, tranne pageReady -> checkoutPageReady
// per mantenere il nome usato nel template e nell'orchestration composable).
const {
	pageReady: checkoutPageReady,
	existingOrderId,
	existingOrder,
	displayPackages,
	initCheckoutPage,
	loadPriceBands,
	totalPackages,
	getNumberTotal,
	finalTotalFormatted,
	fatturazioneType,
	invoiceSubjectType,
	fatturaData,
	billingShippingFullAddress,
	walletFormatted,
	walletLoaded,
	walletSufficient,
	couponCode,
	couponLoading,
	couponError,
	couponApplied,
	couponPanelOpen,
	validateCoupon,
	removeCoupon,
	autoApplyReferral,
	contentDescription: checkoutContentDescription,
} = cart;

const existingOrderPackages = computed(() => (
	existingOrder.value && Array.isArray(displayPackages.value) ? displayPackages.value : []
));
const existingOrderPrimaryPackage = computed(() => existingOrderPackages.value[0] || null);
const hasExistingOrderSummary = computed(() => Boolean(existingOrderId.value && existingOrderPackages.value.length));

const existingOrderPackageCount = computed(() =>
	existingOrderPackages.value.reduce((sum, pack) => sum + getExistingOrderPackageQuantity(pack), 0),
);
const existingOrderPackageLabel = computed(() => {
	const count = existingOrderPackageCount.value;
	return `${count} ${count === 1 ? 'collo' : 'colli'}`;
});
const existingOrderDimensionsLabel = computed(() => {
	const grouped = new Map();
	for (const pack of existingOrderPackages.value) {
		const dimensions = getExistingOrderPackageDimensions(pack);
		if (!dimensions) continue;
		const type = getExistingOrderPackageType(pack);
		const key = `${type}|${dimensions}`;
		grouped.set(key, (grouped.get(key) || 0) + getExistingOrderPackageQuantity(pack));
	}

	const rows = Array.from(grouped.entries()).map(([key, qty]) => {
		const [type, dimensions] = key.split('|');
		return qty > 1 ? `${qty}x ${type}: ${dimensions}` : `${type}: ${dimensions}`;
	});

	if (!rows.length) return '';
	return rows.length === 1 ? rows[0] : `${rows[0]} +${rows.length - 1}`;
});
const existingOrderOriginAddress = computed(() =>
	normalizeExistingOrderAddress(existingOrderPrimaryPackage.value?.origin_address),
);
const existingOrderDestinationAddress = computed(() =>
	normalizeExistingOrderAddress(existingOrderPrimaryPackage.value?.destination_address),
);
const existingOrderService = computed(() => existingOrderPrimaryPackage.value?.services || {});
const existingOrderDeliveryMode = computed(() => (
	existingOrder.value?.brt_pudo_id ? 'pudo' : 'home'
));
const existingOrderPickupDate = computed(() =>
	formatExistingOrderDate(
		existingOrder.value?.pickup_date
		|| existingOrderService.value?.date
		|| existingOrderService.value?.serviceData?.pickup_request?.date,
	),
);
const existingOrderServicesLabel = computed(() => {
	const raw = cleanPaymentSummaryText(existingOrderService.value?.service_type);
	return !raw || raw.toLowerCase() === 'nessuno' ? 'Nessun extra selezionato' : raw;
});
const existingOrderContentDescription = computed(() => (
	cleanPaymentSummaryText(existingOrderPackages.value.find((pack) => cleanPaymentSummaryText(pack?.content_description))?.content_description)
	|| cleanPaymentSummaryText(checkoutContentDescription.value)
));
const existingOrderRouteLabel = computed(() => {
	const originCity = cleanPaymentSummaryText(existingOrderOriginAddress.value.city);
	const destinationCity = cleanPaymentSummaryText(existingOrderDestinationAddress.value.city || existingOrderDestinationAddress.value.name);
	if (originCity && destinationCity) return `${originCity} -> ${destinationCity}`;
	return '';
});

// Boundary payment summary: il checkout da order_id vive sul backend, non nel
// draft del funnel. Qui scegliamo i dati ordine persistito solo per la UI.
const paymentTrattaLabel = computed(() => (
	hasExistingOrderSummary.value ? existingOrderRouteLabel.value : ssrSafeTrattaLabel.value
));
const paymentColloLabel = computed(() => (
	hasExistingOrderSummary.value ? existingOrderPackageLabel.value : colloLabel.value
));
const paymentSummaryPackageLabel = computed(() => (
	hasExistingOrderSummary.value ? existingOrderPackageLabel.value : ssrSafeSummaryPackageLabel.value
));
const paymentSummaryDimensionsLabel = computed(() => (
	hasExistingOrderSummary.value ? existingOrderDimensionsLabel.value : ssrSafeSummaryDimensionsLabel.value
));
const paymentConfirmationOriginContact = computed(() => (
	hasExistingOrderSummary.value ? existingOrderOriginAddress.value.full_name : ssrSafeConfirmationOriginContact.value
));
const paymentConfirmationDestinationContact = computed(() => (
	hasExistingOrderSummary.value ? existingOrderDestinationAddress.value.full_name : ssrSafeConfirmationDestinationContact.value
));
const paymentOriginAddress = computed(() => (
	hasExistingOrderSummary.value ? existingOrderOriginAddress.value : ssrSafePaymentOriginAddress.value
));
const paymentDestinationAddress = computed(() => (
	hasExistingOrderSummary.value ? existingOrderDestinationAddress.value : ssrSafePaymentDestinationAddress.value
));
const paymentDeliveryMode = computed(() => (
	hasExistingOrderSummary.value ? existingOrderDeliveryMode.value : deliveryMode.value
));
const paymentPickupDate = computed(() => (
	hasExistingOrderSummary.value ? existingOrderPickupDate.value : confirmationPickupDate.value
));
const paymentServicesLabel = computed(() => (
	hasExistingOrderSummary.value ? existingOrderServicesLabel.value : paymentSummaryServicesLabel.value
));
const paymentContentDescription = computed(() => (
	hasExistingOrderSummary.value ? existingOrderContentDescription.value : resolvedContentDescription.value
));
const paymentDeliveryDisplayLabel = computed(() => (
	hasExistingOrderSummary.value
		? (existingOrderDeliveryMode.value === 'pudo' ? 'Punto BRT' : 'Consegna a domicilio')
		: paymentDeliveryLabel.value
));
const packagesStageSummary = computed(() => {
	if (!hasExistingOrderSummary.value) return ssrSafePackageAccordionSummary.value;
	const dimensions = cleanPaymentSummaryText(existingOrderDimensionsLabel.value);
	return `${existingOrderPackageLabel.value} · ${dimensions || 'Misure disponibili'}`;
});
const addressStageSummary = computed(() => (
	hasExistingOrderSummary.value
		? (existingOrderRouteLabel.value || ssrSafeAddressAccordionSummary.value)
		: ssrSafeAddressAccordionSummary.value
));

// Destructure da pay (nomi identici, tranne termsAccepted -> checkoutTermsAccepted
// e isProcessing -> checkoutIsProcessing per preservare l'API del template).
const {
	initStripe,
	stripeLoading,
	cardPaymentsUnavailable,
	cardPaymentsNotice,
	paymentMethod,
	paymentMethodOptions,
	selectPaymentMethod,
	cardElementContainer,
	cardError,
	shouldShowCardForm,
	useNewCard,
	saveCardForFuture,
	hasSavedCard,
	defaultPayment,
	termsAccepted: checkoutTermsAccepted,
	showConfirmModal,
	confirmPayment,
	proceedWithPayment,
	isProcessing: checkoutIsProcessing,
	paymentError,
	paymentSuccess,
	successOrderId,
	paymentStep,
	paymentActionLabel,
	canPay,
	payButtonTooltip,
	// Wallet express (Apple Pay / Google Pay) — Stripe PaymentRequestButton.
	// Dopo F-Pay-2 sono fallback inerti (archiviati). Lasciati per retro-compatibilità template.
	canMakePayment,
	paymentRequestContainer,
	paymentRequestError,
	isAppleAvailable,
	isGoogleAvailable,
	mountPaymentRequestButton,
} = pay;

const setCheckoutCardRef = (el) => {
	cardElementContainer.value = el;
};

// Handler per update:xxx emessi da ShipmentStepPagamento.
// Necessari perche' nel template Vue i ref sono auto-unwrappati e non si puo'
// scrivere `ref.value = v` direttamente: serve una funzione nello script setup.
const onPaymentSummaryExpanded = (v) => {
	paymentSummaryExpanded.value = v;
};
const onCouponPanelOpen = (v) => {
	couponPanelOpen.value = v;
};
const onCouponCode = (v) => {
	couponCode.value = v;
};
const onUseNewCard = (v) => {
	useNewCard.value = v;
};
const onSaveCardForFuture = (v) => {
	saveCardForFuture.value = v;
};
const onFatturazioneType = (v) => {
	fatturazioneType.value = v;
};
const onInvoiceSubjectType = (v) => {
	invoiceSubjectType.value = v;
};
const onFatturaData = (v) => {
	fatturaData.value = {
		...fatturaData.value,
		...v,
	};
};
const onCheckoutTermsAccepted = (v) => {
	checkoutTermsAccepted.value = v;
};
const onShowConfirmModal = (v) => {
	showConfirmModal.value = v;
};

// Wallet express: callback ref per container del Stripe PaymentRequestButton.
// onPaymentRequestReady viene chiamato dal PaymentMethods quando il container
// e' in DOM e canMakePayment = true; monta il bottone Apple/Google Pay.
// Guard: se l'utente non ha ancora accettato i termini, non montiamo il bottone
// — il watch su termsAccepted in usePaymentFlow lo monterà appena spunta.
const setPaymentRequestRef = (el) => {
	paymentRequestContainer.value = el;
};
const handlePaymentRequestReady = async () => {
	if (!checkoutTermsAccepted.value) return;
	await mountPaymentRequestButton();
};

const uiFeedback = useUiFeedback();
// Funnel analytics: 5 step principali del preventivo.
const funnelAnalytics = useFunnelAnalytics();
// GA4 e-commerce: add_to_cart / begin_checkout / add_payment_info
debugCheckpoint('analytics ready');

const updateAddressField = (type, field, value) => {
	const target = type === 'origin' ? originAddress : destinationAddress;
	target.value = {
		...target.value,
		[field]: value,
	};
};

const { continueToCart: persistAndContinueToCart, isSubmitting } = useShipmentStepSubmit({
	destinationAddress,
	editablePackages,
	editCartId,
	focusFirstFormError,
	focusPickupDateSection,
	formRef,
	navigateToRiepilogo: false,
	normalizeLocationText,
	originAddress,
	persistSecondStep: (p) => persistShipmentFlowState({ includeAddresses: true, payload: p }),
	routeConsistencyState,
	smsEmailNotification,
	services,
	submitError,
	uiFeedback,
	shipmentFlowStore,
	validateForm,
});
debugCheckpoint('step submit ready');

// --- PROVIDE: funzioni form/validazione iniettate nei componenti figli ---
// Evita prop drilling di 19+ funzioni attraverso StepAddressSection -> AddressFormFields.
provide('shipmentFormHandlers', {
	fieldClass,
	getFieldError,
	fieldErrorText,
	getFieldAssist,
	applyFieldAssist,
	updateAddressField,
	smartBlur,
	onNameInput,
	onCityInput,
	onCityFocus,
	onProvinciaInput,
	onProvinceFocus,
	onCapInput,
	onCapFocus,
	onTelefonoInput,
	selectCity,
	selectProvincia,
	selectCap,
	formatCitySuggestionLabel,
	formatCapSuggestionLabel,
	sv,
});
provide('shipmentSuggestions', {
	originCitySuggestions,
	originProvinceSuggestions,
	originCapSuggestions,
	destCitySuggestions,
	destProvinceSuggestions,
	destCapSuggestions,
});

// continueToCart (handler wrapper analytics + apertura Step 4) resta qui
// perché dipende dall'orchestrazione: deve chiamare openPaymentAccordion DOPO trackServicesSelected.
// È una forward declaration: openPaymentAccordion arriva da useShipmentStepPageOrchestration qui sotto.
const continueToCart = async () => {
	if (document.activeElement instanceof HTMLElement) document.activeElement.blur();
	await nextTick();

	if (!validateInlineServiceDetails()) {
		focusFirstInvalidServiceField();
		return false;
	}

	const persisted = await persistAndContinueToCart();
	if (persisted === false) return false;

	// Analytics: indirizzi compilati e validati. Evento senza PII.
	funnelAnalytics.trackAddressesFilled();

	// Analytics: snapshot dei servizi selezionati al submit (non ad ogni toggle).
	const selectedServiceNames = [];
	if (featuredService.value?.isSelected) selectedServiceNames.push(String(featuredService.value.name || 'featured'));
	for (const s of regularServices.value || []) {
		if (s?.isSelected) selectedServiceNames.push(String(s.name || 'service'));
	}
	if (smsEmailNotification.value) selectedServiceNames.push('sms_email');
	funnelAnalytics.trackServicesSelected(selectedServiceNames);

	await openPaymentAccordion();
	return true;
};

/* USCITE ALTERNATIVE DAL VENTAGLIO (step Indirizzi):
   "Aggiungi al carrello" + handler archiviato "Salva configurata".
   Logica spostata in useShipmentVentaglioActions per isolare il flusso. */
const {
	isAddingToCart: isAddingToCartFromVentaglio,
	isSavingConfigured: isSavingConfiguredFromVentaglio,
	handleAddToCart: handleAddToCartFromVentaglio,
	handleSaveConfigured: handleSaveConfiguredFromVentaglio,
} = useShipmentVentaglioActions({
	sanctumClient,
	uiFeedback,
	isAuthenticated,
	shipmentFlowStore,
	validateInlineServiceDetails,
	focusFirstInvalidServiceField,
	persistAndContinueToCart,
});

// Orchestrazione UI (summary, accordion open/close, sticky bar,
// payment bootstrap). Raggruppa le 35+ computed/funzioni che prima vivevano
// inline in questa pagina senza modificarne la logica.
const {
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
	openShipmentAuthModal,
} = useShipmentStepPageOrchestration({
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
});
</script>

<template>
	<section class="pb-[64px] md:pb-[88px]" style="background: var(--gradient-page-surface)">
		<div v-if="false" class="w-full max-w-[1280px] mx-auto px-[14px] sm:px-[40px] py-[40px]">
			<div class="rounded-[18px] border border-[#D7E4E7] bg-white p-[24px] text-[#1d2738]">debug step shell</div>
		</div>
		<PublicPageHeader
			:title="quoteHeroTitle"
			:description="quoteHeroDescription"
			eyebrow="Preventivo"
			variant="compact"
			:crumbs="[{ label: 'Home', to: '/' }, { label: 'Preventivo' }]" />
		<!-- P8 Progress bar 4 step (Baymard pattern checkout) -->
		<ShipmentStepProgress :current-step="currentStep" />
		<div class="w-full max-w-[1280px] mx-auto px-[14px] sm:px-[40px]">
			<form ref="formRef" class="preventivo-form" novalidate @submit.prevent="continueToCart">
				<div
					v-if="showInitialStepLoading"
					class="mt-[18px] md:mt-[20px] rounded-[28px] border border-[#D7E4E7] bg-white/[0.94] p-[18px] sm:p-[24px] shadow-[0_18px_45px_rgba(15,23,42,0.08)]"
					role="status"
					aria-live="polite"
					data-test="shipment-step-initial-loading">
					<div class="flex items-start gap-[12px]">
						<div
							class="mt-[2px] h-[18px] w-[18px] rounded-full border-[2px] border-[#095866]/20 border-t-[#095866] animate-spin shrink-0" />
						<div class="min-w-0">
							<p class="text-[#1d2738] text-[15px] sm:text-[16px] tracking-[-0.1px]" style="font-weight: 700">
								Ripristiniamo la tua spedizione
							</p>
							<p class="mt-[4px] text-[#667085] text-[13px] leading-[1.55]" style="font-weight: 500">
								Carichiamo i dati salvati prima di mostrare gli step, cosi la pagina non cambia struttura durante l'hydration.
							</p>
						</div>
					</div>

					<div class="mt-[18px] space-y-[12px]" aria-hidden="true">
						<div
							v-for="sectionIndex in 3"
							:key="sectionIndex"
							class="rounded-[22px] border border-[#E6ECEF] bg-[#F8FAFB] px-[16px] py-[18px]">
							<div class="flex items-center justify-between gap-[12px]">
								<div class="min-w-0 flex-1 space-y-[9px]">
									<div class="h-[10px] w-[88px] rounded-full bg-[#D8E3E6]" />
									<div class="h-[14px] max-w-[320px] rounded-full bg-[#E3EAED]" />
								</div>
								<div class="h-[18px] w-[66px] rounded-full bg-[#E3EAED] shrink-0" />
							</div>
							<div v-if="sectionIndex === 1" class="mt-[16px] grid gap-[10px] sm:grid-cols-3">
								<div class="h-[54px] rounded-[16px] bg-white" />
								<div class="h-[54px] rounded-[16px] bg-white" />
								<div class="h-[54px] rounded-[16px] bg-white" />
							</div>
						</div>
					</div>
				</div>

				<div v-else class="mt-[18px] md:mt-[20px] flex flex-col gap-[14px]">
					<!-- Step 1 — Colli -->
					<ShipmentStepColli
						ref="packagesStageRef"
						:is-open="isPackagesAccordionOpen"
						:summary="packagesStageSummary"
						:editable-packages="editablePackages"
						:package-type-list="packageTypeList"
						:is-europe-monocollo="isEuropeMonocollo"
						:europe-restriction-message="europeRestrictionMessage"
						:summary-package-label="paymentSummaryPackageLabel"
						:summary-dimensions-label="paymentSummaryDimensionsLabel"
						:packages-error="packagesError"
						:get-package-metric-class="getPackageMetricClass"
						:get-package-metric-error="getPackageMetricError"
						:handle-add-package="handleAddPackage"
						:handle-delete-package="handleDeletePackage"
						:handle-update-package-type="handleUpdatePackageType"
						:increment-quantity="incrementQuantity"
						:decrement-quantity="decrementQuantity"
						:on-package-quantity-input="onPackageQuantityInput"
						:on-package-weight-input="onPackageWeightInput"
						:on-package-weight-blur="onPackageWeightBlur"
						:on-package-dimension-input="onPackageDimensionInput"
						:on-package-dimension-blur="onPackageDimensionBlur"
						:visible-submit-error="visibleSubmitError"
						:accordion-transitions="accordionTransitions"
						@open="openPackagesAccordion"
						@confirm="openServicesAccordion"
						@dismiss-error="clearVisibleSubmitError" />
					<!-- Step 2 — Servizi -->
					<ShipmentStepServizi
						v-if="!DEBUG_ONLY_PACKAGES"
						ref="servicesStageRef"
						:is-open="isServicesAccordionOpen"
						:summary="servicesAccordionSummary"
						:set-pickup-date-section-ref="setPickupDateSectionRef"
						:date-error="dateError"
						:days-in-month="daysInMonth"
						:services="services"
						:choose-date="chooseDate"
						:featured-service="featuredService"
						:regular-services="regularServices"
						:service-data="serviceData"
						:service-card-errors="serviceCardErrors"
						:update-contrassegno-field="updateContrassegnoField"
						:update-assicurazione-value="updateAssicurazioneValue"
						:clear-contrassegno-error="clearContrassegnoError"
						:clear-assicurazione-error="clearAssicurazioneError"
						:is-service-expanded="isServiceExpanded"
						:can-configure-service="canConfigureService"
						:get-service-configure-label="getServiceConfigureLabel"
						:contrassegno-incasso-options="contrassegnoIncassoOptions"
						:contrassegno-rimborso-options="contrassegnoRimborsoOptions"
						:requires-contrassegno-dettaglio="requiresContrassegnoDettaglio"
						:insurance-packages="insurancePackages"
						:normalize-currency-input="normalizeCurrencyInput"
						:service-icon-filter-idle="SERVICE_ICON_FILTER_IDLE"
						:service-icon-filter-active="SERVICE_ICON_FILTER_ACTIVE"
						:toggle-featured-service="toggleFeaturedService"
						:toggle-regular-service="toggleRegularService"
						:handle-service-primary-action="handleServicePrimaryAction"
						:activate-configured-service="activateConfiguredService"
						:remove-configured-service="removeConfiguredService"
						:resolved-content-description="resolvedContentDescription"
						:content-error="contentError"
						:content-field-hint="contentFieldHint"
						:sms-email-notification="smsEmailNotification"
						:notification-price-label="notificationPriceLabel"
						:update-content-description="(v) => (shipmentFlowStore.contentDescription = v)"
						:update-content-error="(v) => (contentError = v)"
						:update-sms-email-notification="(v) => (smsEmailNotification = v)"
						:visible-submit-error="visibleSubmitError"
						:accordion-transitions="accordionTransitions"
						@open="openServicesAccordion"
						@back="openPackagesAccordion"
						@confirm="openAddressAccordion"
						@dismiss-error="clearVisibleSubmitError" />

					<!-- Step 3 — Indirizzi -->
					<ShipmentStepIndirizzi
						v-if="!DEBUG_ONLY_PACKAGES"
						ref="addressStageRef"
						:is-open="isAddressAccordionOpen"
						:summary="addressStageSummary"
						:show-address-fields="showAddressFields"
						:origin-address="ssrSafePaymentOriginAddress"
						:destination-address="ssrSafePaymentDestinationAddress"
						:is-business-profile="isBusinessProfile"
						:delivery-mode="deliveryMode"
						:saved-addresses="savedAddresses"
						:loading-saved-addresses="loadingSavedAddresses"
						:show-origin-address-selector="showOriginAddressSelector"
						:show-dest-address-selector="showDestAddressSelector"
						:is-authenticated="isAuthenticated"
						:can-save-origin-address="canSaveOriginAddress"
						:can-save-dest-address="canSaveDestAddress"
						:saving-origin-address="savingOriginAddress"
						:saving-dest-address="savingDestAddress"
						:selected-pudo="shipmentFlowStore?.selectedPudo ?? null"
						:origin-selector-ref="originSelectorRef"
						:dest-selector-ref="destSelectorRef"
						:is-submitting="isSubmitting"
						:is-proceeding-to-payment="isProceedingToPayment"
						:is-adding-to-cart="isAddingToCartFromVentaglio"
						:is-saving-configured="isSavingConfiguredFromVentaglio"
						:can-advance-from-addresses="canAdvanceFromAddresses"
						:address-readiness-items="addressReadinessItems"
						:visible-submit-error="visibleSubmitError"
						:accordion-transitions="accordionTransitions"
						:save-address-to-book="saveAddressToBook"
						:toggle-address-selector="toggleAddressSelector"
						:apply-saved-address="applySavedAddress"
						:open-shipment-auth-modal="openShipmentAuthModal"
						:on-pudo-selected="onPudoSelected"
						:on-pudo-deselected="onPudoDeselected"
						@update:delivery-mode="(v) => (deliveryMode = v)"
						@update:origin-selector-ref="(v) => (originSelectorRef = v)"
						@update:dest-selector-ref="(v) => (destSelectorRef = v)"
						@open="openAddressAccordion"
						@back="collapseAddressAccordion"
						@confirm="continueToCart"
						@add-to-cart="handleAddToCartFromVentaglio"
						@save-configured="handleSaveConfiguredFromVentaglio"
						@dismiss-error="clearVisibleSubmitError" />

					<!-- Step 4 — Pagamento -->
					<ShipmentStepPagamento
						v-if="!DEBUG_ONLY_PACKAGES"
						ref="paymentStageRef"
						:is-open="isPaymentAccordionOpen"
						:payment-success="paymentSuccess"
						:tratta-label="paymentTrattaLabel"
						:final-total-formatted="finalTotalFormatted"
						:summary-total-price="summaryTotalPrice"
						:subtotal="getNumberTotal"
						:collo-label="paymentColloLabel"
						:confirmation-pickup-date="paymentPickupDate"
						:payment-delivery-label="paymentDeliveryDisplayLabel"
						:payment-method-label="paymentMethodLabel"
						:payment-summary-expanded="paymentSummaryExpanded"
						:summary-package-label="paymentSummaryPackageLabel"
						:summary-dimensions-label="paymentSummaryDimensionsLabel"
						:confirmation-origin-contact="paymentConfirmationOriginContact"
						:confirmation-destination-contact="paymentConfirmationDestinationContact"
						:origin-address="paymentOriginAddress"
						:destination-address="paymentDestinationAddress"
						:delivery-mode="paymentDeliveryMode"
						:payment-summary-services-label="paymentServicesLabel"
						:resolved-content-description="paymentContentDescription"
						:payment-bootstrap-pending="paymentBootstrapPending"
						:visible-payment-bootstrap-error="visiblePaymentBootstrapError"
						:success-order-id="successOrderId"
						:payment-method="paymentMethod"
						:checkout-page-ready="checkoutPageReady"
						:coupon-panel-open="couponPanelOpen"
						:coupon-applied="couponApplied"
						:coupon-code="couponCode"
						:coupon-loading="couponLoading"
						:coupon-error="couponError"
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
						:set-checkout-card-ref="setCheckoutCardRef"
						:wallet-formatted="walletFormatted"
						:wallet-loaded="walletLoaded"
						:wallet-sufficient="walletSufficient"
						:can-make-payment="canMakePayment"
						:is-apple-available="isAppleAvailable"
						:is-google-available="isGoogleAvailable"
						:payment-request-error="paymentRequestError || ''"
						:payment-request-ref-callback="setPaymentRequestRef"
						:on-payment-request-ready="handlePaymentRequestReady"
						:fatturazione-type="fatturazioneType"
						:invoice-subject-type="invoiceSubjectType"
						:fattura-data="fatturaData"
						:billing-shipping-full-address="billingShippingFullAddress"
						:payment-action-label="paymentActionLabel"
						:pay-button-tooltip="payButtonTooltip"
						:can-pay="canPay"
						:checkout-is-processing="checkoutIsProcessing"
						:visible-payment-error="visiblePaymentError"
						:payment-step="paymentStep"
						:checkout-terms-accepted="checkoutTermsAccepted"
						:show-confirm-modal="showConfirmModal"
						:total-packages="totalPackages"
						:accordion-transitions="accordionTransitions"
						:is-authenticated="isAuthenticated"
						:validate-coupon="validateCoupon"
						:remove-coupon="removeCoupon"
						:select-payment-method="selectPaymentMethod"
						:confirm-payment="confirmPayment"
						:proceed-with-payment="proceedWithPayment"
						@open="openPaymentAccordion"
						@edit-packages="openPackagesAccordion"
						@edit-addresses="openAddressAccordion"
						@edit-services="openServicesAccordion"
						@request-login="(tab) => openShipmentAuthModal(tab || 'login')"
						@update:payment-summary-expanded="onPaymentSummaryExpanded"
						@update:coupon-panel-open="onCouponPanelOpen"
						@update:coupon-code="onCouponCode"
						@update:use-new-card="onUseNewCard"
						@update:save-card-for-future="onSaveCardForFuture"
						@update:fatturazione-type="onFatturazioneType"
						@update:invoice-subject-type="onInvoiceSubjectType"
						@update:fattura-data="onFatturaData"
						@update:checkout-terms-accepted="onCheckoutTermsAccepted"
						@update:show-confirm-modal="onShowConfirmModal" />
				</div>
			</form>
		</div>
	</section>
</template>
