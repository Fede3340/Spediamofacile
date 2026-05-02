/**
 * @file useQuickQuoteLocations — Composable useQuickQuoteLocations.
 */
import { formatResolvedLocation } from "~/utils/quickQuoteHelpers";
import type { Ref } from "vue";
import type { LocationRecord } from "~/utils/location";

type QuickQuoteLocation = LocationRecord;
type ShipmentDetails = Record<string, string | undefined>;
type SmartValidationLike = {
	clearError: (fieldKey: string) => void;
	filterCAP: (value: string, options?: { countryCode?: string }) => string;
};
type LocationSearchApi = {
	cityMatchesQuery: (cityValue: unknown, rawQuery: unknown) => boolean;
	clearLocationSearchError?: () => void;
	getProvinceLabel: (location: LocationRecord | null | undefined) => string;
	locationKey: (location: LocationRecord | null | undefined) => string;
	normalizeLocationText: (value: unknown) => string;
	searchLocations: (query: unknown, limit?: number, countryCode?: string) => Promise<QuickQuoteLocation[]>;
	searchLocationsByCap: (cap: unknown, countryCode?: string) => Promise<QuickQuoteLocation[]>;
	searchLocationsByCity: (city: unknown, limit?: number, countryCode?: string) => Promise<QuickQuoteLocation[]>;
	sortCitySuggestionsByRelevance: (locations: QuickQuoteLocation[], query: unknown) => QuickQuoteLocation[];
	sortLocations: (a: QuickQuoteLocation, b: QuickQuoteLocation) => number;
};
type UseQuickQuoteLocationsArgs = {
	shipmentDetails: ShipmentDetails;
	search: LocationSearchApi;
	smartValidation: SmartValidationLike;
	onCapInputSmart: (fieldKey: string, cap: string, countryCode: string) => void;
	debounceMs?: number;
};
type TimeoutHandle = ReturnType<typeof setTimeout> | null;
type ParsedLocationDraft = {
	rawQuery: string;
	cityPart: string;
	normalizedCap: string;
	queryForSearch: string;
	isCombined: boolean;
	isCapOnly: boolean;
};
type SideKeys = {
	cityKey: string;
	capKey: string;
	provinceKey: string;
	fieldKey: string;
	countryCodeKey: string;
	countryNameKey: string;
};

const clearTimer = (timer: TimeoutHandle) => { if (timer) clearTimeout(timer); };

