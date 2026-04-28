/**
 * @file useQuoteResults — sezione risultati del preventivo rapido.
 * Estratto da useQuote.js. Computed presentazione, navigazione step successivo,
 * watchers per auto-quote, label location formattate.
 */

// SEZIONE 4: RESULTS — computed display + navigazione step + watchers
// (ex usePreventivoResults.js)
// =============================================================================

/**
 * Sub-logica results: computed presentazione (label/subtitle/placeholders),
 * prezzo live, navigazione al prossimo step, watchers reattivi (auto-quote,
 * country selection, postal code changes).
 * Interna all'orchestratore — non esportata pubblicamente.
 */
export const useQuoteResultsInternal = (deps) => {
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
