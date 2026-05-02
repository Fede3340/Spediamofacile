/**
 * useShipmentStepSummary — orchestra le ~30 computed del riepilogo funnel
 * (cart, session, store, props). Helper puri in utils/shipmentSummaryHelpers.ts.
 */
import { calculateShipmentServiceSurcharge } from "~/utils/shipmentServicePricing";
import {
	firstMeaningfulValue as firstMeaningfulValueHelper,
	parsePriceAmount,
	formatPriceAmount,
	pickBestPriceAmount,
	getPackagesTotal,
} from "~/utils/shipmentSummaryHelpers";
import type { Ref } from 'vue';

type Nullable<T> = T | null;
type StrOrNum = number | string | null;
type SummaryAddress = {
	name?: Nullable<string>; surname?: Nullable<string>; city?: Nullable<string>;
	postal_code?: Nullable<string>; zip_code?: Nullable<string>; address?: Nullable<string>;
	address_number?: Nullable<string>; province?: Nullable<string>;
	[key: string]: unknown;
};
type SummaryPackage = {
	package_type?: Nullable<string>; quantity?: StrOrNum;
	weight?: StrOrNum; first_size?: StrOrNum; second_size?: StrOrNum; third_size?: StrOrNum;
	length?: StrOrNum; width?: StrOrNum; height?: StrOrNum;
	single_price?: StrOrNum; single_priceOrig?: StrOrNum; weight_price?: StrOrNum; volume_price?: StrOrNum;
	[key: string]: unknown;
};
type SummaryPudo = SummaryAddress & { pudo_id?: Nullable<string>; name?: Nullable<string> };
type SummaryServices = {
	service_type?: Nullable<string>; serviceData?: Record<string, unknown>;
	sms_email_notification?: boolean; [key: string]: unknown;
};
type SummarySessionData = {
	packages?: SummaryPackage[]; shipment_details?: Record<string, unknown>;
	origin_address?: SummaryAddress; destination_address?: SummaryAddress;
	selected_pudo?: Nullable<SummaryPudo>; services?: SummaryServices;
	total_price?: StrOrNum; sms_email_notification?: boolean; delivery_mode?: string;
};
type SummarySession = { data?: SummarySessionData };
type PendingShipment = {
	packages?: SummaryPackage[]; origin_address?: SummaryAddress; destination_address?: SummaryAddress;
	selected_pudo?: Nullable<SummaryPudo>; services?: SummaryServices; delivery_mode?: string;
};
type ShipmentFlowStoreLike = {
	originAddressData?: Nullable<SummaryAddress>; destinationAddressData?: Nullable<SummaryAddress>;
	selectedPudo?: Nullable<SummaryPudo>; shipmentDetails?: Record<string, unknown>;
	pendingShipment?: Nullable<PendingShipment>; servicesArray?: string[];
	serviceData?: Record<string, unknown>; smsEmailNotification?: boolean;
	totalPrice?: StrOrNum; packages?: SummaryPackage[] | Ref<SummaryPackage[]>; deliveryMode?: string;
};
type MiniStep = { id: number; label: string; to: string; isActive?: boolean; isCompleted?: boolean; isClickable?: boolean };
type SummaryPanel = 'services' | 'dimensions' | null;
type UseShipmentStepSummaryArgs = {
	destinationAddress: Ref<SummaryAddress>; editablePackages: Ref<SummaryPackage[]>;
	normalizeLocationText: (value: string) => string; originAddress: Ref<SummaryAddress>;
	session: Ref<Nullable<SummarySession>>; showAddressFields: Ref<boolean>;
	status: Ref<string>; stepsRef: Ref<Nullable<HTMLElement>>;
	shipmentFlowStore?: Nullable<ShipmentFlowStoreLike>;
};

const PACKAGE_TYPE_VISUAL_MAP: Record<string, { label: string; icon: string }> = {
	pacco: { label: 'Pacco', icon: '/img/quote/first-step/pack.png' },
	pallet: { label: 'Pallet', icon: '/img/quote/first-step/pallet.png' },
	valigia: { label: 'Valigia', icon: '/img/quote/first-step/suitcase.png' },
	busta: { label: 'Busta', icon: '/img/quote/first-step/envelope.png' },
	wallet: { label: 'Wallet', icon: '/img/quote/first-step/suitcase.png' },
};
const DEFAULT_PACKAGE_VISUAL = PACKAGE_TYPE_VISUAL_MAP.pacco as { label: string; icon: string };

const getStorePackages = (source?: SummaryPackage[] | Ref<SummaryPackage[]>): SummaryPackage[] => {
	if (Array.isArray(source)) return source;
	return source && Array.isArray(source.value) ? source.value : [];
};

