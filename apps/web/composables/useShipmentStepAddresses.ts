/**
 * Shipment addresses step boundary: form state, saved-address lookup, persistence.
 */
import { computed, ref, watch, type ComputedRef, type Ref } from 'vue';

type AddressTarget = 'origin' | 'dest';

type StepAddress = {
	type?: string;
	full_name: string;
	additional_information: string;
	address: string;
	address_number: string;
	intercom_code: string;
	country: string;
	city: string;
	postal_code: string;
	province: string;
	telephone_number: string;
	email: string;
};

type AddressBookAddress = {
	name?: string;
	additional_information?: string;
	address?: string;
	address_number?: string;
	intercom_code?: string;
	country?: string;
	city?: string;
	postal_code?: string;
	province?: string;
	telephone_number?: string;
	email?: string;
};

type ShipmentSessionDetails = {
	origin_city?: string;
	origin_postal_code?: string;
	origin_country?: string;
	origin_province?: string;
	destination_city?: string;
	destination_postal_code?: string;
	destination_country?: string;
	destination_province?: string;
};

type SessionData = {
	shipment_details?: ShipmentSessionDetails;
	origin_address?: AddressBookAddress | null;
	destination_address?: AddressBookAddress | null;
};

type RouteLike = {
	path: string;
	fullPath: string;
	query: Record<string, string | string[] | null | undefined>;
};

type ShipmentFlowAddressStore = {
	originAddressData?: StepAddress | null;
	destinationAddressData?: StepAddress | null;
	shipmentDetails?: ShipmentSessionDetails | null;
};

type SanctumClient = <T = unknown>(url: string, options?: { method?: string; body?: unknown }) => Promise<T>;

type UseShipmentStepAddressesArgs = {
	shipmentFlowStore?: ShipmentFlowAddressStore | null;
	session: Ref<{ data?: SessionData } | null | undefined>;
	route: RouteLike;
	isAuthenticated: Ref<boolean>;
	sanctumClient: SanctumClient;
	deliveryMode: Ref<string>;
	submitError: Ref<string | null>;
};

const isRecord = (value: unknown): value is Record<string, unknown> => typeof value === 'object' && value !== null;

const baseAddress = (): StepAddress => ({
	full_name: '', additional_information: '', address: '', address_number: '', intercom_code: '',
	country: 'Italia', city: '', postal_code: '', province: '', telephone_number: '', email: '',
});

const fromSessionAddress = (a: AddressBookAddress | null | undefined, type: string): StepAddress => ({
	...baseAddress(), type,
	full_name: a?.name || '',
	additional_information: a?.additional_information || '',
	address: a?.address || '',
	address_number: a?.address_number || '',
	intercom_code: a?.intercom_code || '',
	country: a?.country || 'Italia',
	city: a?.city || '',
	postal_code: a?.postal_code || '',
	province: a?.province || '',
	telephone_number: a?.telephone_number || '',
	email: a?.email || '',
});

const initStepAddress = (
	stored: StepAddress | null | undefined,
	sessAddr: AddressBookAddress | null | undefined,
	details: ShipmentSessionDetails | undefined,
	type: string,
	cityKey: keyof ShipmentSessionDetails,
	pcKey: keyof ShipmentSessionDetails,
	countryKey: keyof ShipmentSessionDetails,
): StepAddress => {
	if (stored) return { ...stored };
	if (sessAddr) return fromSessionAddress(sessAddr, type);
	return {
		...baseAddress(), type,
		city: String(details?.[cityKey] || ''),
		postal_code: String(details?.[pcKey] || ''),
		country: String(details?.[countryKey] || 'Italia'),
	};
};

const hasMinAddress = (a: StepAddress) =>
	Boolean(a.full_name.trim() && a.address.trim() && a.city.trim() && a.postal_code.trim());

const normPostal = (a: Pick<StepAddress | AddressBookAddress, 'country' | 'postal_code'>) => {
	const country = String(a.country || 'Italia').trim().toLowerCase();
	const raw = String(a.postal_code || '');
	return country === 'italia' ? raw.replace(/\D/g, '') : raw.toUpperCase().replace(/[^A-Z0-9-\s]/g, '').trim();
};

