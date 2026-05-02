/**
 * usePudoSearchApi — fetch BRT PUDO + geocoding Nominatim + normalize/dedup/sort.
 */
import { computed, ref, watch } from 'vue'
import {
	parseCoordinate, extractLatitude, extractLongitude, parseDistanceMeters,
	isFiniteCoordinate, normalizeTextKey,
	getPudoErrorStatus as getErrorStatus, getPudoErrorMessage as getErrorMessage,
} from '~/utils/pudoHelpers'
import type { CoordinatePoint, PudoNormalized, PudoRawPoint } from '~/utils/pudoHelpers'

type PudoSearchProps = { initialCity?: string; initialZip?: string }
type PudoEmit = (event: string, ...payload: unknown[]) => void
type SearchMeta = { strategy_used?: string[]; returned_count?: number; requested_count?: number; provider?: string; fallback?: boolean; [key: string]: unknown }
type ReferencePoint = CoordinatePoint & { source: string; address: string; city: string; zip_code: string; label: string }
type PudoDetail = { opening_hours: unknown; localization_hint: string; enabled: boolean }
type PudoApiResponse = { success?: boolean; error?: string; pudo?: unknown[]; meta?: SearchMeta; data?: { pudo?: unknown[]; meta?: SearchMeta; [key: string]: unknown }; [key: string]: unknown }

const asRecord = (v: unknown): Record<string, unknown> => v && typeof v === 'object' ? v as Record<string, unknown> : {}
const asRawPoint = (v: unknown): PudoRawPoint => v && typeof v === 'object' ? v as PudoRawPoint : {}
const getPudoList = (r: PudoApiResponse | null | undefined): unknown[] =>
	Array.isArray(r?.pudo) ? r.pudo : Array.isArray(r?.data?.pudo) ? r.data.pudo : []
const getApiMeta = (r: PudoApiResponse | null | undefined): SearchMeta => r?.meta || r?.data?.meta || {}
const finiteOrInf = (v: unknown): number => Number.isFinite(Number(v)) ? Number(v) : Number.POSITIVE_INFINITY

const STRATEGY_LABELS: Record<string, string> = {
	city_zip: 'citta + CAP',
	city_only: 'solo citta',
	city_alt_zip: 'citta con CAP alternativi',
	zip_only: 'solo CAP',
	nearby_geo: 'integrazione nearby geolocalizzata',
	nearby_geo_input: 'nearby da geocodifica indirizzo',
	nearby_geo_grid: 'copertura geografica estesa (griglia)',
	fallback_db: 'fallback database locale',
	fallback_db_coordinates: 'fallback database da coordinate',
}

