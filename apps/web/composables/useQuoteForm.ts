/**
 * @file useQuoteForm — sezione form del preventivo rapido.
 * Estratto da useQuote.js. Stato form, validazione, location manuali,
 * gestione country/CAP, reset, flush drafts pre-submit.
 */

// =============================================================================
// SEZIONE 2: FORM — stato/validazione/reset/location manuali
// (ex usePreventivoForm.js)
// =============================================================================

/**
 * Sub-logica form: stato del form, validazione campi, gestione country/location,
 * input manuali, reset, flush drafts prima del submit.
 * Interna all'orchestratore — non esportata pubblicamente.
 */
export const useQuoteFormInternal = (deps) => {
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
