/**
 * pudoHelpers — funzioni pure per normalizzazione, distanza, geocoding PUDO.
 *
 * Estratte da `composables/usePudo.js` (split atomico Pinia 2026-04-26).
 * Tutte le funzioni qui dentro sono SSR-safe e non dipendono da Vue/Pinia.
 *
 * @typedef {object} PudoNormalized
 * @property {string} pudo_id
 * @property {string} carrier_pudo_id
 * @property {string} ui_key
 * @property {string} provider
 * @property {string} name
 * @property {string} address
 * @property {string} city
 * @property {string} zip_code
 * @property {string} province
 * @property {string} country
 * @property {number|null} latitude
 * @property {number|null} longitude
 * @property {number|null} distance_meters
 * @property {boolean} enabled
 * @property {unknown} opening_hours
 * @property {string} localization_hint
 */

export const STRATEGY_LABELS = {
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

export function parseCoordinate(value) {
	if (value === null || value === undefined || value === '') return null
	const parsed = Number.parseFloat(String(value).trim().replace(',', '.'))
	return Number.isFinite(parsed) ? parsed : null
}

export function extractLatitude(point = {}) {
	const nested = (key) => point[key]
	return parseCoordinate(
		point.latitude ?? point.lat ?? nested('coordinates')?.latitude ?? nested('coordinates')?.lat
		?? nested('coordinate')?.latitude ?? nested('coordinate')?.lat ?? nested('geo')?.latitude ?? nested('geo')?.lat
		?? nested('location')?.latitude ?? nested('location')?.lat ?? nested('address_coordinates')?.latitude ?? nested('address_coordinates')?.lat,
	)
}

export function extractLongitude(point = {}) {
	const nested = (key) => point[key]
	return parseCoordinate(
		point.longitude ?? point.lng ?? point.lon ?? nested('coordinates')?.longitude ?? nested('coordinates')?.lng
		?? nested('coordinates')?.lon ?? nested('coordinate')?.longitude ?? nested('coordinate')?.lng ?? nested('coordinate')?.lon
		?? nested('geo')?.longitude ?? nested('geo')?.lng ?? nested('geo')?.lon ?? nested('location')?.longitude ?? nested('location')?.lng
		?? nested('location')?.lon ?? nested('address_coordinates')?.longitude ?? nested('address_coordinates')?.lng ?? nested('address_coordinates')?.lon,
	)
}

export function parseDistanceMeters(value) {
	if (value === null || value === undefined || value === '') return null
	const raw = String(value).trim().toLowerCase()
	const cleaned = raw.replace(',', '.').replace(/[^\d.-]/g, '')
	if (!cleaned) return null
	const parsed = Number.parseFloat(cleaned)
	if (!Number.isFinite(parsed)) return null
	if (raw.includes('km')) return Math.round(parsed * 1000)
	return Math.round(parsed)
}

export const isFiniteCoordinate = (value) => Number.isFinite(parseCoordinate(value))
const normalizeTextKey = (value) => String(value || '').trim().toLowerCase()

export function getPudoUiKey(point) {
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

export function normalizePudoPoint(rawPoint) {
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
		distance_meters,
		enabled: typeof point.enabled === 'boolean' ? point.enabled : true,
		opening_hours: point.opening_hours ?? null,
		localization_hint: point.localization_hint || '',
	}
}

export function dedupePudoPoints(points) {
	const byKey = new Map()
	points.forEach((point) => {
		const key = point.ui_key
		if (!byKey.has(key)) { byKey.set(key, point); return }
		const current = byKey.get(key)
		const currentD = Number.isFinite(Number(current.distance_meters)) ? Number(current.distance_meters) : Number.POSITIVE_INFINITY
		const incomingD = Number.isFinite(Number(point.distance_meters)) ? Number(point.distance_meters) : Number.POSITIVE_INFINITY
		if (incomingD < currentD) byKey.set(key, point)
	})
	return Array.from(byKey.values())
}

export function sortPudoByDistance(points) {
	return [...points].sort((a, b) => {
		const aD = Number.isFinite(Number(a.distance_meters)) ? Number(a.distance_meters) : Number.POSITIVE_INFINITY
		const bD = Number.isFinite(Number(b.distance_meters)) ? Number(b.distance_meters) : Number.POSITIVE_INFINITY
		if (aD !== bD) return aD - bD
		return String(a.name || '').localeCompare(String(b.name || ''), 'it', { sensitivity: 'base' })
	})
}

const toRadians = (deg) => deg * (Math.PI / 180)
export function distanceInMeters(from, to) {
	if (!from || !to) return null
	const R = 6371000
	const dLat = toRadians(to.latitude - from.latitude)
	const dLng = toRadians(to.longitude - from.longitude)
	const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRadians(from.latitude)) * Math.cos(toRadians(to.latitude)) * Math.sin(dLng / 2) ** 2
	return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)))
}