const normText = (v: unknown) => String(v || '').trim().replace(/\s+/g, ' ').toLowerCase();
const normCountry = (v: unknown) => {
	const n = normText(v);
	return n === 'italia' || n === 'it' ? 'it' : n;
};
const normEmail = (v: unknown) => String(v || '').trim().toLowerCase();
const normPhone = (v: unknown) => String(v || '').replace(/\s+/g, '');

const toBookPayload = (a: StepAddress): Required<AddressBookAddress> & { number_type: string } => ({
	name: a.full_name.trim(),
	additional_information: a.additional_information || '',
	address: a.address.trim(),
	number_type: 'Numero Civico',
	address_number: a.address_number.trim(),
	intercom_code: a.intercom_code || '',
	country: a.country || 'Italia',
	city: a.city.trim(),
	postal_code: normPostal(a),
	province: a.province.trim(),
	telephone_number: a.telephone_number.trim(),
	email: a.email || '',
});

const bookSignature = (a: StepAddress | AddressBookAddress) => {
	const p = 'full_name' in a ? toBookPayload(a) : a;
	return JSON.stringify({
		name: normText(p.name),
		additional_information: normText(p.additional_information),
		address: normText(p.address),
		address_number: normText(p.address_number),
		intercom_code: normText(p.intercom_code),
		country: normCountry(p.country),
		city: normText(p.city),
		postal_code: normPostal(p),
		province: normText(p.province),
		telephone_number: normPhone(p.telephone_number),
		email: normEmail(p.email),
	});
};

const apiErrorMessage = (error: unknown, fallback: string) => {
	if (!isRecord(error)) return fallback;
	const data = isRecord(error.data) ? error.data : null;
	const msg = data?.message;
	return typeof msg === 'string' && msg ? msg : fallback;
};

