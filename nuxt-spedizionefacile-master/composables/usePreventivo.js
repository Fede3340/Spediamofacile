/**
 * COMPOSABLE: usePreventivo (usePreventivo.js)
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
	buildQuoteComparableSignature as buildQuoteComparableSignatureCanonical,
	clonePackageForQuote as clonePackageForQuoteCanonical,
	clonePackagesForQuote as clonePackagesForQuoteCanonical,
	cloneShipmentDetailsForQuote as cloneShipmentDetailsForQuoteCanonical,
	extractSessionComparablePayload as extractSessionComparablePayloadCanonical,
	formatResolvedLocation as formatResolvedLocationCanonical,
} from "~/utils/quickQuoteContract";
import { buildShipmentFlowLocation } from "~/utils/shipment";

// =============================================================================
// SEZIONE 1: QUOTE SNAPSHOT — helper puri + composable snapshot
// (ex usePreventivoQuoteSnapshot.js)
// =============================================================================

/** Clona i campi rilevanti di `shipmentDetails` normalizzandoli per il quote payload. */
const cloneShipmentDetailsForQuote = cloneShipmentDetailsForQuoteCanonical;

/** Clona un singolo package con i campi necessari al preventivo. */
const clonePackageForQuote = clonePackageForQuoteCanonical;

/** Clona un array di pacchi usando clonePackageForQuote. */
const clonePackagesForQuote = clonePackagesForQuoteCanonical;

/**
 * Costruisce una signature JSON-stabile da un payload preventivo,
 * usata per dedup richieste e confronto con lo stato sessione.
 */
const buildQuoteComparableSignature = buildQuoteComparableSignatureCanonical;

/** Estrae da una sessione backend i campi comparabili con il payload locale. */
const extractSessionComparablePayload = extractSessionComparablePayloadCanonical;

/** Formatta una location risolta ("Citta · CAP") per confronti UI. */
const formatResolvedLocation = formatResolvedLocationCanonical;
	/* legacy local formatter body retired in favor of quickQuoteContract.js
	const trimmedCap = String(cap || "").trim();
	if (trimmedCity && trimmedCap) return `${trimmedCity} · ${trimmedCap}`;
	return trimmedCity || trimmedCap || "";
	*/

/**
 * Composable snapshot: espone `buildQuotePayloadSnapshot` + `quoteSignature`
 * computed sullo store passato. Mantenuto esportato per retrocompat nel caso
 * qualche consumer esterno lo importi direttamente.
 */
