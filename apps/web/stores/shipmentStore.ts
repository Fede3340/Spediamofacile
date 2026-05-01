/**
 * shipmentStore — store unificato del modulo Spedizione/Preventivo.
 *
 * Fonde i 3 store storici (Ondata 3 consolidamento Pinia):
 *  - shipmentFlowStore: 16 ref dati spedizione (step, packages, indirizzi, …)
 *  - preventivoStore: 5 ref orchestrazione preventivo + actions navigation
 *  - shipmentFlowAdminGateStore: 1 ref challenge accesso fuori flusso
 *
 * Dominio unico: tutto cio' che riguarda la creazione/calcolo/navigazione di
 * una spedizione passa da qui. Persistenza in sessionStorage debounced.
 */
import { defineStore } from 'pinia';
import type { Ref } from 'vue';
import { ref, watch } from 'vue';
import type {
	Address,
	Package,
	PendingShipment,
	PudoPoint,
	ShipmentDetails,
	ShipmentFlowStoreState,
} from '~/types';
import { buildQuotePayloadSnapshotFor } from '~/utils/preventivoHelpers';
import {
	buildQuoteComparableSignature,
	extractSessionComparablePayload,
	formatResolvedLocation,
} from '~/utils/quickQuoteContract';
import { buildShipmentFlowLocation } from '~/utils/shipment';

// ── Tipi per orchestrazione preventivo (ex preventivoStore) ────────────────
type QuotePackage = Record<string, unknown>;
type QuoteShipmentDetails = Record<string, string | number | null | undefined>;
type QuoteSessionData = {
	shipment_details?: QuoteShipmentDetails;
	packages?: QuotePackage[];
	total_price?: number | string;
	step?: number | string;
	[key: string]: unknown;
};
type QuoteSyncOptions = {
	sourceSignature?: string;
};
type ShipmentFlowStoreLike = {
	shipmentDetails: QuoteShipmentDetails;
	packages: QuotePackage[];
	totalPrice: number;
	stepNumber: number;
	isQuoteStarted: boolean;
};
type ContinueToNextStepDeps = {
	shipmentFlowStore: ShipmentFlowStoreLike;
	flushLocationDraftsForSubmit: (formatter: (city?: string, cap?: string) => string) => Promise<unknown>;
	calculateRate: (options: { silent: boolean; payload: QuoteSessionData }) => Promise<boolean>;
	ensurePackagesIdentity: () => void;
	ensurePrimaryPackage: () => void;
	session: Ref<QuoteSessionData | { data?: QuoteSessionData } | null | undefined>;
	refresh: () => Promise<QuoteSessionData | { data?: QuoteSessionData } | null | undefined>;
};

// ── Tipi per admin gate (ex shipmentFlowAdminGateStore) ────────────────────
type AdminGatePayload = {
	targetPath?: string;
	lastValidRoute?: string;
	reason?: string;
};
type AdminGateChallenge = Required<AdminGatePayload> & {
	createdAt: number;
};

// ── Persistenza sessionStorage (ex shipmentFlowStore) ──────────────────────
const STORAGE_KEY = 'spedizionefacile_user_store';
const DEBOUNCE_MS = 300;
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

const DEFAULT_SHIPMENT_DETAILS: ShipmentDetails = {
	origin_city: '',
	origin_postal_code: '',
	origin_province: '',
	origin_country_code: 'IT',
	origin_country: 'Italia',
	destination_city: '',
	destination_postal_code: '',
	destination_province: '',
	destination_country_code: 'IT',
	destination_country: 'Italia',
	date: '',
};

function loadFromSession(): Partial<ShipmentFlowStoreState> | null {
	if (!import.meta.client) return null;
	try {
		const saved = sessionStorage.getItem(STORAGE_KEY);
		if (!saved) return null;
		const parsed = JSON.parse(saved);
		if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) return null;
		return parsed as Partial<ShipmentFlowStoreState>;
	} catch {
		return null;
	}
}

function saveToSession(state: ShipmentFlowStoreState) {
	if (!import.meta.client) return;
	try {
		sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
	} catch {
		// sessionStorage pieno o non disponibile: ignoriamo
	}
}

