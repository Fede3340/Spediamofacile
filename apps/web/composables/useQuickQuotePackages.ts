/**
 * @file useQuickQuotePackages — Composable useQuickQuotePackages.
 */
const QUICK_QUOTE_PACKAGE_TYPES = [
	{ text: "Pacco", img: "pack.png", width: 43, height: 47 },
	{ text: "Pallet", img: "pallet.png", width: 43, height: 42 },
	{ text: "Valigia", img: "suitcase.png", width: 30, height: 52 },
] as const;

type QuickQuotePackageType = typeof QUICK_QUOTE_PACKAGE_TYPES[number];
type EuropeQuote = { status?: string; price?: number | string; message?: string; price_cents?: number | null; [key: string]: unknown };
type QuickQuotePackageDraft = {
	_qid?: string;
	package_type?: string;
	quantity?: number | string;
	img?: string;
	width?: number;
	height?: number;
	weight?: string | number | null;
	first_size?: string | number | null;
	second_size?: string | number | null;
	third_size?: string | number | null;
	weight_price?: number | null;
	volume_price?: number | null;
	single_price?: number | null;
	single_priceOrig?: number | null;
	europe_quote?: EuropeQuote | null;
};
type QuickQuoteShipmentFlowStore = {
	packages: QuickQuotePackageDraft[];
	totalPrice: number;
	shipmentDetails: { origin_postal_code?: string; destination_postal_code?: string; destination_country_code?: string };
};
type QuickQuotePriceBands = { europe?: { enabled?: boolean; supported_country_codes?: string[] } };
type UseQuickQuotePackagesArgs = {
	shipmentFlowStore: QuickQuoteShipmentFlowStore;
	getWeightPrice: (weight: number) => number | null;
	getVolumePrice: (volume: number) => number | null;
	getCapSupplement: (originCap: string, destCap: string) => number | string | null;
	getEuropeQuote: (countryCode: string, weight: number | string, volume: number | string) => EuropeQuote | null;
	priceBands: { value: QuickQuotePriceBands | null | undefined };
};

const DEFAULT_PACKAGE_TYPE: QuickQuotePackageType = QUICK_QUOTE_PACKAGE_TYPES[0];
const QUICK_QUOTE_PACKAGE_ID_PREFIX = "qqp_";
let quickQuotePackageCounter = 0;

const parseQuickQuotePackageId = (value: unknown): number => {
	const normalized = String(value || "").trim();
	if (!normalized.startsWith(QUICK_QUOTE_PACKAGE_ID_PREFIX)) return 0;
	const parsedIndex = Number.parseInt(normalized.slice(QUICK_QUOTE_PACKAGE_ID_PREFIX.length), 36);
	return Number.isFinite(parsedIndex) && parsedIndex > 0 ? parsedIndex : 0;
};

const syncQuickQuotePackageCounter = (packages: QuickQuotePackageDraft[] = []) => {
	const highestKnownId = packages.reduce((maxValue, pack) => Math.max(maxValue, parseQuickQuotePackageId(pack?._qid)), 0);
	if (highestKnownId > quickQuotePackageCounter) quickQuotePackageCounter = highestKnownId;
};

const createQuickQuotePackageId = (): string => {
	quickQuotePackageCounter += 1;
	return `${QUICK_QUOTE_PACKAGE_ID_PREFIX}${quickQuotePackageCounter.toString(36)}`;
};

