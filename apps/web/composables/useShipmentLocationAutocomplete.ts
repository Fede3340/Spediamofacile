// Autocomplete città/CAP/provincia per origin/dest del form spedizione. Helpers puri in utils/location.
import { sanitizeFullName, formatCitySuggestionLabel, formatCapSuggestionLabel, isLocationCoherent, extractUniqueProvinces } from '~/utils/location';
import type { Ref } from 'vue';

type AddressSection = 'origin' | 'dest';
type AddressField = 'full_name' | 'city' | 'postal_code' | 'telephone_number' | 'email' | 'province' | string;
type LocationSuggestion = { place_name: string; postal_code: string; province?: string; province_name?: string; country_name?: string; [key: string]: unknown };
type ShipmentAddress = { full_name?: string; city?: string; postal_code?: string; country?: string; province?: string; telephone_number?: string; email?: string; [key: string]: string | undefined };
type SectionMap<T> = Record<AddressSection, T>;
type DebounceMap = SectionMap<ReturnType<typeof setTimeout> | null>;
type InputOptions = { immediate?: boolean };
type LocationClient = (url: string) => Promise<unknown>;
type ShipmentFlowStoreLike = { shipmentDetails?: { origin_country_code?: string; destination_country_code?: string } };
type SmartValidationLike = {
	autoCapitalize: (value: unknown) => string;
	clearError: (key: string) => void;
	filterCAP: (value: unknown, options?: { countryCode?: string }) => string;
	filterProvincia: (value: unknown) => string;
	formatTelefono: (value: unknown) => string;
	getProvinceSuggestions: (value: string) => string[];
	onBlur: (key: string, callback: () => unknown) => void;
	onInput: (key: string, callback: () => unknown) => void;
	setError: (key: string, message: string) => void;
	validateCAP: (key: string, value: string, options?: { countryCode?: string }) => boolean;
	validateEmail: (key: string, value: string) => boolean;
	validateNomeCognome: (key: string, value: string) => boolean;
	validateProvincia: (key: string, value: string) => boolean;
	validateTelefono: (key: string, value: string) => boolean;
};
type UseShipmentLocationAutocompleteArgs = {
	deliveryMode: Ref<string>;
	destinationAddress: Ref<ShipmentAddress>;
	originAddress: Ref<ShipmentAddress>;
	sanctumClient?: LocationClient;
	sv: SmartValidationLike;
	shipmentFlowStore?: ShipmentFlowStoreLike;
};
type LocationSearchApi = {
	dedupeLocations: (locations: unknown[]) => LocationSuggestion[];
	getProvinceLabel: (location: unknown) => string;
	normalizeLocationText: (value: unknown) => string;
	searchLocations: (query: unknown, limit?: number, countryCode?: string) => Promise<LocationSuggestion[]>;
	searchLocationsByCap: (cap: unknown, countryCode?: string) => Promise<LocationSuggestion[]>;
	searchLocationsByCity: (city: unknown, limit?: number, countryCode?: string) => Promise<LocationSuggestion[]>;
};

const compareCityResult = (norm: (v: unknown) => string, queryNorm: string) => (a: LocationSuggestion, b: LocationSuggestion) => {
	const aName = norm(a.place_name); const bName = norm(b.place_name)
	const aExact = aName === queryNorm ? 0 : 1; const bExact = bName === queryNorm ? 0 : 1
	if (aExact !== bExact) return aExact - bExact
	if (aName.length !== bName.length) return aName.length - bName.length
	if (aName !== bName) return aName.localeCompare(bName)
	return String(a.postal_code || '').localeCompare(String(b.postal_code || ''))
}
const compareCapResult = (norm: (v: unknown) => string) => (a: LocationSuggestion, b: LocationSuggestion) => {
	const aCap = String(a.postal_code || ''); const bCap = String(b.postal_code || '')
	if (aCap !== bCap) return aCap.localeCompare(bCap)
	return norm(a.place_name).localeCompare(norm(b.place_name))
}

