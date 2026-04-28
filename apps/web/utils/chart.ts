/**
 * utils/chart — helpers puri per i grafici admin.
 *
 * Sono funzioni totalmente prive di stato reattivo: formatters numerici/data
 * + normalizzatori di serie. Esistevano come `useChartLogic()` composable
 * ma il "use" prefix era misleading — qui vivono come modulo utility puro.
 *
 * I caller (AdminChartOrders/Revenue/Status, AdminConsoleAnalytics) importano
 * le singole funzioni necessarie direttamente da `~/utils/chart`.
 *
 * @typedef {number | string | null | undefined} NumericLike
 *
 * @typedef {object} NormalizedPoint
 * @property {string} key
 * @property {string} label
 * @property {string} fullLabel
 * @property {number} value
 * @property {string|null} date
 *
 * @typedef {object} ChartSegment
 * @property {string} key
 * @property {string} label
 * @property {number} count
 * @property {number} share
 */

/** Converte un input numerico in un numero finito. Fallback 0 per non-finiti. */
export const chartToNumber = (value) => {
	const n = Number(value ?? 0)
	return Number.isFinite(n) ? n : 0
}

/** Formatta cents come EUR italiana. Es. 1999 -> "19,99 €". */
export const formatCurrency = (cents) => {
	const euros = chartToNumber(cents) / 100
	return new Intl.NumberFormat('it-IT', {
		style: 'currency',
		currency: 'EUR',
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	}).format(euros)
}

/** Versione compatta: sopra 1000€ elide i decimali, sotto 2 decimali. */
export const formatCurrencyShort = (cents) => {
	const euros = chartToNumber(cents) / 100
	if (euros >= 1000) {
		return new Intl.NumberFormat('it-IT', {
			style: 'currency',
			currency: 'EUR',
			minimumFractionDigits: 0,
			maximumFractionDigits: 0,
		}).format(euros)
	}
	return formatCurrency(cents)
}

/** Percentuale clampata in [0, 1] formattata come "42%". */
export const formatPercentage = (value) => {
	return new Intl.NumberFormat('it-IT', {
		style: 'percent',
		maximumFractionDigits: 0,
	}).format(Math.max(0, Math.min(1, chartToNumber(value))))
}

/** Intero formattato alla italiana (separatori migliaia). */
export const formatInteger = (value) => {
	return new Intl.NumberFormat('it-IT', {
		maximumFractionDigits: 0,
	}).format(Math.round(chartToNumber(value)))
}

/** Data breve: "17/4". Fallback "{idx+1}/4" se non parseabile. */
export const formatDateShort = (value, fallbackIndex = 0) => {
	if (!value) return `${fallbackIndex + 1}/4`
	const date = new Date(value)
	if (Number.isNaN(date.getTime())) return `${fallbackIndex + 1}/4`
	return new Intl.DateTimeFormat('it-IT', {
		day: 'numeric',
		month: 'numeric',
	}).format(date)
}

/** Data lunga: "17 apr". Usata nei tooltip e fullLabel. */
export const formatDate = (value, fallbackIndex = 0) => {
	if (!value) return `Giorno ${fallbackIndex + 1}`
	const date = new Date(value)
	if (Number.isNaN(date.getTime())) return `Giorno ${fallbackIndex + 1}`
	return new Intl.DateTimeFormat('it-IT', {
		day: 'numeric',
		month: 'short',
	}).format(date)
}

/**
 * Normalizza una serie temporale: ultimi 30 punti, label/date uniformi.
 * Input eterogeneo: il backend espone count/value/orders/amount/revenue indistintamente.
 */
export const normalizeChartData = (data) => {
	const series = Array.isArray(data) ? data : []
	return series.slice(-30).map((item, index) => ({
		key: item?.date ? String(item.date) : `day-${index}`,
		label: formatDateShort(item?.date, index),
		fullLabel: formatDate(item?.date, index),
		value: chartToNumber(item?.count ?? item?.value ?? item?.orders ?? item?.amount ?? item?.revenue),
		date: item?.date ? String(item.date) : null,
	}))
}

/** Calcola le quote (share) per donut/pie chart. Share in [0,1]. */
export const computeSegments = (items) => {
	const raw = Array.isArray(items) ? items : []
	const normalized = raw.map((item, index) => {
		const key = (item?.status || item?.key || item?.label || `status-${index}`)
			.toString()
			.toLowerCase()
			.replace(/\s+/g, '_')
		const count = chartToNumber(item?.count ?? item?.value ?? 0)
		return {
			key,
			label: (item?.label || item?.status || key).toString(),
			count,
			share: 0,
		}
	})

	const total = normalized.reduce((sum, item) => sum + item.count, 0)
	if (total <= 0) return normalized

	return normalized.map((item) => ({
		...item,
		share: item.count / total,
	}))
}
