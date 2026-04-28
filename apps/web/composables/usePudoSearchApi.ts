/**
 * usePudo.js — PUDO completo (API+Search+Map). Quattro entry point per retrocompat:
 *   usePudoSearch(props, emit)    → funnel `<PudoSelector />` (/la-tua-spedizione)
 *   usePudoSearch()               → pagina /pudo
 *   usePudoSearchApi(props, emit) → accesso API grezzo (fetch, geocoding, distanze)
 *   usePudoMap(deps, emit)        → helper mappa (selezione, dettagli, orari, stato)
 *
 * @typedef {object} PudoFilters
 * @property {boolean} openNow
 * @property {boolean} ritiro
 * @property {boolean} consegna
 * @property {boolean} sabato
 *
 * @typedef {{ label: string, className: string }} PudoStatus
 */
import { computed, ref, watch } from 'vue'

/* ============================================================================
 * SEZIONE 1 — API FETCH, GEOCODING, NORMALIZZAZIONE, DISTANZE
 * (ex `usePudoSearchApi.js`)
 *
 * Responsabilita':
 *   - fetch `/api/brt/pudo/search`, `/api/brt/pudo/nearby`, `/api/brt/pudo/:id`
 *   - geocoding Nominatim (search + reverse)
 *   - parsing coordinate, deduplica, sort per distanza
 *   - gestione reference point (fields | geo | manual | results)
 * ============================================================================ */

/** @returns {object} composable PUDO search API — state + azioni API grezze. */

