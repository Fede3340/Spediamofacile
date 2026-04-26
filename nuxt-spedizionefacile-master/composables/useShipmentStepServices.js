import { DEFAULT_PICKUP_TIME_SLOT, normalizePickupRequestDate } from '~/composables/useShipmentStepDraftPayload';

const DEFAULT_SHIPMENT_SERVICES = [
	{
		key: 'senza_etichetta',
		img: 'no-label.png',
		width: 26,
		height: 17,
		name: 'Senza etichetta',
		description: 'Niente stampante? Il corriere pensa a tutto lui.',
		isSelected: false,
		featured: true,
	},
	{
		key: 'contrassegno',
		img: 'cash-on-delivery.png',
		width: 28,
		height: 24,
		name: 'Contrassegno',
		description: 'Incasso alla consegna.',
		priceLabel: '',
		statusLabel: 'Da configurare',
		isSelected: false,
		hasDetails: true,
	},
	{
		key: 'assicurazione',
		img: 'insurance.png',
		width: 24,
		height: 24,
		name: 'Assicurazione',
		description: 'Copertura completa.',
		priceLabel: '',
		statusLabel: 'Copertura completa',
		isSelected: false,
		hasDetails: true,
	},
	{
		key: 'sponda_idraulica',
		img: 'tail-lift.png',
		width: 24,
		height: 24,
		name: 'Sponda idraulica',
		description: 'Per colli pesanti.',
		priceLabel: '',
		statusLabel: 'Per colli pesanti',
		isSelected: false,
	},
];

const createDefaultServiceData = () => ({
	contrassegno: {
		importo: '',
		modalita_incasso: '',
		modalita_rimborso: '',
		dettaglio_rimborso: '',
	},
	assicurazione: {},
	sponda_idraulica: {
		note: '',
	},
	pickup_request: {
		enabled: false,
		date: '',
		time_slot: DEFAULT_PICKUP_TIME_SLOT,
		notes: '',
	},
	telefono_notifica: '',
});

const createMergedServiceData = (storedData = {}) => {
	const base = createDefaultServiceData();

	return {
		contrassegno: {
			...base.contrassegno,
			...(storedData.contrassegno || {}),
		},
		assicurazione: {
			...base.assicurazione,
			...(storedData.assicurazione || {}),
		},
		sponda_idraulica: {
			...base.sponda_idraulica,
			...(storedData.sponda_idraulica || {}),
		},
		pickup_request: {
			...base.pickup_request,
			...(storedData.pickup_request || {}),
		},
		telefono_notifica: storedData.telefono_notifica || '',
	};
};

const EURO_FORMATTER = new Intl.NumberFormat('it-IT', {
	style: 'currency',
	currency: 'EUR',
	minimumFractionDigits: 2,
	maximumFractionDigits: 2,
});

const formatCurrencyCents = (cents, { withPlus = false } = {}) => {
	const normalizedCents = Math.max(0, Math.round(Number(cents || 0)));
	const formatted = EURO_FORMATTER.format(normalizedCents / 100);
	return withPlus ? `+${formatted}` : formatted;
};

const formatPercentageLabel = (value) => {
	const number = Number(value || 0);
	return Number.isInteger(number) ? String(number) : number.toLocaleString('it-IT');
};

