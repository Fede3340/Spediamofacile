/**
 * Utility condivise per normalizzazione e gestione località.
 * Usato da useLocationAutocomplete, useLocationSearch, useAddressForm,
 * useCityCapAutocomplete, useShipmentStepValidation, useQuickQuoteLocations,
 * [step].vue, Preventivo.
 *
 * Le località italiane hanno: place_name (città), postal_code (CAP), province/province_name (sigla provincia).
 */

/**
 * Normalizza testo località per confronti case/accent-insensitive.
 * Rimuove accenti, converte in lowercase, collassa spazi multipli.
 * @param {string} text
 * @returns {string}
 */
export const normalizeLocationText = (value = "") =>
	String(value)
		.toLowerCase()
		.normalize("NFD")
		.replace(/[\u0300-\u036f]/g, "")
		.replace(/\s+/g, " ")
		.trim();

/**
 * Restituisce la sigla provincia in uppercase da un oggetto località.
 * Supporta sia `province` che `province_name` come campo sorgente.
 * @param {object} location
 * @returns {string}
 */
export const getProvinceLabel = (location) =>
	String(location?.province ?? location?.province_name ?? "")
		.toUpperCase()
		.trim();

/**
 * Genera una chiave univoca per una località (CAP|città|provincia).
 * Usata per deduplicazione e confronti.
 * @param {object} location
 * @returns {string}
 */
export const locationKey = (location) => [
	String(location?.postal_code || "").trim(),
	normalizeLocationText(location?.place_name),
	getProvinceLabel(location),
].join("|");

/**
 * Rimuove località duplicate da un array, basandosi su locationKey.
 * Ignora entries senza place_name o postal_code.
 * @param {Array} locations
 * @returns {Array}
 */
export const dedupeLocations = (locations = []) => {
	const map = new Map();
	for (const location of locations) {
		if (!location?.place_name || !location?.postal_code) continue;
		const key = locationKey(location);
		if (!map.has(key)) map.set(key, location);
	}
	return Array.from(map.values());
};