const normalizePackageTypeLabel = (value: unknown): string => (value ? String(value).trim().toLowerCase() : 'pacco');

const capitalize = (value: string): string => (value ? value.charAt(0).toUpperCase() + value.slice(1) : '');

const normalizeDimensionValue = (value: unknown): number | null => {
	const parsed = Number(value);
	return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
};

const getPackageDimensionLabel = (pack: SummaryPackage): string | null => {
	const s1 = normalizeDimensionValue(pack?.first_size ?? pack?.length);
	const s2 = normalizeDimensionValue(pack?.second_size ?? pack?.width);
	const s3 = normalizeDimensionValue(pack?.third_size ?? pack?.height);
	return s1 && s2 && s3 ? `${s1}×${s2}×${s3} cm` : null;
};

export const useShipmentStepSummary = ({
	destinationAddress,
	editablePackages,
	normalizeLocationText,
	originAddress,
	session,
	showAddressFields,
	status,
	stepsRef,
	shipmentFlowStore,
}: UseShipmentStepSummaryArgs) => {
	const { priceBands, loadPriceBands } = usePriceBands();
	const stepsVisible = ref(true);
	const clientDraftSummaryReady = ref(false);
	let stepsObserver: IntersectionObserver | null = null;
	let stepsVisibilityRaf: number | null = null;

	onMounted(() => {
		loadPriceBands();
		nextTick(() => { clientDraftSummaryReady.value = true; });
	});

	const firstMeaningfulValue = (...candidates: unknown[]): string => firstMeaningfulValueHelper(candidates, normalizeLocationText);

	const summaryPackagesSource = computed(() => {
		const src = clientDraftSummaryReady.value ? editablePackages.value : session.value?.data?.packages;
		return Array.isArray(src) ? src : [];
	});

	const summaryPackageLabel = computed(() => {
		const count = summaryPackagesSource.value.length;
		return `${count} ${count === 1 ? 'collo' : 'colli'}`;
	});

	const getPackageTypeLabel = (pack: SummaryPackage): string => {
		const normalized = normalizePackageTypeLabel(pack?.package_type || 'Pacco');
		return PACKAGE_TYPE_VISUAL_MAP[normalized]?.label || capitalize(normalized) || 'Pacco';
	};

	const getPackageTypeIcon = (pack: SummaryPackage): string => {
		const normalized = normalizePackageTypeLabel(pack?.package_type || 'Pacco');
		return PACKAGE_TYPE_VISUAL_MAP[normalized]?.icon || DEFAULT_PACKAGE_VISUAL.icon;
	};

	const summaryPackageTypeInfo = computed(() => {
		const uniqueTypes = [...new Set(
			summaryPackagesSource.value.map((p) => normalizePackageTypeLabel(p?.package_type || 'Pacco')).filter(Boolean),
		)];
		if (!uniqueTypes.length) return DEFAULT_PACKAGE_VISUAL;
		if (uniqueTypes.length > 1) return { label: 'Misto', icon: DEFAULT_PACKAGE_VISUAL.icon };
		const normalized = uniqueTypes[0] || 'pacco';
		return PACKAGE_TYPE_VISUAL_MAP[normalized] || { label: capitalize(normalized), icon: DEFAULT_PACKAGE_VISUAL.icon };
	});

	const summaryOriginCity = computed(() => {
		if (!clientDraftSummaryReady.value) return session.value?.data?.shipment_details?.origin_city || 'â€”';
		const liveCity = String(originAddress.value?.city || '').trim();
		if (liveCity) return liveCity;
		if (showAddressFields.value) return '—';
		return shipmentFlowStore?.originAddressData?.city
			|| shipmentFlowStore?.shipmentDetails?.origin_city
			|| session.value?.data?.shipment_details?.origin_city || '—';
	});

	const summaryDestinationCity = computed(() => {
		if (!clientDraftSummaryReady.value) return session.value?.data?.shipment_details?.destination_city || 'â€”';
		const pudoCity = String(shipmentFlowStore?.selectedPudo?.city || '').trim();
		if (pudoCity) return pudoCity;
		const liveCity = String(destinationAddress.value?.city || '').trim();
		if (liveCity) return liveCity;
		if (showAddressFields.value) return '—';
		return shipmentFlowStore?.destinationAddressData?.city
			|| shipmentFlowStore?.shipmentDetails?.destination_city
			|| session.value?.data?.shipment_details?.destination_city || '—';
	});

	const resolvedSummaryOriginCity = computed(() => firstMeaningfulValue(
		originAddress.value?.city,
		shipmentFlowStore?.originAddressData?.city,
		shipmentFlowStore?.pendingShipment?.origin_address?.city,
		session.value?.data?.origin_address?.city,
		shipmentFlowStore?.shipmentDetails?.origin_city,
		session.value?.data?.shipment_details?.origin_city,
		summaryOriginCity.value,
	) || '—');

	const resolvedSummaryDestinationCity = computed(() => firstMeaningfulValue(
		shipmentFlowStore?.selectedPudo?.city,
		shipmentFlowStore?.pendingShipment?.selected_pudo?.city,
		session.value?.data?.selected_pudo?.city,
		destinationAddress.value?.city,
		shipmentFlowStore?.destinationAddressData?.city,
		shipmentFlowStore?.pendingShipment?.destination_address?.city,
		session.value?.data?.destination_address?.city,
		shipmentFlowStore?.shipmentDetails?.destination_city,
		session.value?.data?.shipment_details?.destination_city,
		summaryDestinationCity.value,
	) || '—');

	const resolvedSummaryRouteLabel = computed(() => `${resolvedSummaryOriginCity.value} → ${resolvedSummaryDestinationCity.value}`);

	const normalizeRouteText = (value: unknown): string => normalizeLocationText(String(value || '').replace(/\s+/g, ' '));
	const normalizeRouteNumber = (value: unknown): string => String(value || '').trim().toLowerCase().replace(/\s+/g, '');

	const routeConsistencyState = computed(() => {
		const empty = { blocking: false, warning: false, message: '' };
		const originCity = normalizeRouteText(originAddress.value?.city);
		const destinationCity = normalizeRouteText(
			shipmentFlowStore?.selectedPudo?.city
			|| destinationAddress.value?.city
			|| shipmentFlowStore?.shipmentDetails?.destination_city
		);
		if (!originCity || !destinationCity) return empty;

		const originCap = String(originAddress.value?.postal_code || '').trim();
		const destinationCap = String(
			shipmentFlowStore?.selectedPudo?.zip_code
			|| destinationAddress.value?.postal_code
			|| shipmentFlowStore?.shipmentDetails?.destination_postal_code || ''
		).trim();
		const sameCity = originCity === destinationCity;
		const sameCap = !!originCap && !!destinationCap && originCap === destinationCap;

		const originStreet = normalizeRouteText(originAddress.value?.address);
		const destinationStreet = normalizeRouteText(shipmentFlowStore?.selectedPudo?.address || destinationAddress.value?.address);
		const originNumber = normalizeRouteNumber(originAddress.value?.address_number);
		const destinationNumber = normalizeRouteNumber(shipmentFlowStore?.selectedPudo ? 'SNC' : destinationAddress.value?.address_number);
		const sameAddress = sameCity && sameCap
			&& !!originStreet && !!destinationStreet && originStreet === destinationStreet
			&& !!originNumber && !!destinationNumber && originNumber === destinationNumber;

		if (sameAddress) return { blocking: true, warning: true, message: 'Partenza e destinazione coincidono. Inserisci una destinazione diversa prima di continuare.' };
		if (sameCity && sameCap) return { blocking: false, warning: true, message: 'Tratta locale: verifica disponibilità del servizio BRT per questa combinazione di indirizzi.' };
		return empty;
	});

	const routeWarningMessage = computed(() => routeConsistencyState.value.warning ? routeConsistencyState.value.message : '');

	const selectedServicesFromState = computed(() => {
		const local = Array.isArray(shipmentFlowStore?.servicesArray) ? shipmentFlowStore.servicesArray.filter(Boolean) : [];
		if (local.length) return local;
		return String(shipmentFlowStore?.pendingShipment?.services?.service_type || session.value?.data?.services?.service_type || "")
			.split(",").map((s) => s.trim()).filter(Boolean);
	});

	const summaryServicesLabel = computed(() => selectedServicesFromState.value.length
		? selectedServicesFromState.value.join(', ') : 'Nessun servizio');
	const summaryServicesItems = computed(() => selectedServicesFromState.value.length
		? selectedServicesFromState.value : ['Nessun servizio selezionato']);

	const summaryDimensionsLabel = computed(() => {
		const rows = (editablePackages.value || [])
			.map((pack) => ({ label: getPackageDimensionLabel(pack), qty: Math.max(1, Number(pack?.quantity) || 1) }))
			.filter((r): r is { label: string; qty: number } => !!r.label);
		if (!rows.length) return '—';
		const totalQty = rows.reduce((sum, item) => sum + item.qty, 0);
		const primary = rows[0]?.label || 'Misure non definite';
		if (rows.length === 1 && totalQty === 1) return primary;
		if (rows.length === 1) return `${primary} × ${totalQty}`;
		return `${primary} +${Math.max(totalQty - 1, 1)}`;
	});

	const summaryDimensionsItems = computed(() => {
		const grouped = new Map<string, { type: string; dimension: string; icon: string; count: number }>();
		for (const pack of (editablePackages.value || [])) {
			const dimension = getPackageDimensionLabel(pack) || 'Misure non definite';
			const type = getPackageTypeLabel(pack);
			const key = `${normalizePackageTypeLabel(type)}|${dimension}`;
			const current = grouped.get(key) || { type, dimension, icon: getPackageTypeIcon(pack), count: 0 };
			current.count += Math.max(1, Number(pack?.quantity) || 1);
			grouped.set(key, current);
		}
		const rows = Array.from(grouped.values()).map(({ type, dimension, icon, count }) => ({
			label: count > 1 ? `${count}x ${type}: ${dimension}` : `${type}: ${dimension}`,
			icon, type,
		}));
		return rows.length ? rows : [{ label: 'Misure non disponibili', icon: DEFAULT_PACKAGE_VISUAL.icon, type: 'Pacco' }];
	});

	const canExpandSummaryServices = computed(() => summaryServicesItems.value.length > 1 || summaryServicesLabel.value.length > 26);
	const canExpandSummaryDimensions = computed(() => summaryDimensionsItems.value.length > 1 || summaryDimensionsLabel.value.length > 20);

	const summaryTotalPrice = computed(() => {
		const pendingServices = shipmentFlowStore?.pendingShipment?.services || {};
		const sessionServices = session.value?.data?.services || {};
		const baseAmount = pickBestPriceAmount([
			getPackagesTotal(shipmentFlowStore?.pendingShipment?.packages),
			getPackagesTotal(editablePackages.value),
			getPackagesTotal(session.value?.data?.packages),
			getPackagesTotal(getStorePackages(shipmentFlowStore?.packages)),
			parsePriceAmount(shipmentFlowStore?.totalPrice),
			parsePriceAmount(session.value?.data?.total_price),
		]);
		const serviceSurcharge = calculateShipmentServiceSurcharge({
			selectedServices: Array.isArray(shipmentFlowStore?.servicesArray) && shipmentFlowStore?.servicesArray.length
				? shipmentFlowStore?.servicesArray
				: (pendingServices.service_type || sessionServices.service_type || ""),
			serviceData: Object.keys(shipmentFlowStore?.serviceData || {}).length
				? shipmentFlowStore?.serviceData
				: (pendingServices.serviceData || sessionServices.serviceData || {}),
			smsEmailNotification: Boolean(
				shipmentFlowStore?.smsEmailNotification
				|| pendingServices.sms_email_notification
				|| pendingServices.serviceData?.sms_email_notification
				|| session.value?.data?.sms_email_notification
				|| sessionServices.sms_email_notification
				|| sessionServices.serviceData?.sms_email_notification
			),
			pricingConfig: priceBands.value,
			packages: editablePackages.value?.length
				? editablePackages.value
				: (shipmentFlowStore?.pendingShipment?.packages || session.value?.data?.packages || []),
			originAddress: originAddress.value || shipmentFlowStore?.originAddressData || session.value?.data?.origin_address || {},
			destinationAddress: destinationAddress.value || shipmentFlowStore?.destinationAddressData || session.value?.data?.destination_address || {},
			deliveryMode: shipmentFlowStore?.deliveryMode || shipmentFlowStore?.pendingShipment?.delivery_mode || session.value?.data?.delivery_mode || "home",
			selectedPudo: shipmentFlowStore?.selectedPudo || shipmentFlowStore?.pendingShipment?.selected_pudo || session.value?.data?.selected_pudo || null,
		}).total;
		return formatPriceAmount(baseAmount + serviceSurcharge);
	});

	const currentShipmentStep = computed(() => showAddressFields.value ? 3 : 2);

	const summaryMiniSteps = computed(() => {
		const defs = [
			{ id: 1, label: 'Misure', to: '/#preventivo' },
			{ id: 2, label: 'Servizi', to: '/la-tua-spedizione/2?step=servizi' },
			{ id: 3, label: 'Indirizzi', to: '/la-tua-spedizione/2?step=indirizzi' },
			{ id: 4, label: 'Pagamento', to: '/la-tua-spedizione/2?step=pagamento' },
		];
		return defs.map((step) => ({
			...step,
			isActive: step.id === currentShipmentStep.value,
			isCompleted: step.id < currentShipmentStep.value,
			isClickable: step.id < currentShipmentStep.value,
		}));
	});

	const showSummaryMiniSteps = computed(() => !stepsVisible.value);

	const goToSummaryMiniStep = async (step: MiniStep) => {
		if (!step?.isClickable) return;
		await navigateTo(step.to);
	};

	const updateStepsVisibility = () => {
		if (!import.meta.client || !stepsRef.value) return;
		const rect = stepsRef.value.getBoundingClientRect();
		const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
		const visibleHeight = Math.max(0, Math.min(rect.bottom, viewportHeight) - Math.max(rect.top, 0));
		const visibleRatio = rect.height > 0 ? visibleHeight / rect.height : 0;
		stepsVisible.value = rect.bottom > 0 && rect.top < viewportHeight && visibleRatio >= 0.55;
	};

	const scheduleStepsVisibilityUpdate = () => {
		if (!import.meta.client) return;
		if (stepsVisibilityRaf) cancelAnimationFrame(stepsVisibilityRaf);
		stepsVisibilityRaf = requestAnimationFrame(() => { updateStepsVisibility(); stepsVisibilityRaf = null; });
	};

	const teardownStepsVisibilityObserver = () => {
		if (!import.meta.client) return;
		window.removeEventListener('scroll', scheduleStepsVisibilityUpdate);
		window.removeEventListener('resize', scheduleStepsVisibilityUpdate);
		if (stepsVisibilityRaf) { cancelAnimationFrame(stepsVisibilityRaf); stepsVisibilityRaf = null; }
		if (stepsObserver) { stepsObserver.disconnect(); stepsObserver = null; }
	};

	const initStepsVisibilityObserver = () => {
		if (!import.meta.client || !stepsRef.value) return;
		teardownStepsVisibilityObserver();
		if ('IntersectionObserver' in window) {
			stepsObserver = new IntersectionObserver(() => scheduleStepsVisibilityUpdate(), {
				root: null, threshold: [0, 0.2, 0.4, 0.55, 0.75, 1], rootMargin: '0px',
			});
			stepsObserver.observe(stepsRef.value);
		}
		window.addEventListener('scroll', scheduleStepsVisibilityUpdate, { passive: true });
		window.addEventListener('resize', scheduleStepsVisibilityUpdate);
		scheduleStepsVisibilityUpdate();
	};

	const summaryExpanded = ref(false);
	const summaryDetailPanel = ref<SummaryPanel>(null);

	const toggleSummaryDetailPanel = (panel: SummaryPanel) => {
		summaryDetailPanel.value = summaryDetailPanel.value === panel ? null : panel;
	};

	watch(summaryExpanded, (isOpen) => {
		if (!isOpen) summaryDetailPanel.value = null;
		scheduleStepsVisibilityUpdate();
	});
	watch(() => stepsRef.value, (el) => {
		if (import.meta.client && el) nextTick(() => initStepsVisibilityObserver());
	}, { flush: 'post' });
	watch(() => status.value, (newStatus) => {
		if (import.meta.client && newStatus !== 'pending') nextTick(() => initStepsVisibilityObserver());
	});

	onMounted(() => { nextTick(() => initStepsVisibilityObserver()); });
	onBeforeUnmount(() => { teardownStepsVisibilityObserver(); });

	const setAccordionStyle = (el: Element, height: string, overflow: string) => {
		const target = el as HTMLElement;
		target.style.height = height;
		target.style.overflow = overflow;
	};
	const onAccordionEnter = (el: Element) => setAccordionStyle(el, '0', 'hidden');
	const onAccordionAfterEnter = (el: Element) => setAccordionStyle(el, 'auto', 'visible');
	const onAccordionLeave = (el: Element) => {
		setAccordionStyle(el, `${(el as HTMLElement).scrollHeight}px`, 'hidden');
		requestAnimationFrame(() => { (el as HTMLElement).style.height = '0'; });
	};

	return {
		canExpandSummaryDimensions, canExpandSummaryServices, currentShipmentStep,
		goToSummaryMiniStep, onAccordionAfterEnter, onAccordionEnter, onAccordionLeave,
		routeConsistencyState, routeWarningMessage, showSummaryMiniSteps,
		summaryDetailPanel, summaryDimensionsItems, summaryDimensionsLabel,
		summaryDestinationCity: resolvedSummaryDestinationCity,
		summaryExpanded, summaryMiniSteps,
		summaryOriginCity: resolvedSummaryOriginCity,
		summaryPackageLabel, summaryPackageTypeInfo,
		summaryRouteLabel: resolvedSummaryRouteLabel,
		summaryServicesItems, summaryServicesLabel, summaryTotalPrice,
		toggleSummaryDetailPanel,
	};
};
