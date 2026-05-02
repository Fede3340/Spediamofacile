/**
 * @file useShipmentStepServices — orchestratore step "Servizi" del funnel.
 * Helpers in utils/shipmentServiceData.js. Cards in useShipmentStepServiceCards.
 */
import { ref, computed, watch, type Ref } from 'vue';
import type { PricingRuleGroup } from '~/types/pricing';
import {
	DEFAULT_SHIPMENT_SERVICES,
	createMergedServiceData,
	formatCurrencyCents,
	formatPercentageLabel,
} from '~/utils/shipmentServiceData';

type ShipmentService = (typeof DEFAULT_SHIPMENT_SERVICES)[number] & {
	currentPriceLabel?: string;
	priceLabel?: string;
	isSelected?: boolean;
};

type PickupRequestData = { enabled?: boolean; date?: string; time_slot?: string; notes?: string };

type ShipmentServiceData = {
	contrassegno: Record<string, unknown>;
	assicurazione: Record<string, unknown>;
	sponda_idraulica: Record<string, unknown>;
	pickup_request: PickupRequestData;
	telefono_notifica?: string;
};

type ShipmentStepServicesStore = {
	serviceData: ShipmentServiceData;
	servicesArray: string[];
	pickupDate?: string;
	smsEmailNotification?: boolean;
	shipmentDetails: Record<string, unknown>;
};

type PickupDay = { date: Date; weekday: string; dayNumber: number; monthAbbr: string; formattedDate: string };

type UseShipmentStepServicesArgs = { shipmentFlowStore: ShipmentStepServicesStore; dateError: Ref<string | null> };

