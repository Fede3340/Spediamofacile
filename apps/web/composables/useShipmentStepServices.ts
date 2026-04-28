/**
 * @file useShipmentStepServices — orchestratore step "Servizi" del funnel.
 * Helpers in utils/shipmentServiceData.js. Cards in useShipmentStepServiceCards.
 */
import { ref, computed, watch } from 'vue';
import {
  DEFAULT_SHIPMENT_SERVICES,
  createDefaultServiceData,
  createMergedServiceData,
  formatCurrencyCents,
  formatPercentageLabel,
} from '~/utils/shipmentServiceData';

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
