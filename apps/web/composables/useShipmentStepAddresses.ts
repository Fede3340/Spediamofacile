/**
 * @file useShipmentStepAddresses — Composable useShipmentStepAddresses.
 */
const createBaseAddress = () => ({
	full_name: "",
	additional_information: "",
	address: "",
	address_number: "",
	intercom_code: "",
	country: "Italia",
	province: "",
	telephone_number: "",
	email: "",
});

const fromSessionAddress = (sessionAddress, type) => ({
	...createBaseAddress(),
	type,
	full_name: sessionAddress?.name || "",
	additional_information: sessionAddress?.additional_information || "",
	address: sessionAddress?.address || "",
	address_number: sessionAddress?.address_number || "",
	intercom_code: sessionAddress?.intercom_code || "",
	country: sessionAddress?.country || "Italia",
	city: sessionAddress?.city || "",
	postal_code: sessionAddress?.postal_code || "",
	province: sessionAddress?.province || "",
	telephone_number: sessionAddress?.telephone_number || "",
	email: sessionAddress?.email || "",
});

const createStepAddress = ({ storedAddress, sessionAddress, sessionDetails, type, cityKey, postalCodeKey, countryKey }) => {
	if (storedAddress) return { ...storedAddress };
	if (sessionAddress) return fromSessionAddress(sessionAddress, type);

	return {
		...createBaseAddress(),
		type,
		city: sessionDetails?.[cityKey] || "",
		postal_code: sessionDetails?.[postalCodeKey] || "",
		country: sessionDetails?.[countryKey] || "Italia",
	};
};

const hasMinimumAddressData = (address) => {
	return !!(
		address?.full_name?.trim()
		&& address?.address?.trim()
		&& address?.city?.trim()
		&& address?.postal_code?.trim()
	);
};

const normalizePostalCode = (address) => {
	const country = String(address?.country || "Italia").trim().toLowerCase();
	const rawPostalCode = String(address?.postal_code || "");
	if (country === "italia") {
		return rawPostalCode.replace(/[^0-9]/g, "");
	}

	return rawPostalCode.toUpperCase().replace(/[^A-Z0-9-\s]/g, "").trim();
};

const normalizeAddressText = (value) => String(value || "").trim().replace(/\s+/g, " ").toLowerCase();

const normalizeAddressCountry = (value) => {
	const normalized = normalizeAddressText(value);
	if (normalized === "italia" || normalized === "it") {
		return "it";
	}

	return normalized;
};

const normalizeAddressEmail = (value) => String(value || "").trim().toLowerCase();

const normalizeAddressPhone = (value) => String(value || "").replace(/\s+/g, "");

const getAddressBookSignature = (address) => {
	const payload = "name" in address ? address : toAddressBookPayload(address);

	return JSON.stringify({
		name: normalizeAddressText(payload.name),
		additional_information: normalizeAddressText(payload.additional_information),
		address: normalizeAddressText(payload.address),
		address_number: normalizeAddressText(payload.address_number),
		intercom_code: normalizeAddressText(payload.intercom_code),
		country: normalizeAddressCountry(payload.country),
		city: normalizeAddressText(payload.city),
		postal_code: normalizePostalCode(payload),
		province: normalizeAddressText(payload.province),
		telephone_number: normalizeAddressPhone(payload.telephone_number),
		email: normalizeAddressEmail(payload.email),
	});
};

const toAddressBookPayload = (address) => ({
	name: address.full_name?.trim() || "",
	additional_information: address.additional_information || "",
	address: address.address?.trim() || "",
	number_type: "Numero Civico",
	address_number: address.address_number?.trim() || "",
	intercom_code: address.intercom_code || "",
	country: address.country || "Italia",
	city: address.city?.trim() || "",
	postal_code: normalizePostalCode(address),
	province: address.province?.trim() || "",
	telephone_number: address.telephone_number?.trim() || "",
	email: address.email || "",
});