export const useShipmentStepAddresses = ({
	shipmentFlowStore, session, route, isAuthenticated, sanctumClient, deliveryMode, submitError,
}: UseShipmentStepAddressesArgs) => {
	const storedOrigin = shipmentFlowStore?.originAddressData;
	const storedDest = shipmentFlowStore?.destinationAddressData;
	const sessionDetails = session.value?.data?.shipment_details;
	const sessionOrigin = session.value?.data?.origin_address;
	const sessionDest = session.value?.data?.destination_address;

	const originAddress = ref<StepAddress>(initStepAddress(storedOrigin, sessionOrigin, sessionDetails, 'Partenza', 'origin_city', 'origin_postal_code', 'origin_country'));
	const destinationAddress = ref<StepAddress>(initStepAddress(storedDest, sessionDest, sessionDetails, 'Destinazione', 'destination_city', 'destination_postal_code', 'destination_country'));

	const shouldAutoShowAddressFields = route.query.step === 'ritiro' || Boolean(storedOrigin || sessionOrigin);
	const savedAddresses = ref<AddressBookAddress[]>([]);
	const loadingSavedAddresses = ref(false);
	const showOriginAddressSelector = ref(false);
	const showDestAddressSelector = ref(false);
	const showOriginGuestPrompt = ref(false);
	const showDestGuestPrompt = ref(false);
	const showOriginConfigGuestPrompt = ref(false);
	const showDestConfigGuestPrompt = ref(false);
	const originSelectorRef = ref<HTMLElement | null>(null);
	const destSelectorRef = ref<HTMLElement | null>(null);
	const defaultDropdownRef = ref<HTMLElement | null>(null);
	const destDefaultDropdownRef = ref<HTMLElement | null>(null);

	const originFromSaved = ref(false);
	const destFromSaved = ref(false);
	const savingOriginAddress = ref(false);
	const savingDestAddress = ref(false);
	const originSaveSuccess = ref(false);
	const destSaveSuccess = ref(false);
	const originSavedSnapshot = ref<string | null>(null);
	const destSavedSnapshot = ref<string | null>(null);

	const requestedPath = () => {
		const r = Array.isArray(route.query.redirect) ? route.query.redirect[0] : route.query.redirect;
		if (route.path === '/autenticazione' || route.path === '/login' || route.path === '/registrazione') {
			return typeof r === 'string' && r.startsWith('/') ? r : '/';
		}
		return route.fullPath;
	};

	const authRedirectPath = computed(() => {
		const p = requestedPath();
		return p === '/' ? '/autenticazione' : `/autenticazione?redirect=${encodeURIComponent(p)}`;
	});

	const authRegisterRedirectPath = computed(() => {
		const p = requestedPath();
		const q = new URLSearchParams({ mode: 'register' });
		if (p !== '/') q.set('redirect', p);
		return `/autenticazione?${q.toString()}`;
	});

	const clearAddressSelectorsAndPrompts = () => {
		showOriginAddressSelector.value = false;
		showDestAddressSelector.value = false;
		showOriginGuestPrompt.value = false;
		showDestGuestPrompt.value = false;
		showOriginConfigGuestPrompt.value = false;
		showDestConfigGuestPrompt.value = false;
	};

	const loadSavedAddresses = async () => {
		if (!isAuthenticated.value || savedAddresses.value.length > 0) return;
		loadingSavedAddresses.value = true;
		try {
			const result = await sanctumClient<{ data?: AddressBookAddress[] }>('/api/user-addresses');
			savedAddresses.value = result.data || [];
		} catch { /* selector optional: never block checkout */ } finally {
			loadingSavedAddresses.value = false;
		}
	};

	watch(isAuthenticated, (auth) => {
		if (!auth) { savedAddresses.value = []; return; }
		void loadSavedAddresses();
	}, { immediate: true });

	const applySavedAddress = (address: AddressBookAddress, target: AddressTarget) => {
		const ref_ = target === 'origin' ? originAddress : destinationAddress;
		const contactOnly = target === 'dest' && deliveryMode.value === 'pudo';
		ref_.value.full_name = address.name || '';
		ref_.value.telephone_number = address.telephone_number || '';
		ref_.value.email = address.email || '';
		ref_.value.additional_information = address.additional_information || '';
		if (!contactOnly) {
			ref_.value.address = address.address || '';
			ref_.value.address_number = address.address_number || '';
			ref_.value.city = address.city || '';
			ref_.value.postal_code = address.postal_code || '';
			ref_.value.province = address.province || '';
			ref_.value.intercom_code = address.intercom_code || '';
		}
		const isOrigin = target === 'origin';
		(isOrigin ? showOriginAddressSelector : showDestAddressSelector).value = false;
		(isOrigin ? originFromSaved : destFromSaved).value = true;
		(isOrigin ? originSaveSuccess : destSaveSuccess).value = false;
		(isOrigin ? originSavedSnapshot : destSavedSnapshot).value = bookSignature(ref_.value);
	};

	const watchSavedDrift = (snapshot: Ref<string | null>, fromSaved: Ref<boolean>, success: Ref<boolean>) =>
		(newValue: StepAddress) => {
			if (!snapshot.value || bookSignature(newValue) === snapshot.value) return;
			fromSaved.value = false;
			success.value = false;
			snapshot.value = null;
		};

	watch(originAddress, watchSavedDrift(originSavedSnapshot, originFromSaved, originSaveSuccess), { deep: true });
	watch(destinationAddress, watchSavedDrift(destSavedSnapshot, destFromSaved, destSaveSuccess), { deep: true });

	const isDuplicate = (addr: Ref<StepAddress>) => () => {
		if (!isAuthenticated.value || !savedAddresses.value.length || !hasMinAddress(addr.value)) return false;
		const sig = bookSignature(addr.value);
		return savedAddresses.value.some((s) => bookSignature(s) === sig);
	};

	const isOriginDuplicateAddress = computed(isDuplicate(originAddress));
	const isDestDuplicateAddress = computed(isDuplicate(destinationAddress));

	const canSave = (addr: Ref<StepAddress>, fromSaved: Ref<boolean>, success: Ref<boolean>, dup: ComputedRef<boolean>) => () =>
		isAuthenticated.value && !fromSaved.value && !success.value && !dup.value && hasMinAddress(addr.value);

	const canSaveOriginAddress = computed(canSave(originAddress, originFromSaved, originSaveSuccess, isOriginDuplicateAddress));
	const canSaveDestAddress = computed(canSave(destinationAddress, destFromSaved, destSaveSuccess, isDestDuplicateAddress));

	const saveAddressToBook = async (target: AddressTarget) => {
		const isOrigin = target === 'origin';
		const address = (isOrigin ? originAddress : destinationAddress).value;
		const savingRef = isOrigin ? savingOriginAddress : savingDestAddress;
		const successRef = isOrigin ? originSaveSuccess : destSaveSuccess;
		const dupRef = isOrigin ? isOriginDuplicateAddress : isDestDuplicateAddress;
		const snapshotRef = isOrigin ? originSavedSnapshot : destSavedSnapshot;

		if (dupRef.value) {
			submitError.value = 'Questo indirizzo e gia presente tra gli indirizzi salvati.';
			return;
		}
		savingRef.value = true;
		try {
			await sanctumClient('/api/user-addresses', { method: 'POST', body: toBookPayload(address) });
			successRef.value = true;
			savedAddresses.value = [];
			snapshotRef.value = bookSignature(address);
			await loadSavedAddresses();
		} catch (error) {
			submitError.value = apiErrorMessage(error, "Errore nel salvataggio dell'indirizzo.");
		} finally {
			savingRef.value = false;
		}
	};

	const toggleAddressSelector = (target: AddressTarget) => {
		const isOrigin = target === 'origin';
		if (!isAuthenticated.value) {
			showOriginGuestPrompt.value = isOrigin ? !showOriginGuestPrompt.value : false;
			showDestGuestPrompt.value = !isOrigin ? !showDestGuestPrompt.value : false;
			showOriginAddressSelector.value = false;
			showDestAddressSelector.value = false;
			return;
		}
		void loadSavedAddresses();
		showOriginGuestPrompt.value = false;
		showDestGuestPrompt.value = false;
		showOriginAddressSelector.value = isOrigin ? !showOriginAddressSelector.value : false;
		showDestAddressSelector.value = !isOrigin ? !showDestAddressSelector.value : false;
	};

	const fillFromDetails = (d: ShipmentSessionDetails | null | undefined) => {
		if (!d) return;
		const o = originAddress.value;
		const t = destinationAddress.value;
		if (!o.city) o.city = d.origin_city || '';
		if (!o.postal_code) o.postal_code = d.origin_postal_code || '';
		if (!o.province && d.origin_province) o.province = d.origin_province;
		if (!t.city) t.city = d.destination_city || '';
		if (!t.postal_code) t.postal_code = d.destination_postal_code || '';
		if (!t.province && d.destination_province) t.province = d.destination_province;
	};

	watch(() => session.value?.data?.shipment_details, fillFromDetails, { immediate: true });

	watch(() => session.value?.data?.origin_address, (a) => {
		if (!a || originAddress.value.full_name) return;
		originAddress.value = fromSessionAddress(a, 'Partenza');
	}, { immediate: true });

	watch(() => session.value?.data?.destination_address, (a) => {
		if (!a || destinationAddress.value.full_name) return;
		destinationAddress.value = fromSessionAddress(a, 'Destinazione');
	}, { immediate: true });

	watch(() => shipmentFlowStore?.shipmentDetails, fillFromDetails, { immediate: true, deep: true });

	return {
		authRedirectPath,
		authRegisterRedirectPath,
		canSaveDestAddress,
		canSaveOriginAddress,
		clearAddressSelectorsAndPrompts,
		defaultDropdownRef,
		destDefaultDropdownRef,
		destFromSaved,
		destSaveSuccess,
		destSavedSnapshot,
		destSelectorRef,
		destinationAddress,
		loadSavedAddresses,
		loadingSavedAddresses,
		originAddress,
		originFromSaved,
		originSaveSuccess,
		originSavedSnapshot,
		originSelectorRef,
		applySavedAddress,
		saveAddressToBook,
		savedAddresses,
		savingDestAddress,
		savingOriginAddress,
		showDestAddressSelector,
		showDestConfigGuestPrompt,
		showDestGuestPrompt,
		showOriginAddressSelector,
		showOriginConfigGuestPrompt,
		showOriginGuestPrompt,
		shouldAutoShowAddressFields,
		toggleAddressSelector,
	};
};