export function usePudoSearchApi(props: PudoSearchProps = {}, emit: PudoEmit = () => {}) {
	const apiBase = useRuntimeConfig().public?.apiBase || ''
	const apiFetch = <T = PudoApiResponse>(path: string): Promise<T> =>
		$fetch<T>(path.startsWith('http') ? path : `${apiBase}${path}`, { method: 'GET', credentials: 'include', timeout: 15000 })

	const searchAddress = ref(''), searchCity = ref(props.initialCity || ''), searchZip = ref(props.initialZip || '')
	const loading = ref(false), geolocating = ref(false), searched = ref(false), mapClickLoading = ref(false)
	const searchError = ref<string | null>(null)
	const searchMeta = ref<SearchMeta | null>(null)
	const referenceUpdateMessage = ref('')
	const pudoResults = ref<PudoNormalized[]>([])
	const selectedPudoKey = ref<string | null>(null), expandedPudoKey = ref<string | null>(null), loadingDetailsKey = ref<string | null>(null)
	const pudoDetails = ref<Record<string, PudoDetail>>({})
	const detailsErrors = ref<Record<string, string | null>>({})
	const referencePoint = ref<ReferencePoint | null>(null)

	watch(() => props.initialCity, (v) => { if (v && !searchCity.value) searchCity.value = v })
	watch(() => props.initialZip, (v) => { if (v && !searchZip.value) searchZip.value = v })

	const hasSearchInput = computed(() => Boolean(searchCity.value?.trim() || searchZip.value?.trim()))
	const mapPoints = computed(() => pudoResults.value.filter((p) => isFiniteCoordinate(p.latitude) && isFiniteCoordinate(p.longitude)))
	const mapReferencePoint = computed(() => {
		const r = referencePoint.value
		if (!r) return null
		return { latitude: r.latitude, longitude: r.longitude, address: r.address || '', city: r.city || '', zip_code: r.zip_code || '', label: r.label || '' }
	})
	const strategyListLabel = computed(() => {
		const s = Array.isArray(searchMeta.value?.strategy_used) ? searchMeta.value.strategy_used : []
		return s.map((item) => STRATEGY_LABELS[item] || item).join(' • ')
	})

	const getPudoUiKey = (point: unknown): string => {
		const p = asRawPoint(point)
		const primary = String(p.pudo_id || p.carrier_pudo_id || p.id || '').trim()
		if (primary) return primary
		const lat = extractLatitude(p)
		const lng = extractLongitude(p)
		return [normalizeTextKey(p.name), normalizeTextKey(p.address), normalizeTextKey(p.zip_code), normalizeTextKey(p.city),
			lat !== null ? lat.toFixed(6) : 'na', lng !== null ? lng.toFixed(6) : 'na'].join('|')
	}

	const normalizePudoPoint = (raw: unknown): PudoNormalized => {
		const p = asRawPoint(raw), id = p.pudo_id || p.carrier_pudo_id || p.id || ''
		return {
			pudo_id: String(id), carrier_pudo_id: String(p.carrier_pudo_id || id || ''), ui_key: getPudoUiKey(p),
			provider: String(p.provider || 'BRT'), name: String(p.name || 'Punto di ritiro BRT'),
			address: String(p.address || ''), city: String(p.city || ''), zip_code: String(p.zip_code || ''),
			province: String(p.province || ''), country: String(p.country || 'ITA'),
			latitude: extractLatitude(p), longitude: extractLongitude(p),
			distance_meters: parseDistanceMeters(p.distance_meters ?? p.distance ?? p.distance_text ?? p.distance_label),
			enabled: typeof p.enabled === 'boolean' ? p.enabled : true,
			opening_hours: p.opening_hours ?? null,
			localization_hint: String(p.localization_hint || ''),
		}
	}

	const dedupePudoPoints = (points: PudoNormalized[]): PudoNormalized[] => {
		const byKey = new Map<string, PudoNormalized>()
		points.forEach((point) => {
			const key = getPudoUiKey(point)
			const current = byKey.get(key)
			if (!current || finiteOrInf(point.distance_meters) < finiteOrInf(current.distance_meters)) byKey.set(key, point)
		})
		return Array.from(byKey.values())
	}

	const sortByDistance = (points: PudoNormalized[]): PudoNormalized[] => [...points].sort((a, b) => {
		const aD = finiteOrInf(a.distance_meters), bD = finiteOrInf(b.distance_meters)
		return aD !== bD ? aD - bD : String(a.name || '').localeCompare(String(b.name || ''), 'it', { sensitivity: 'base' })
	})

	const setReferencePoint = (lat: unknown, lng: unknown, source = 'fields', extra: Partial<Omit<ReferencePoint, 'latitude' | 'longitude' | 'source'>> = {}) => {
		const la = parseCoordinate(lat), ln = parseCoordinate(lng)
		if (la === null || ln === null) return false
		referencePoint.value = {
			latitude: la, longitude: ln, source,
			address: extra.address || searchAddress.value || '',
			city: extra.city || searchCity.value || '',
			zip_code: extra.zip_code || searchZip.value || '',
			label: extra.label || '',
		}
		return true
	}

	const inferReferenceFromResults = (points: Array<PudoNormalized | PudoRawPoint> = []): ReferencePoint | null => {
		const coords = points.map((p) => {
			const r = asRawPoint(p)
			return { latitude: parseCoordinate(r.latitude ?? r.lat), longitude: parseCoordinate(r.longitude ?? r.lng) }
		}).filter((c): c is CoordinatePoint => c.latitude !== null && c.longitude !== null)
		if (!coords.length) return null
		return {
			latitude: coords.reduce((s, c) => s + c.latitude, 0) / coords.length,
			longitude: coords.reduce((s, c) => s + c.longitude, 0) / coords.length,
			source: 'results', address: searchAddress.value || '', city: searchCity.value || '', zip_code: searchZip.value || '',
			label: [searchCity.value, searchZip.value].filter(Boolean).join(' ').trim() || 'Area selezionata',
		}
	}

	const distanceInMeters = (from: CoordinatePoint | null | undefined, to: CoordinatePoint | null | undefined): number | null => {
		if (!from || !to) return null
		const rad = (d: number) => d * (Math.PI / 180)
		const dLat = rad(to.latitude - from.latitude), dLng = rad(to.longitude - from.longitude)
		const a = Math.sin(dLat / 2) ** 2 + Math.cos(rad(from.latitude)) * Math.cos(rad(to.latitude)) * Math.sin(dLng / 2) ** 2
		return Math.round(6371000 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)))
	}

	const applyResults = (rawPoints: unknown[]) => {
		const normalized = (rawPoints || []).map(normalizePudoPoint)
		const allZero = normalized.length > 0 && normalized.every((p) => Number.isFinite(Number(p.distance_meters)) && Number(p.distance_meters) === 0)
		if (!referencePoint.value) {
			const inferred = inferReferenceFromResults(normalized)
			if (inferred) referencePoint.value = inferred
		}
		const distRef = referencePoint.value

		pudoResults.value = sortByDistance(dedupePudoPoints(normalized.map((point) => {
			const apiD = Number(point.distance_meters), hasApi = Number.isFinite(apiD)
			if (distRef && point.latitude !== null && point.longitude !== null) {
				const computed = distanceInMeters(distRef, { latitude: point.latitude, longitude: point.longitude })
				if ((!hasApi || apiD <= 0 || allZero) && computed !== null) return { ...point, distance_meters: computed }
				if (hasApi && apiD > 0 && computed !== null && Math.abs(apiD - computed) > 200000) return { ...point, distance_meters: computed }
				return { ...point, distance_meters: hasApi ? apiD : computed ?? null }
			}
			if (allZero && Number(point.distance_meters) === 0) return { ...point, distance_meters: null }
			return point
		})))
		if (selectedPudoKey.value && !pudoResults.value.some((p) => String(p.ui_key) === String(selectedPudoKey.value))) {
			selectedPudoKey.value = null
			emit('deselect')
		}
	}

	const fetchNominatim = async (path: string): Promise<unknown> => {
		const controller = new AbortController()
		const timeoutId = window.setTimeout(() => controller.abort(), 4000)
		try {
			const response = await fetch(`https://nominatim.openstreetmap.org/${path}`,
				{ method: 'GET', headers: { Accept: 'application/json' }, signal: controller.signal })
			return response.ok ? await response.json() : null
		} finally { window.clearTimeout(timeoutId) }
	}

	const geocodeFromSearchFields = async () => {
		const parts = [searchAddress.value, searchZip.value, searchCity.value, 'Italia'].map((s) => String(s || '').trim()).filter(Boolean)
		if (!parts.length) return null
		const payload = await fetchNominatim(`search?format=jsonv2&limit=1&q=${encodeURIComponent(parts.join(', '))}`)
		const first = asRawPoint(Array.isArray(payload) ? payload[0] : null)
		const lat = parseCoordinate(first.lat), lng = parseCoordinate(first.lon)
		return lat === null || lng === null ? null : { latitude: lat, longitude: lng, label: String(first.display_name || '') }
	}

	const reverseGeocodeFromCoordinates = async (latitude: unknown, longitude: unknown) => {
		const lat = parseCoordinate(latitude), lng = parseCoordinate(longitude)
		if (lat === null || lng === null) return null
		try {
			const payload = asRecord(await fetchNominatim(`reverse?format=jsonv2&lat=${lat}&lon=${lng}&addressdetails=1`))
			const addr = asRecord(payload.address)
			return {
				address: [addr.road || addr.pedestrian || addr.path || '', addr.house_number || ''].filter(Boolean).join(' ').trim(),
				city: String(addr.city || addr.town || addr.village || addr.municipality || ''),
				zip_code: String(addr.postcode || '').replace(/\D/g, '').slice(0, 5),
				label: String(payload.display_name || ''),
			}
		} catch { return null }
	}

	const fetchNearbyPudo = async (lat: unknown, lng: unknown, max = 50): Promise<unknown[]> =>
		getPudoList(await apiFetch<PudoApiResponse>(`/api/brt/pudo/nearby?latitude=${lat}&longitude=${lng}&max_results=${max}`))

	const searchPudo = async () => {
		if (!hasSearchInput.value) return
		loading.value = true
		searched.value = true
		searchError.value = null
		searchMeta.value = null
		pudoResults.value = []

		try {
			const params = new URLSearchParams({ country: 'ITA', max_results: '50' })
			if (searchAddress.value?.trim()) params.set('address', searchAddress.value.trim())
			if (searchCity.value?.trim()) params.set('city', searchCity.value.trim())
			if (searchZip.value?.trim()) params.set('zip_code', searchZip.value.trim())

			const result = await apiFetch<PudoApiResponse>(`/api/brt/pudo/search?${params.toString()}`)
			if (result?.success === false) { searchError.value = result?.error || 'Errore durante la ricerca dei punti di ritiro.'; return }

			let points = getPudoList(result)
			const apiMeta = getApiMeta(result)
			let strategyUsed = Array.isArray(apiMeta.strategy_used) ? [...apiMeta.strategy_used] : []

			if (!referencePoint.value || referencePoint.value.source !== 'manual') {
				try {
					const geocoded = await geocodeFromSearchFields()
					if (geocoded) setReferencePoint(geocoded.latitude, geocoded.longitude, 'fields', { label: geocoded.label })
				} catch { /* geocoding non disponibile */ }
			}

			applyResults(points)

			if (referencePoint.value) {
				try {
					const nearbyPoints = await fetchNearbyPudo(referencePoint.value.latitude, referencePoint.value.longitude, 50)
					if (nearbyPoints.length) {
						points = dedupePudoPoints([...points.map(normalizePudoPoint), ...nearbyPoints.map(normalizePudoPoint)])
						strategyUsed = Array.from(new Set([...strategyUsed, 'nearby_geo']))
						applyResults(points)
					}
				} catch { /* nearby non disponibile */ }
			}

			searchMeta.value = { ...apiMeta, strategy_used: strategyUsed.length ? strategyUsed : apiMeta.strategy_used, returned_count: pudoResults.value.length, requested_count: 50 }
		} catch (error) {
			const status = getErrorStatus(error), backendMessage = getErrorMessage(error)
			if (status === 401 || status === 403) searchError.value = 'Servizio punti di ritiro temporaneamente non disponibile. Riprova tra poco.'
			else if (status === 422) searchError.value = backendMessage || 'Inserisci almeno citta o CAP per cercare i punti di ritiro.'
			else if (status >= 500) searchError.value = 'Il servizio BRT non risponde al momento. Riprova tra qualche minuto.'
			else searchError.value = backendMessage ? `Errore: ${backendMessage}` : 'Errore durante la ricerca. Riprova.'
			pudoResults.value = []
		} finally {
			loading.value = false
		}
	}

	const updateFromCoordinates = async (lat: number, lng: number, source: 'geo' | 'manual', defaultLabel: string, message: string) => {
		const reversed = await reverseGeocodeFromCoordinates(lat, lng)
		if (reversed?.address) searchAddress.value = reversed.address
		if (reversed?.city) searchCity.value = reversed.city
		if (reversed?.zip_code) searchZip.value = reversed.zip_code
		const ok = setReferencePoint(lat, lng, source, {
			label: reversed?.label || defaultLabel,
			address: reversed?.address || '', city: reversed?.city || '', zip_code: reversed?.zip_code || '',
		})
		if (!ok && source === 'geo') throw new Error('Coordinate non valide.')
		referenceUpdateMessage.value = message
		if (hasSearchInput.value) { await searchPudo(); return }
		loading.value = true
		searched.value = true
		applyResults(await fetchNearbyPudo(lat, lng, 50))
		searchMeta.value = { strategy_used: ['nearby_geo'], returned_count: pudoResults.value.length, requested_count: 50, provider: 'BRT', fallback: false }
	}

	const useCurrentLocation = async () => {
		if (!navigator?.geolocation) { searchError.value = 'Geolocalizzazione non supportata dal browser.'; return }
		geolocating.value = true
		searchError.value = null
		referenceUpdateMessage.value = ''
		try {
			const position = await new Promise<GeolocationPosition>((resolve, reject) => {
				navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 })
			})
			await updateFromCoordinates(position.coords.latitude, position.coords.longitude, 'geo',
				'Posizione attuale', 'Riferimento aggiornato dalla tua posizione. Ricerca punti avviata automaticamente.')
		} catch (error) {
			const status = getErrorStatus(error), geoCode = Number(asRecord(error).code || 0)
			if (status >= 500) searchError.value = 'Servizio geolocalizzazione temporaneamente non disponibile.'
			else if (geoCode === 1) searchError.value = 'Permesso posizione negato. Attiva la geolocalizzazione per cercare i punti vicini.'
			else if (geoCode === 3) searchError.value = 'Timeout posizione. Riprova oppure usa citta e CAP.'
			else searchError.value = 'Impossibile recuperare la posizione attuale.'
			pudoResults.value = []
			searched.value = true
		} finally {
			loading.value = false
			geolocating.value = false
		}
	}

	const onMapReferenceClick = async (payload: Partial<CoordinatePoint>) => {
		const lat = parseCoordinate(payload?.latitude), lng = parseCoordinate(payload?.longitude)
		if (lat === null || lng === null) return
		mapClickLoading.value = true
		searchError.value = null
		referenceUpdateMessage.value = ''
		try {
			await updateFromCoordinates(lat, lng, 'manual',
				'Punto selezionato da mappa', 'Riferimento mappa aggiornato. Ricerca punti avviata automaticamente.')
		} catch {
			searchError.value = 'Posizione mappa rilevata, ma non sono riuscito ad aggiornare i punti ora. Riprova.'
		} finally {
			loading.value = false
			mapClickLoading.value = false
		}
	}

	const fetchPudoDetails = async (pudo: PudoNormalized, detailKey: string) => {
		detailsErrors.value[detailKey] = null
		if (!pudo?.pudo_id) { detailsErrors.value[detailKey] = 'Dettagli non disponibili per questo punto.'; return }

		loadingDetailsKey.value = detailKey
		try {
			const result = await apiFetch<PudoApiResponse>(`/api/brt/pudo/${pudo.pudo_id}`)
			const dataField = asRecord(result?.data)
			const p = asRawPoint(
				(result?.pudo && typeof result.pudo === 'object' ? result.pudo : undefined)
				|| (dataField?.pudo && typeof dataField.pudo === 'object' ? dataField.pudo : undefined)
				|| dataField || result || {},
			)
			pudoDetails.value[detailKey] = {
				opening_hours: ((p.opening_hours ?? p.hours ?? pudo.opening_hours ?? '') || null),
				localization_hint: String((p.localization_hint ?? p.localizationHint ?? pudo.localization_hint) ?? ''),
				enabled: typeof p.enabled === 'boolean' ? p.enabled : pudo.enabled,
			}
		} catch (error) {
			const status = getErrorStatus(error)
			if (status === 401) detailsErrors.value[detailKey] = 'Dettagli non disponibili al momento.'
			else if (status >= 500) detailsErrors.value[detailKey] = 'Errore server nel caricamento dettagli.'
			else detailsErrors.value[detailKey] = 'Impossibile caricare i dettagli di questo punto.'
		} finally {
			loadingDetailsKey.value = null
		}
	}

	return {
		searchAddress, searchCity, searchZip,
		loading, geolocating, searched, searchError, searchMeta, referenceUpdateMessage,
		pudoResults, selectedPudoKey, expandedPudoKey, loadingDetailsKey, pudoDetails, detailsErrors, mapClickLoading,
		hasSearchInput, mapPoints, mapReferencePoint, strategyListLabel,
		searchPudo, useCurrentLocation, onMapReferenceClick, fetchPudoDetails,
	}
}
