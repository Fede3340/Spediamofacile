/**
 * COMPOSABLE: useQuote (useQuote.js)
 * SCOPO: orchestratore Preventivo Rapido. Unifica 4 sezioni in un solo file:
 *   - Sezione 1: QuoteSnapshot (helper puri + composable snapshot)
 *   - Sezione 2: Form (stato/validazione/reset/location manuali)
 *   - Sezione 3: Calc (API/tariffe/sessione/de-dup richieste)
 *   - Sezione 4: Results (computed presentazione + navigazione + watchers)
 *
 * Consumer: components/Preventivo.vue
 *
 * Nota storica: le sezioni 2-4 vivevano come sub-composable separati
 * (usePreventivoForm.js, usePreventivoCalc.js, usePreventivoResults.js);
 * venivano importati SOLO da questo orchestratore. Li abbiamo consolidati
 * inline per ridurre indiretture e facilitare la lettura del flusso
 * reattivo (timer auto-quote condiviso, de-dup richieste, watchers).
 * Gli helper puri canonici vivono in utils/quickQuoteContract.js.
 */

import {
	buildQuoteComparableSignature,
	clonePackagesForQuote,
	cloneShipmentDetailsForQuote,
	extractSessionComparablePayload,
	formatResolvedLocation,
} from "~/utils/quickQuoteHelpers";

type QuoteFlowStore = ReturnType<typeof useShipmentStore>;
type QuoteTimer = ReturnType<typeof setTimeout> | null;

/**
 * Composable snapshot: espone `buildQuotePayloadSnapshot` + `quoteSignature`
 * computed sullo store passato. Mantenuto esportato per retrocompat nel caso
 * qualche consumer esterno lo importi direttamente.
 */
export const useQuoteSnapshot = (shipmentFlowStore: QuoteFlowStore) => {
	const buildQuotePayloadSnapshot = () => ({
		shipment_details: cloneShipmentDetailsForQuote(shipmentFlowStore?.shipmentDetails),
		packages: clonePackagesForQuote(shipmentFlowStore?.packages),
	});

	const quoteSignature = computed(() => buildQuoteComparableSignature(buildQuotePayloadSnapshot()));

	return {
		buildQuotePayloadSnapshot,
		buildQuoteComparableSignature,
		extractSessionComparablePayload,
		formatResolvedLocation,
		quoteSignature,
	};
};

// =============================================================================
// SEZIONE 2: FORM — stato/validazione/reset/location manuali
// (ex usePreventivoForm.js)
// =============================================================================

/**
 * Sub-logica form: stato del form, validazione campi, gestione country/location,
 * input manuali, reset, flush drafts prima del submit.
 * Interna all'orchestratore — non esportata pubblicamente.

// Sezioni Form/Pricing/Results estratte in file dedicati.
import { useQuoteFormInternal } from '~/composables/useQuoteForm';
import { useQuotePricingInternal } from '~/composables/useQuotePricing';
import { useQuoteResultsInternal } from '~/composables/useQuoteResults';

// API PUBBLICA: useQuote()
// =============================================================================

/**
 * useQuote — orchestratore del form Preventivo Rapido.
 * Compone: Form + Calc + Results + QuoteSnapshot.
 * Consumer: components/Preventivo.vue.
 *
 * Return signature IDENTICA alla vecchia implementazione (orchestrava 4 sub).
 */