const normalizePackageType = (value: unknown): string =>
	String(value || "").toLowerCase().replace(/\s*#\d+\s*$/u, "").trim();

const findPackageTypeConfig = (packageType: unknown): QuickQuotePackageType => {
	const typeText = packageType && typeof packageType === "object" && "text" in packageType ? (packageType as { text?: unknown }).text : packageType;
	const normalized = normalizePackageType(typeText);
	return QUICK_QUOTE_PACKAGE_TYPES.find((item) => normalizePackageType(item.text) === normalized) || DEFAULT_PACKAGE_TYPE;
};

const ensurePackageDraftIdentity = (pack: QuickQuotePackageDraft = {}, fallbackType: unknown = null): QuickQuotePackageDraft => {
	const config = findPackageTypeConfig(fallbackType || pack?.package_type);
	if (!pack._qid) pack._qid = createQuickQuotePackageId();
	pack.package_type = config.text;
	pack.img = config.img;
	pack.width = config.width;
	pack.height = config.height;
	return pack;
};

const buildPackageDraft = (packageType: unknown): QuickQuotePackageDraft => {
	const config = findPackageTypeConfig(packageType);
	return ensurePackageDraftIdentity({ package_type: config.text, quantity: 1, img: config.img, width: config.width, height: config.height });
};

const sanitizeQuantity = (value: unknown): number => {
	const parsedValue = Number.parseInt(String(value ?? ""), 10);
	return Number.isFinite(parsedValue) && parsedValue >= 1 ? parsedValue : 1;
};

const isPositiveNumber = (value: unknown): boolean => {
	const num = Number(value);
	return Number.isFinite(num) && num > 0;
};

const resetPackPricing = (pack: QuickQuotePackageDraft) => {
	pack.weight_price = null;
	pack.volume_price = null;
	pack.single_price = null;
	pack.single_priceOrig = null;
};

const stripDigits = (value: unknown): string => String(value).replace(/\D/g, "");

export const useQuickQuotePackages = ({ shipmentFlowStore, getWeightPrice, getVolumePrice, getCapSupplement, getEuropeQuote, priceBands }: UseQuickQuotePackagesArgs) => {
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

	const europeRestrictionMessage = computed(() => isEuropeMonocollo.value ? "Per le spedizioni in Europa e disponibile un solo collo per ordine." : "");

	const enforceEuropeMonocollo = () => {
		if (!isEuropeMonocollo.value) return;
		if (shipmentFlowStore?.packages.length > 1) shipmentFlowStore?.packages.splice(1);
		const firstPack = shipmentFlowStore?.packages[0];
		if (firstPack) firstPack.quantity = 1;
	};

	const getPackVisual = (pack: QuickQuotePackageDraft) => {
		const byType = QUICK_QUOTE_PACKAGE_TYPES.find((item) => normalizePackageType(item.text) === normalizePackageType(pack?.package_type));
		const img = pack?.img || byType?.img || DEFAULT_PACKAGE_TYPE.img;
		const width = Number(pack?.width) > 0 ? Number(pack.width) : (byType?.width || DEFAULT_PACKAGE_TYPE.width);
		const height = Number(pack?.height) > 0 ? Number(pack.height) : (byType?.height || DEFAULT_PACKAGE_TYPE.height);
		return { img, width, height };
	};

	const recalculatePackagesTotal = () => {
		shipmentFlowStore.totalPrice = shipmentFlowStore?.packages.reduce((total: number, pack: QuickQuotePackageDraft) => total + (Number(pack?.single_price) || 0), 0);
	};

	const calcQuantity = (pack: QuickQuotePackageDraft) => {
		if (isEuropeMonocollo.value) {
			pack.quantity = 1;
			recalculatePackagesTotal();
			return;
		}
		pack.quantity = sanitizeQuantity(pack.quantity);
		pack.single_price = (Number(pack.single_priceOrig) || 0) * sanitizeQuantity(pack.quantity);
		recalculatePackagesTotal();
	};

	const incrementQuantity = (pack: QuickQuotePackageDraft) => {
		if (isEuropeMonocollo.value) { pack.quantity = 1; return; }
		pack.quantity = sanitizeQuantity(pack.quantity) + 1;
		calcQuantity(pack);
	};

	const decrementQuantity = (pack: QuickQuotePackageDraft) => {
		if (isEuropeMonocollo.value) { pack.quantity = 1; return; }
		pack.quantity = Math.max(1, sanitizeQuantity(pack.quantity) - 1);
		calcQuantity(pack);
	};

	const applyEuropeQuote = (pack: QuickQuotePackageDraft) => {
		const weight = Number(pack.weight);
		const s1 = Number(pack.first_size);
		const s2 = Number(pack.second_size);
		const s3 = Number(pack.third_size);
		if (!isPositiveNumber(weight) || !isPositiveNumber(s1) || !isPositiveNumber(s2) || !isPositiveNumber(s3)) {
			resetPackPricing(pack);
			return;
		}
		const volume = Number(((s1 / 100) * (s2 / 100) * (s3 / 100)).toFixed(6));
		const quote = getEuropeQuote(String(shipmentFlowStore?.shipmentDetails.destination_country_code || "IT"), weight, volume);
		pack.europe_quote = quote;
		if (quote?.status === "priced") {
			const price = Number(quote.price || 0);
			pack.quantity = 1;
			pack.weight_price = price;
			pack.volume_price = price;
			pack.single_price = price;
			pack.single_priceOrig = price;
		} else {
			resetPackPricing(pack);
		}
		recalculatePackagesTotal();
	};

	const computeBasePrice = (pack: QuickQuotePackageDraft): number | null => {
		const weightPrice = pack.weight_price != null && !Number.isNaN(Number(pack.weight_price)) ? Number(pack.weight_price) : null;
		const volumePrice = pack.volume_price != null && !Number.isNaN(Number(pack.volume_price)) ? Number(pack.volume_price) : null;
		if (weightPrice != null && volumePrice != null) return Math.max(weightPrice, volumePrice);
		return weightPrice ?? volumePrice;
	};

	const checkPrices = (pack: QuickQuotePackageDraft) => {
		if (isEuropeMonocollo.value) return applyEuropeQuote(pack);
		const basePrice = computeBasePrice(pack);
		if (basePrice == null || basePrice <= 0) return;
		const supplement = Number(getCapSupplement(shipmentFlowStore?.shipmentDetails.origin_postal_code || "", shipmentFlowStore?.shipmentDetails.destination_postal_code || "") || 0);
		pack.single_price = Number((basePrice + supplement).toFixed(2));
		pack.single_priceOrig = pack.single_price;
		calcQuantity(pack);
	};

	const calcPriceWithWeight = (pack: QuickQuotePackageDraft) => {
		if (pack.weight != null) pack.weight = String(pack.weight).replace(/[a-z]/gi, "");
		const weight = Number(pack.weight);
		if (!pack.weight || !isPositiveNumber(weight)) {
			pack.weight_price = null;
			return;
		}
		pack.weight_price = getWeightPrice(weight);
		checkPrices(pack);
	};

	const calcPriceWithVolume = (pack: QuickQuotePackageDraft) => {
		if (pack.first_size) pack.first_size = stripDigits(pack.first_size);
		if (pack.second_size) pack.second_size = stripDigits(pack.second_size);
		if (pack.third_size) pack.third_size = stripDigits(pack.third_size);
		if (!pack.first_size || !pack.second_size || !pack.third_size) return;
		const s1 = Number(pack.first_size);
		const s2 = Number(pack.second_size);
		const s3 = Number(pack.third_size);
		if (s1 <= 0 || s2 <= 0 || s3 <= 0) {
			pack.volume_price = null;
			return;
		}
		const volume = (s1 / 100) * (s2 / 100) * (s3 / 100);
		pack.volume_price = getVolumePrice(Number(volume.toFixed(6)));
		checkPrices(pack);
	};

	const selectPackageType = (packageType: unknown) => {
		if (isEuropeMonocollo.value && shipmentFlowStore?.packages.length > 0) return;
		shipmentFlowStore?.packages.push(buildPackageDraft(packageType));
	};

	const addPackageInline = (packageType?: unknown) => {
		if (isEuropeMonocollo.value) { enforceEuropeMonocollo(); return; }
		shipmentFlowStore?.packages.push(buildPackageDraft(packageType || shipmentFlowStore?.packages.at(-1)?.package_type));
	};

	const updatePackageType = (pack: QuickQuotePackageDraft, packageType: unknown) => {
		ensurePackageDraftIdentity(pack, packageType);
	};

	const deletePack = async (targetPackId: number | string) => {
		const index = typeof targetPackId === "number" ? targetPackId : shipmentFlowStore?.packages.findIndex((pack) => pack._qid === targetPackId);
		if (index < 0) return;
		shipmentFlowStore?.packages.splice(index, 1);
		if (shipmentFlowStore.packages.length === 0) shipmentFlowStore?.packages.push(buildPackageDraft(QUICK_QUOTE_PACKAGE_TYPES[0]));
		recalculatePackagesTotal();
	};

	watch(() => isEuropeMonocollo.value, (isEurope) => {
		if (!isEurope) return;
		enforceEuropeMonocollo();
		shipmentFlowStore?.packages.forEach((pack) => checkPrices(pack));
	}, { immediate: true });

	watch(() => shipmentFlowStore?.packages, () => ensurePackagesIdentity(), { deep: true, immediate: true });

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
		packageTypeList: [...QUICK_QUOTE_PACKAGE_TYPES],
		recalculatePackagesTotal,
		selectPackageType,
		updatePackageType,
	};
};