export function getPudoErrorStatus(error) {
	return Number(error?.status ?? error?.response?.status ?? error?.data?.statusCode ?? 0)
}
export function getPudoErrorMessage(error) {
	return error?.data?.error || error?.data?.message || error?.response?._data?.message || error?.message || ''
}

export async function fetchWithTimeout(url, options = {}, timeoutMs = 4500) {
	const controller = new AbortController()
	const timeoutId = window.setTimeout(() => controller.abort(), timeoutMs)
	try {
		return await fetch(url, { ...options, signal: controller.signal })
	} finally {
		window.clearTimeout(timeoutId)
	}
}

export async function geocodeNominatim(parts) {
	const valid = parts.map((s) => String(s || '').trim()).filter(Boolean)
	if (!valid.length) return null
	const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(valid.join(', '))}`
	const response = await fetchWithTimeout(url, { method: 'GET', headers: { Accept: 'application/json' } }, 4000)
	if (!response.ok) return null
	const payload = await response.json()
	const first = Array.isArray(payload) ? payload[0] : null
	if (!first) return null
	const lat = parseCoordinate(first.lat)
	const lng = parseCoordinate(first.lon)
	if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null
	return { latitude: lat, longitude, label: first.display_name || '' }
}

// ────────────────────────────────────────────────────────────────────────────
// Map helpers (orari, distanza, stato apertura) — usati dal composable wrapper
// ────────────────────────────────────────────────────────────────────────────

export const PUDO_DAY_TOKENS = {
	0: ['dom', 'domenica', 'sun', 'sunday'], 1: ['lun', 'lunedi', 'mon', 'monday'],
	2: ['mar', 'martedi', 'tue', 'tuesday'], 3: ['mer', 'mercoledi', 'wed', 'wednesday'],
	4: ['gio', 'giovedi', 'thu', 'thursday'], 5: ['ven', 'venerdi', 'fri', 'friday'],
	6: ['sab', 'sabato', 'sat', 'saturday'],
}

export function splitHoursParts(rawHours) {
	if (!rawHours) return []
	if (Array.isArray(rawHours)) return rawHours.map((i) => String(i || '').trim()).filter(Boolean)
	if (typeof rawHours === 'object') return Object.entries(rawHours).map(([k, v]) => `${k}: ${v}`).filter(Boolean)
	return String(rawHours).split(/[\n|;]/).map((i) => i.trim()).filter(Boolean)
}

export function parseHourToMinutes(hourText) {
	const normalized = String(hourText || '').trim().replace('.', ':')
	const match = normalized.match(/^(\d{1,2}):(\d{2})$/)
	if (!match) return null
	const h = Number(match[1])
	const m = Number(match[2])
	return Number.isFinite(h) && Number.isFinite(m) ? h * 60 + m : null
}

export function formatPudoDistance(meters) {
	const v = Number(meters)
	if (!Number.isFinite(v)) return ''
	return v >= 1000 ? `${(v / 1000).toFixed(1)} km` : `${Math.round(v)} m`
}

export function formatOpeningHoursText(hours) {
	if (!hours) return ''
	if (typeof hours === 'string') return hours
	if (Array.isArray(hours)) return hours.join(' | ')
	if (typeof hours === 'object') return Object.entries(hours).map(([d, v]) => `${d}: ${v}`).join(' | ')
	return ''
}

export function extractTodayHoursAt(rawHours, nowMs) {
	const dayTokens = PUDO_DAY_TOKENS[new Date(nowMs).getDay()] || []
	const parts = splitHoursParts(rawHours)
	if (!parts.length) return ''
	const matches = parts.filter((p) => dayTokens.some((t) => p.toLowerCase().includes(t)))
	if (matches.length) return matches.join(' | ')
	return parts.length === 1 ? (parts[0] ?? '') : ''
}

export function isPudoCurrentlyOpenAt(hoursText, nowMs) {
	if (!hoursText) return null
	if (hoursText.toLowerCase().includes('chiuso')) return false
	const ranges = [...hoursText.matchAll(/(\d{1,2}[:.]\d{2})\s*[-\u2013]\s*(\d{1,2}[:.]\d{2})/g)]
	if (!ranges.length) return null
	const nowMinutes = new Date(nowMs).getHours() * 60 + new Date(nowMs).getMinutes()
	return ranges.some((r) => { const s = parseHourToMinutes(r[1] || ''); const e = parseHourToMinutes(r[2] || ''); return s !== null && e !== null && nowMinutes >= s && nowMinutes <= e })
}

export async function reverseGeocodeNominatim(latitude, longitude) {
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
