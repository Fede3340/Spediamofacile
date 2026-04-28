/**
 * @file useQuotePricing — sezione calcolo prezzi del preventivo rapido.
 * Estratto da useQuote.js. Chiama API tariffe, gestisce sessione,
 * dedup richieste, applica price-bands.
 */

// =============================================================================
// SEZIONE 3: CALC — API tariffe, sync sessione, de-dup richieste
// (ex usePreventivoCalc.js)
// =============================================================================

/**
 * Sub-logica calc: CSRF + API `/api/session/first-step`, sync sessione backend,
 * auto-quote con timer condiviso, de-duplicazione richieste identiche.
 * Interna all'orchestratore — non esportata pubblicamente.
 */
export const useQuotePricingInternal = (deps) => {
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
