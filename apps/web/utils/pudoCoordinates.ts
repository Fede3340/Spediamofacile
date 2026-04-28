/**
 * @file pudoCoordinates — helpers puri parse/extract coordinate da payload PUDO BRT.
 * Estratti da composables/usePudoSearchApi.ts: BRT ritorna lat/lng in 6+ shape diverse
 * (top-level lat/latitude/lng/lon, nested coordinates/coordinate/geo/location/address_coordinates).
 */

/** Parse stringa/numero a Number, accetta virgola decimale italiana. Ritorna null se non valido. */
export const parseCoordinate = (value) => {
	if (value === null || value === undefined || value === '') return null;
	const parsed = Number.parseFloat(String(value).trim().replace(',', '.'));
	return Number.isFinite(parsed) ? parsed : null;
};

/** Estrae latitudine considerando i 6 shape che BRT restituisce nei vari endpoint PUDO. */
export const extractLatitude = (point = {}) => {
	const nested = (key) => point[key];
	return parseCoordinate(
		point.latitude ?? point.lat
		?? nested('coordinates')?.latitude ?? nested('coordinates')?.lat
		?? nested('coordinate')?.latitude ?? nested('coordinate')?.lat
		?? nested('geo')?.latitude ?? nested('geo')?.lat
		?? nested('location')?.latitude ?? nested('location')?.lat
		?? nested('address_coordinates')?.latitude ?? nested('address_coordinates')?.lat,
	);
};

/** Estrae longitudine considerando i 6 shape che BRT restituisce. */
export const extractLongitude = (point = {}) => {
	const nested = (key) => point[key];
	return parseCoordinate(
		point.longitude ?? point.lng ?? point.lon
		?? nested('coordinates')?.longitude ?? nested('coordinates')?.lng ?? nested('coordinates')?.lon
		?? nested('coordinate')?.longitude ?? nested('coordinate')?.lng ?? nested('coordinate')?.lon
		?? nested('geo')?.longitude ?? nested('geo')?.lng ?? nested('geo')?.lon
		?? nested('location')?.longitude ?? nested('location')?.lng ?? nested('location')?.lon
		?? nested('address_coordinates')?.longitude ?? nested('address_coordinates')?.lng ?? nested('address_coordinates')?.lon,
	);
};

/** Distanza in metri da string (BRT mescola "1.5 km", "1500m", "1500", "1,5km"). */
export const parseDistanceMeters = (value) => {
	if (value === null || value === undefined || value === '') return null;
	const raw = String(value).trim().toLowerCase();
	const cleaned = raw.replace(',', '.').replace(/[^\d.-]/g, '');
	if (!cleaned) return null;
	const parsed = Number.parseFloat(cleaned);
	if (!Number.isFinite(parsed)) return null;
	if (raw.includes('km')) return Math.round(parsed * 1000);
	return Math.round(parsed);
};

export const isFiniteCoordinate = (value) => Number.isFinite(parseCoordinate(value));

/** Normalizza chiave testo per dedup case-insensitive (es. via Roma === VIA ROMA). */
export const normalizeTextKey = (value) => String(value || '').trim().toLowerCase();

/** Estrae status numerico da errore $fetch (status code) o errore generico. */
export const getErrorStatus = (error) =>
	Number(error?.status ?? error?.response?.status ?? error?.data?.statusCode ?? 0);

/** Estrae messaggio human-readable da errore $fetch. */
export const getErrorMessage = (error) =>
	error?.data?.error || error?.data?.message || error?.response?._data?.message || error?.message || '';
