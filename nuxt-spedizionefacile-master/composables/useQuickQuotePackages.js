const QUICK_QUOTE_PACKAGE_TYPES = [
	{
		text: "Pacco",
		img: "pack.png",
		width: 43,
		height: 47,
	},
	{
		text: "Pallet",
		img: "pallet.png",
		width: 43,
		height: 42,
	},
	{
		text: "Valigia",
		img: "suitcase.png",
		width: 30,
		height: 52,
	},
];

let quickQuotePackageCounter = 0;
const QUICK_QUOTE_PACKAGE_ID_PREFIX = "qqp_";

const parseQuickQuotePackageId = (value) => {
	const normalized = String(value || "").trim();

	if (!normalized.startsWith(QUICK_QUOTE_PACKAGE_ID_PREFIX)) {
		return 0;
	}

	const rawIndex = normalized.slice(QUICK_QUOTE_PACKAGE_ID_PREFIX.length);
	const parsedIndex = Number.parseInt(rawIndex, 36);

	return Number.isFinite(parsedIndex) && parsedIndex > 0 ? parsedIndex : 0;
};

const syncQuickQuotePackageCounter = (packages = []) => {
	const highestKnownId = packages.reduce((maxValue, pack) => (
		Math.max(maxValue, parseQuickQuotePackageId(pack?._qid))
	), 0);

	if (highestKnownId > quickQuotePackageCounter) {
		quickQuotePackageCounter = highestKnownId;
	}
};

const createQuickQuotePackageId = () => {
	quickQuotePackageCounter += 1;
	return `${QUICK_QUOTE_PACKAGE_ID_PREFIX}${quickQuotePackageCounter.toString(36)}`;
};