export const useShipmentStepServices = ({ shipmentFlowStore, dateError }) => {
	const pickupCalendarAnchor = useState('shipment-pickup-calendar-anchor', () => new Date().toISOString().slice(0, 10));
	const { priceBands, loadPriceBands } = usePriceBands();
	const services = ref({
		service_type: '',
		date: '',
		time: '',
	});

	const servicesList = ref(DEFAULT_SHIPMENT_SERVICES.map((service) => ({ ...service })));
	const expandedServiceKey = ref('');
	const serviceData = ref(createMergedServiceData(shipmentFlowStore?.serviceData || {}));
	const smsEmailNotification = ref(false);

	const servicePricing = computed(() => priceBands.value?.service_pricing || {});

	const featuredCurrentPriceCents = computed(() => {
		return Math.max(0, Math.round(Number(servicePricing.value?.senza_etichetta?.price_cents ?? 99)));
	});

	const featuredCurrentPriceLabel = computed(() => formatCurrencyCents(featuredCurrentPriceCents.value));

	const notificationPriceLabel = computed(() => {
		return formatCurrencyCents(servicePricing.value?.notifications?.price_cents ?? 50, { withPlus: true });
	});

	const getServicePriceLabel = (service) => {
		if (service?.key === 'contrassegno') {
			const rule = servicePricing.value?.contrassegno || {};
			return `da ${formatCurrencyCents(rule.min_fee_cents ?? 700)} + ${formatPercentageLabel(rule.percentage_rate ?? 2)}%`;
		}
		if (service?.key === 'assicurazione') {
			const rule = servicePricing.value?.assicurazione || {};
			return `da ${formatCurrencyCents(rule.min_fee_cents ?? 700)} + ${formatPercentageLabel(rule.percentage_rate ?? 2)}%`;
		}
		if (service?.key === 'sponda_idraulica') {
			return formatCurrencyCents(servicePricing.value?.sponda_idraulica?.price_cents ?? 1500, { withPlus: true });
		}
		return service.priceLabel || '';
	};

	const findServiceByKey = (serviceKey) => servicesList.value.find((service) => service.key === serviceKey) || null;

	const syncSelectedServicesVisual = () => {
		servicesList.value.forEach((service) => {
			service.isSelected = shipmentFlowStore?.servicesArray.includes(service.name);
		});
	};

	const removeService = (service) => {
		const index = shipmentFlowStore?.servicesArray.indexOf(service.name);
		if (index !== -1) {
			shipmentFlowStore?.servicesArray.splice(index, 1);
		}

		const visual = findServiceByKey(service.key);
		if (visual) {
			visual.isSelected = false;
		}

		if (expandedServiceKey.value === service.key) {
			expandedServiceKey.value = '';
		}

		services.value.service_type = shipmentFlowStore?.servicesArray.join(', ');
	};

	const ensureServiceSelected = (service, serviceIndex) => {
		const visual = servicesList.value[serviceIndex];
		if (visual) {
			visual.isSelected = true;
		}

		if (!shipmentFlowStore?.servicesArray.includes(service.name)) {
			shipmentFlowStore?.servicesArray.push(service.name);
		}

		services.value.service_type = shipmentFlowStore?.servicesArray.join(', ');
	};

	const chooseService = (service, serviceIndex) => {
		const visual = servicesList.value[serviceIndex];
		if (!visual) return;

		const isCurrentlySelected = Boolean(visual.isSelected);
		visual.isSelected = !isCurrentlySelected;

		if (!isCurrentlySelected) {
			if (!shipmentFlowStore?.servicesArray.includes(service.name)) {
				shipmentFlowStore?.servicesArray.push(service.name);
			}

			if (service.key === 'sponda_idraulica') {
				shipmentFlowStore.serviceData = shipmentFlowStore?.serviceData || {};
				shipmentFlowStore.serviceData.sponda_idraulica = { ...serviceData.value.sponda_idraulica };
			}
		} else {
			const index = shipmentFlowStore?.servicesArray.indexOf(service.name);
			if (index !== -1) {
				shipmentFlowStore?.servicesArray.splice(index, 1);
			}
			if (expandedServiceKey.value === service.key) {
				expandedServiceKey.value = '';
			}
		}

		services.value.service_type = shipmentFlowStore?.servicesArray.join(', ');
	};

	const toggleServiceDetails = (service) => {
		if (!service?.hasDetails) return;
		expandedServiceKey.value = expandedServiceKey.value === service.key ? '' : service.key;
	};

	const toggleServiceSelection = (service, serviceIndex) => {
		const visual = servicesList.value[serviceIndex];
		const isSelected = Boolean(visual?.isSelected);
		const shouldToggleDirectly = service.featured || !service.hasDetails;

		if (shouldToggleDirectly) {
			chooseService(service, serviceIndex);
			return;
		}

		if (isSelected) {
			removeService(service);
			return;
		}

		ensureServiceSelected(service, serviceIndex);
		if (!service.hasDetails) {
			expandedServiceKey.value = '';
		}
	};

	const chooseDate = (day) => {
		const nextDate = day.formattedDate || day.date.toLocaleDateString('it-IT');
		services.value.date = nextDate;
		dateError.value = null;
	};

	const daysInMonth = computed(() => {
		const result = [];
		const today = new Date(`${pickupCalendarAnchor.value}T12:00:00`);
		const year = today.getFullYear();
		const month = today.getMonth();
		const day = today.getDate() + 1;

		const appendWorkingDays = (targetYear, targetMonth, startDay, endDay) => {
			for (let index = startDay; index <= endDay; index++) {
				const date = new Date(targetYear, targetMonth, index);
				const weekdayIndex = date.getDay();
				const isWeekend = weekdayIndex === 0 || weekdayIndex === 6;
				const weekday = date.toLocaleString('it-IT', { weekday: 'short' });
				const formattedWeekday = weekday.charAt(0).toUpperCase() + weekday.slice(1);
				const monthAbbr = date.toLocaleString('it-IT', { month: 'short' });
				const formattedMonthAbbr = monthAbbr.charAt(0).toUpperCase() + monthAbbr.slice(1);
				const formattedDate = date.toLocaleDateString('it-IT');

				if (isWeekend) continue;

				result.push({
					date,
					weekday: formattedWeekday,
					dayNumber: date.getDate(),
					monthAbbr: formattedMonthAbbr,
					formattedDate,
				});
			}
		};

		appendWorkingDays(year, month, day, new Date(year, month + 1, 0).getDate());

		const nextMonth = month + 1;
		appendWorkingDays(year, nextMonth, 1, new Date(year, nextMonth + 1, 0).getDate());

		return result;
	});

	const featuredService = computed(() => {
		const service = servicesList.value.find((entry) => entry.featured);
		if (!service) return null;

		return {
			...service,
			currentPriceLabel: featuredCurrentPriceLabel.value,
		};
	});

	const regularServices = computed(() =>
		servicesList.value
			.filter((service) => !service.featured)
			.map((service) => ({
				...service,
				priceLabel: getServicePriceLabel(service),
			})),
	);

	const resetServicesState = () => {
		shipmentFlowStore.servicesArray = [];
		services.value.service_type = '';
		smsEmailNotification.value = false;
		serviceData.value = createMergedServiceData();
		shipmentFlowStore.serviceData = createMergedServiceData();
		expandedServiceKey.value = '';
		syncSelectedServicesVisual();
	};

	if (shipmentFlowStore?.pickupDate) {
		services.value.date = shipmentFlowStore?.pickupDate;
	}

	if (!services.value.time && shipmentFlowStore?.serviceData?.pickup_request?.time_slot) {
		services.value.time = shipmentFlowStore?.serviceData.pickup_request.time_slot;
	}

	if (shipmentFlowStore?.servicesArray.length > 0) {
		services.value.service_type = shipmentFlowStore?.servicesArray.join(', ');
		syncSelectedServicesVisual();
	}

	if (shipmentFlowStore?.smsEmailNotification !== undefined) {
		smsEmailNotification.value = shipmentFlowStore?.smsEmailNotification;
	}

	watch(
		daysInMonth,
		(availableDays) => {
			if (!Array.isArray(availableDays) || availableDays.length === 0) return;

			const hasSelectedDay = availableDays.some((day) => day.formattedDate === services.value.date);
			if (hasSelectedDay) return;

			chooseDate(availableDays[0]);
		},
		{ immediate: true },
	);

	onMounted(() => {
		loadPriceBands().catch(() => {
			// Warning already logged inside usePriceBands
		});
	});

	const syncPickupRequestState = () => {
		const pickupRequestDate = normalizePickupRequestDate(services.value.date || shipmentFlowStore?.pickupDate || '');
		const pickupTimeSlot =
			String(services.value.time || serviceData.value?.pickup_request?.time_slot || DEFAULT_PICKUP_TIME_SLOT).trim() ||
			DEFAULT_PICKUP_TIME_SLOT;
		const currentPickupRequest = serviceData.value?.pickup_request || {};
		const nextPickupRequest = {
			enabled: Boolean(pickupRequestDate),
			date: pickupRequestDate,
			time_slot: pickupTimeSlot,
			notes: String(currentPickupRequest.notes || '').trim(),
		};

		if (
			currentPickupRequest.enabled !== nextPickupRequest.enabled ||
			currentPickupRequest.date !== nextPickupRequest.date ||
			currentPickupRequest.time_slot !== nextPickupRequest.time_slot ||
			String(currentPickupRequest.notes || '') !== nextPickupRequest.notes
		) {
			serviceData.value.pickup_request = nextPickupRequest;
		}

		if (services.value.time !== pickupTimeSlot) {
			services.value.time = pickupTimeSlot;
		}
	};

	watch(
		() => [services.value.date, services.value.time],
		([newDate]) => {
			const selectedDate = newDate || '';
			if (shipmentFlowStore?.pickupDate !== selectedDate) {
				shipmentFlowStore.pickupDate = selectedDate;
			}

			const currentDetails = shipmentFlowStore?.shipmentDetails || {};
			if ((currentDetails.date || '') !== selectedDate) {
				shipmentFlowStore.shipmentDetails = {
					...currentDetails,
					date: selectedDate,
				};
			}

			syncPickupRequestState();
		},
		{ immediate: true },
	);

	watch(
		smsEmailNotification,
		(enabled) => {
			shipmentFlowStore.smsEmailNotification = Boolean(enabled);
		},
		{ immediate: true },
	);

	watch(
		serviceData,
		(nextValue) => {
			shipmentFlowStore.serviceData = {
				contrassegno: { ...(nextValue?.contrassegno || {}) },
				assicurazione: { ...(nextValue?.assicurazione || {}) },
				sponda_idraulica: { ...(nextValue?.sponda_idraulica || {}) },
				pickup_request: { ...(nextValue?.pickup_request || {}) },
				telefono_notifica: nextValue?.telefono_notifica || '',
			};
		},
		{ immediate: true, deep: true },
	);

	watch(
		() => [...shipmentFlowStore?.servicesArray],
		() => {
			syncSelectedServicesVisual();
			services.value.service_type = shipmentFlowStore?.servicesArray.join(', ');
			if (!expandedServiceKey.value) return;
			const expandedService = findServiceByKey(expandedServiceKey.value);
			if (!expandedService) {
				expandedServiceKey.value = '';
			}
			if (expandedService?.isSelected === false) return;
		},
		{ immediate: true },
	);

	return {
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
	};
};

// === ServiceCards ===
// Merged from useShipmentStepServiceCards.js (2026-04-20 consolidamento composables).
// Gestisce UI card servizi step 2: validazione inline contrassegno/assicurazione,
// state labels, toggle/activate/expand interactions, normalizzazione input currency.
// Exposed as a separate auto-imported composable — consumer (pages/la-tua-spedizione/[step].vue)
// lo usa distintamente da useShipmentStepServices.

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
