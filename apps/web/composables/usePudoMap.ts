/**
 * @file usePudoMap — helper mappa PUDO.
 * Estratto da usePudo.js. Selezione/dettagli PUDO, orari giornalieri,
 * stato aperto/chiuso, timer "ora corrente".
 */
import { ref, computed, onBeforeUnmount } from 'vue'

/* ============================================================================
 * SEZIONE 2 — HELPER MAPPA (SELEZIONE + DETTAGLI + ORARI)
 * (ex `usePudoMap.js`)
 *
 * Responsabilita':
 *   - selezione PUDO (toggle) e apertura dettagli on-demand
 *   - formattazione distanza (m / km)
 *   - parsing orari di oggi per il giorno della settimana corrente
 *   - calcolo stato aperto/chiuso dal range orario del giorno
 *   - timer interno per invalidare "ora corrente" ogni minuto
 * ============================================================================ */

/** Helper mappa PUDO: espone selezione, dettagli e info (orari/distanza/stato). */
export function usePudoMap(deps, emit) {
	const {
		selectedPudoKey,
		expandedPudoKey,
		pudoDetails,
		fetchPudoDetails,
	} = deps

	const nowTick = ref(Date.now())
	let nowTimer = null

	const startNowTimer = () => { nowTimer = window.setInterval(() => { nowTick.value = Date.now() }, 60000) }
	const stopNowTimer = () => { if (nowTimer) { window.clearInterval(nowTimer); nowTimer = null } }

	// Safety net: se il caller scorda stopNowTimer, evitiamo il leak su unmount/HMR.
	onScopeDispose(stopNowTimer)

	const selectPudo = (pudo) => {
		if (selectedPudoKey.value === pudo.ui_key) { selectedPudoKey.value = null; emit('deselect'); return }
		selectedPudoKey.value = pudo.ui_key || null
		emit('select', pudo)
	}

	const toggleDetails = async (pudo) => {
		const detailKey = String(pudo.pudo_id || pudo.ui_key)
		if (expandedPudoKey.value === detailKey) { expandedPudoKey.value = null; return }
		expandedPudoKey.value = detailKey
		if (pudoDetails.value[detailKey]) return
		await fetchPudoDetails(pudo, detailKey)
	}

	const formatDistance = (meters) => {
		const v = Number(meters)
		if (!Number.isFinite(v)) return ''
		return v >= 1000 ? `${(v / 1000).toFixed(1)} km` : `${Math.round(v)} m`
	}

	const hasDistance = (pudo) => Number.isFinite(Number(pudo?.distance_meters))
	const distanceLabel = (pudo) => hasDistance(pudo) ? formatDistance(pudo.distance_meters) : 'n/d'

	const splitHoursParts = (rawHours) => {
		if (!rawHours) return []
		if (Array.isArray(rawHours)) return rawHours.map((i) => String(i || '').trim()).filter(Boolean)
		if (typeof rawHours === 'object') return Object.entries(rawHours).map(([k, v]) => `${k}: ${v}`).filter(Boolean)
		return String(rawHours).split(/[\n|;]/).map((i) => i.trim()).filter(Boolean)
	}

	const dayTokenMap = {
		0: ['dom', 'domenica', 'sun', 'sunday'], 1: ['lun', 'lunedi', 'mon', 'monday'],
		2: ['mar', 'martedi', 'tue', 'tuesday'], 3: ['mer', 'mercoledi', 'wed', 'wednesday'],
		4: ['gio', 'giovedi', 'thu', 'thursday'], 5: ['ven', 'venerdi', 'fri', 'friday'],
		6: ['sab', 'sabato', 'sat', 'saturday'],
	}

	const extractTodayHours = (rawHours) => {
		const dayTokens = dayTokenMap[new Date(nowTick.value).getDay()] || []
		const parts = splitHoursParts(rawHours)
		if (!parts.length) return ''
		const matches = parts.filter((p) => dayTokens.some((t) => p.toLowerCase().includes(t)))
		if (matches.length) return matches.join(' | ')
		return parts.length === 1 ? parts[0] : ''
	}

	const parseHourToMinutes = (hourText) => {
		const normalized = String(hourText || '').trim().replace('.', ':')
		const match = normalized.match(/^(\d{1,2}):(\d{2})$/)
		if (!match) return null
		const h = Number(match[1])
		const m = Number(match[2])
		return Number.isFinite(h) && Number.isFinite(m) ? h * 60 + m : null
	}

	const isCurrentlyOpen = (hoursText) => {
		if (!hoursText) return null
		if (hoursText.toLowerCase().includes('chiuso')) return false
		const ranges = [...hoursText.matchAll(/(\d{1,2}[:.]\d{2})\s*[-\u2013]\s*(\d{1,2}[:.]\d{2})/g)]
		if (!ranges.length) return null
		const nowMinutes = new Date(nowTick.value).getHours() * 60 + new Date(nowTick.value).getMinutes()
		return ranges.some((r) => { const s = parseHourToMinutes(r[1] || ''); const e = parseHourToMinutes(r[2] || ''); return s !== null && e !== null && nowMinutes >= s && nowMinutes <= e })
	}

	const getRawOpeningHours = (pudo) => {
		const dk = String(pudo.pudo_id || pudo.ui_key)
		return (pudoDetails.value[dk] || {}).opening_hours ?? pudo.opening_hours
	}

	const getTodayHoursText = (pudo) => extractTodayHours(getRawOpeningHours(pudo)) || 'Orari di oggi non disponibili'

	const getPudoStatus = (pudo) => {
		const dk = String(pudo.pudo_id || pudo.ui_key)
		const details = pudoDetails.value[dk] || {}
		const enabled = typeof details.enabled === 'boolean' ? details.enabled : pudo.enabled
		const open = isCurrentlyOpen(getTodayHoursText(pudo))
		if (enabled === false) return { label: 'Chiuso', className: 'text-rose-700 bg-rose-50 border-rose-200' }
		if (open === true) return { label: 'Aperto ora', className: 'text-[#0a8a7a] bg-[#f0fdf4] border-[#d1fae5]' }
		if (open === false) return { label: 'Chiuso ora', className: 'text-rose-700 bg-rose-50 border-rose-200' }
		return { label: 'Da verificare', className: 'text-gray-700 bg-gray-100 border-gray-200' }
	}

	const formatOpeningHours = (hours) => {
		if (!hours) return ''
		if (typeof hours === 'string') return hours
		if (Array.isArray(hours)) return hours.join(' | ')
		if (typeof hours === 'object') return Object.entries(hours).map(([d, v]) => `${d}: ${v}`).join(' | ')
		return ''
	}

	return {
		selectPudo, toggleDetails,
		distanceLabel, getTodayHoursText, getPudoStatus, formatOpeningHours,
		startNowTimer, stopNowTimer,
	}
}