const normalizePackageType = (value) =>
	String(value || "")
		.toLowerCase()
		.replace(/\s*#\d+\s*$/u, "")
		.trim();

const findPackageTypeConfig = (packageType) => {
	const normalized = normalizePackageType(
		typeof packageType === "string" ? packageType : packageType?.text,
	);

	return QUICK_QUOTE_PACKAGE_TYPES.find(
		(item) => normalizePackageType(item.text) === normalized,
	) || QUICK_QUOTE_PACKAGE_TYPES[0];
};

const ensurePackageDraftIdentity = (pack = {}, fallbackType = null) => {
	const config = findPackageTypeConfig(fallbackType || pack?.package_type);

	if (!pack._qid) {
		pack._qid = createQuickQuotePackageId();
	}

	pack.package_type = config.text;
	pack.img = config.img;
	pack.width = config.width;
	pack.height = config.height;

	return pack;
};

const buildPackageDraft = (packageType) => {
	const config = findPackageTypeConfig(packageType);

	return ensurePackageDraftIdentity({
		package_type: config.text,
		quantity: 1,
		img: config.img,
		width: config.width,
		height: config.height,
	});
};

const sanitizeQuantity = (value) => {
	const parsedValue = Number.parseInt(String(value ?? ""), 10);

	if (!Number.isFinite(parsedValue) || parsedValue < 1) {
		return 1;
	}

	return parsedValue;
};

export const useQuickQuotePackages = ({
	shipmentFlowStore,
	getWeightPrice,
	getVolumePrice,
	getCapSupplement,
	getEuropeQuote,
	priceBands,
}) => {
	const ensurePackagesIdentity = () => {
		syncQuickQuotePackageCounter(shipmentFlowStore?.packages);
		shipmentFlowStore?.packages.forEach((pack) => ensurePackageDraftIdentity(pack));
	};
	const isEuropeMonocollo = computed(() => {
		const europePricing = priceBands.value?.europe;
		if (!europePricing?.enabled) return false;
		const destinationCountryCode = String(shipmentFlowStore?.shipmentDetails.destination_country_code || "").trim().toUpperCase();
		return !!destinationCountryCode && destinationCountryCode !== "IT" && (europePricing.supported_country_codes || []).includes(destinationCountryCode);
	});

	const europeRestrictionMessage = computed(() => (
		isEuropeMonocollo.value
			? "Per le spedizioni in Europa e disponibile un solo collo per ordine."
			: ""
	));

	const enforceEuropeMonocollo = () => {
		if (!isEuropeMonocollo.value) return;
		if (shipmentFlowStore?.packages.length > 1) {
			shipmentFlowStore?.packages.splice(1);
		}
		const firstPack = shipmentFlowStore?.packages[0];
		if (firstPack) {
			firstPack.quantity = 1;
		}
	};

	const getPackVisual = (pack) => {
		const fallback = QUICK_QUOTE_PACKAGE_TYPES[0];
		const byType = QUICK_QUOTE_PACKAGE_TYPES.find(
			(item) => normalizePackageType(item.text) === normalizePackageType(pack?.package_type),
		);

		const img = pack?.img || byType?.img || fallback.img;
		const width = Number(pack?.width) > 0 ? Number(pack.width) : (byType?.width || fallback.width);
		const height = Number(pack?.height) > 0 ? Number(pack.height) : (byType?.height || fallback.height);

		return { img, width, height };
	};

	const recalculatePackagesTotal = () => {
		shipmentFlowStore.totalPrice = shipmentFlowStore?.packages.reduce(
			(total, pack) => total + (Number(pack?.single_price) || 0),
			0,
		);
	};

	const calcQuantity = (pack) => {
		if (isEuropeMonocollo.value) {
			pack.quantity = 1;
			recalculatePackagesTotal();
			return;
		}

		pack.quantity = sanitizeQuantity(pack.quantity);
		const originalSinglePrice = Number(pack.single_priceOrig) || 0;
		const quantity = sanitizeQuantity(pack.quantity);
		pack.single_price = originalSinglePrice * quantity;
		recalculatePackagesTotal();
	};

	const incrementQuantity = (pack) => {
		if (isEuropeMonocollo.value) {
			pack.quantity = 1;
			return;
		}
		pack.quantity = sanitizeQuantity(pack.quantity) + 1;
		calcQuantity(pack);
	};

	const decrementQuantity = (pack) => {
		if (isEuropeMonocollo.value) {
			pack.quantity = 1;
			return;
		}
		pack.quantity = Math.max(1, sanitizeQuantity(pack.quantity) - 1);
		calcQuantity(pack);
	};

	const checkPrices = (pack) => {
		if (isEuropeMonocollo.value) {
			const weight = Number(pack.weight);
			const firstSize = Number(pack.first_size);
			const secondSize = Number(pack.second_size);
			const thirdSize = Number(pack.third_size);
			if (!Number.isFinite(weight) || weight <= 0 || !Number.isFinite(firstSize) || firstSize <= 0 || !Number.isFinite(secondSize) || secondSize <= 0 || !Number.isFinite(thirdSize) || thirdSize <= 0) {
				pack.weight_price = null;
				pack.volume_price = null;
				pack.single_price = null;
				pack.single_priceOrig = null;
				return;
			}

			const volume = Number((((firstSize / 100) * (secondSize / 100) * (thirdSize / 100))).toFixed(6));
			const quote = getEuropeQuote(shipmentFlowStore?.shipmentDetails.destination_country_code, weight, volume);
			pack.europe_quote = quote;

			if (quote?.status === "priced") {
				const price = Number(quote.price || 0);
				pack.quantity = 1;
				pack.weight_price = price;
				pack.volume_price = price;
				pack.single_price = price;
				pack.single_priceOrig = price;
				recalculatePackagesTotal();
				return;
			}

			pack.weight_price = null;
			pack.volume_price = null;
			pack.single_price = null;
			pack.single_priceOrig = null;
			recalculatePackagesTotal();
			return;
		}

		let basePrice = null;

		const weightPrice = pack.weight_price != null ? Number(pack.weight_price) : null;
		const volumePrice = pack.volume_price != null ? Number(pack.volume_price) : null;

		if (weightPrice != null && !Number.isNaN(weightPrice) && volumePrice != null && !Number.isNaN(volumePrice)) {
			basePrice = Math.max(weightPrice, volumePrice);
		} else if (weightPrice != null && !Number.isNaN(weightPrice)) {
			basePrice = weightPrice;
		} else if (volumePrice != null && !Number.isNaN(volumePrice)) {
			basePrice = volumePrice;
		}

		if (basePrice == null || basePrice <= 0) return;

		const originCap = shipmentFlowStore?.shipmentDetails.origin_postal_code || "";
		const destCap = shipmentFlowStore?.shipmentDetails.destination_postal_code || "";
		const supplement = Number(getCapSupplement(originCap, destCap) || 0);

		pack.single_price = Number((basePrice + supplement).toFixed(2));
		pack.single_priceOrig = pack.single_price;
		calcQuantity(pack);
	};

	const calcPriceWithWeight = (pack) => {
		if (pack.weight != null) {
			pack.weight = String(pack.weight).replace(/[a-zA-Z]/g, "");
		}

		const weight = Number(pack.weight);
		if (!pack.weight || Number.isNaN(weight) || weight <= 0) {
			pack.weight_price = null;
			return;
		}

		pack.weight_price = getWeightPrice(weight);
		checkPrices(pack);
	};

	const calcPriceWithVolume = (pack) => {
		if (pack.first_size) {
			pack.first_size = String(pack.first_size).replace(/[^0-9]/g, "");
		}
		if (pack.second_size) {
			pack.second_size = String(pack.second_size).replace(/[^0-9]/g, "");
		}
		if (pack.third_size) {
			pack.third_size = String(pack.third_size).replace(/[^0-9]/g, "");
		}

		if (!pack.first_size || !pack.second_size || !pack.third_size) return;

		const firstSize = Number(pack.first_size);
		const secondSize = Number(pack.second_size);
		const thirdSize = Number(pack.third_size);

		if (firstSize <= 0 || secondSize <= 0 || thirdSize <= 0) {
			pack.volume_price = null;
			return;
		}

		const volume = (firstSize / 100) * (secondSize / 100) * (thirdSize / 100);
		pack.volume_price = getVolumePrice(Number(volume.toFixed(6)));
		checkPrices(pack);
	};

	const selectPackageType = (packageType) => {
		if (isEuropeMonocollo.value && shipmentFlowStore?.packages.length > 0) {
			return;
		}
		shipmentFlowStore?.packages.push(buildPackageDraft(packageType));
	};

	const addPackageInline = (packageType) => {
		if (isEuropeMonocollo.value) {
			enforceEuropeMonocollo();
			return;
		}
		const lastPackageType = shipmentFlowStore?.packages.at(-1)?.package_type;
		shipmentFlowStore?.packages.push(buildPackageDraft(packageType || lastPackageType));
	};

	const updatePackageType = (pack, packageType) => {
		ensurePackageDraftIdentity(pack, packageType);
	};

	const deletePack = async (targetPackId) => {
		const index = typeof targetPackId === "number"
			? targetPackId
			: shipmentFlowStore?.packages.findIndex((pack) => pack._qid === targetPackId);
		if (index < 0) return;
		shipmentFlowStore?.packages.splice(index, 1);

		if (shipmentFlowStore.packages.length === 0) {
			shipmentFlowStore?.packages.push(buildPackageDraft(QUICK_QUOTE_PACKAGE_TYPES[0]));
		}

		recalculatePackagesTotal();
	};

	watch(
		() => isEuropeMonocollo.value,
		(isEurope) => {
			if (!isEurope) return;
			enforceEuropeMonocollo();
			shipmentFlowStore?.packages.forEach((pack) => checkPrices(pack));
		},
		{ immediate: true },
	);

	watch(
		() => shipmentFlowStore?.packages,
		() => {
			ensurePackagesIdentity();
		},
		{ deep: true, immediate: true },
	);

	return {
		addPackageInline,
		calcPriceWithVolume,
		calcPriceWithWeight,
		calcQuantity,
		checkPrices,
		decrementQuantity,
		deletePack,
		ensurePackagesIdentity,
		getPackVisual,
		incrementQuantity,
		isEuropeMonocollo,
		europeRestrictionMessage,
		packageTypeList: QUICK_QUOTE_PACKAGE_TYPES,
		recalculatePackagesTotal,
		selectPackageType,
		updatePackageType,
	};
};