export const useShipmentStepAddresses = ({
	shipmentFlowStore,
	session,
	route,
	isAuthenticated,
	sanctumClient,
	deliveryMode,
	submitError,
}) => {
	const storedOrigin = shipmentFlowStore?.originAddressData;
	const storedDest = shipmentFlowStore?.destinationAddressData;
	const sessionDetails = session.value?.data?.shipment_details;
	const sessionOriginAddress = session.value?.data?.origin_address;
	const sessionDestinationAddress = session.value?.data?.destination_address;

	const originAddress = ref(createStepAddress({
		storedAddress: storedOrigin,
		sessionAddress: sessionOriginAddress,
		sessionDetails,
		type: "Partenza",
		cityKey: "origin_city",
		postalCodeKey: "origin_postal_code",
		countryKey: "origin_country",
	}));

	const destinationAddress = ref(createStepAddress({
		storedAddress: storedDest,
		sessionAddress: sessionDestinationAddress,
		sessionDetails,
		type: "Destinazione",
		cityKey: "destination_city",
		postalCodeKey: "destination_postal_code",
		countryKey: "destination_country",
	}));

	const shouldAutoShowAddressFields = route.query.step === "ritiro" || !!storedOrigin || !!sessionOriginAddress;

	const savedAddresses = ref([]);
	const loadingSavedAddresses = ref(false);
	const showOriginAddressSelector = ref(false);
	const showDestAddressSelector = ref(false);
	const showOriginGuestPrompt = ref(false);
	const showDestGuestPrompt = ref(false);
	const showOriginConfigGuestPrompt = ref(false);
	const showDestConfigGuestPrompt = ref(false);
	const originSelectorRef = ref(null);
	const destSelectorRef = ref(null);
	const defaultDropdownRef = ref(null);
	const destDefaultDropdownRef = ref(null);

	const originFromSaved = ref(false);
	const destFromSaved = ref(false);
	const savingOriginAddress = ref(false);
	const savingDestAddress = ref(false);
	const originSaveSuccess = ref(false);
	const destSaveSuccess = ref(false);
	const originSavedSnapshot = ref(null);
	const destSavedSnapshot = ref(null);

	const getRequestedPath = () => {
		const redirectQuery = Array.isArray(route.query.redirect)
			? route.query.redirect[0]
			: route.query.redirect;

		if (route.path === "/autenticazione" || route.path === "/login" || route.path === "/registrazione") {
			if (typeof redirectQuery === "string" && redirectQuery.startsWith("/")) {
				return redirectQuery;
			}
			return "/";
		}

		return route.fullPath;
	};

	const authRedirectPath = computed(() => {
		const requestedPath = getRequestedPath();
		return requestedPath === "/"
			? "/autenticazione"
			: `/autenticazione?redirect=${encodeURIComponent(requestedPath)}`;
	});

	const authRegisterRedirectPath = computed(() => {
		const requestedPath = getRequestedPath();
		const query = new URLSearchParams({ mode: "register" });
		if (requestedPath !== "/") {
			query.set("redirect", requestedPath);
		}
		return `/autenticazione?${query.toString()}`;
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
		if (!isAuthenticated.value) return;
		if (savedAddresses.value.length > 0) return;

		loadingSavedAddresses.value = true;
		try {
			const result = await sanctumClient("/api/user-addresses");
			savedAddresses.value = result?.data || [];
		} catch (error) {
		} finally {
			loadingSavedAddresses.value = false;
		}
	};

	watch(isAuthenticated, (authenticated) => {
		if (!authenticated) {
			savedAddresses.value = [];
			return;
		}

		void loadSavedAddresses();
	}, { immediate: true });

	const applySavedAddress = (address, target) => {
		const addressRef = target === "origin" ? originAddress : destinationAddress;
		const isDestPudoContactOnly = target === "dest" && deliveryMode.value === "pudo";

		addressRef.value.full_name = address.name || "";
		addressRef.value.telephone_number = address.telephone_number || "";
		addressRef.value.email = address.email || "";
		addressRef.value.additional_information = address.additional_information || "";

		if (!isDestPudoContactOnly) {
			addressRef.value.address = address.address || "";
			addressRef.value.address_number = address.address_number || "";
			addressRef.value.city = address.city || "";
			addressRef.value.postal_code = address.postal_code || "";
			addressRef.value.province = address.province || "";
			addressRef.value.intercom_code = address.intercom_code || "";
		}

		if (target === "origin") {
			showOriginAddressSelector.value = false;
			originFromSaved.value = true;
			originSaveSuccess.value = false;
			originSavedSnapshot.value = getAddressBookSignature(addressRef.value);
			return;
		}

		showDestAddressSelector.value = false;
		destFromSaved.value = true;
		destSaveSuccess.value = false;
		destSavedSnapshot.value = getAddressBookSignature(addressRef.value);
	};

	watch(originAddress, (newValue) => {
		if (!originSavedSnapshot.value) return;
		if (getAddressBookSignature(newValue) === originSavedSnapshot.value) return;

		originFromSaved.value = false;
		originSaveSuccess.value = false;
		originSavedSnapshot.value = null;
	}, { deep: true });

	watch(destinationAddress, (newValue) => {
		if (!destSavedSnapshot.value) return;
		if (getAddressBookSignature(newValue) === destSavedSnapshot.value) return;

		destFromSaved.value = false;
		destSaveSuccess.value = false;
		destSavedSnapshot.value = null;
	}, { deep: true });

	const isOriginDuplicateAddress = computed(() => {
		if (!isAuthenticated.value || !savedAddresses.value.length) return false;
		if (!hasMinimumAddressData(originAddress.value)) return false;

		const originSignature = getAddressBookSignature(originAddress.value);
		return savedAddresses.value.some((savedAddress) => getAddressBookSignature(savedAddress) === originSignature);
	});

	const isDestDuplicateAddress = computed(() => {
		if (!isAuthenticated.value || !savedAddresses.value.length) return false;
		if (!hasMinimumAddressData(destinationAddress.value)) return false;

		const destinationSignature = getAddressBookSignature(destinationAddress.value);
		return savedAddresses.value.some((savedAddress) => getAddressBookSignature(savedAddress) === destinationSignature);
	});

	const canSaveOriginAddress = computed(() => {
		if (!isAuthenticated.value || originFromSaved.value || originSaveSuccess.value || isOriginDuplicateAddress.value) return false;
		return hasMinimumAddressData(originAddress.value);
	});

	const canSaveDestAddress = computed(() => {
		if (!isAuthenticated.value || destFromSaved.value || destSaveSuccess.value || isDestDuplicateAddress.value) return false;
		return hasMinimumAddressData(destinationAddress.value);
	});

	const saveAddressToBook = async (target) => {
		const address = target === "origin" ? originAddress.value : destinationAddress.value;
		const savingRef = target === "origin" ? savingOriginAddress : savingDestAddress;
		const successRef = target === "origin" ? originSaveSuccess : destSaveSuccess;
		const duplicateRef = target === "origin" ? isOriginDuplicateAddress : isDestDuplicateAddress;
		const snapshotRef = target === "origin" ? originSavedSnapshot : destSavedSnapshot;

		if (duplicateRef.value) {
			submitError.value = "Questo indirizzo è già presente tra gli indirizzi salvati.";
			return;
		}

		savingRef.value = true;
		try {
			await sanctumClient("/api/user-addresses", {
				method: "POST",
				body: toAddressBookPayload(address),
			});
			successRef.value = true;
			savedAddresses.value = [];
			snapshotRef.value = getAddressBookSignature(address);
			await loadSavedAddresses();
		} catch (error) {
			submitError.value = error?.data?.message || "Errore nel salvataggio dell'indirizzo.";
		} finally {
			savingRef.value = false;
		}
	};

	const toggleAddressSelector = (target) => {
		if (!isAuthenticated.value) {
			if (target === "origin") {
				showOriginGuestPrompt.value = !showOriginGuestPrompt.value;
				showDestGuestPrompt.value = false;
				showOriginAddressSelector.value = false;
				showDestAddressSelector.value = false;
			} else {
				showDestGuestPrompt.value = !showDestGuestPrompt.value;
				showOriginGuestPrompt.value = false;
				showOriginAddressSelector.value = false;
				showDestAddressSelector.value = false;
			}
			return;
		}

		void loadSavedAddresses();
		showOriginGuestPrompt.value = false;
		showDestGuestPrompt.value = false;

		if (target === "origin") {
			showOriginAddressSelector.value = !showOriginAddressSelector.value;
			showDestAddressSelector.value = false;
			return;
		}

		showDestAddressSelector.value = !showDestAddressSelector.value;
		showOriginAddressSelector.value = false;
	};

	watch(() => session.value?.data?.shipment_details, (details) => {
		if (!details) return;
		if (!originAddress.value.city) originAddress.value.city = details.origin_city;
		if (!originAddress.value.postal_code) originAddress.value.postal_code = details.origin_postal_code;
		if (!originAddress.value.province && details.origin_province) originAddress.value.province = details.origin_province;
		if (!destinationAddress.value.city) destinationAddress.value.city = details.destination_city;
		if (!destinationAddress.value.postal_code) destinationAddress.value.postal_code = details.destination_postal_code;
		if (!destinationAddress.value.province && details.destination_province) destinationAddress.value.province = details.destination_province;
	}, { immediate: true });

	watch(() => session.value?.data?.origin_address, (address) => {
		if (!address || originAddress.value.full_name) return;
		originAddress.value = fromSessionAddress(address, "Partenza");
	}, { immediate: true });

	watch(() => session.value?.data?.destination_address, (address) => {
		if (!address || destinationAddress.value.full_name) return;
		destinationAddress.value = fromSessionAddress(address, "Destinazione");
	}, { immediate: true });

	watch(() => shipmentFlowStore?.shipmentDetails, (shipmentDetails) => {
		if (!shipmentDetails) return;
		if (shipmentDetails.origin_city && !originAddress.value.city) originAddress.value.city = shipmentDetails.origin_city;
		if (shipmentDetails.origin_postal_code && !originAddress.value.postal_code) originAddress.value.postal_code = shipmentDetails.origin_postal_code;
		if (shipmentDetails.origin_province && !originAddress.value.province) originAddress.value.province = shipmentDetails.origin_province;
		if (shipmentDetails.destination_city && !destinationAddress.value.city) destinationAddress.value.city = shipmentDetails.destination_city;
		if (shipmentDetails.destination_postal_code && !destinationAddress.value.postal_code) destinationAddress.value.postal_code = shipmentDetails.destination_postal_code;
		if (shipmentDetails.destination_province && !destinationAddress.value.province) destinationAddress.value.province = shipmentDetails.destination_province;
	}, { immediate: true, deep: true });

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