export const useShipmentStore = defineStore('shipment', () => {
	// ═══════════════════════════════════════════════════════════════════════
	// SEZIONE 1 — DATI SPEDIZIONE (ex shipmentFlowStore, 16 ref)
	// ═══════════════════════════════════════════════════════════════════════

	const stepNumber = ref(1);
	const hasPersistedHydration = ref(false);

	const shipmentDetails = ref<ShipmentDetails>({ ...DEFAULT_SHIPMENT_DETAILS });
	if (!shipmentDetails.value.origin_country_code) shipmentDetails.value.origin_country_code = 'IT';
	if (!shipmentDetails.value.origin_country) shipmentDetails.value.origin_country = 'Italia';
	if (!shipmentDetails.value.destination_country_code) shipmentDetails.value.destination_country_code = 'IT';
	if (!shipmentDetails.value.destination_country) shipmentDetails.value.destination_country = 'Italia';

	const isQuoteStarted = ref(false);
	const totalPrice = ref(0);
	const packages = ref<Package[]>([]);
	const servicesArray = ref<string[]>([]);
	const contentDescription = ref('');
	const pendingShipment = ref<PendingShipment | null>(null);
	const originAddressData = ref<Partial<Address> | null>(null);
	const destinationAddressData = ref<Partial<Address> | null>(null);
	const pickupDate = ref('');
	const editingCartItemId = ref<number | string | null>(null);
	const deliveryMode = ref<'home' | 'pudo'>('home');
	const selectedPudo = ref<PudoPoint | null>(null);
	const smsEmailNotification = ref(false);
	const serviceData = ref<Record<string, unknown>>({});

	function applyPersistedState(saved: Partial<ShipmentFlowStoreState> | null) {
		if (!saved || typeof saved !== 'object') return;
		stepNumber.value = typeof saved.stepNumber === 'number' ? saved.stepNumber : 1;
		shipmentDetails.value = { ...DEFAULT_SHIPMENT_DETAILS, ...(saved.shipmentDetails || {}) };
		isQuoteStarted.value = saved.isQuoteStarted ?? false;
		totalPrice.value = typeof saved.totalPrice === 'number' ? saved.totalPrice : 0;
		packages.value = Array.isArray(saved.packages) ? saved.packages : [];
		servicesArray.value = Array.isArray(saved.servicesArray) ? saved.servicesArray : [];
		contentDescription.value = saved.contentDescription ?? '';
		pendingShipment.value = saved.pendingShipment ?? null;
		originAddressData.value = saved.originAddressData ?? null;
		destinationAddressData.value = saved.destinationAddressData ?? null;
		pickupDate.value = saved.pickupDate ?? '';
		editingCartItemId.value = saved.editingCartItemId ?? null;
		deliveryMode.value = saved.deliveryMode ?? 'home';
		selectedPudo.value = saved.selectedPudo ?? null;
		smsEmailNotification.value = saved.smsEmailNotification ?? false;
		serviceData.value = saved.serviceData ?? {};
	}

	function hydrateFromSession() {
		if (hasPersistedHydration.value) return;
		applyPersistedState(loadFromSession());
		hasPersistedHydration.value = true;
	}

	function persist() {
		if (!hasPersistedHydration.value) return;
		if (debounceTimer) clearTimeout(debounceTimer);
		debounceTimer = setTimeout(() => {
			saveToSession({
				stepNumber: stepNumber.value,
				shipmentDetails: shipmentDetails.value,
				isQuoteStarted: isQuoteStarted.value,
				totalPrice: totalPrice.value,
				packages: packages.value,
				servicesArray: servicesArray.value,
				contentDescription: contentDescription.value,
				pendingShipment: pendingShipment.value,
				originAddressData: originAddressData.value,
				destinationAddressData: destinationAddressData.value,
				pickupDate: pickupDate.value,
				editingCartItemId: editingCartItemId.value,
				deliveryMode: deliveryMode.value,
				selectedPudo: selectedPudo.value,
				smsEmailNotification: smsEmailNotification.value,
				serviceData: serviceData.value,
			});
		}, DEBOUNCE_MS);
	}

	watch(
		[
			stepNumber, shipmentDetails, isQuoteStarted, totalPrice, packages,
			servicesArray, contentDescription, pendingShipment, originAddressData,
			destinationAddressData, pickupDate, editingCartItemId, deliveryMode, selectedPudo,
			smsEmailNotification, serviceData,
		],
		persist,
		{ deep: true },
	);

	// ═══════════════════════════════════════════════════════════════════════
	// SEZIONE 2 — ORCHESTRAZIONE PREVENTIVO (ex preventivoStore)
	// ═══════════════════════════════════════════════════════════════════════

	const messageError = ref<string | null>(null);
	const isCalculating = ref(false);
	const isSyncingQuote = ref(false);
	const isAdvancingToServices = ref(false);
	const lastQuotedSignature = ref('');
	const quoteTransitionLock = useState('shipment-flow-quote-transition-lock', () => false);

	let autoQuoteTimer: ReturnType<typeof setTimeout> | null = null;
	let pendingQuotePromise: Promise<boolean> | null = null;
	let pendingQuoteSignature = '';
	let pendingQuoteSilent = false;
	let pendingQuoteRequestId = 0;
	let latestQuoteRequestId = 0;

	const getAutoQuoteTimer = () => autoQuoteTimer;
	const setAutoQuoteTimer = (timer: ReturnType<typeof setTimeout> | null) => {
		autoQuoteTimer = timer;
	};
	const clearAutoQuoteTimer = () => {
		if (autoQuoteTimer) {
			clearTimeout(autoQuoteTimer);
			autoQuoteTimer = null;
		}
	};

	const getPendingQuotePromise = () => pendingQuotePromise;
	const getPendingQuoteSignature = () => pendingQuoteSignature;
	const setPending = (
		promise: Promise<boolean> | null,
		signature: string,
		silent: boolean,
		requestId: number,
	) => {
		pendingQuotePromise = promise;
		pendingQuoteSignature = signature;
		pendingQuoteSilent = silent;
		pendingQuoteRequestId = requestId;
	};
	const releasePendingIfMatches = (requestId: number) => {
		if (pendingQuoteRequestId === requestId) {
			pendingQuotePromise = null;
			pendingQuoteSignature = '';
			pendingQuoteSilent = false;
			pendingQuoteRequestId = 0;
		}
	};
	const isPendingSilent = () => pendingQuoteSilent;
	const nextRequestId = () => ++latestQuoteRequestId;
	const isLatestRequest = (requestId: number) => requestId === latestQuoteRequestId;

	const syncQuoteStateFromSession = (
		shipmentFlowStore: ShipmentFlowStoreLike,
		ensurePackagesIdentity: () => void,
		ensurePrimaryPackage: () => void,
		sessionData: QuoteSessionData = {},
		options: QuoteSyncOptions = {},
	) => {
		const sourceSignature = String(options?.sourceSignature || '');
		const sessionSignature = buildQuoteComparableSignature(extractSessionComparablePayload(sessionData));
		if (sourceSignature) {
			if (sourceSignature !== sessionSignature) return;
			shipmentFlowStore.totalPrice = Number(sessionData?.total_price || shipmentFlowStore?.totalPrice || 0);
			shipmentFlowStore.stepNumber = Number(sessionData?.step || 2);
			shipmentFlowStore.isQuoteStarted = true;
			ensurePackagesIdentity();
			ensurePrimaryPackage();
			return;
		}
		const sessionDetails = sessionData?.shipment_details || {};
		for (const [key, value] of Object.entries(sessionDetails)) {
			if (key in shipmentFlowStore.shipmentDetails) {
				shipmentFlowStore.shipmentDetails[key] = value ?? '';
			}
		}
		const newPackages = Array.isArray(sessionData?.packages)
			? sessionData.packages.map((pack) => ({ ...pack }))
			: null;
		if (newPackages) {
			shipmentFlowStore?.packages.splice(0, shipmentFlowStore?.packages.length, ...newPackages);
			ensurePackagesIdentity();
		}
		shipmentFlowStore.totalPrice = Number(sessionData?.total_price || shipmentFlowStore?.totalPrice || 0);
		shipmentFlowStore.stepNumber = Number(sessionData?.step || 2);
		shipmentFlowStore.isQuoteStarted = true;
		ensurePrimaryPackage();
	};

	const resetQuoteState = () => {
		if (isAdvancingToServices.value) return;
		messageError.value = null;
		clearAutoQuoteTimer();
	};

	const continueToNextStep = async (deps: ContinueToNextStepDeps) => {
		const {
			shipmentFlowStore,
			flushLocationDraftsForSubmit,
			calculateRate,
			ensurePackagesIdentity,
			ensurePrimaryPackage,
			session,
			refresh,
		} = deps;
		if (isCalculating.value || isAdvancingToServices.value) return;
		messageError.value = null;
		isAdvancingToServices.value = true;
		quoteTransitionLock.value = true;
		clearAutoQuoteTimer();
		const unlockTimer = setTimeout(() => {
			quoteTransitionLock.value = false;
		}, 8000);
		try {
			await flushLocationDraftsForSubmit(formatResolvedLocation);
			const payloadSnapshot = buildQuotePayloadSnapshotFor(shipmentFlowStore) as QuoteSessionData;
			const payloadSignature = buildQuoteComparableSignature(payloadSnapshot);
			const pendingPromise = getPendingQuotePromise();
			const pendingSig = getPendingQuoteSignature();
			let isValid = false;
			if (pendingPromise && pendingSig === payloadSignature) {
				isValid = await pendingPromise;
				if (!isValid) {
					isValid = await calculateRate({ silent: false, payload: payloadSnapshot });
				}
			} else {
				isValid = await calculateRate({ silent: false, payload: payloadSnapshot });
			}
			if (!isValid) return;
			const refreshedSession = await refresh().catch(() => session.value);
			const refreshedData = (refreshedSession && 'data' in refreshedSession ? refreshedSession.data : refreshedSession) as QuoteSessionData | null;
			if (refreshedData) {
				syncQuoteStateFromSession(
					shipmentFlowStore,
					ensurePackagesIdentity,
					ensurePrimaryPackage,
					refreshedData,
					{ sourceSignature: payloadSignature },
				);
			} else {
				syncQuoteStateFromSession(
					shipmentFlowStore,
					ensurePackagesIdentity,
					ensurePrimaryPackage,
					payloadSnapshot,
					{ sourceSignature: payloadSignature },
				);
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

	// ═══════════════════════════════════════════════════════════════════════
	// SEZIONE 3 — ADMIN GATE (ex shipmentFlowAdminGateStore)
	// ═══════════════════════════════════════════════════════════════════════

	const adminGateChallenge = ref<AdminGateChallenge | null>(null);

	function openAdminGate(payload: AdminGatePayload = {}) {
		adminGateChallenge.value = {
			targetPath: payload.targetPath || '/',
			lastValidRoute: payload.lastValidRoute || '/preventivo',
			reason: payload.reason || 'accesso fuori flusso',
			createdAt: Date.now(),
		};
	}

	function closeAdminGate() {
		adminGateChallenge.value = null;
	}

	return {
		// Sezione 1: dati spedizione
		stepNumber,
		isQuoteStarted,
		shipmentDetails,
		packages,
		totalPrice,
		servicesArray,
		contentDescription,
		pendingShipment,
		originAddressData,
		destinationAddressData,
		pickupDate,
		editingCartItemId,
		deliveryMode,
		selectedPudo,
		smsEmailNotification,
		serviceData,
		hasPersistedHydration,
		hydrateFromSession,

		// Sezione 2: orchestrazione preventivo
		messageError,
		isCalculating,
		isSyncingQuote,
		isAdvancingToServices,
		lastQuotedSignature,
		quoteTransitionLock,
		getAutoQuoteTimer,
		setAutoQuoteTimer,
		clearAutoQuoteTimer,
		getPendingQuotePromise,
		getPendingQuoteSignature,
		setPending,
		releasePendingIfMatches,
		isPendingSilent,
		nextRequestId,
		isLatestRequest,
		syncQuoteStateFromSession,
		resetQuoteState,
		continueToNextStep,

		// Sezione 3: admin gate
		adminGateChallenge,
		openAdminGate,
		closeAdminGate,
	};
});