export const useShipmentStepServices = ({ shipmentFlowStore, dateError }: UseShipmentStepServicesArgs) => {
	const pickupCalendarAnchor = useState('shipment-pickup-calendar-anchor', () => new Date().toISOString().slice(0, 10));
	const { priceBands, loadPriceBands } = usePriceBands();
	const services = ref({ service_type: '', date: '', time: '' });

	const servicesList = ref<ShipmentService[]>(DEFAULT_SHIPMENT_SERVICES.map((service) => ({ ...service })));
	const expandedServiceKey = ref('');
	const serviceData = ref<ShipmentServiceData>(createMergedServiceData(shipmentFlowStore.serviceData || {}) as ShipmentServiceData);
	const smsEmailNotification = ref(false);

	const servicePricing = computed<PricingRuleGroup>(() => priceBands.value?.service_pricing || {});
	const featuredCurrentPriceCents = computed(() => Math.max(0, Math.round(Number(servicePricing.value?.senza_etichetta?.price_cents ?? 99))));
	const featuredCurrentPriceLabel = computed(() => formatCurrencyCents(featuredCurrentPriceCents.value));
	const notificationPriceLabel = computed(() => formatCurrencyCents(servicePricing.value?.notifications?.price_cents ?? 50, { withPlus: true }));

	const percentRuleLabel = (key: 'contrassegno' | 'assicurazione') => {
		const rule = servicePricing.value[key];
		return `da ${formatCurrencyCents(rule?.min_fee_cents ?? 700)} + ${formatPercentageLabel(rule?.percentage_rate ?? 2)}%`;
	};
	const getServicePriceLabel = (service: ShipmentService) => {
		if (service?.key === 'contrassegno' || service?.key === 'assicurazione') return percentRuleLabel(service.key);
		if (service?.key === 'sponda_idraulica') return formatCurrencyCents(servicePricing.value?.sponda_idraulica?.price_cents ?? 1500, { withPlus: true });
		return service.priceLabel || '';
	};

	const findServiceByKey = (serviceKey: string) => servicesList.value.find((service) => service.key === serviceKey) || null;
	const syncSelectedServicesVisual = () => {
		servicesList.value.forEach((service) => { service.isSelected = shipmentFlowStore?.servicesArray.includes(service.name); });
	};
	const syncServiceTypeString = () => { services.value.service_type = shipmentFlowStore?.servicesArray.join(', '); };

	const addToSelection = (service: ShipmentService, visual?: ShipmentService | null) => {
		if (visual) visual.isSelected = true;
		if (!shipmentFlowStore?.servicesArray.includes(service.name)) shipmentFlowStore?.servicesArray.push(service.name);
	};
	const removeFromSelection = (service: ShipmentService, visual?: ShipmentService | null) => {
		if (visual) visual.isSelected = false;
		const index = shipmentFlowStore?.servicesArray.indexOf(service.name);
		if (index !== -1) shipmentFlowStore?.servicesArray.splice(index, 1);
		if (expandedServiceKey.value === service.key) expandedServiceKey.value = '';
	};

	const removeService = (service: ShipmentService) => {
		removeFromSelection(service, findServiceByKey(service.key));
		syncServiceTypeString();
	};

	const ensureServiceSelected = (service: ShipmentService, serviceIndex: number) => {
		addToSelection(service, servicesList.value[serviceIndex]);
		syncServiceTypeString();
	};

	const chooseService = (service: ShipmentService, serviceIndex: number) => {
		const visual = servicesList.value[serviceIndex];
		if (!visual) return;
		const isCurrentlySelected = Boolean(visual.isSelected);
		visual.isSelected = !isCurrentlySelected;

		if (!isCurrentlySelected) {
			addToSelection(service, null);
			if (service.key === 'sponda_idraulica') {
				shipmentFlowStore.serviceData = shipmentFlowStore?.serviceData || {};
				shipmentFlowStore.serviceData.sponda_idraulica = { ...serviceData.value.sponda_idraulica };
			}
		} else {
			removeFromSelection(service, null);
		}
		syncServiceTypeString();
	};

	const toggleServiceDetails = (service: ShipmentService) => {
		if (!service?.hasDetails) return;
		expandedServiceKey.value = expandedServiceKey.value === service.key ? '' : service.key;
	};

	const toggleServiceSelection = (service: ShipmentService, serviceIndex: number) => {
		const visual = servicesList.value[serviceIndex];
		const isSelected = Boolean(visual?.isSelected);
		const shouldToggleDirectly = service.featured || !service.hasDetails;
		if (shouldToggleDirectly) return chooseService(service, serviceIndex);
		if (isSelected) return removeService(service);
		ensureServiceSelected(service, serviceIndex);
		if (!service.hasDetails) expandedServiceKey.value = '';
	};

	const chooseDate = (day: PickupDay) => {
		services.value.date = day.formattedDate || day.date.toLocaleDateString('it-IT');
		dateError.value = null;
	};

	const daysInMonth = computed(() => {
		const result: PickupDay[] = [];
		const today = new Date(`${pickupCalendarAnchor.value}T12:00:00`);
		const year = today.getFullYear();
		const month = today.getMonth();
		const startDay = today.getDate() + 1;

		const appendWorkingDays = (targetYear: number, targetMonth: number, from: number, to: number) => {
			for (let index = from; index <= to; index++) {
				const date = new Date(targetYear, targetMonth, index);
				const weekdayIndex = date.getDay();
				if (weekdayIndex === 0 || weekdayIndex === 6) continue;
				const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);
				result.push({
					date,
					weekday: cap(date.toLocaleString('it-IT', { weekday: 'short' })),
					dayNumber: date.getDate(),
					monthAbbr: cap(date.toLocaleString('it-IT', { month: 'short' })),
					formattedDate: date.toLocaleDateString('it-IT'),
				});
			}
		};

		appendWorkingDays(year, month, startDay, new Date(year, month + 1, 0).getDate());
		appendWorkingDays(year, month + 1, 1, new Date(year, month + 2, 0).getDate());
		return result;
	});

	const featuredService = computed(() => {
		const service = servicesList.value.find((entry) => entry.featured);
		return service ? { ...service, currentPriceLabel: featuredCurrentPriceLabel.value } : null;
	});

	const regularServices = computed(() =>
		servicesList.value.filter((service) => !service.featured).map((service) => ({ ...service, priceLabel: getServicePriceLabel(service) })),
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

	if (shipmentFlowStore?.pickupDate) services.value.date = shipmentFlowStore.pickupDate;
	if (!services.value.time && shipmentFlowStore?.serviceData?.pickup_request?.time_slot) {
		services.value.time = shipmentFlowStore.serviceData.pickup_request.time_slot;
	}
	if (shipmentFlowStore?.servicesArray.length > 0) {
		syncServiceTypeString();
		syncSelectedServicesVisual();
	}
	if (shipmentFlowStore?.smsEmailNotification !== undefined) smsEmailNotification.value = shipmentFlowStore.smsEmailNotification;

	watch(daysInMonth, (availableDays) => {
		if (!Array.isArray(availableDays) || availableDays.length === 0) return;
		if (availableDays.some((day) => day.formattedDate === services.value.date)) return;
		const firstAvailableDay = availableDays[0];
		if (firstAvailableDay) chooseDate(firstAvailableDay);
	}, { immediate: true });

	onMounted(() => { loadPriceBands().catch(() => { /* warning loggato in usePriceBands */ }); });

	const syncPickupRequestState = () => {
		const pickupRequestDate = normalizePickupRequestDate(services.value.date || shipmentFlowStore?.pickupDate || '');
		const pickupTimeSlot = String(services.value.time || serviceData.value?.pickup_request?.time_slot || DEFAULT_PICKUP_TIME_SLOT).trim() || DEFAULT_PICKUP_TIME_SLOT;
		const current = serviceData.value?.pickup_request || {};
		const next = { enabled: Boolean(pickupRequestDate), date: pickupRequestDate, time_slot: pickupTimeSlot, notes: String(current.notes || '').trim() };
		const changed = current.enabled !== next.enabled || current.date !== next.date || current.time_slot !== next.time_slot || String(current.notes || '') !== next.notes;
		if (changed) serviceData.value.pickup_request = next;
		if (services.value.time !== pickupTimeSlot) services.value.time = pickupTimeSlot;
	};

	watch(() => [services.value.date, services.value.time], ([newDate]) => {
		const selectedDate = newDate || '';
		if (shipmentFlowStore?.pickupDate !== selectedDate) shipmentFlowStore.pickupDate = selectedDate;
		const currentDetails = shipmentFlowStore?.shipmentDetails || {};
		if ((currentDetails.date || '') !== selectedDate) shipmentFlowStore.shipmentDetails = { ...currentDetails, date: selectedDate };
		syncPickupRequestState();
	}, { immediate: true });

	watch(smsEmailNotification, (enabled) => { shipmentFlowStore.smsEmailNotification = Boolean(enabled); }, { immediate: true });

	watch(serviceData, (nextValue) => {
		shipmentFlowStore.serviceData = {
			contrassegno: { ...(nextValue?.contrassegno || {}) },
			assicurazione: { ...(nextValue?.assicurazione || {}) },
			sponda_idraulica: { ...(nextValue?.sponda_idraulica || {}) },
			pickup_request: { ...(nextValue?.pickup_request || {}) },
			telefono_notifica: nextValue?.telefono_notifica || '',
		};
	}, { immediate: true, deep: true });

	watch(() => [...(shipmentFlowStore?.servicesArray || [])], () => {
		syncSelectedServicesVisual();
		syncServiceTypeString();
		if (!expandedServiceKey.value) return;
		const expandedService = findServiceByKey(expandedServiceKey.value);
		if (!expandedService) expandedServiceKey.value = '';
	}, { immediate: true });

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