export const useQuickQuoteLocations = ({
	shipmentDetails,
	search,
	smartValidation,
	onCapInputSmart,
	debounceMs = 180,
}: UseQuickQuoteLocationsArgs) => {
	const {
		cityMatchesQuery, clearLocationSearchError, getProvinceLabel, locationKey,
		normalizeLocationText, searchLocations, searchLocationsByCap, searchLocationsByCity,
		sortCitySuggestionsByRelevance, sortLocations,
	} = search;

	const isCapQuery = (value: unknown = ""): boolean => /^\d+$/.test(String(value).trim());
	const normalizeCap = (value: unknown = "", countryCode = "IT"): string =>
		smartValidation.filterCAP(String(value).trim(), { countryCode });
	const resolveCountryCode = (location: LocationRecord): string =>
		String(location?.country_code || "IT").trim().toUpperCase() || "IT";
	const resolveCountryName = (location: LocationRecord): string =>
		String(location?.country_name || (resolveCountryCode(location) === "IT" ? "Italia" : resolveCountryCode(location))).trim();

	const stripTrailingSeparator = (value: string): string => {
		const trimmed = value.trim();
		const last = trimmed.at(-1) || "";
		return ["/", ",", ";", "-", "·", "•"].includes(last) ? trimmed.slice(0, -1).trim() : trimmed;
	};

	const splitTrailingCap = (value: string): { cityPart: string; capPart: string } | null => {
		const m = value.match(/\d{3,5}$/u);
		if (!m) return null;
		const cityPart = stripTrailingSeparator(value.slice(0, m.index).trim());
		return cityPart ? { cityPart, capPart: m[0] } : null;
	};

	const findSeparatorIndex = (value: string): number => {
		const a = value.indexOf("·");
		const b = value.indexOf("•");
		if (a === -1) return b;
		if (b === -1) return a;
		return Math.min(a, b);
	};

	const parseLocationDraft = (value: unknown = "", countryCode = "IT"): ParsedLocationDraft => {
		const rawQuery = String(value || "").trim();
		const empty = { rawQuery: "", cityPart: "", normalizedCap: "", queryForSearch: "", isCombined: false, isCapOnly: false };
		if (!rawQuery) return empty;

		const combined = splitTrailingCap(rawQuery);
		if (combined) {
			const normalizedCap = normalizeCap(combined.capPart, countryCode);
			return {
				rawQuery, cityPart: combined.cityPart, normalizedCap,
				queryForSearch: normalizedCap || combined.cityPart,
				isCombined: Boolean(combined.cityPart && normalizedCap), isCapOnly: false,
			};
		}
		if (isCapQuery(rawQuery)) {
			const normalizedCap = normalizeCap(rawQuery, countryCode);
			return { rawQuery, cityPart: "", normalizedCap, queryForSearch: normalizedCap, isCombined: false, isCapOnly: true };
		}
		return { rawQuery, cityPart: "", normalizedCap: "", queryForSearch: rawQuery, isCombined: false, isCapOnly: false };
	};

	const formatDisplay = (city: unknown = "", cap: unknown = ""): string => {
		const c = String(city || "").trim();
		const p = String(cap || "").trim();
		if (c && p) return `${c} · ${p}`;
		return c || p || "";
	};

	const writeShipment = (keys: SideKeys, city: string, cap: string, code: string, name: string) => {
		shipmentDetails[keys.cityKey] = city;
		shipmentDetails[keys.capKey] = cap;
		shipmentDetails[keys.countryCodeKey] = code;
		shipmentDetails[keys.countryNameKey] = name;
	};

	const applyQueryDraft = (queryRef: Ref<string>, keys: SideKeys): string => {
		const code = String(shipmentDetails[keys.countryCodeKey] || "IT").trim().toUpperCase() || "IT";
		const name = String(shipmentDetails[keys.countryNameKey] || (code === "IT" ? "Italia" : code)).trim();
		const parsed = parseLocationDraft(queryRef.value, code);
		const { rawQuery } = parsed;
		clearLocationSearchError?.();

		if (!rawQuery) {
			writeShipment(keys, "", "", code, name);
			smartValidation.clearError(keys.fieldKey);
			return "";
		}
		// Formato combinato "Città · CAP" già selezionato: estrai parte città in editing.
		const sepIdx = findSeparatorIndex(rawQuery);
		if (sepIdx > -1) {
			const cityPart = rawQuery.slice(0, sepIdx).trim();
			const capPart = rawQuery.slice(sepIdx + 1).trim();
			writeShipment(keys, cityPart, capPart, code, name);
			smartValidation.clearError(keys.fieldKey);
			return cityPart;
		}
		if (parsed.isCombined) {
			writeShipment(keys, parsed.cityPart, parsed.normalizedCap, code, name);
			onCapInputSmart(keys.fieldKey, parsed.normalizedCap, code);
			smartValidation.clearError(keys.fieldKey);
			queryRef.value = formatDisplay(parsed.cityPart, parsed.normalizedCap);
			return parsed.normalizedCap;
		}
		if (isCapQuery(rawQuery)) {
			const filtered = normalizeCap(rawQuery, code);
			queryRef.value = filtered;
			writeShipment(keys, "", filtered, code, name);
			onCapInputSmart(keys.fieldKey, filtered, code);
			return filtered;
		}
		writeShipment(keys, rawQuery, "", code, name);
		smartValidation.clearError(keys.fieldKey);
		return rawQuery;
	};

	const getCitySuggestions = async (query: string, countryCode = "IT"): Promise<QuickQuoteLocation[]> => {
		if (!query || query.length < 2) return [];
		let results = await searchLocationsByCity(query, 200, countryCode);
		if (!results.length) results = await searchLocations(query, 500, countryCode);
		return sortCitySuggestionsByRelevance(
			results.filter((l) => cityMatchesQuery(l.place_name, query)).sort(sortLocations), query,
		);
	};

	const getCapSuggestions = async (capQuery: string, linkedCity = "", countryCode = "IT"): Promise<QuickQuoteLocation[]> => {
		if (!capQuery || capQuery.length < 3) return [];
		const results = capQuery.length === 5
			? await searchLocationsByCap(capQuery, countryCode)
			: await searchLocations(capQuery, 500, countryCode);
		return results
			.filter((l) => String(l.postal_code || "").startsWith(capQuery))
			.filter((l) => !linkedCity || cityMatchesQuery(l.place_name, linkedCity))
			.sort(sortLocations);
	};

	const getSuggestionsForQuery = async (queryValue: string, linkedCity = "", countryCode = "IT"): Promise<QuickQuoteLocation[]> => {
		const parsed = parseLocationDraft(queryValue, countryCode);
		if (!parsed.rawQuery) return [];
		if (parsed.isCombined || parsed.isCapOnly) {
			return getCapSuggestions(parsed.normalizedCap, parsed.cityPart || linkedCity, countryCode);
		}
		return getCitySuggestions(parsed.queryForSearch, countryCode);
	};

	const findAutoResolved = (queryValue: string, suggestions: QuickQuoteLocation[] = [], countryCode = "IT"): QuickQuoteLocation | null => {
		const parsed = parseLocationDraft(queryValue, countryCode);
		if (!parsed.rawQuery || !suggestions.length) return null;
		if (parsed.isCombined) {
			const nCity = normalizeLocationText(parsed.cityPart);
			return suggestions.find((l) =>
				String(l.postal_code || "") === parsed.normalizedCap
				&& normalizeLocationText(l.place_name) === nCity,
			) || null;
		}
		if (parsed.isCapOnly) {
			return suggestions.find((l) => String(l.postal_code || "") === parsed.normalizedCap) || null;
		}
		const nQuery = normalizeLocationText(parsed.queryForSearch);
		const exact = suggestions.filter((l) => normalizeLocationText(l.place_name) === nQuery);
		return exact.length === 1 ? exact[0] ?? null : null;
	};

	// Side factory: tutto lo stato + handlers per "origin" o "destination".
	const createSide = (keys: SideKeys) => {
		const queryRef = ref("");
		const suggestions = ref<QuickQuoteLocation[]>([]);
		const showSuggestions = ref(false);
		let hideTimeout: TimeoutHandle = null;
		let searchTimeout: TimeoutHandle = null;
		let searchSeq = 0;

		const linkedCity = () => shipmentDetails[keys.cityKey];
		const countryCode = () => shipmentDetails[keys.countryCodeKey];

		const hide = () => {
			clearTimer(hideTimeout);
			hideTimeout = setTimeout(() => { showSuggestions.value = false; hideTimeout = null; }, 200);
		};

		const select = (location: QuickQuoteLocation) => {
			clearLocationSearchError?.();
			shipmentDetails[keys.cityKey] = String(location.place_name || "");
			shipmentDetails[keys.capKey] = String(location.postal_code || "");
			// L'API ritorna anche `province` (es. "MI"); salviamola per evitare reinserimento.
			shipmentDetails[keys.provinceKey] = String(location.province || "").trim().toUpperCase();
			shipmentDetails[keys.countryCodeKey] = resolveCountryCode(location);
			shipmentDetails[keys.countryNameKey] = resolveCountryName(location);
			queryRef.value = formatResolvedLocation(location.place_name, location.postal_code);
			onCapInputSmart(keys.fieldKey, shipmentDetails[keys.capKey] || "", shipmentDetails[keys.countryCodeKey] || "IT");
			smartValidation.clearError(keys.fieldKey);
			clearTimer(hideTimeout);
			showSuggestions.value = false;
		};

		const tooShort = (q: string) => (isCapQuery(q) && q.length < 3) || (!isCapQuery(q) && q.length < 2);

		const runSearch = async (query: string): Promise<{ seq: number; results: QuickQuoteLocation[] } | null> => {
			const seq = ++searchSeq;
			if (tooShort(query)) return { seq, results: [] };
			const results = await getSuggestionsForQuery(query, linkedCity(), countryCode());
			return { seq, results };
		};

		const update = async () => {
			const query = applyQueryDraft(queryRef, keys);
			const seq = ++searchSeq;
			if (tooShort(query)) {
				suggestions.value = [];
				showSuggestions.value = false;
				return;
			}
			const results = await getSuggestionsForQuery(query, linkedCity(), countryCode());
			if (seq !== searchSeq) return;
			suggestions.value = results;
			showSuggestions.value = results.length > 0;
		};

		const onInput = () => {
			clearLocationSearchError?.();
			clearTimer(searchTimeout);
			clearTimer(hideTimeout);
			searchTimeout = setTimeout(update, debounceMs);
		};

		const onFocus = async () => {
			clearLocationSearchError?.();
			clearTimer(hideTimeout);
			const query = String(queryRef.value || "").trim()
				|| formatDisplay(shipmentDetails[keys.cityKey], shipmentDetails[keys.capKey]);
			const out = await runSearch(query);
			if (!out || out.seq !== searchSeq) return;
			suggestions.value = out.results;
			showSuggestions.value = out.results.length > 0;
		};

		const settle = async () => {
			const query = String(queryRef.value || "").trim();
			if (!query) { hide(); return; }
			const results = await getSuggestionsForQuery(query, linkedCity(), countryCode());
			const resolved = findAutoResolved(query, results, countryCode());
			if (resolved) { select(resolved); return; }
			hide();
		};

		watch(
			() => [shipmentDetails[keys.cityKey], shipmentDetails[keys.capKey]],
			([city, cap]) => {
				const formatted = formatDisplay(city, cap);
				if (formatted !== queryRef.value) queryRef.value = formatted;
			},
			{ immediate: true },
		);

		const cleanup = () => {
			clearTimer(searchTimeout);
			clearTimer(hideTimeout);
		};

		return { queryRef, suggestions, showSuggestions, hide, select, onInput, onFocus, settle, cleanup };
	};

	const origin = createSide({
		cityKey: "origin_city", capKey: "origin_postal_code", provinceKey: "origin_province",
		fieldKey: "origin_cap", countryCodeKey: "origin_country_code", countryNameKey: "origin_country",
	});
	const dest = createSide({
		cityKey: "destination_city", capKey: "destination_postal_code", provinceKey: "destination_province",
		fieldKey: "dest_cap", countryCodeKey: "destination_country_code", countryNameKey: "destination_country",
	});

	onBeforeUnmount(() => { origin.cleanup(); dest.cleanup(); });

	return {
		destQuery: dest.queryRef,
		destSuggestions: dest.suggestions,
		getProvinceLabel,
		hideDestSuggestions: dest.hide,
		hideOriginSuggestions: origin.hide,
		locationKey,
		onDestQueryFocus: dest.onFocus,
		onDestQueryInput: dest.onInput,
		onOriginQueryFocus: origin.onFocus,
		onOriginQueryInput: origin.onInput,
		originQuery: origin.queryRef,
		originSuggestions: origin.suggestions,
		selectDestLocation: dest.select,
		selectOriginLocation: origin.select,
		settleDestQuery: dest.settle,
		settleOriginQuery: origin.settle,
		showDestSuggestions: dest.showSuggestions,
		showOriginSuggestions: origin.showSuggestions,
	};
};