export const useShipmentLocationAutocomplete = ({
	deliveryMode, destinationAddress, originAddress, sanctumClient, sv, shipmentFlowStore,
}: UseShipmentLocationAutocompleteArgs) => {
	const { dedupeLocations, getProvinceLabel, normalizeLocationText, searchLocations, searchLocationsByCap, searchLocationsByCity } =
		useLocationSearch(sanctumClient) as LocationSearchApi

	const provinceSuggestions: SectionMap<Ref<string[]>> = { origin: ref([]), dest: ref([]) }
	const citySuggestions: SectionMap<Ref<LocationSuggestion[]>> = { origin: ref([]), dest: ref([]) }
	const capSuggestions: SectionMap<Ref<LocationSuggestion[]>> = { origin: ref([]), dest: ref([]) }
	const citySearchTimeout: DebounceMap = { origin: null, dest: null }
	const capSearchTimeout: DebounceMap = { origin: null, dest: null }
	const citySearchSeq = reactive<SectionMap<number>>({ origin: 0, dest: 0 })
	const capSearchSeq = reactive<SectionMap<number>>({ origin: 0, dest: 0 })
	const locationLinkHints = reactive<SectionMap<LocationSuggestion[]>>({ origin: [], dest: [] })

	// Cleanup debounce: evita fetch su scope smontata se utente naviga via durante typing.
	onScopeDispose(() => {
		Object.values(citySearchTimeout).concat(Object.values(capSearchTimeout)).forEach((t) => { if (t) clearTimeout(t) })
	})

	const sanitizeFullNameValue = (value: unknown) => sanitizeFullName(value, sv.autoCapitalize);
	const getSectionAddress = (section: AddressSection) => (section === 'origin' ? originAddress.value : destinationAddress.value)
	const getSectionCountryCode = (section: AddressSection) => {
		const code = section === 'origin'
			? shipmentFlowStore?.shipmentDetails?.origin_country_code
			: shipmentFlowStore?.shipmentDetails?.destination_country_code
		return String(code || 'IT').trim().toUpperCase()
	}
	const isItalianSection = (section: AddressSection) => getSectionCountryCode(section) === 'IT'

	const setCity = (section: AddressSection, s: LocationSuggestion[]) => { citySuggestions[section].value = s }
	const setCap = (section: AddressSection, s: LocationSuggestion[]) => { capSuggestions[section].value = s }
	const setProvince = (section: AddressSection, s: string[]) => { provinceSuggestions[section].value = s }
	const setProvinceFromLocations = (section: AddressSection, locations: LocationSuggestion[]) => setProvince(section, extractUniqueProvinces(locations))

	const validateProvinceField = (section: AddressSection, value: unknown) => {
		if (isItalianSection(section)) return sv.validateProvincia(`${section}_province`, String(value || ''))
		if (!value || !String(value).trim()) {
			sv.setError(`${section}_province`, 'Provincia/Stato è obbligatorio')
			return false
		}
		sv.clearError(`${section}_province`)
		return true
	}

	const applyLocationToSection = (section: AddressSection, location: LocationSuggestion) => {
		const address = getSectionAddress(section)
		address.city = location.place_name || address.city
		address.postal_code = String(location.postal_code || address.postal_code || '')
		address.country = location.country_name || address.country || 'Italia'
		const province = getProvinceLabel(location)
		if (province) address.province = province
		setCity(section, [])
		setCap(section, [])
		setProvince(section, [])
		sv.clearError(`${section}_city`)
		sv.clearError(`${section}_postal_code`)
		sv.clearError(`${section}_province`)
	}

	const validateAddressLocationLink = async (section: AddressSection) => {
		if (section === 'dest' && deliveryMode.value === 'pudo') return true
		if (!isItalianSection(section)) return true

		const address = getSectionAddress(section)
		const city = String(address.city || '').trim()
		const province = sv.filterProvincia(address.province || '')
		const cap = sv.filterCAP(address.postal_code || '')
		if (!city || !province || cap.length !== 5) return true

		try {
			const results = await searchLocationsByCap(cap, getSectionCountryCode(section))
			locationLinkHints[section] = results
			if (!results.length) {
				sv.setError(`${section}_postal_code`, `CAP ${cap} non trovato.`)
				return false
			}

			const cityNorm = normalizeLocationText(city)
			const provinceNorm = normalizeLocationText(province)
			const exact = results.find((loc) =>
				normalizeLocationText(loc.place_name) === cityNorm &&
				normalizeLocationText(getProvinceLabel(loc) || '') === provinceNorm,
			)

			if (!exact) {
				const cityMatch = results.find((loc) => normalizeLocationText(loc.place_name) === cityNorm)
				const provinceMatch = results.find((loc) => normalizeLocationText(getProvinceLabel(loc) || '') === provinceNorm)
				const hint = results[0]
				if (!hint) return false
				const hintProvince = getProvinceLabel(hint)
				const hintText = hintProvince ? `${hint.place_name} (${hintProvince})` : hint.place_name

				sv.setError(`${section}_postal_code`, `CAP ${cap} non coerente con città/provincia.`)
				if (!cityMatch) sv.setError(`${section}_city`, `Per CAP ${cap} la città corretta è ${hintText}.`)
				if (!provinceMatch) sv.setError(`${section}_province`, `Provincia non coerente con CAP ${cap}.`)
				return false
			}

			address.city = exact.place_name || address.city
			address.province = getProvinceLabel(exact) || address.province
			sv.clearError(`${section}_city`)
			sv.clearError(`${section}_province`)
			sv.clearError(`${section}_postal_code`)
			locationLinkHints[section] = []
			return true
		} catch {
			locationLinkHints[section] = []
			return true
		}
	}

	const onProvinciaInput = (section: AddressSection, value: unknown) => {
		const address = getSectionAddress(section)
		if (!isItalianSection(section)) {
			const cleaned = String(value || '').trimStart()
			address.province = cleaned
			setProvince(section, [])
			sv.onInput(`${section}_province`, () => validateProvinceField(section, cleaned))
			return
		}
		const filtered = sv.filterProvincia(value)
		const contextual = [...new Set(
			dedupeLocations([...citySuggestions[section].value, ...capSuggestions[section].value])
				.map((loc) => getProvinceLabel(loc)).filter(Boolean),
		)].filter((prov) => prov.startsWith(filtered))
		address.province = filtered
		setProvince(section, contextual.length > 0 ? contextual.slice(0, 20) : sv.getProvinceSuggestions(filtered))
		sv.onInput(`${section}_province`, () => validateProvinceField(section, filtered))
	}

	const selectProvincia = (section: AddressSection, prov: string) => {
		getSectionAddress(section).province = prov
		setProvince(section, [])
		sv.clearError(`${section}_province`)
		void validateAddressLocationLink(section)
	}

	const loadCapSuggestionsFromCity = async (section: AddressSection, cityValue: unknown) => {
		const city = String(cityValue || '').trim()
		if (city.length < 2) return
		try {
			const results = await searchLocationsByCity(city, 300, getSectionCountryCode(section))
			const cityNorm = normalizeLocationText(city)
			let filtered = results.filter((loc) => normalizeLocationText(loc.place_name).startsWith(cityNorm))
			if (isItalianSection(section)) {
				filtered = filtered.sort((a, b) => String(a.postal_code).localeCompare(String(b.postal_code)))
			}
			setCap(section, filtered.slice(0, 40))
			setProvinceFromLocations(section, filtered)
		} catch { /* ignore */ }
	}

	const onCityFocus = (section: AddressSection) => {
		const addr = getSectionAddress(section)
		if (addr.city && String(addr.city).trim().length >= 2) void onCityInput(section, addr.city, { immediate: true })
	}

	const onCapFocus = (section: AddressSection) => {
		const addr = getSectionAddress(section)
		const cap = String(addr.postal_code || '')
		if (cap.length >= 3) { void onCapInput(section, cap, { immediate: true }); return }
		if (String(addr.city || '').trim().length >= 2) void loadCapSuggestionsFromCity(section, addr.city)
	}

	const onProvinceFocus = (section: AddressSection) => {
		const addr = getSectionAddress(section)
		const filtered = isItalianSection(section) ? sv.filterProvincia(addr.province || '') : String(addr.province || '').trimStart()
		onProvinciaInput(section, filtered)
		if (!filtered && String(addr.postal_code || '').length >= 3) void onCapInput(section, addr.postal_code || '', { immediate: true })
		if (!filtered && String(addr.city || '').trim().length >= 2) void onCityInput(section, addr.city || '', { immediate: true })
	}

	const onCityInput = async (section: AddressSection, value: string, options: InputOptions = {}) => {
		if (citySearchTimeout[section]) clearTimeout(citySearchTimeout[section])

		sv.onInput(`${section}_city`, () => {
			if (!value || !String(value).trim()) sv.setError(`${section}_city`, 'Città è obbligatoria')
			else sv.clearError(`${section}_city`)
		})

		if (!value || value.length < 2) {
			setCity(section, [])
			return
		}

		citySearchTimeout[section] = setTimeout(async () => {
			const seq = ++citySearchSeq[section]
			try {
				const results = await searchLocationsByCity(value, 300, getSectionCountryCode(section))
				if (seq !== citySearchSeq[section]) return

				const queryNorm = normalizeLocationText(value)
				const address = getSectionAddress(section)
				const capPrefix = String(address.postal_code || '')
				const provincePrefix = normalizeLocationText(address.province || '')
				const currentCity = normalizeLocationText(address.city || '')
				const isEditingAfterSelection = currentCity && queryNorm !== currentCity

				let suggestions = results.filter((loc) => normalizeLocationText(loc.place_name).startsWith(queryNorm))
				if (!isEditingAfterSelection && capPrefix.length >= 3) {
					suggestions = suggestions.filter((loc) => String(loc.postal_code || '').startsWith(capPrefix))
				}
				if (!isEditingAfterSelection && provincePrefix.length === 2) {
					suggestions = suggestions.filter((loc) => normalizeLocationText(getProvinceLabel(loc) || '') === provincePrefix)
				}

				suggestions.sort(compareCityResult(normalizeLocationText, queryNorm))

				setCity(section, suggestions.slice(0, 25))
				setProvinceFromLocations(section, suggestions)

				if (capPrefix.length >= 3) {
					setCap(section, suggestions.filter((loc) => String(loc.postal_code || '').startsWith(capPrefix)).slice(0, 40))
				}
			} catch {
				setCity(section, [])
			}
		}, options.immediate ? 0 : 260)
	}

	const onNameInput = (section: AddressSection, value: unknown) => {
		const capitalized = sanitizeFullNameValue(value)
		getSectionAddress(section).full_name = capitalized
		sv.onInput(`${section}_full_name`, () => sv.validateNomeCognome(`${section}_full_name`, capitalized))
	}

	const onCapInput = async (section: AddressSection, value: unknown, options: InputOptions = {}) => {
		if (capSearchTimeout[section]) clearTimeout(capSearchTimeout[section])
		const countryCode = getSectionCountryCode(section)
		const filtered = sv.filterCAP(value, { countryCode })
		getSectionAddress(section).postal_code = filtered
		sv.onInput(`${section}_postal_code`, () => sv.validateCAP(`${section}_postal_code`, filtered, { countryCode }))

		if (!filtered || filtered.length < (countryCode === 'IT' ? 3 : 2)) {
			setCap(section, [])
			return
		}

		capSearchTimeout[section] = setTimeout(async () => {
			const seq = ++capSearchSeq[section]
			const address = getSectionAddress(section)
			const cityNorm = normalizeLocationText(address.city || '')
			const provinceNorm = normalizeLocationText(address.province || '')

			try {
				const results = countryCode === 'IT' && filtered.length === 5
					? await searchLocationsByCap(filtered, countryCode)
					: await searchLocations(filtered, 300, countryCode)
				if (seq !== capSearchSeq[section]) return

				let suggestions = countryCode === 'IT'
					? results.filter((loc) => String(loc.postal_code || '').startsWith(filtered))
					: results.filter((loc) => String(loc.postal_code || '').toUpperCase().startsWith(filtered.toUpperCase()))

				const currentCap = String(address.postal_code || '')
				const isEditingCap = currentCap && filtered !== currentCap

				if (!isEditingCap && cityNorm.length >= 2) {
					suggestions = suggestions.filter((loc) => normalizeLocationText(loc.place_name).startsWith(cityNorm))
				}
				if (!isEditingCap && countryCode === 'IT' && provinceNorm.length === 2) {
					suggestions = suggestions.filter((loc) => normalizeLocationText(getProvinceLabel(loc) || '') === provinceNorm)
				}

				suggestions.sort(compareCapResult(normalizeLocationText))

				setCap(section, suggestions.slice(0, 40))
				setProvinceFromLocations(section, suggestions)

				if (countryCode === 'IT' && filtered.length === 5) {
					const exactCoherent = suggestions.find((loc) => isLocationCoherent(loc, address.city, address.province))
					if (exactCoherent) applyLocationToSection(section, exactCoherent)
					else if (!address.city && suggestions.length === 1 && suggestions[0]) applyLocationToSection(section, suggestions[0])
				}
			} catch {
				setCap(section, [])
			}
		}, options.immediate ? 0 : 220)
	}

	const onTelefonoInput = (section: AddressSection, value: unknown) => {
		const formatted = sv.formatTelefono(value)
		getSectionAddress(section).telephone_number = formatted
		sv.onInput(`${section}_telephone_number`, () => sv.validateTelefono(`${section}_telephone_number`, formatted))
	}

	const smartBlur = (section: AddressSection, field: AddressField) => {
		const key = `${section}_${field}`
		const value = getSectionAddress(section)[field]
		const requireField = (msg: string) => () => {
			if (!value || !String(value).trim()) sv.setError(key, msg)
			else sv.clearError(key)
		}

		if (field === 'full_name') sv.onBlur(key, () => sv.validateNomeCognome(key, value || ''))
		else if (field === 'telephone_number') sv.onBlur(key, () => sv.validateTelefono(key, value || ''))
		else if (field === 'email') sv.onBlur(key, () => sv.validateEmail(key, value || ''))
		else if (field === 'city') {
			sv.onBlur(key, requireField('Città è obbligatoria'))
			setTimeout(() => setCity(section, []), 200)
			void validateAddressLocationLink(section)
		} else if (field === 'postal_code') {
			sv.onBlur(key, () => sv.validateCAP(key, value || '', { countryCode: getSectionCountryCode(section) }))
			setTimeout(() => setCap(section, []), 200)
			void validateAddressLocationLink(section)
		} else if (field === 'province') {
			sv.onBlur(key, () => validateProvinceField(section, value))
			setTimeout(() => setProvince(section, []), 200)
			void validateAddressLocationLink(section)
		} else {
			sv.onBlur(key, requireField('Campo obbligatorio'))
		}
	}

	return {
		applyLocationToSection, formatCapSuggestionLabel, formatCitySuggestionLabel,
		getSectionAddress, getSectionCountryCode, isLocationCoherent, locationLinkHints, normalizeLocationText,
		onCapFocus, onCapInput, onCityFocus, onCityInput, onNameInput, onProvinceFocus, onProvinciaInput, onTelefonoInput,
		selectCap: applyLocationToSection, selectCity: applyLocationToSection, selectProvincia,
		smartBlur, validateAddressLocationLink, validateProvinceField,
		destCapSuggestions: capSuggestions.dest,
		destCitySuggestions: citySuggestions.dest,
		destProvinceSuggestions: provinceSuggestions.dest,
		originCapSuggestions: capSuggestions.origin,
		originCitySuggestions: citySuggestions.origin,
		originProvinceSuggestions: provinceSuggestions.origin,
	}
}
