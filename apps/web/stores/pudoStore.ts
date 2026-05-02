/**
 * pudoStore — stato canonico ricerca PUDO BRT (search, risultati, mappa, dettagli).
 * Side effects (emit/timer/lifecycle) vivono in `usePudo()`. Helper puri in `~/utils/pudoHelpers.ts`.
 */
import { defineStore } from 'pinia';
import { dedupePudoPoints, distanceInMeters, geocodeNominatim, getPudoErrorMessage, getPudoErrorStatus, isFiniteCoordinate, normalizePudoPoint, parseCoordinate, reverseGeocodeNominatim, sortPudoByDistance, STRATEGY_LABELS } from '~/utils/pudoHelpers';
import type { CoordinatePoint, PudoNormalized } from '~/utils/pudoHelpers';

type PudoReferenceSource = 'fields' | 'results' | 'manual' | 'geo';
type PudoReferencePoint = CoordinatePoint & { source: PudoReferenceSource; address: string; city: string; zip_code: string; label: string };
type PudoReferenceExtra = Partial<Pick<PudoReferencePoint, 'address' | 'city' | 'zip_code' | 'label'>>;
type PudoSearchMeta = { strategy_used?: string[] | string; returned_count?: number; requested_count?: number; provider?: string; fallback?: boolean; [key: string]: unknown };
type PudoApiResponse = { success?: boolean; error?: string; pudo?: unknown; data?: unknown; meta?: PudoSearchMeta };
type PudoDetails = { opening_hours: unknown; localization_hint: string; enabled: boolean };
type MapReferencePayload = { latitude?: unknown; longitude?: unknown };
type ReverseGeo = { address?: string; city?: string; zip_code?: string; label?: string } | null;

const asRecord = (v: unknown): Record<string, unknown> => (v && typeof v === 'object' ? v as Record<string, unknown> : {});
const extractPudoList = (r: PudoApiResponse): unknown[] => Array.isArray(r.pudo) ? r.pudo : (Array.isArray(asRecord(r.data).pudo) ? asRecord(r.data).pudo as unknown[] : []);
const extractMeta = (r: PudoApiResponse): PudoSearchMeta => asRecord(r.meta ?? asRecord(r.data).meta) as PudoSearchMeta;

