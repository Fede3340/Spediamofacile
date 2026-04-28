/**
 * @file locationMatch — helpers puri per matching/format suggestion BRT autocomplete.
 * Estratti da composables/useShipmentLocationAutocomplete.ts per chiarezza:
 * funzioni senza ref/state, solo stringhe in/out.
 */
import { dedupeLocations, getProvinceLabel, normalizeLocationText } from '~/utils/location';

/**
 * Pulizia carattere-by-carattere del campo "Nome e Cognome" digitato a mano.
 * Rimuove cifre + caratteri non alfabetici/spazio/apostrofo, poi applica
 * `autoCapitalize` esterno (Title Case con eccezioni "di"/"de"/"da" passato dal caller).
 */
export const sanitizeFullName = (value, autoCapitalize) => {
	const cleaned = String(value || '')
		.replace(/\d/g, '')
		.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ'’`.\-\s]/g, ' ')
		.replace(/\s+/g, ' ')
		.trim();
	return typeof autoCapitalize === 'function' ? autoCapitalize(cleaned) : cleaned;
};

/** Etichetta suggestion city: "Milano (MI) - 20121" o "Milano - 20121" se no provincia. */
export const formatCitySuggestionLabel = (location) => {
	const province = getProvinceLabel(location);
	if (province) return `${location.place_name} (${province}) - ${location.postal_code}`;
	return `${location.place_name} - ${location.postal_code}`;
};

/** Etichetta suggestion cap: "20121 - Milano (MI)" o "20121 - Milano". */
export const formatCapSuggestionLabel = (location) => {
	const province = getProvinceLabel(location);
	if (province) return `${location.postal_code} - ${location.place_name} (${province})`;
	return `${location.postal_code} - ${location.place_name}`;
};

/**
 * Verifica che `location` abbia city/province coerenti con quelle digitate.
 * Confronto normalizzato (case + accenti). City/province vuote = nessun vincolo.
 */
export const isLocationCoherent = (location, city, province) => {
	const cityNorm = normalizeLocationText(city || '');
	const provinceNorm = normalizeLocationText(province || '');
	const locCityNorm = normalizeLocationText(location?.place_name || '');
	const locProvinceNorm = normalizeLocationText(getProvinceLabel(location) || '');

	if (cityNorm && locCityNorm !== cityNorm) return false;
	if (provinceNorm && locProvinceNorm !== provinceNorm) return false;
	return true;
};

/**
 * Estrae lista province uniche (sortita) da un insieme di locations,
 * deduplicandole prima per evitare doppi (place_name + postal_code univoco).
 */
export const extractUniqueProvinces = (locations, max = 20) => {
	const provinces = [...new Set(
		dedupeLocations(locations)
			.map((loc) => getProvinceLabel(loc))
			.filter(Boolean),
	)].sort();
	return provinces.slice(0, max);
};