export const useQuote = () => {
	// --- DIPENDENZE GLOBALI ---
	const shipmentFlowStore = useShipmentStore();
	const route = useRoute();
	const runtimeConfig = useRuntimeConfig();
	const apiBase = String(runtimeConfig.public?.apiBase || "http://127.0.0.1:8787").replace(/\/$/, "");

	// --- AUTO-QUOTE TIMER (shared mutable state across sections) ---
	// Wrapper a oggetto per permettere alle sezioni di leggere/scrivere il timer.
	const _timerBox: { value: QuoteTimer } = { value: null };
	function autoQuoteTimerRef(): QuoteTimer;
	function autoQuoteTimerRef(value: QuoteTimer): QuoteTimer;
	function autoQuoteTimerRef(value?: QuoteTimer) {
		if (arguments.length === 0) return _timerBox.value;
		_timerBox.value = value ?? null;
		return _timerBox.value;
	}

	onBeforeUnmount(() => {
		const timer = autoQuoteTimerRef();
		if (timer) {
			clearTimeout(timer);
		}
	});

	// --- PRICE BANDS ---
	const { loadPriceBands, getWeightPrice, getVolumePrice, getCapSupplement, getEuropeQuote, priceBands, promoSettings } = usePriceBands();
	onMounted(() => {
		loadPriceBands().catch(() => {});
	});

	// --- LOCATION SEARCH ---
	const publicApiFetchForLocation = async (path: string, options: Record<string, unknown> = {}) => {
		const url = path.startsWith("http") ? path : `${apiBase}${path}`;
		return await $fetch(url, { credentials: "include", ...options });
	};
	const locationSearch = useLocationSearch(publicApiFetchForLocation);

	// --- SESSION ---
	const { session, refresh } = useSession();

	// --- PACKAGES ---
	const {
		addPackageInline,
		calcPriceWithVolume,
		calcPriceWithWeight,
		calcQuantity,
		checkPrices,
		decrementQuantity,
		deletePack,
		ensurePackagesIdentity,
		europeRestrictionMessage,
		incrementQuantity,
		isEuropeMonocollo,
		packageTypeList,
		selectPackageType,
		updatePackageType,
	} = useQuickQuotePackages({
		shipmentFlowStore,
		getWeightPrice,
		getVolumePrice,
		getCapSupplement,
		getEuropeQuote,
		priceBands,
	});

	// --- COUNTRY COMPUTED (serve prima delle sezioni) ---
	const isDestinationItaly = computed(() => (
		String(shipmentFlowStore?.shipmentDetails.destination_country_code || "IT").trim().toUpperCase() === "IT"
	));

	const isOriginItaly = computed(() => (
		String(shipmentFlowStore?.shipmentDetails.origin_country_code || "IT").trim().toUpperCase() === "IT"
	));

	// --- QUOTE SNAPSHOT (Sezione 1) ---
	const {
		buildQuotePayloadSnapshot,
		quoteSignature,
	} = useQuoteSnapshot(shipmentFlowStore);

	// --- SMART VALIDATION (istanza condivisa tra le sezioni) ---
	const sv = useSmartValidation();
	const onCapInputSmartForLocations = (fieldKey: string, value: string, countryCode = "IT") => {
		if (sv.isTouched(fieldKey)) {
			sv.validateCAP(fieldKey, value, { countryCode });
		}
	};

	// --- LOCATIONS (autocomplete IT) ---
	const {
		destQuery,
		destSuggestions,
		getProvinceLabel,
		hideDestSuggestions,
		hideOriginSuggestions,
		locationKey,
		onDestQueryFocus,
		onDestQueryInput,
		onOriginQueryFocus,
		onOriginQueryInput,
		originQuery,
		originSuggestions,
		selectDestLocation,
		selectOriginLocation,
		settleDestQuery,
		settleOriginQuery,
		showDestSuggestions,
		showOriginSuggestions,
	} = useQuickQuoteLocations({
		shipmentDetails: shipmentFlowStore?.shipmentDetails,
		search: locationSearch,
		smartValidation: sv,
		onCapInputSmart: onCapInputSmartForLocations,
	});

	// =========================================================================
	// SEZIONE 2: FORM
	// =========================================================================
	const {
		formRef,
		onWeightInput,
		onWeightBlur,
		onDimInput,
		onDimBlur,
		europeCountryOptions,
		applyOriginCountrySelection,
		applyDestinationCountrySelection,
		onDestManualInput,
		onDestManualBlur,
		onOriginManualInput,
		onOriginManualBlur,
		scrollToFirstError,
		ensurePrimaryPackage,
		resetForm: _resetFormInner,
		flushLocationDraftsForSubmit,
	} = useQuoteFormInternal({
		shipmentFlowStore,
		locationSearch,
		priceBands,
		packageTypeList,
		selectPackageType,
		calcPriceWithWeight,
		calcPriceWithVolume,
		originQuery,
		originSuggestions,
		showOriginSuggestions,
		destQuery,
		destSuggestions,
		showDestSuggestions,
		settleOriginQuery,
		settleDestQuery,
		isOriginItaly,
		isDestinationItaly,
		autoQuoteTimerRef,
		sv,
	});

	// Garantisci almeno un package all'init.
	ensurePrimaryPackage();

	// =========================================================================
	// SEZIONE 3: CALC
	// =========================================================================
	const {
		messageError,
		isCalculating,
		isSyncingQuote,
		isAdvancingToServices,
		lastQuotedSignature,
		syncQuoteStateFromSession,
		calculateRate,
		resetQuoteState,
		getPendingQuotePromise,
		getPendingQuoteSignature,
	} = useQuotePricingInternal({
		shipmentFlowStore,
		apiBase,
		formRef,
		sv,
		scrollToFirstError,
		ensurePrimaryPackage,
		ensurePackagesIdentity,
		calcPriceWithWeight,
		calcPriceWithVolume,
		checkPrices,
		isOriginItaly,
		isDestinationItaly,
		isEuropeMonocollo,
		buildQuotePayloadSnapshot,
		autoQuoteTimerRef,
	});

	// =========================================================================
	// SEZIONE 4: RESULTS & NAVIGATION
	// =========================================================================
	const {
		originLocationError,
		destLocationError,
		liveQuotePrice,
		continueButtonLabel,
		quoteSubtitle,
		packageCountLabel,
		originPlaceholder,
		destinationPlaceholder,
		isStandalonePreventivoRoute,
		isHomepageLikeRoute,
		hasFormData,
		continueToNextStep,
	} = useQuoteResultsInternal({
		shipmentFlowStore,
		route,
		messageError,
		sv,
		locationSearch,
		isOriginItaly,
		isDestinationItaly,
		isEuropeMonocollo,
		isCalculating,
		isAdvancingToServices,
		lastQuotedSignature,
		quoteSignature,
		autoQuoteTimerRef,
		calculateRate,
		getPendingQuotePromise,
		getPendingQuoteSignature,
		flushLocationDraftsForSubmit,
		buildQuotePayloadSnapshot,
		syncQuoteStateFromSession,
		session,
		refresh,
		checkPrices,
		ensurePrimaryPackage,
		applyOriginCountrySelection,
		applyDestinationCountrySelection,
		resetQuoteState,
	});

	// --- RESET (collega inner reset con stato calc) ---
	const resetForm = () => {
		_resetFormInner(messageError, lastQuotedSignature);
	};

	// =========================================================================
	// PUBLIC API — deve coincidere esattamente col return object originale
	// =========================================================================
	return {
		// Refs
		formRef,
		messageError,
		isCalculating,
		isSyncingQuote,
		isAdvancingToServices,

		// Store
		shipmentFlowStore,

		// Computed
		isHomepageLikeRoute,
		isDestinationItaly,
		isOriginItaly,
		originLocationError,
		destLocationError,
		liveQuotePrice,
		continueButtonLabel,
		quoteSubtitle,
		packageCountLabel,
		originPlaceholder,
		destinationPlaceholder,
		isStandalonePreventivoRoute,
		europeCountryOptions,
		hasFormData,
		isEuropeMonocollo,
		europeRestrictionMessage,

		// Location
		originQuery,
		originSuggestions,
		showOriginSuggestions,
		destQuery,
		destSuggestions,
		showDestSuggestions,
		locationKey,
		getProvinceLabel,
		selectOriginLocation,
		selectDestLocation,
		settleOriginQuery,
		settleDestQuery,
		onOriginQueryFocus,
		onOriginQueryInput,
		onDestQueryFocus,
		onDestQueryInput,
		hideOriginSuggestions,
		hideDestSuggestions,
		onOriginManualInput,
		onOriginManualBlur,
		onDestManualInput,
		onDestManualBlur,
		applyOriginCountrySelection,
		applyDestinationCountrySelection,

		// Packages
		packageTypeList,
		addPackageInline,
		deletePack,
		updatePackageType,
		calcQuantity,
		incrementQuantity,
		decrementQuantity,

		// Validation
		sv,
		onWeightInput,
		onWeightBlur,
		onDimInput,
		onDimBlur,

		// Promo
		promoSettings,

		// Actions
		continueToNextStep,
		resetForm,
	};
};