export const usePudoStore = defineStore('pudo', () => {
    const config = useRuntimeConfig();
    const apiBase = config.public?.apiBase || '';
    const publicApiFetch = async <T = unknown>(path: string): Promise<T> =>
        $fetch<T>(path.startsWith('http') ? path : `${apiBase}${path}`, { method: 'GET', credentials: 'include', timeout: 15000 });

    // State
    const searchAddress = ref(''), searchCity = ref(''), searchZip = ref('');
    const loading = ref(false), geolocating = ref(false), searched = ref(false), mapClickLoading = ref(false);
    const searchError = ref<string | null>(null);
    const searchMeta = ref<PudoSearchMeta | null>(null);
    const referenceUpdateMessage = ref('');
    const pudoResults = ref<PudoNormalized[]>([]);
    const selectedPudoKey = ref<string | null>(null), expandedPudoKey = ref<string | null>(null), loadingDetailsKey = ref<string | null>(null);
    const pudoDetails = ref<Record<string, PudoDetails>>({});
    const detailsErrors = ref<Record<string, string | null>>({});
    const referencePoint = ref<PudoReferencePoint | null>(null);

    // Computed
    const hasSearchInput = computed(() => Boolean(searchCity.value?.trim() || searchZip.value?.trim()));
    const mapPoints = computed(() => pudoResults.value.filter((p) => isFiniteCoordinate(p.latitude) && isFiniteCoordinate(p.longitude)));
    const mapReferencePoint = computed(() => {
        const r = referencePoint.value;
        return r ? { latitude: r.latitude, longitude: r.longitude, address: r.address || '', city: r.city || '', zip_code: r.zip_code || '', label: r.label || '' } : null;
    });
    const strategyLabels = STRATEGY_LABELS as Record<string, string>;
    const strategyListLabel = computed(() => {
        const s = Array.isArray(searchMeta.value?.strategy_used) ? searchMeta.value.strategy_used : [];
        return s.length ? s.map((i) => strategyLabels[i] || i).join(' • ') : '';
    });

    // Helpers privati
    const applyReverseGeo = (r: ReverseGeo) => {
        if (r?.address) searchAddress.value = r.address;
        if (r?.city) searchCity.value = r.city;
        if (r?.zip_code) searchZip.value = r.zip_code;
    };
    const buildNearbyMeta = (): PudoSearchMeta =>
        ({ strategy_used: ['nearby_geo'], returned_count: pudoResults.value.length, requested_count: 50, provider: 'BRT', fallback: false });
    const geocodeFromSearchFields = () => geocodeNominatim([searchAddress.value, searchZip.value, searchCity.value, 'Italia']);

    // Reference point
    function setReferencePoint(latitude: unknown, longitude: unknown, source: PudoReferenceSource = 'fields', extra: PudoReferenceExtra = {}) {
        const lat = parseCoordinate(latitude), lng = parseCoordinate(longitude);
        if (lat === null || lng === null || !Number.isFinite(lat) || !Number.isFinite(lng)) return false;
        referencePoint.value = { latitude: lat, longitude: lng, source,
            address: extra.address || searchAddress.value || '', city: extra.city || searchCity.value || '',
            zip_code: extra.zip_code || searchZip.value || '', label: extra.label || '' };
        return true;
    }
    function inferReferenceFromResults(points: PudoNormalized[] = []): PudoReferencePoint | null {
        const coords = points.map((p) => ({ latitude: parseCoordinate(p?.latitude), longitude: parseCoordinate(p?.longitude) }))
            .filter((c): c is CoordinatePoint => c.latitude !== null && c.longitude !== null && Number.isFinite(c.latitude) && Number.isFinite(c.longitude));
        if (!coords.length) return null;
        return { latitude: coords.reduce((s, c) => s + c.latitude, 0) / coords.length, longitude: coords.reduce((s, c) => s + c.longitude, 0) / coords.length,
            source: 'results', address: searchAddress.value || '', city: searchCity.value || '', zip_code: searchZip.value || '',
            label: [searchCity.value, searchZip.value].filter(Boolean).join(' ').trim() || 'Area selezionata' };
    }

    /** Applica risultati ricalcolando distanze. @returns true se la selezione corrente e' stata invalidata. */
    function applyResults(rawPoints: unknown[]) {
        const normalized = (rawPoints || []).map(normalizePudoPoint);
        let distRef = referencePoint.value;
        const allZero = normalized.length > 0 && normalized.every((p) => Number.isFinite(Number(p.distance_meters)) && Number(p.distance_meters) === 0);
        if (!distRef) {
            const inferred = inferReferenceFromResults(normalized);
            if (inferred) { referencePoint.value = inferred; distRef = inferred; }
        }
        const withDist = normalized.map((point) => {
            const apiD = Number(point.distance_meters), hasApi = Number.isFinite(apiD);
            if (distRef && point.latitude !== null && point.longitude !== null && Number.isFinite(point.latitude) && Number.isFinite(point.longitude)) {
                const computedDist = distanceInMeters(distRef, { latitude: point.latitude, longitude: point.longitude });
                const shouldReplace = !hasApi || apiD <= 0 || allZero;
                if (computedDist !== null && shouldReplace) return { ...point, distance_meters: computedDist };
                if (computedDist !== null && hasApi && apiD > 0 && Math.abs(apiD - computedDist) > 200000) return { ...point, distance_meters: computedDist };
                return { ...point, distance_meters: hasApi ? apiD : computedDist ?? null };
            }
            if (allZero && Number(point.distance_meters) === 0) return { ...point, distance_meters: null };
            return point;
        });
        pudoResults.value = sortPudoByDistance(dedupePudoPoints(withDist));
        if (selectedPudoKey.value && !pudoResults.value.some((p) => String(p.ui_key) === String(selectedPudoKey.value))) {
            selectedPudoKey.value = null;
            return true;
        }
        return false;
    }

    // PUDO API calls
    async function fetchNearbyPudo(latitude: unknown, longitude: unknown, maxResults = 50): Promise<unknown[]> {
        const params = new URLSearchParams({ latitude: String(latitude), longitude: String(longitude), max_results: String(maxResults) });
        return extractPudoList(await publicApiFetch<PudoApiResponse>(`/api/brt/pudo/nearby?${params.toString()}`));
    }
    function setSearchError(error: unknown) {
        const status = getPudoErrorStatus(error), backendMessage = getPudoErrorMessage(error);
        if (status === 401 || status === 403) searchError.value = 'Servizio punti di ritiro temporaneamente non disponibile. Riprova tra poco.';
        else if (status === 422) searchError.value = backendMessage || 'Inserisci almeno citta o CAP per cercare i punti di ritiro.';
        else if (status >= 500) searchError.value = 'Il servizio BRT non risponde al momento. Riprova tra qualche minuto.';
        else searchError.value = backendMessage ? `Errore: ${backendMessage}` : 'Errore durante la ricerca. Riprova.';
    }

    /** @returns true se la selezione corrente e' stata invalidata. */
    async function searchPudo(): Promise<boolean> {
        if (!hasSearchInput.value) return false;
        loading.value = true; searched.value = true;
        searchError.value = null; searchMeta.value = null; pudoResults.value = [];
        let invalidated = false;
        try {
            const params = new URLSearchParams();
            if (searchAddress.value?.trim()) params.set('address', searchAddress.value.trim());
            if (searchCity.value?.trim()) params.set('city', searchCity.value.trim());
            if (searchZip.value?.trim()) params.set('zip_code', searchZip.value.trim());
            params.set('country', 'ITA'); params.set('max_results', '50');
            const result = await publicApiFetch<PudoApiResponse>(`/api/brt/pudo/search?${params.toString()}`);
            if (result?.success === false) { searchError.value = result?.error || 'Errore durante la ricerca dei punti di ritiro.'; return false; }
            let points = extractPudoList(result);
            const apiMeta = extractMeta(result);
            let strategyUsed = Array.isArray(apiMeta.strategy_used) ? [...apiMeta.strategy_used] : [];
            if (!referencePoint.value || referencePoint.value.source !== 'manual') {
                try {
                    const geocoded = await geocodeFromSearchFields();
                    if (geocoded) setReferencePoint(geocoded.latitude, geocoded.longitude, 'fields', { label: geocoded.label });
                } catch { /* geocoding non disponibile */ }
            }
            if (applyResults(points)) invalidated = true;
            searchMeta.value = { ...apiMeta, strategy_used: strategyUsed.length ? strategyUsed : apiMeta.strategy_used, returned_count: pudoResults.value.length, requested_count: 50 };
            if (referencePoint.value) {
                try {
                    const nearby = await fetchNearbyPudo(referencePoint.value.latitude, referencePoint.value.longitude, 50);
                    if (nearby.length) {
                        const merged = dedupePudoPoints([...points.map(normalizePudoPoint), ...nearby.map(normalizePudoPoint)]);
                        strategyUsed = Array.from(new Set([...strategyUsed, 'nearby_geo']));
                        if (applyResults(merged)) invalidated = true;
                        points = merged;
                    }
                } catch { /* nearby non disponibile */ }
            }
            searchMeta.value = { ...apiMeta, strategy_used: strategyUsed.length ? strategyUsed : apiMeta.strategy_used, returned_count: pudoResults.value.length, requested_count: 50 };
        } catch (error) { setSearchError(error); pudoResults.value = []; }
        finally { loading.value = false; }
        return invalidated;
    }

    /** @returns true se la selezione corrente e' stata invalidata. */
    async function useCurrentLocation(): Promise<boolean> {
        if (!navigator?.geolocation) { searchError.value = 'Geolocalizzazione non supportata dal browser.'; return false; }
        geolocating.value = true; searchError.value = null; referenceUpdateMessage.value = '';
        let invalidated = false;
        try {
            const position = await new Promise<GeolocationPosition>((resolve, reject) =>
                navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }));
            const lat = position?.coords?.latitude, lng = position?.coords?.longitude;
            const reversed = await reverseGeocodeNominatim(lat, lng);
            applyReverseGeo(reversed);
            if (!setReferencePoint(lat, lng, 'geo', { label: reversed?.label || 'Posizione attuale',
                address: reversed?.address || '', city: reversed?.city || '', zip_code: reversed?.zip_code || '' })) throw new Error('Coordinate non valide.');
            referenceUpdateMessage.value = 'Riferimento aggiornato dalla tua posizione. Ricerca punti avviata automaticamente.';
            if (hasSearchInput.value) return (invalidated = await searchPudo());
            loading.value = true; searched.value = true;
            const nearby = await fetchNearbyPudo(lat, lng, 50);
            if (applyResults(nearby)) invalidated = true;
            searchMeta.value = buildNearbyMeta();
        } catch (error) {
            const status = getPudoErrorStatus(error), geoCode = Number(asRecord(error).code || 0);
            if (status >= 500) searchError.value = 'Servizio geolocalizzazione temporaneamente non disponibile.';
            else if (geoCode === 1) searchError.value = 'Permesso posizione negato. Attiva la geolocalizzazione per cercare i punti vicini.';
            else if (geoCode === 3) searchError.value = 'Timeout posizione. Riprova oppure usa citta e CAP.';
            else searchError.value = 'Impossibile recuperare la posizione attuale.';
            pudoResults.value = []; searched.value = true;
        } finally { loading.value = false; geolocating.value = false; }
        return invalidated;
    }

    /** @returns true se la selezione corrente e' stata invalidata. */
    async function onMapReferenceClick(payload: MapReferencePayload): Promise<boolean> {
        const lat = parseCoordinate(payload?.latitude), lng = parseCoordinate(payload?.longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false;
        mapClickLoading.value = true; searchError.value = null; referenceUpdateMessage.value = '';
        let invalidated = false;
        try {
            const reversed = await reverseGeocodeNominatim(lat, lng);
            applyReverseGeo(reversed);
            setReferencePoint(lat, lng, 'manual', { label: reversed?.label || 'Punto selezionato da mappa',
                address: reversed?.address || '', city: reversed?.city || '', zip_code: reversed?.zip_code || '' });
            referenceUpdateMessage.value = 'Riferimento mappa aggiornato. Ricerca punti avviata automaticamente.';
            if (hasSearchInput.value) return (invalidated = await searchPudo());
            loading.value = true; searched.value = true;
            const nearby = await fetchNearbyPudo(lat, lng, 50);
            if (applyResults(nearby)) invalidated = true;
            searchMeta.value = buildNearbyMeta();
        } catch { searchError.value = 'Posizione mappa rilevata, ma non sono riuscito ad aggiornare i punti ora. Riprova.'; }
        finally { loading.value = false; mapClickLoading.value = false; }
        return invalidated;
    }

    async function fetchPudoDetails(pudo: PudoNormalized, detailKey: string): Promise<void> {
        detailsErrors.value[detailKey] = null;
        if (!pudo?.pudo_id) { detailsErrors.value[detailKey] = 'Dettagli non disponibili per questo punto.'; return; }
        loadingDetailsKey.value = detailKey;
        try {
            const result = await publicApiFetch<PudoApiResponse>(`/api/brt/pudo/${pudo.pudo_id}`);
            const dataField = asRecord(result.data);
            const p = asRecord(result.pudo ?? dataField.pudo ?? dataField ?? result);
            pudoDetails.value[detailKey] = {
                opening_hours: ((p.opening_hours ?? p.hours ?? pudo.opening_hours ?? '') || null),
                localization_hint: String((p.localization_hint ?? p.localizationHint ?? pudo.localization_hint) ?? ''),
                enabled: typeof p.enabled === 'boolean' ? p.enabled : pudo.enabled,
            };
        } catch (error) {
            const status = getPudoErrorStatus(error);
            if (status === 401) detailsErrors.value[detailKey] = 'Dettagli non disponibili al momento.';
            else if (status >= 500) detailsErrors.value[detailKey] = 'Errore server nel caricamento dettagli.';
            else detailsErrors.value[detailKey] = 'Impossibile caricare i dettagli di questo punto.';
        } finally { loadingDetailsKey.value = null; }
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
});
