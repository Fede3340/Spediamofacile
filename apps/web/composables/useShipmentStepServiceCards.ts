/**
 * @file useShipmentStepServiceCards — UI service cards per step "Servizi".
 * Estratto da useShipmentStepServices.js.
 */
import { computed, ref } from 'vue';

export function useShipmentStepServiceCards({
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
}) {
	const CONFIGURABLE_SERVICE_KEYS = new Set(["contrassegno", "assicurazione"]);

	// --- ERRORS ---
	const serviceCardErrors = reactive({
		contrassegnoImporto: "",
		contrassegnoIncasso: "",
		contrassegnoRimborso: "",
		contrassegnoDettaglio: "",
		assicurazione: {},
	});

	const clearServiceCardErrors = () => {
		serviceCardErrors.contrassegnoImporto = "";
		serviceCardErrors.contrassegnoIncasso = "";
		serviceCardErrors.contrassegnoRimborso = "";
		serviceCardErrors.contrassegnoDettaglio = "";
		serviceCardErrors.assicurazione = {};
	};

	// --- CURRENCY HELPERS ---
	const normalizeCurrencyInput = (value) => {
		const sanitized = String(value || "")
			.replace(/[^\d,.\s]/g, "")
			.replace(/\s+/g, "")
			.replace(/\./g, ",");

		const [integerRaw = "", ...decimalParts] = sanitized.split(",");
		const integer = integerRaw.replace(/^0+(?=\d)/, "");
		const decimals = decimalParts.join("").slice(0, 2);

		if (!integer && !decimals) return "";
		if (!decimalParts.length) return integer || "0";

		return `${integer || "0"},${decimals}`;
	};

	const parseCurrencyValue = (value) => {
		const normalized = normalizeCurrencyInput(value);
		if (!normalized) return 0;
		return Number(normalized.replace(",", ".")) || 0;
	};

	// --- INSURANCE PACKAGES ---
	const insurancePackages = computed(() => {
		if (Array.isArray(editablePackages.value) && editablePackages.value.length > 0) {
			return editablePackages.value;
		}
		return [{ package_type: "pacco", weight: "", first_size: "", second_size: "", third_size: "" }];
	});

	// --- CONTRASSEGNO/ASSICURAZIONE OPTIONS ---
	const contrassegnoIncassoOptions = [
		{ value: "contanti", label: "Contanti" },
		{ value: "assegno", label: "Assegno" },
	];
	const contrassegnoRimborsoOptions = [
		{ value: "bonifico", label: "Bonifico" },
		{ value: "assegno", label: "Assegno" },
		{ value: "assegno_circolare", label: "Assegno circolare" },
	];

	const requiresContrassegnoDettaglio = computed(() => (
		serviceData.value.contrassegno.modalita_rimborso === "bonifico"
	));

	const clearInlineState = () => {
		clearServiceCardErrors();
		submitError.value = null;
	};

	// --- VALIDATION ---
	const validateContrassegnoInline = (force = false) => {
		clearServiceCardErrors();
		let isValid = true;

		if (!force && !isServiceSelected("contrassegno")) return true;

		if (parseCurrencyValue(serviceData.value.contrassegno.importo) <= 0) {
			serviceCardErrors.contrassegnoImporto = "Inserisci un importo valido.";
			isValid = false;
		}
		if (!serviceData.value.contrassegno.modalita_incasso) {
			serviceCardErrors.contrassegnoIncasso = "Seleziona l'incasso.";
			isValid = false;
		}
		if (!serviceData.value.contrassegno.modalita_rimborso) {
			serviceCardErrors.contrassegnoRimborso = "Seleziona il rimborso.";
			isValid = false;
		}
		if (requiresContrassegnoDettaglio.value && !String(serviceData.value.contrassegno.dettaglio_rimborso || "").trim()) {
			serviceCardErrors.contrassegnoDettaglio = "Inserisci l'IBAN.";
			isValid = false;
		}

		return isValid;
	};

	const validateAssicurazioneInline = (force = false) => {
		let isValid = true;
		if (!force && !isServiceSelected("assicurazione")) return true;

		const nextErrors = {};
		insurancePackages.value.forEach((_, index) => {
			if (parseCurrencyValue(serviceData.value.assicurazione[index]) <= 0) {
				nextErrors[index] = "Inserisci un valore.";
				isValid = false;
			}
		});

		serviceCardErrors.assicurazione = nextErrors;
		return isValid;
	};

	const validateInlineServiceDetails = () => {
		const contrassegnoValid = validateContrassegnoInline();
		if (!contrassegnoValid) {
			expandedServiceKey.value = "contrassegno";
			submitError.value = "Completa i dettagli del contrassegno.";
			return false;
		}

		const assicurazioneValid = validateAssicurazioneInline();
		if (!assicurazioneValid) {
			expandedServiceKey.value = "assicurazione";
			submitError.value = "Completa i dettagli dell'assicurazione.";
			return false;
		}

		submitError.value = null;
		return true;
	};

	// --- SERVICE STATE HELPERS ---
	const isServiceExpanded = (serviceKey) => expandedServiceKey.value === serviceKey;
	const getServiceIndex = (service) => servicesList.value.findIndex((item) => item.key === service.key);
	const isServiceSelected = (serviceKey) => {
		const service = servicesList.value.find((item) => item.key === serviceKey);
		return service ? shipmentFlowStore?.servicesArray.includes(service.name) : false;
	};
	const featuredServiceIndex = computed(() => servicesList.value.findIndex((item) => item.featured));
	const canConfigureService = (service) => CONFIGURABLE_SERVICE_KEYS.has(service?.key);

	const getServiceConfigureLabel = (service) => (
		service.isSelected ? "Modifica" : "Configura"
	);

	const focusInvalidServiceField = (serviceKey) => {
		nextTick(() => {
			const expandedCard = document.querySelector(".service-surface--expanded");
			const panel =
				document.getElementById(`service-inline-panel-${serviceKey}`) ||
				expandedCard?.querySelector(".service-panel");
			if (!panel) return;

			let focusTarget = null;
			if (serviceKey === "contrassegno") {
				const inputs = panel.querySelectorAll(".service-panel__input");
				if (serviceCardErrors.contrassegnoImporto) {
					focusTarget = inputs[0] || panel.querySelector(".service-panel__input");
				} else if (serviceCardErrors.contrassegnoDettaglio) {
					focusTarget = inputs[1] || panel.querySelector(".service-panel__input");
				} else if (serviceCardErrors.contrassegnoIncasso) {
					focusTarget = panel.querySelector('[aria-label="Modalita incasso contrassegno"] .sf-shared-segment');
				} else if (serviceCardErrors.contrassegnoRimborso) {
					focusTarget = panel.querySelector('[aria-label="Modalita accredito contrassegno"] .sf-shared-segment');
				}
			}

			if (serviceKey === "assicurazione") {
				focusTarget = panel.querySelector(".service-panel__input");
			}

			(focusTarget || panel.querySelector(".service-panel__footer .btn-primary"))?.focus?.({ preventScroll: true });
		});
	};

	// --- INTERACTIONS ---
	const activateConfiguredService = (service) => {
		if (!canConfigureService(service)) return;
		clearInlineState();

		let isValid = true;
		if (service.key === "contrassegno") {
			isValid = validateContrassegnoInline(true);
			if (!isValid) submitError.value = "Completa i dettagli del contrassegno.";
		}
		if (service.key === "assicurazione") {
			isValid = validateAssicurazioneInline(true);
			if (!isValid) submitError.value = "Completa i dettagli dell'assicurazione.";
		}

		if (!isValid) {
			expandedServiceKey.value = service.key;
			focusInvalidServiceField(service.key);
			return;
		}

		if (!service.isSelected) {
			const serviceIndex = getServiceIndex(service);
			if (serviceIndex === -1) return;
			ensureServiceSelected(service, serviceIndex);
		}
		expandedServiceKey.value = "";
	};

	const handleServicePrimaryAction = (service) => {
		if (!canConfigureService(service)) {
			toggleRegularService(service);
			return;
		}
		toggleServiceAccordion(service);
	};

	const removeConfiguredService = (service) => {
		if (!canConfigureService(service) || !service.isSelected) return;
		clearInlineState();
		removeService(service);
	};

	const toggleRegularService = (service) => {
		const serviceIndex = getServiceIndex(service);
		if (serviceIndex === -1) return;
		clearInlineState();
		toggleServiceSelection(service, serviceIndex);
	};

	const toggleServiceAccordion = (service) => {
		const serviceIndex = getServiceIndex(service);
		if (serviceIndex === -1) return;
		clearInlineState();
		toggleServiceDetails(service, serviceIndex);
	};

	const toggleFeaturedService = () => {
		if (!featuredService.value || featuredServiceIndex.value === -1) return;
		clearInlineState();
		chooseService(featuredService.value, featuredServiceIndex.value);
	};

	return {
		// errors
		serviceCardErrors,
		clearServiceCardErrors,
		// currency
		normalizeCurrencyInput,
		parseCurrencyValue,
		// options
		contrassegnoIncassoOptions,
		contrassegnoRimborsoOptions,
		requiresContrassegnoDettaglio,
		insurancePackages,
		// validation
		validateContrassegnoInline,
		validateAssicurazioneInline,
		validateInlineServiceDetails,
		// state helpers
		isServiceExpanded,
		featuredServiceIndex,
		canConfigureService,
		getServiceConfigureLabel,
		// interactions
		activateConfiguredService,
		handleServicePrimaryAction,
		removeConfiguredService,
		toggleRegularService,
		toggleServiceAccordion,
		toggleFeaturedService,
	};
}