export function usePudoSearchApi(props, emit) {
	const config = useRuntimeConfig()
	const apiBase = config.public?.apiBase || ''

	// ── API helpers ──
	const publicApiFetch = async (path) => {
		const url = path.startsWith('http') ? path : `${apiBase}${path}`
		return await $fetch(url, { method: 'GET', credentials: 'include', timeout: 15000 })
	}

	const getErrorStatus = (error) => {
		return Number(error?.status ?? error?.response?.status ?? error?.data?.statusCode ?? 0)
	}
	const getErrorMessage = (error) => {
		return error?.data?.error || error?.data?.message || error?.response?._data?.message || error?.message || ''
	}

	// ── Search state ──
	const searchAddress = ref('')
	const searchCity = ref(props.initialCity || '')
	const searchZip = ref(props.initialZip || '')
	const loading = ref(false)
	const geolocating = ref(false)
	const searched = ref(false)
	const searchError = ref(null)
	const searchMeta = ref(null)
	const referenceUpdateMessage = ref('')

	// ── Results state ──
	const pudoResults = ref([])
	const selectedPudoKey = ref(null)
	const expandedPudoKey = ref(null)
	const loadingDetailsKey = ref(null)
	const pudoDetails = ref({})
	const detailsErrors = ref({})
	const mapClickLoading = ref(false)
	const referencePoint = ref(null)

	// ── Watchers for props ──
	watch(() => props.initialCity, (v) => { if (v && !searchCity.value) searchCity.value = v })
	watch(() => props.initialZip, (v) => { if (v && !searchZip.value) searchZip.value = v })

	// ── Coordinate utilities ──
	const parseCoordinate = (value) => {
		if (value === null || value === undefined || value === '') return null
		const parsed = Number.parseFloat(String(value).trim().replace(',', '.'))
		return Number.isFinite(parsed) ? parsed : null
	}

	const extractLatitude = (point = {}) => {
		const nested = (key) => point[key]
		return parseCoordinate(
			point.latitude ?? point.lat ?? nested('coordinates')?.latitude ?? nested('coordinates')?.lat
			?? nested('coordinate')?.latitude ?? nested('coordinate')?.lat ?? nested('geo')?.latitude ?? nested('geo')?.lat
			?? nested('location')?.latitude ?? nested('location')?.lat ?? nested('address_coordinates')?.latitude ?? nested('address_coordinates')?.lat,
		)
	}

	const extractLongitude = (point = {}) => {
		const nested = (key) => point[key]
		return parseCoordinate(
			point.longitude ?? point.lng ?? point.lon ?? nested('coordinates')?.longitude ?? nested('coordinates')?.lng
			?? nested('coordinates')?.lon ?? nested('coordinate')?.longitude ?? nested('coordinate')?.lng ?? nested('coordinate')?.lon
			?? nested('geo')?.longitude ?? nested('geo')?.lng ?? nested('geo')?.lon ?? nested('location')?.longitude ?? nested('location')?.lng
			?? nested('location')?.lon ?? nested('address_coordinates')?.longitude ?? nested('address_coordinates')?.lng ?? nested('address_coordinates')?.lon,
		)
	}

	const parseDistanceMeters = (value) => {
		if (value === null || value === undefined || value === '') return null
		const raw = String(value).trim().toLowerCase()
		const cleaned = raw.replace(',', '.').replace(/[^\d.-]/g, '')
		if (!cleaned) return null
		const parsed = Number.parseFloat(cleaned)
		if (!Number.isFinite(parsed)) return null
		if (raw.includes('km')) return Math.round(parsed * 1000)
		return Math.round(parsed)
	}

	const isFiniteCoordinate = (value) => Number.isFinite(parseCoordinate(value))
	const normalizeTextKey = (value) => String(value || '').trim().toLowerCase()

	// ── Computed ──
	const hasSearchInput = computed(() => Boolean(searchCity.value?.trim() || searchZip.value?.trim()))
	const mapPoints = computed(() =>
		pudoResults.value.filter((p) => isFiniteCoordinate(p.latitude) && isFiniteCoordinate(p.longitude)),
	)
	const mapReferencePoint = computed(() => {
		if (!referencePoint.value) return null
		return {
			latitude: referencePoint.value.latitude,
			longitude: referencePoint.value.longitude,
			address: referencePoint.value.address || '',
			city: referencePoint.value.city || '',
			zip_code: referencePoint.value.zip_code || '',
			label: referencePoint.value.label || '',
		}
	})

	const strategyListLabel = computed(() => {
		const strategies = Array.isArray(searchMeta.value?.strategy_used) ? searchMeta.value.strategy_used : []
		if (!strategies.length) return ''
		const labels = {
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
		return strategies.map((item) => labels[item] || item).join(' \u2022 ')
	})

	// ── PUDO key/normalization ──
	const getPudoUiKey = (point) => {
		const p = point || {}
		const primary = String(p.pudo_id || p.carrier_pudo_id || p.id || '').trim()
		if (primary) return primary
		const lat = extractLatitude(p)
		const lng = extractLongitude(p)
		return [
			normalizeTextKey(p.name),
			normalizeTextKey(p.address),
			normalizeTextKey(p.zip_code),
			normalizeTextKey(p.city),
			Number.isFinite(lat) ? lat.toFixed(6) : 'na',
			Number.isFinite(lng) ? lng.toFixed(6) : 'na',
		].join('|')
	}

	const normalizePudoPoint = (rawPoint) => {
		const point = rawPoint || {}
		const id = point.pudo_id || point.carrier_pudo_id || point.id || ''
		const latitude = extractLatitude(point)
		const longitude = extractLongitude(point)
		const distance = parseDistanceMeters(point.distance_meters ?? point.distance ?? point.distance_text ?? point.distance_label)
		return {
			pudo_id: String(id),
			carrier_pudo_id: String(point.carrier_pudo_id || id || ''),
			ui_key: getPudoUiKey(point),
			provider: String(point.provider || 'BRT'),
			name: point.name || 'Punto di ritiro BRT',
			address: point.address || '',
			city: point.city || '',
			zip_code: point.zip_code || '',
			province: point.province || '',
			country: point.country || 'ITA',
			latitude,
			longitude,
			distance_meters: distance,
			enabled: typeof point.enabled === 'boolean' ? point.enabled : true,
			opening_hours: point.opening_hours ?? null,
			localization_hint: point.localization_hint || '',
		}
	}

	const dedupePudoPoints = (points) => {
		const byKey = new Map()
		points.forEach((point) => {
			const key = getPudoUiKey(point)
			if (!byKey.has(key)) { byKey.set(key, point); return }
			const current = byKey.get(key)
			const currentD = Number.isFinite(Number(current.distance_meters)) ? Number(current.distance_meters) : Number.POSITIVE_INFINITY
			const incomingD = Number.isFinite(Number(point.distance_meters)) ? Number(point.distance_meters) : Number.POSITIVE_INFINITY
			if (incomingD < currentD) byKey.set(key, point)
		})
		return Array.from(byKey.values())
	}

	const sortByDistance = (points) =>
		[...points].sort((a, b) => {
			const aD = Number.isFinite(Number(a.distance_meters)) ? Number(a.distance_meters) : Number.POSITIVE_INFINITY
			const bD = Number.isFinite(Number(b.distance_meters)) ? Number(b.distance_meters) : Number.POSITIVE_INFINITY
			if (aD !== bD) return aD - bD
			return String(a.name || '').localeCompare(String(b.name || ''), 'it', { sensitivity: 'base' })
		})

	// ── Reference point ──
	const setReferencePoint = (
		latitude,
		longitude,
		source = 'fields',
		extra = {},
	) => {
		const lat = parseCoordinate(latitude)
		const lng = parseCoordinate(longitude)
		if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false
		referencePoint.value = {
			latitude: lat,
			longitude: lng,
			source,
			address: extra.address || searchAddress.value || '',
			city: extra.city || searchCity.value || '',
			zip_code: extra.zip_code || searchZip.value || '',
			label: extra.label || '',
		}
		return true
	}

	const inferReferenceFromResults = (points = []) => {
		const coords = points.map((p) => ({
			latitude: parseCoordinate(p?.latitude ?? p?.lat),
			longitude: parseCoordinate(p?.longitude ?? p?.lng),
		}))
			.filter((c) => Number.isFinite(c.latitude) && Number.isFinite(c.longitude))
		if (!coords.length) return null
		return {
			latitude: coords.reduce((s, c) => s + c.latitude, 0) / coords.length,
			longitude: coords.reduce((s, c) => s + c.longitude, 0) / coords.length,
			source: 'results',
			address: searchAddress.value || '',
			city: searchCity.value || '',
			zip_code: searchZip.value || '',
			label: [searchCity.value, searchZip.value].filter(Boolean).join(' ').trim() || 'Area selezionata',
		}
	}

	// ── Distance calculation ──
	const toRadians = (deg) => deg * (Math.PI / 180)
	const distanceInMeters = (from, to) => {
		if (!from || !to) return null
		const R = 6371000
		const dLat = toRadians(to.latitude - from.latitude)
		const dLng = toRadians(to.longitude - from.longitude)
		const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRadians(from.latitude)) * Math.cos(toRadians(to.latitude)) * Math.sin(dLng / 2) ** 2
		return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)))
	}

	// ── Apply results with distance recalculation ──
	const applyResults = (rawPoints) => {
		const normalized = (rawPoints || []).map(normalizePudoPoint)
		let distRef = referencePoint.value
		const allZero = normalized.length > 0 && normalized.every((p) => Number.isFinite(Number(p.distance_meters)) && Number(p.distance_meters) === 0)

		if (!distRef) {
			const inferred = inferReferenceFromResults(normalized)
			if (inferred) { referencePoint.value = inferred; distRef = inferred }
		}

		const withDist = normalized.map((point) => {
			const apiD = Number(point.distance_meters)
			const hasApi = Number.isFinite(apiD)
			if (distRef && Number.isFinite(point.latitude) && Number.isFinite(point.longitude)) {
				const computed = distanceInMeters(distRef, { latitude: point.latitude, longitude: point.longitude })
				const shouldReplace = !hasApi || apiD <= 0 || allZero
				if (shouldReplace && Number.isFinite(computed)) return { ...point, distance_meters: computed }
				if (hasApi && apiD > 0 && Number.isFinite(computed) && Math.abs(apiD - computed) > 200000) return { ...point, distance_meters: computed }
				return { ...point, distance_meters: hasApi ? apiD : computed ?? null }
			}
			if (allZero && Number(point.distance_meters) === 0) return { ...point, distance_meters: null }
			return point
		})

		pudoResults.value = sortByDistance(dedupePudoPoints(withDist))

		if (selectedPudoKey.value) {
			const exists = pudoResults.value.some((p) => String(p.ui_key) === String(selectedPudoKey.value))
			if (!exists) { selectedPudoKey.value = null; emit('deselect') }
		}
	}

	// ── Geocoding (Nominatim OpenStreetMap) ──
	const fetchWithTimeout = async (url, options = {}, timeoutMs = 4500) => {
		const controller = new AbortController()
		const timeoutId = window.setTimeout(() => controller.abort(), timeoutMs)
		try {
			return await fetch(url, { ...options, signal: controller.signal })
		} finally {
			window.clearTimeout(timeoutId)
		}
	}

	const geocodeFromSearchFields = async () => {
		const parts = [searchAddress.value, searchZip.value, searchCity.value, 'Italia'].map((s) => String(s || '').trim()).filter(Boolean)
		if (!parts.length) return null
		const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(parts.join(', '))}`
		const response = await fetchWithTimeout(url, { method: 'GET', headers: { Accept: 'application/json' } }, 4000)
		if (!response.ok) return null
		const payload = await response.json()
		const first = Array.isArray(payload) ? payload[0] : null
		if (!first) return null
		const lat = parseCoordinate(first.lat)
		const lng = parseCoordinate(first.lon)
		if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null
		return { latitude: lat, longitude: lng, label: first.display_name || '' }
	}

	const reverseGeocodeFromCoordinates = async (
		latitude,
		longitude,
	) => {
		const lat = parseCoordinate(latitude)
		const lng = parseCoordinate(longitude)
		if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null
		try {
			const params = new URLSearchParams({ format: 'jsonv2', lat: String(lat), lon: String(lng), addressdetails: '1' })
			const response = await fetchWithTimeout(`https://nominatim.openstreetmap.org/reverse?${params.toString()}`, { method: 'GET', headers: { Accept: 'application/json' } }, 4000)
			if (!response.ok) return null
			const payload = await response.json()
			const addr = payload?.address || {}
			const street = addr.road || addr.pedestrian || addr.path || ''
			const houseNumber = addr.house_number || ''
			return {
				address: [street, houseNumber].filter(Boolean).join(' ').trim(),
				city: addr.city || addr.town || addr.village || addr.municipality || '',
				zip_code: String(addr.postcode || '').replace(/\D/g, '').slice(0, 5),
				label: payload?.display_name || '',
			}
		} catch {
			return null
		}
	}

	// ── PUDO API calls ──
	const fetchNearbyPudo = async (latitude, longitude, maxResults = 50) => {
		const params = new URLSearchParams()
		params.set('latitude', String(latitude))
		params.set('longitude', String(longitude))
		params.set('max_results', String(maxResults))
		const result = await publicApiFetch(`/api/brt/pudo/nearby?${params.toString()}`)
		return result?.pudo || result?.data?.pudo || []
	}

	const searchPudo = async () => {
		if (!hasSearchInput.value) return
		loading.value = true
		searched.value = true
		searchError.value = null
		searchMeta.value = null
		pudoResults.value = []

		try {
			const params = new URLSearchParams()
			if (searchAddress.value?.trim()) params.set('address', searchAddress.value.trim())
			if (searchCity.value?.trim()) params.set('city', searchCity.value.trim())
			if (searchZip.value?.trim()) params.set('zip_code', searchZip.value.trim())
			params.set('country', 'ITA')
			params.set('max_results', '50')

			const result = await publicApiFetch(`/api/brt/pudo/search?${params.toString()}`)
			if (result?.success === false) { searchError.value = result?.error || 'Errore durante la ricerca dei punti di ritiro.'; return }

			let points = result?.pudo || result?.data?.pudo || []
			const apiMeta = result?.meta || result?.data?.meta || {}
			let strategyUsed = Array.isArray(apiMeta.strategy_used) ? [...apiMeta.strategy_used] : []

			if (!referencePoint.value || referencePoint.value.source !== 'manual') {
				try {
					const geocoded = await geocodeFromSearchFields()
					if (geocoded) setReferencePoint(geocoded.latitude, geocoded.longitude, 'fields', { label: geocoded.label })
				} catch { /* geocoding non disponibile */ }
			}

			applyResults(points)
			searchMeta.value = { ...apiMeta, strategy_used: strategyUsed.length ? strategyUsed : apiMeta.strategy_used, returned_count: pudoResults.value.length, requested_count: 50 }

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
			const status = getErrorStatus(error)
			const backendMessage = getErrorMessage(error)
			if (status === 401 || status === 403) searchError.value = 'Servizio punti di ritiro temporaneamente non disponibile. Riprova tra poco.'
			else if (status === 422) searchError.value = backendMessage || 'Inserisci almeno citta o CAP per cercare i punti di ritiro.'
			else if (status >= 500) searchError.value = 'Il servizio BRT non risponde al momento. Riprova tra qualche minuto.'
			else searchError.value = backendMessage ? `Errore: ${backendMessage}` : 'Errore durante la ricerca. Riprova.'
			pudoResults.value = []
		} finally {
			loading.value = false
		}
	}

	// ── Geolocation ──
	const useCurrentLocation = async () => {
		if (!navigator?.geolocation) { searchError.value = 'Geolocalizzazione non supportata dal browser.'; return }
		geolocating.value = true
		searchError.value = null
		referenceUpdateMessage.value = ''

		try {
			const position = await new Promise((resolve, reject) => {
				navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 })
			})
			const lat = position?.coords?.latitude
			const lng = position?.coords?.longitude
			const reversed = await reverseGeocodeFromCoordinates(lat, lng)
			if (reversed?.address) searchAddress.value = reversed.address
			if (reversed?.city) searchCity.value = reversed.city
			if (reversed?.zip_code) searchZip.value = reversed.zip_code

			if (!setReferencePoint(lat, lng, 'geo', {
				label: reversed?.label || 'Posizione attuale',
				address: reversed?.address || '',
				city: reversed?.city || '',
				zip_code: reversed?.zip_code || '',
			})) {
				throw new Error('Coordinate non valide.')
			}
			referenceUpdateMessage.value = 'Riferimento aggiornato dalla tua posizione. Ricerca punti avviata automaticamente.'

			if (hasSearchInput.value) { await searchPudo(); return }

			loading.value = true
			searched.value = true
			const nearbyPoints = await fetchNearbyPudo(lat, lng, 50)
			applyResults(nearbyPoints)
			searchMeta.value = { strategy_used: ['nearby_geo'], returned_count: pudoResults.value.length, requested_count: 50, provider: 'BRT', fallback: false }
		} catch (error) {
			const status = getErrorStatus(error)
			const geoCode = Number(error?.code || 0)
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

	// ── Map click handler ──
	const onMapReferenceClick = async (payload) => {
		const lat = parseCoordinate(payload?.latitude)
		const lng = parseCoordinate(payload?.longitude)
		if (!Number.isFinite(lat) || !Number.isFinite(lng)) return
		mapClickLoading.value = true
		searchError.value = null
		referenceUpdateMessage.value = ''

		try {
			const reversed = await reverseGeocodeFromCoordinates(lat, lng)
			if (reversed?.address) searchAddress.value = reversed.address
			if (reversed?.city) searchCity.value = reversed.city
			if (reversed?.zip_code) searchZip.value = reversed.zip_code
			setReferencePoint(lat, lng, 'manual', {
				label: reversed?.label || 'Punto selezionato da mappa',
				address: reversed?.address || '',
				city: reversed?.city || '',
				zip_code: reversed?.zip_code || '',
			})
			referenceUpdateMessage.value = 'Riferimento mappa aggiornato. Ricerca punti avviata automaticamente.'

			if (hasSearchInput.value) { await searchPudo(); return }

			loading.value = true
			searched.value = true
			const nearbyPoints = await fetchNearbyPudo(lat, lng, 50)
			applyResults(nearbyPoints)
			searchMeta.value = { strategy_used: ['nearby_geo'], returned_count: pudoResults.value.length, requested_count: 50, provider: 'BRT', fallback: false }
		} catch {
			searchError.value = 'Posizione mappa rilevata, ma non sono riuscito ad aggiornare i punti ora. Riprova.'
		} finally {
			loading.value = false
			mapClickLoading.value = false
		}
	}

	// ── PUDO detail fetch ──
	const fetchPudoDetails = async (pudo, detailKey) => {
		detailsErrors.value[detailKey] = null
		if (!pudo?.pudo_id) { detailsErrors.value[detailKey] = 'Dettagli non disponibili per questo punto.'; return }

		loadingDetailsKey.value = detailKey
		try {
			const result = await publicApiFetch(`/api/brt/pudo/${pudo.pudo_id}`)
			const pudoField = result?.pudo
			const dataField = result?.data
			const dataPudo = dataField?.pudo
			const p =
				((pudoField && typeof pudoField === 'object' ? pudoField : undefined)
				|| (dataPudo && typeof dataPudo === 'object' ? dataPudo : undefined)
				|| dataField
				|| result
				|| {})
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
		// Search fields
		searchAddress, searchCity, searchZip,
		// State
		loading, geolocating, searched, searchError, searchMeta, referenceUpdateMessage,
		// Results state
		pudoResults, selectedPudoKey, expandedPudoKey, loadingDetailsKey, pudoDetails, detailsErrors, mapClickLoading,
		// Computed
		hasSearchInput, mapPoints, mapReferencePoint, strategyListLabel,
		// Actions
		searchPudo, useCurrentLocation, onMapReferenceClick, fetchPudoDetails,
	}
}