export const usePreventivoQuoteSnapshot = (shipmentFlowStore) => {
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
 */
const usePreventivoFormInternal = (deps) => {
	const {
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
	} = deps;

	const formRef = ref(null);

	// --- VALIDAZIONE PESO / DIMENSIONI ---
	const onWeightInput = (pack, packIndex) => {
		calcPriceWithWeight(pack);
		const key = `peso_${packIndex}`;
		if (sv.isTouched(key)) {
			sv.validatePeso(key, pack.weight);
		}
	};

	const onWeightBlur = (pack, packIndex) => {
		const key = `peso_${packIndex}`;
		sv.onBlur(key, () => sv.validatePeso(key, pack.weight));
	};

	const onDimInput = (pack, packIndex, dimName, label) => {
		calcPriceWithVolume(pack);
		const key = `${dimName}_${packIndex}`;
		if (sv.isTouched(key)) {
			sv.validateDimensione(key, pack[dimName], label);
		}
	};

	const onDimBlur = (pack, packIndex, dimName, label) => {
		const key = `${dimName}_${packIndex}`;
		sv.onBlur(key, () => sv.validateDimensione(key, pack[dimName], label));
	};

	// --- EUROPE COUNTRY OPTIONS ---
	const europeCountryOptions = computed(() => {
		const countries = new Map([["IT", "Italia"]]);

		for (const band of priceBands.value?.europe?.bands || []) {
			for (const rate of band?.rates || []) {
				const code = String(rate?.country_code || "").trim().toUpperCase();
				const name = String(rate?.country_name || code).trim();
				if (code && !countries.has(code)) {
					countries.set(code, name);
				}
			}
		}

		return Array.from(countries.entries())
			.map(([code, label]) => ({ code, label }))
			.sort((a, b) => {
				if (a.code === "IT") return -1;
				if (b.code === "IT") return 1;
				return a.label.localeCompare(b.label, "it");
			});
	});

	// --- COUNTRY SELECTION ---
	const applyOriginCountrySelection = (resetFields = false) => {
		const countryCode = String(shipmentFlowStore?.shipmentDetails.origin_country_code || "IT").trim().toUpperCase() || "IT";
		const option = europeCountryOptions.value.find((entry) => entry.code === countryCode);

		shipmentFlowStore.shipmentDetails.origin_country_code = countryCode;
		shipmentFlowStore.shipmentDetails.origin_country = option?.label || countryCode;
		originSuggestions.value = [];
		showOriginSuggestions.value = false;
		locationSearch.clearLocationSearchError();
		sv.clearError("origin_cap");

		if (countryCode === "IT") {
			if (resetFields) {
				shipmentFlowStore.shipmentDetails.origin_city = "";
				shipmentFlowStore.shipmentDetails.origin_postal_code = "";
				originQuery.value = "";
			}
			return;
		}

		if (resetFields) {
			shipmentFlowStore.shipmentDetails.origin_city = "";
			shipmentFlowStore.shipmentDetails.origin_postal_code = "";
			originQuery.value = "";
			return;
		}

		shipmentFlowStore.shipmentDetails.origin_postal_code = "";
		originQuery.value = String(shipmentFlowStore?.shipmentDetails.origin_city || originQuery.value || "").trim();
	};

	const applyDestinationCountrySelection = (resetFields = false) => {
		const countryCode = String(shipmentFlowStore?.shipmentDetails.destination_country_code || "IT").trim().toUpperCase() || "IT";
		const option = europeCountryOptions.value.find((entry) => entry.code === countryCode);

		shipmentFlowStore.shipmentDetails.destination_country_code = countryCode;
		shipmentFlowStore.shipmentDetails.destination_country = option?.label || countryCode;
		destSuggestions.value = [];
		showDestSuggestions.value = false;
		locationSearch.clearLocationSearchError();
		sv.clearError("dest_cap");

		if (countryCode === "IT") {
			if (resetFields) {
				shipmentFlowStore.shipmentDetails.destination_city = "";
				shipmentFlowStore.shipmentDetails.destination_postal_code = "";
				destQuery.value = "";
			}
			return;
		}

		if (resetFields) {
			shipmentFlowStore.shipmentDetails.destination_city = "";
			shipmentFlowStore.shipmentDetails.destination_postal_code = "";
			destQuery.value = "";
			return;
		}

		shipmentFlowStore.shipmentDetails.destination_postal_code = "";
		destQuery.value = String(shipmentFlowStore?.shipmentDetails.destination_city || destQuery.value || "").trim();
	};

	// --- MANUAL LOCATION INPUT (paesi non-IT) ---
	const onDestManualInput = () => {
		const value = String(destQuery.value || "").trimStart();
		locationSearch.clearLocationSearchError();
		shipmentFlowStore.shipmentDetails.destination_city = value;
		shipmentFlowStore.shipmentDetails.destination_postal_code = "";
		destSuggestions.value = [];
		showDestSuggestions.value = false;
		sv.clearError("dest_cap");
	};

	const onDestManualBlur = () => {
		const value = String(destQuery.value || "").trim();
		locationSearch.clearLocationSearchError();
		destQuery.value = value;
		shipmentFlowStore.shipmentDetails.destination_city = value;
		shipmentFlowStore.shipmentDetails.destination_postal_code = "";
		destSuggestions.value = [];
		showDestSuggestions.value = false;
	};

	const onOriginManualInput = () => {
		const value = String(originQuery.value || "").trimStart();
		locationSearch.clearLocationSearchError();
		shipmentFlowStore.shipmentDetails.origin_city = value;
		shipmentFlowStore.shipmentDetails.origin_postal_code = "";
		originSuggestions.value = [];
		showOriginSuggestions.value = false;
		sv.clearError("origin_cap");
	};

	const onOriginManualBlur = () => {
		const value = String(originQuery.value || "").trim();
		locationSearch.clearLocationSearchError();
		originQuery.value = value;
		shipmentFlowStore.shipmentDetails.origin_city = value;
		shipmentFlowStore.shipmentDetails.origin_postal_code = "";
		originSuggestions.value = [];
		showOriginSuggestions.value = false;
	};

	// --- SCROLL TO FIRST ERROR ---
	const scrollToFirstError = () => {
		nextTick(() => {
			const invalidField = formRef.value?.querySelector(':invalid');
			if (invalidField) {
				invalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
				setTimeout(() => invalidField.focus?.(), 120);
				return;
			}

			const requiredField = formRef.value?.querySelector('input[required], select[required], textarea[required]');
			if (requiredField && !requiredField.value) {
				requiredField.scrollIntoView({ behavior: 'smooth', block: 'center' });
				setTimeout(() => requiredField.focus?.(), 120);
				return;
			}

			const errorEl = document.querySelector('.route-card__error, .package-field-card__error, .preventivo-inline-error');
			if (errorEl) {
				errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		});
	};

	// --- ENSURE PRIMARY PACKAGE ---
	const ensurePrimaryPackage = () => {
		if (shipmentFlowStore?.packages.length > 0) return;
		selectPackageType(packageTypeList[0]);
	};

	// --- RESET FORM ---
	// Riceve `messageError` e `lastQuotedSignature` dalla sezione Calc.
	const resetForm = (messageError, lastQuotedSignature) => {
		const timer = autoQuoteTimerRef();
		if (timer) {
			clearTimeout(timer);
			autoQuoteTimerRef(null);
		}
		shipmentFlowStore?.packages.splice(0);
		shipmentFlowStore.shipmentDetails.origin_city = "";
		shipmentFlowStore.shipmentDetails.origin_postal_code = "";
		shipmentFlowStore.shipmentDetails.origin_country_code = "IT";
		shipmentFlowStore.shipmentDetails.origin_country = "Italia";
		shipmentFlowStore.shipmentDetails.destination_city = "";
		shipmentFlowStore.shipmentDetails.destination_postal_code = "";
		shipmentFlowStore.shipmentDetails.destination_country_code = "IT";
		shipmentFlowStore.shipmentDetails.destination_country = "Italia";
		shipmentFlowStore.shipmentDetails.date = "";
		shipmentFlowStore.totalPrice = 0;
		shipmentFlowStore.stepNumber = 1;
		shipmentFlowStore.isQuoteStarted = false;
		messageError.value = null;
		locationSearch.clearLocationSearchError();
		lastQuotedSignature.value = "";
		ensurePrimaryPackage();
	};

	// --- FLUSH DRAFTS (pre-submit) ---
	// Forza la risoluzione della city/cap attualmente in input prima del submit.
	const flushLocationDraftsForSubmit = async (formatResolvedLocationFn) => {
		const timer = autoQuoteTimerRef();
		if (timer) {
			clearTimeout(timer);
			autoQuoteTimerRef(null);
		}

		const originDraft = String(originQuery.value || "").trim();
		const destinationDraft = String(destQuery.value || "").trim();
		const resolvedOrigin = formatResolvedLocationFn(
			shipmentFlowStore?.shipmentDetails.origin_city,
			shipmentFlowStore?.shipmentDetails.origin_postal_code,
		);
		const resolvedDestination = formatResolvedLocationFn(
			shipmentFlowStore?.shipmentDetails.destination_city,
			shipmentFlowStore?.shipmentDetails.destination_postal_code,
		);
		const activeFieldId = import.meta.client ? document?.activeElement?.id : "";
		if (activeFieldId === "origin_city" || (originDraft && originDraft !== resolvedOrigin)) {
			if (isOriginItaly.value) {
				await settleOriginQuery();
			} else {
				onOriginManualBlur();
			}
		}

		if (activeFieldId === "destination_city" || (destinationDraft && destinationDraft !== resolvedDestination)) {
			if (isDestinationItaly.value) {
				await settleDestQuery();
			} else {
				onDestManualBlur();
			}
		}

		await nextTick();
	};

	return {
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
		resetForm,
		flushLocationDraftsForSubmit,
	};
};

// =============================================================================
// SEZIONE 3: CALC — API tariffe, sync sessione, de-dup richieste
// (ex usePreventivoCalc.js)
// =============================================================================

/**
 * Sub-logica calc: CSRF + API `/api/session/first-step`, sync sessione backend,
 * auto-quote con timer condiviso, de-duplicazione richieste identiche.
 * Interna all'orchestratore — non esportata pubblicamente.
 */
const usePreventivoCalcInternal = (deps) => {
	const {
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
	} = deps;

	// --- STATO CALCOLO ---
	const messageError = ref(null);
	const isCalculating = ref(false);
	const isSyncingQuote = ref(false);
	const isAdvancingToServices = ref(false);
	const lastQuotedSignature = ref("");

	// --- STATO RICHIESTE PENDENTI (de-dup) ---
	let pendingQuotePromise = null;
	let pendingQuoteSignature = "";
	let pendingQuoteSilent = false;
	let pendingQuoteRequestId = 0;
	let latestQuoteRequestId = 0;

	// --- API HELPERS ---
	const publicApiFetch = async (path, options = {}) => {
		const url = path.startsWith("http") ? path : `${apiBase}${path}`;
		return await $fetch(url, {
			credentials: "include",
			...options,
		});
	};

	const readXsrfToken = () => {
		if (import.meta.server) return "";
		const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
		return match?.[1] ? decodeURIComponent(match[1]) : "";
	};

	const requestClient = async (path, options = {}) => {
		const method = String(options?.method || "GET").trim().toUpperCase();
		const token = readXsrfToken();
		const headers = {
			Accept: "application/json",
			...(options?.headers || {}),
		};

		if (token && !["GET", "HEAD", "OPTIONS"].includes(method)) {
			headers["X-XSRF-TOKEN"] = token;
		}

		return await publicApiFetch(path, {
			...options,
			method,
			headers,
		});
	};

	// --- SESSION SYNC ---
	const syncQuoteStateFromSession = (sessionData = {}, options = {}) => {
		const sourceSignature = String(options?.sourceSignature || "");
		const sessionSignature = buildQuoteComparableSignature(extractSessionComparablePayload(sessionData));

		if (sourceSignature) {
			if (sourceSignature !== sessionSignature) {
				return;
			}

			shipmentFlowStore.totalPrice = Number(sessionData?.total_price || shipmentFlowStore?.totalPrice || 0);
			shipmentFlowStore.stepNumber = Number(sessionData?.step || 2);
			shipmentFlowStore.isQuoteStarted = true;
			ensurePackagesIdentity();
			ensurePrimaryPackage();
			return;
		}

		const shipmentDetails = sessionData?.shipment_details || {};
		for (const [key, value] of Object.entries(shipmentDetails)) {
			if (key in shipmentFlowStore?.shipmentDetails) {
				shipmentFlowStore.shipmentDetails[key] = value ?? "";
			}
		}

		const packages = Array.isArray(sessionData?.packages)
			? sessionData.packages.map((pack) => ({ ...pack }))
			: null;

		if (packages) {
			shipmentFlowStore?.packages.splice(0, shipmentFlowStore?.packages.length, ...packages);
			ensurePackagesIdentity();
		}

		shipmentFlowStore.totalPrice = Number(sessionData?.total_price || shipmentFlowStore?.totalPrice || 0);
		shipmentFlowStore.stepNumber = Number(sessionData?.step || 2);
		shipmentFlowStore.isQuoteStarted = true;
		ensurePrimaryPackage();
	};

	// --- CALCOLO TARIFFA ---
	const calculateRate = async ({ silent = false, payload = null } = {}) => {
		if (silent && isAdvancingToServices.value) {
			return false;
		}

		if (!silent) {
			messageError.value = null;
		}

		const quotePayload = payload || buildQuotePayloadSnapshot();
		const currentSignature = buildQuoteComparableSignature(quotePayload);
		if (
			pendingQuotePromise
			&& pendingQuoteSignature === currentSignature
			&& pendingQuoteSilent === silent
		) {
			return pendingQuotePromise;
		}

		const requestId = ++latestQuoteRequestId;

		if (!formRef.value || !formRef.value.checkValidity()) {
			if (!silent) {
				formRef.value?.reportValidity();
			}
			return false;
		}

		if (
			!String(shipmentFlowStore?.shipmentDetails.origin_city || "").trim()
			|| (isOriginItaly.value && !String(shipmentFlowStore?.shipmentDetails.origin_postal_code || "").trim())
			|| !String(shipmentFlowStore?.shipmentDetails.destination_city || "").trim()
			|| (isDestinationItaly.value && !String(shipmentFlowStore?.shipmentDetails.destination_postal_code || "").trim())
		) {
			if (!silent) {
				messageError.value = {
					...(!String(shipmentFlowStore?.shipmentDetails.origin_city || "").trim()
						|| (isOriginItaly.value && !String(shipmentFlowStore?.shipmentDetails.origin_postal_code || "").trim())
						? { origin_query: [isOriginItaly.value
							? "Seleziona una località valida per la partenza."
							: "Inserisci almeno la città di partenza per il paese selezionato."] }
						: {}),
					...(!String(shipmentFlowStore?.shipmentDetails.destination_city || "").trim()
						|| (isDestinationItaly.value && !String(shipmentFlowStore?.shipmentDetails.destination_postal_code || "").trim())
						? { dest_query: [isDestinationItaly.value
							? "Seleziona una località valida per la destinazione."
							: "Inserisci almeno la città di destinazione per il paese selezionato."] }
						: {}),
				};
				scrollToFirstError();
			}
			return false;
		}

		if (isEuropeMonocollo.value) {
			if (shipmentFlowStore?.packages.length !== 1) {
				if (!silent) {
					messageError.value = { packages: ["Per l'Europa e disponibile un solo collo per spedizione."] };
					scrollToFirstError();
				}
				return false;
			}

			if ((Number(shipmentFlowStore?.packages[0]?.quantity) || 1) !== 1) {
				if (!silent) {
					messageError.value = { packages: ["Per l'Europa la quantita deve essere 1."] };
					scrollToFirstError();
				}
				return false;
			}
		}

		if (!shipmentFlowStore?.packages || shipmentFlowStore.packages.length === 0) {
			if (!silent) {
				messageError.value = { packages: ["Seleziona almeno un tipo di collo."] };
				scrollToFirstError();
			}
			return false;
		}

		for (let i = 0; i < shipmentFlowStore?.packages.length; i++) {
			const pack = shipmentFlowStore?.packages[i];
			if (!pack.weight || !pack.first_size || !pack.second_size || !pack.third_size) {
				if (!silent) {
					messageError.value = { packages: ["Compila peso e dimensioni per tutti i colli."] };
					scrollToFirstError();
				}
				return false;
			}

			if (pack.weight_price == null) {
				calcPriceWithWeight(pack);
			}
			if (pack.volume_price == null && pack.first_size && pack.second_size && pack.third_size) {
				calcPriceWithVolume(pack);
			}
			if (pack.single_price == null || pack.single_price === undefined) {
				checkPrices(pack);
			}
			if (pack.single_price == null || pack.single_price === undefined) {
				if (!silent) {
					messageError.value = { packages: ["Errore nel calcolo del prezzo. Reinserisci peso e dimensioni."] };
				}
				return false;
			}
		}

		const runRequest = async () => {
			if (silent) {
				isSyncingQuote.value = true;
			} else {
				isCalculating.value = true;
			}
			try {
				await requestClient("/sanctum/csrf-cookie");
				const response = await requestClient("/api/session/first-step", {
					method: "POST",
					body: quotePayload,
				});
				if (requestId !== latestQuoteRequestId) {
					return false;
				}
				syncQuoteStateFromSession(response?.data || response, { sourceSignature: currentSignature });
				lastQuotedSignature.value = currentSignature;
				if (!silent) {
					messageError.value = null;
				}
				return true;
			} catch (error) {
				if (!silent) {
					messageError.value = error?.data?.errors || { packages: ["Errore durante il calcolo. Riprova."] };
					scrollToFirstError();
				}
				return false;
			} finally {
				if (silent) {
					isSyncingQuote.value = false;
				} else {
					isCalculating.value = false;
				}
				if (pendingQuoteRequestId === requestId) {
					pendingQuoteSignature = "";
					pendingQuoteSilent = false;
					pendingQuoteRequestId = 0;
					pendingQuotePromise = null;
				}
			}
		};

		pendingQuoteSignature = currentSignature;
		pendingQuoteSilent = silent;
		pendingQuoteRequestId = requestId;
		pendingQuotePromise = runRequest();
		return pendingQuotePromise;
	};

	// --- RESET QUOTE STATE (usato dai watcher) ---
	const resetQuoteState = () => {
		if (isAdvancingToServices.value) return;
		messageError.value = null;
		const timer = autoQuoteTimerRef();
		if (timer) {
			clearTimeout(timer);
			autoQuoteTimerRef(null);
		}
	};

	return {
		messageError,
		isCalculating,
		isSyncingQuote,
		isAdvancingToServices,
		lastQuotedSignature,
		publicApiFetch,
		requestClient,
		syncQuoteStateFromSession,
		calculateRate,
		resetQuoteState,
		// Expose pending state for continueToNextStep
		getPendingQuotePromise: () => pendingQuotePromise,
		getPendingQuoteSignature: () => pendingQuoteSignature,
	};
};

// =============================================================================
// SEZIONE 4: RESULTS — computed display + navigazione step + watchers
// (ex usePreventivoResults.js)
// =============================================================================

/**
 * Sub-logica results: computed presentazione (label/subtitle/placeholders),
 * prezzo live, navigazione al prossimo step, watchers reattivi (auto-quote,
 * country selection, postal code changes).
 * Interna all'orchestratore — non esportata pubblicamente.
 */
const usePreventivoResultsInternal = (deps) => {
	const {
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
	} = deps;

	const quoteTransitionLock = useState('shipment-flow-quote-transition-lock', () => false);

	// --- COMPUTED: ERRORI LOCATION ---
	const originLocationError = computed(() =>
		messageError.value?.origin_query?.[0]
			|| sv.getError("origin_cap")
			|| messageError.value?.["shipment_details.origin_city"]?.[0]
			|| messageError.value?.["shipment_details.origin_postal_code"]?.[0]
			|| locationSearch.locationSearchError.value
			|| "",
	);

	const destLocationError = computed(() =>
		messageError.value?.dest_query?.[0]
			|| sv.getError("dest_cap")
			|| messageError.value?.["shipment_details.destination_city"]?.[0]
			|| messageError.value?.["shipment_details.destination_postal_code"]?.[0]
			|| locationSearch.locationSearchError.value
			|| "",
	);

	// --- COMPUTED: READINESS ---
	const hasResolvedLocations = computed(() => (
		!!String(shipmentFlowStore?.shipmentDetails.origin_city || "").trim()
		&& (
			!isOriginItaly.value
			|| !!String(shipmentFlowStore?.shipmentDetails.origin_postal_code || "").trim()
		)
		&& !!String(shipmentFlowStore?.shipmentDetails.destination_city || "").trim()
		&& (
			!isDestinationItaly.value
			|| !!String(shipmentFlowStore?.shipmentDetails.destination_postal_code || "").trim()
		)
	));

	const hasCompletePackages = computed(() => (
		Array.isArray(shipmentFlowStore?.packages)
		&& shipmentFlowStore?.packages.length > 0
		&& shipmentFlowStore?.packages.every((pack) => (
			!!String(pack?.weight || "").trim()
			&& !!String(pack?.first_size || "").trim()
			&& !!String(pack?.second_size || "").trim()
			&& !!String(pack?.third_size || "").trim()
			&& Number(pack?.single_price) > 0
		))
	));

	const quoteReadyForRealtime = computed(() => (
		hasResolvedLocations.value
		&& hasCompletePackages.value
	));

	// --- COMPUTED: DISPLAY ---
	const formatLivePrice = (amount) => (
		new Intl.NumberFormat("it-IT", {
			style: "currency",
			currency: "EUR",
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		}).format(Number(amount) || 0).replace(/\s/g, "")
	);

	const liveQuotePrice = computed(() => (
		quoteReadyForRealtime.value && Number(shipmentFlowStore?.totalPrice) > 0
			? formatLivePrice(shipmentFlowStore?.totalPrice)
			: ""
	));

	const continueButtonLabel = computed(() => (
		liveQuotePrice.value
			? "Calcola e scegli servizio"
			: "Calcola il prezzo"
	));

	const preventivoSubtitle = computed(() => (
		isEuropeMonocollo.value
			? "Europa monocollo · Ritiro a domicilio"
			: "Prezzo immediato · IVA e ritiro inclusi"
	));

	const packageCountLabel = computed(() => shipmentFlowStore?.packages.length || 0);

	const originPlaceholder = computed(() => (
		isOriginItaly.value ? "Es. Comune o CAP (Roma / 00118)" : "Citta di partenza"
	));

	const destinationPlaceholder = computed(() => (
		isDestinationItaly.value ? "Es. Comune o CAP (Milano / 20121)" : "Citta di destinazione"
	));

	const isStandalonePreventivoRoute = computed(() => route.path === '/preventivo');

	const isHomepageLikeRoute = computed(() => route.path === '/' || route.path === '/preview/home-hero');

	const hasFormData = computed(() => {
		const sd = shipmentFlowStore?.shipmentDetails;
		return shipmentFlowStore?.packages.length > 0 || sd.origin_city || sd.origin_postal_code || sd.destination_city || sd.destination_postal_code;
	});

	// --- NAVIGAZIONE STEP ---
	const continueToNextStep = async () => {
		if (isCalculating.value || isAdvancingToServices.value) return;

		messageError.value = null;
		isAdvancingToServices.value = true;
		quoteTransitionLock.value = true;
		const timer = autoQuoteTimerRef();
		if (timer) {
			clearTimeout(timer);
			autoQuoteTimerRef(null);
		}
		const unlockTimer = setTimeout(() => {
			quoteTransitionLock.value = false;
		}, 8000);
		try {
			await flushLocationDraftsForSubmit(formatResolvedLocation);
			const payloadSnapshot = buildQuotePayloadSnapshot();
			const payloadSignature = buildQuoteComparableSignature(payloadSnapshot);
			const pendingPromise = getPendingQuotePromise();
			const pendingSig = getPendingQuoteSignature();
			const hasPendingSameQuote = Boolean(
				pendingPromise
				&& pendingSig === payloadSignature
			);
			let isValid = false;

			if (hasPendingSameQuote) {
				isValid = await pendingPromise;
				if (!isValid) {
					isValid = await calculateRate({ silent: false, payload: payloadSnapshot });
				}
			} else {
				isValid = await calculateRate({ silent: false, payload: payloadSnapshot });
			}

			if (!isValid) return;

			const refreshedSession = await refresh().catch(() => session.value);
			const refreshedData = refreshedSession?.data || refreshedSession || null;

			if (refreshedData) {
				syncQuoteStateFromSession(refreshedData, { sourceSignature: payloadSignature });
			} else {
				syncQuoteStateFromSession(payloadSnapshot, { sourceSignature: payloadSignature });
			}

			lastQuotedSignature.value = payloadSignature;
			await nextTick();
			await navigateTo(buildShipmentFlowLocation({}, 'servizi'), { replace: true });
			shipmentFlowStore.stepNumber = 2;
			shipmentFlowStore.isQuoteStarted = true;
		} finally {
			clearTimeout(unlockTimer);
			await nextTick();
			quoteTransitionLock.value = false;
			isAdvancingToServices.value = false;
		}
	};

	// --- WATCHERS ---
	// Reset dello stato quote a ogni modifica packages/details (evita stale).
	watch(
		() => shipmentFlowStore?.packages,
		resetQuoteState,
		{ deep: true },
	);

	watch(
		() => shipmentFlowStore?.shipmentDetails,
		resetQuoteState,
		{ deep: true },
	);

	// Sync country selection -> fields (immediate per init-time).
	watch(
		() => shipmentFlowStore?.shipmentDetails.destination_country_code,
		() => {
			applyDestinationCountrySelection(false);
		},
		{ immediate: true },
	);

	watch(
		() => shipmentFlowStore?.shipmentDetails.origin_country_code,
		() => {
			applyOriginCountrySelection(false);
		},
		{ immediate: true },
	);

	// Cambio CAP -> rical prezzi per ogni package gia' con weight/volume price.
	watch(
		() => [shipmentFlowStore?.shipmentDetails.origin_postal_code, shipmentFlowStore?.shipmentDetails.destination_postal_code],
		() => {
			for (const pack of shipmentFlowStore?.packages) {
				if (pack.weight_price != null || pack.volume_price != null) {
					checkPrices(pack);
				}
			}
		},
	);

	// Auto-quote debounced (450ms): quando signature cambia e tutti i campi ready.
	watch(
		() => [quoteSignature.value, quoteReadyForRealtime.value],
		() => {
			let currentTimer = autoQuoteTimerRef();
			if (currentTimer) {
				clearTimeout(currentTimer);
				autoQuoteTimerRef(null);
			}

			if (isAdvancingToServices.value || isCalculating.value) return;
			if (!quoteReadyForRealtime.value) return;
			if (lastQuotedSignature.value === quoteSignature.value) return;

			const newTimer = setTimeout(() => {
				calculateRate({ silent: true }).catch(() => null);
			}, 450);
			autoQuoteTimerRef(newTimer);
		},
		{ flush: "post" },
	);

	// Garantisci sempre almeno un package.
	watch(
		() => shipmentFlowStore?.packages.length,
		(length) => {
			if (length === 0) {
				ensurePrimaryPackage();
			}
		},
	);

	return {
		originLocationError,
		destLocationError,
		liveQuotePrice,
		continueButtonLabel,
		preventivoSubtitle,
		packageCountLabel,
		originPlaceholder,
		destinationPlaceholder,
		isStandalonePreventivoRoute,
		isHomepageLikeRoute,
		hasFormData,
		continueToNextStep,
	};
};

// =============================================================================
// API PUBBLICA: usePreventivo()
// =============================================================================

/**
 * usePreventivo — orchestratore del form Preventivo Rapido.
 * Compone: Form + Calc + Results + QuoteSnapshot.
 * Consumer: components/Preventivo.vue.
 *
 * Return signature IDENTICA alla vecchia implementazione (orchestrava 4 sub).
 */
export const usePreventivo = () => {
	// --- DIPENDENZE GLOBALI ---
	const shipmentFlowStore = useShipmentFlowStore();
	const route = useRoute();
	const runtimeConfig = useRuntimeConfig();
	const apiBase = String(runtimeConfig.public?.apiBase || "http://127.0.0.1:8787").replace(/\/$/, "");

	// --- AUTO-QUOTE TIMER (shared mutable state across sections) ---
	// Wrapper a oggetto per permettere alle sezioni di leggere/scrivere il timer.
	const _timerBox = { value: null };
	const autoQuoteTimerRef = (...args) => {
		if (args.length === 0) return _timerBox.value;
		_timerBox.value = args[0];
		return _timerBox.value;
	};

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
	const publicApiFetchForLocation = async (path, options = {}) => {
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
		recalculatePackagesTotal,
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
	} = usePreventivoQuoteSnapshot(shipmentFlowStore);

	// --- SMART VALIDATION (istanza condivisa tra le sezioni) ---
	const sv = useSmartValidation();
	const onCapInputSmartForLocations = (fieldKey, value, countryCode = "IT") => {
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
	} = usePreventivoFormInternal({
		shipmentFlowStore,
		locationSearch,
		priceBands,
		packageTypeList,
		selectPackageType,
		ensurePackagesIdentity,
		checkPrices,
		calcPriceWithWeight,
		calcPriceWithVolume,
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
	} = usePreventivoCalcInternal({
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
		preventivoSubtitle,
		packageCountLabel,
		originPlaceholder,
		destinationPlaceholder,
		isStandalonePreventivoRoute,
		isHomepageLikeRoute,
		hasFormData,
		continueToNextStep,
	} = usePreventivoResultsInternal({
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
		preventivoSubtitle,
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
