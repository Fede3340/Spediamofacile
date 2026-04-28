/**
 * pudoStore — stato canonico ricerca PUDO BRT (search, risultati, mappa, dettagli).
 *
 * Lo store contiene SOLO state, actions e computed neutri rispetto al ciclo Vue.
 * Side effects legati a emit/timer/lifecycle vivono nel composable wrapper `usePudo()`.
 * I helper puri (normalizzazione, distanza, geocoding) vivono in `~/utils/pudoHelpers.ts`.
 */
import { defineStore } from 'pinia';
import { dedupePudoPoints, distanceInMeters, geocodeNominatim, getPudoErrorMessage, getPudoErrorStatus, isFiniteCoordinate, normalizePudoPoint, parseCoordinate, reverseGeocodeNominatim, sortPudoByDistance, STRATEGY_LABELS, } from '~/utils/pudoHelpers';
export const usePudoStore = defineStore('pudo', () => {
    const config = useRuntimeConfig();
    const apiBase = config.public?.apiBase || '';
    const publicApiFetch = async (path) => {
        const url = path.startsWith('http') ? path : `${apiBase}${path}`;
        return await $fetch(url, { method: 'GET', credentials: 'include', timeout: 15000 });
    };
    // ── State ──
    const searchAddress = ref('');
    const searchCity = ref('');
    const searchZip = ref('');
    const loading = ref(false);
    const geolocating = ref(false);
    const searched = ref(false);
    const searchError = ref(null);
    const searchMeta = ref(null);
    const referenceUpdateMessage = ref('');
    const pudoResults = ref([]);
    const selectedPudoKey = ref(null);
    const expandedPudoKey = ref(null);
    const loadingDetailsKey = ref(null);
    const pudoDetails = ref({});
    const detailsErrors = ref({});
    const mapClickLoading = ref(false);
    const referencePoint = ref(null);
    // ── Computed ──
    const hasSearchInput = computed(() => Boolean(searchCity.value?.trim() || searchZip.value?.trim()));
    const mapPoints = computed(() => pudoResults.value.filter((p) => isFiniteCoordinate(p.latitude) && isFiniteCoordinate(p.longitude)));
    const mapReferencePoint = computed(() => {
        if (!referencePoint.value)
            return null;
        return {
            latitude: referencePoint.value.latitude,
            longitude: referencePoint.value.longitude,
            address: referencePoint.value.address || '',
            city: referencePoint.value.city || '',
            zip_code: referencePoint.value.zip_code || '',
            label: referencePoint.value.label || '',
        };
    });
    const strategyListLabel = computed(() => {
        const strategies = Array.isArray(searchMeta.value?.strategy_used) ? searchMeta.value.strategy_used : [];
        if (!strategies.length)
            return '';
        return strategies.map((item) => STRATEGY_LABELS[item] || item).join(' \u2022 ');
    });
    // ── Reference point ──
    function setReferencePoint(latitude, longitude, source = 'fields', extra = {}) {
        const lat = parseCoordinate(latitude);
        const lng = parseCoordinate(longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng))
            return false;
        referencePoint.value = {
            latitude: lat,
            longitude,
            source,
            address: extra.address || searchAddress.value || '',
            city: extra.city || searchCity.value || '',
            zip_code: extra.zip_code || searchZip.value || '',
            label: extra.label || '',
        };
        return true;
    }
    function inferReferenceFromResults(points = []) {
        const coords = points
            .map((p) => ({ latitude: parseCoordinate(p?.latitude), longitude: parseCoordinate(p?.longitude) }))
            .filter((c) => { latitude: number, longitude; number; });
    }
});
Number.isFinite(c.latitude) && Number.isFinite(c.longitude),
;
if (!coords.length)
    return null;
return {
    latitude: coords.reduce((s, c) => s + c.latitude, 0) / coords.length,
    longitude: coords.reduce((s, c) => s + c.longitude, 0) / coords.length,
    source: 'results',
    address: searchAddress.value || '',
    city: searchCity.value || '',
    zip_code: searchZip.value || '',
    label: [searchCity.value, searchZip.value].filter(Boolean).join(' ').trim() || 'Area selezionata',
};
/** Applica risultati ricalcolando distanze. @returns true se la selezione corrente e' stata invalidata. */
function applyResults(rawPoints) {
    const normalized = (rawPoints || []).map(normalizePudoPoint);
    let distRef = referencePoint.value;
    const allZero = normalized.length > 0 && normalized.every((p) => Number.isFinite(Number(p.distance_meters)) && Number(p.distance_meters) === 0);
    if (!distRef) {
        const inferred = inferReferenceFromResults(normalized);
        if (inferred) {
            referencePoint.value = inferred;
            distRef = inferred;
        }
    }
    const withDist = normalized.map((point) => {
        const apiD = Number(point.distance_meters);
        const hasApi = Number.isFinite(apiD);
        if (distRef && Number.isFinite(point.latitude) && Number.isFinite(point.longitude)) {
            const computedDist = distanceInMeters(distRef, { latitude: point.latitude, longitude: point.longitude });
            const shouldReplace = !hasApi || apiD <= 0 || allZero;
            if (shouldReplace && Number.isFinite(computedDist))
                return { ...point, distance_meters: computedDist };
            if (hasApi && apiD > 0 && Number.isFinite(computedDist) && Math.abs(apiD - computedDist) > 200000)
                return { ...point, distance_meters: computedDist };
            return { ...point, distance_meters: hasApi ? apiD : computedDist ?? null };
        }
        if (allZero && Number(point.distance_meters) === 0)
            return { ...point, distance_meters: null };
        return point;
    });
    pudoResults.value = sortPudoByDistance(dedupePudoPoints(withDist));
    if (selectedPudoKey.value) {
        const exists = pudoResults.value.some((p) => String(p.ui_key) === String(selectedPudoKey.value));
        if (!exists) {
            selectedPudoKey.value = null;
            return true;
        }
    }
    return false;
}
const geocodeFromSearchFields = () => geocodeNominatim([searchAddress.value, searchZip.value, searchCity.value, 'Italia']);
// ── PUDO API calls ──
async function fetchNearbyPudo(latitude, longitude, maxResults = 50) {
    const params = new URLSearchParams({ latitude: String(latitude), longitude: String(longitude), max_results: String(maxResults) });
    const result = await publicApiFetch(`/api/brt/pudo/nearby?${params.toString()}`);
    return result?.pudo || result?.data?.pudo || [];
}
function setSearchError(error) {
    const status = getPudoErrorStatus(error);
    const backendMessage = getPudoErrorMessage(error);
    if (status === 401 || status === 403)
        searchError.value = 'Servizio punti di ritiro temporaneamente non disponibile. Riprova tra poco.';
    else if (status === 422)
        searchError.value = backendMessage || 'Inserisci almeno citta o CAP per cercare i punti di ritiro.';
    else if (status >= 500)
        searchError.value = 'Il servizio BRT non risponde al momento. Riprova tra qualche minuto.';
    else
        searchError.value = backendMessage ? `Errore: ${backendMessage}` : 'Errore durante la ricerca. Riprova.';
}
/** @returns true se la selezione corrente e' stata invalidata. */
async function searchPudo() {
    if (!hasSearchInput.value)
        return false;
    loading.value = true;
    searched.value = true;
    searchError.value = null;
    searchMeta.value = null;
    pudoResults.value = [];
    let invalidated = false;
    try {
        const params = new URLSearchParams();
        if (searchAddress.value?.trim())
            params.set('address', searchAddress.value.trim());
        if (searchCity.value?.trim())
            params.set('city', searchCity.value.trim());
        if (searchZip.value?.trim())
            params.set('zip_code', searchZip.value.trim());
        params.set('country', 'ITA');
        params.set('max_results', '50');
        const result = await publicApiFetch(`/api/brt/pudo/search?${params.toString()}`);
        if (result?.success === false) {
            searchError.value = result?.error || 'Errore durante la ricerca dei punti di ritiro.';
            return false;
        }
        let points = result?.pudo || result?.data?.pudo || [];
        const apiMeta = result?.meta || result?.data?.meta || {};
        let strategyUsed = Array.isArray(apiMeta.strategy_used) ? [...apiMeta.strategy_used] : [];
        if (!referencePoint.value || referencePoint.value.source !== 'manual') {
            try {
                const geocoded = await geocodeFromSearchFields();
                if (geocoded)
                    setReferencePoint(geocoded.latitude, geocoded.longitude, 'fields', { label: geocoded.label });
            }
            catch { /* geocoding non disponibile */ }
        }
        if (applyResults(points))
            invalidated = true;
        searchMeta.value = { ...apiMeta, strategy_used: strategyUsed.length ? strategyUsed : apiMeta.strategy_used, returned_count: pudoResults.value.length, requested_count: 50 };
        if (referencePoint.value) {
            try {
                const nearby = await fetchNearbyPudo(referencePoint.value.latitude, referencePoint.value.longitude, 50);
                if (nearby.length) {
                    const merged = dedupePudoPoints([...points.map(normalizePudoPoint), ...nearby.map(normalizePudoPoint)]);
                    strategyUsed = Array.from(new Set([...strategyUsed, 'nearby_geo']));
                    if (applyResults(merged))
                        invalidated = true;
                    points = merged;
                }
            }
            catch { /* nearby non disponibile */ }
        }
        searchMeta.value = { ...apiMeta, strategy_used: strategyUsed.length ? strategyUsed : apiMeta.strategy_used, returned_count: pudoResults.value.length, requested_count: 50 };
    }
    catch (error) {
        setSearchError(error);
        pudoResults.value = [];
    }
    finally {
        loading.value = false;
    }
    return invalidated;
}
/** @returns true se la selezione corrente e' stata invalidata. */
async function useCurrentLocation() {
    if (!navigator?.geolocation) {
        searchError.value = 'Geolocalizzazione non supportata dal browser.';
        return false;
    }
    geolocating.value = true;
    searchError.value = null;
    referenceUpdateMessage.value = '';
    let invalidated = false;
    try {
        const position = await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 });
        });
        const lat = position?.coords?.latitude;
        const lng = position?.coords?.longitude;
        const reversed = await reverseGeocodeNominatim(lat, lng);
        if (reversed?.address)
            searchAddress.value = reversed.address;
        if (reversed?.city)
            searchCity.value = reversed.city;
        if (reversed?.zip_code)
            searchZip.value = reversed.zip_code;
        if (!setReferencePoint(lat, lng, 'geo', {
            label: reversed?.label || 'Posizione attuale',
            address: reversed?.address || '', city: reversed?.city || '', zip_code: reversed?.zip_code || '',
        })) {
            throw new Error('Coordinate non valide.');
        }
        referenceUpdateMessage.value = 'Riferimento aggiornato dalla tua posizione. Ricerca punti avviata automaticamente.';
        if (hasSearchInput.value) {
            invalidated = await searchPudo();
            return invalidated;
        }
        loading.value = true;
        searched.value = true;
        const nearby = await fetchNearbyPudo(lat, lng, 50);
        if (applyResults(nearby))
            invalidated = true;
        searchMeta.value = { strategy_used: ['nearby_geo'], returned_count: pudoResults.value.length, requested_count: 50, provider: 'BRT', fallback: false };
    }
    catch (error) {
        const status = getPudoErrorStatus(error);
        const geoCode = Number(error?.code || 0);
        if (status >= 500)
            searchError.value = 'Servizio geolocalizzazione temporaneamente non disponibile.';
        else if (geoCode === 1)
            searchError.value = 'Permesso posizione negato. Attiva la geolocalizzazione per cercare i punti vicini.';
        else if (geoCode === 3)
            searchError.value = 'Timeout posizione. Riprova oppure usa citta e CAP.';
        else
            searchError.value = 'Impossibile recuperare la posizione attuale.';
        pudoResults.value = [];
        searched.value = true;
    }
    finally {
        loading.value = false;
        geolocating.value = false;
    }
    return invalidated;
}
/** @returns true se la selezione corrente e' stata invalidata. */
async function onMapReferenceClick(payload) {
    const lat = parseCoordinate(payload?.latitude);
    const lng = parseCoordinate(payload?.longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng))
        return false;
    mapClickLoading.value = true;
    searchError.value = null;
    referenceUpdateMessage.value = '';
    let invalidated = false;
    try {
        const reversed = await reverseGeocodeNominatim(lat, lng);
        if (reversed?.address)
            searchAddress.value = reversed.address;
        if (reversed?.city)
            searchCity.value = reversed.city;
        if (reversed?.zip_code)
            searchZip.value = reversed.zip_code;
        setReferencePoint(lat, lng, 'manual', {
            label: reversed?.label || 'Punto selezionato da mappa',
            address: reversed?.address || '', city: reversed?.city || '', zip_code: reversed?.zip_code || '',
        });
        referenceUpdateMessage.value = 'Riferimento mappa aggiornato. Ricerca punti avviata automaticamente.';
        if (hasSearchInput.value) {
            invalidated = await searchPudo();
            return invalidated;
        }
        loading.value = true;
        searched.value = true;
        const nearby = await fetchNearbyPudo(lat, lng, 50);
        if (applyResults(nearby))
            invalidated = true;
        searchMeta.value = { strategy_used: ['nearby_geo'], returned_count: pudoResults.value.length, requested_count: 50, provider: 'BRT', fallback: false };
    }
    catch {
        searchError.value = 'Posizione mappa rilevata, ma non sono riuscito ad aggiornare i punti ora. Riprova.';
    }
    finally {
        loading.value = false;
        mapClickLoading.value = false;
    }
    return invalidated;
}
async function fetchPudoDetails(pudo, detailKey) {
    detailsErrors.value[detailKey] = null;
    if (!pudo?.pudo_id) {
        detailsErrors.value[detailKey] = 'Dettagli non disponibili per questo punto.';
        return;
    }
    loadingDetailsKey.value = detailKey;
    try {
        const result = await publicApiFetch(`/api/brt/pudo/${pudo.pudo_id}`);
        const pudoField = result?.pudo;
        const dataField = result?.data;
        const dataPudo = dataField?.pudo;
        const p = ((pudoField && typeof pudoField === 'object' ? pudoField : undefined)
            || (dataPudo && typeof dataPudo === 'object' ? dataPudo : undefined)
            || dataField || result || {});
        pudoDetails.value[detailKey] = {
            opening_hours: ((p.opening_hours ?? p.hours ?? pudo.opening_hours ?? '') || null),
            localization_hint: String((p.localization_hint ?? p.localizationHint ?? pudo.localization_hint) ?? ''),
            enabled: typeof p.enabled === 'boolean' ? p.enabled : pudo.enabled,
        };
    }
    catch (error) {
        const status = getPudoErrorStatus(error);
        if (status === 401)
            detailsErrors.value[detailKey] = 'Dettagli non disponibili al momento.';
        else if (status >= 500)
            detailsErrors.value[detailKey] = 'Errore server nel caricamento dettagli.';
        else
            detailsErrors.value[detailKey] = 'Impossibile caricare i dettagli di questo punto.';
    }
    finally {
        loadingDetailsKey.value = null;
    }
}
return {
    // state
    searchAddress, searchCity, searchZip,
    loading, geolocating, searched, searchError, searchMeta, referenceUpdateMessage,
    pudoResults, selectedPudoKey, expandedPudoKey, loadingDetailsKey, pudoDetails, detailsErrors, mapClickLoading,
    referencePoint,
    // computed
    hasSearchInput, mapPoints, mapReferencePoint, strategyListLabel,
    // actions
    setReferencePoint, applyResults,
    searchPudo, useCurrentLocation, onMapReferenceClick, fetchPudoDetails,
};
