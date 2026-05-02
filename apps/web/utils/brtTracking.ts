/**
 * BRT tracking helpers — estrae il riferimento di tracking spedizione e
 * costruisce link di tracking interni/esterni.
 *
 * BRT espone più nomi di campo per lo stesso identificativo (legacy + REST 3.x):
 * `tracking_number`, `brt_tracking_number`, `parcel_id`, `brt_parcel_id`,
 * `brt_numeric_sender_reference`. Le funzioni sotto restituiscono il primo
 * non vuoto, in ordine di priorità documentato per ogni helper.
 */

type BrtTrackingSource = {
	tracking_number?: unknown
	brt_tracking_number?: unknown
	parcel_id?: unknown
	brt_parcel_id?: unknown
	brt_numeric_sender_reference?: unknown
	tracking_url?: unknown
	brt_tracking_url?: unknown
}

const firstFilled = (...values: unknown[]): string | null => {
	for (const value of values) {
		if (typeof value === 'string' && value.trim()) return value.trim()
	}
	return null
}

/**
 * Riferimento canonico per query BRT (sender_reference > parcel_id > tracking).
 * Usa la chiave più specifica disponibile — `tracking_number` per primo
 * perché è il valore mostrato all'utente in fattura.
 */
export const getBrtTrackingReference = (value: BrtTrackingSource = {}) => firstFilled(
	value.tracking_number,
	value.brt_tracking_number,
	value.parcel_id,
	value.brt_parcel_id,
	value.brt_numeric_sender_reference,
)

/**
 * URL pubblico BRT per dettaglio spedizione. Preferisce un eventuale
 * `tracking_url` esplicito; altrimenti costruisce link `vas.brt.it` con
 * il reference canonico.
 */
export const getBrtTrackingUrl = (value: BrtTrackingSource = {}) => {
	const explicitUrl = firstFilled(value.tracking_url, value.brt_tracking_url)
	if (explicitUrl) return explicitUrl

	const reference = getBrtTrackingReference(value)
	return reference
		? `https://vas.brt.it/vas/sped_det_show.hsm?refnr=${encodeURIComponent(reference)}`
		: null
}

/** Path interno `/traccia-spedizione?code=...` per page tracking propria. */
export const getBrtTrackingSearchHref = (value: BrtTrackingSource = {}) => {
	const reference = getBrtTrackingReference(value)
	return reference ? `/traccia-spedizione?code=${encodeURIComponent(reference)}` : null
}

/**
 * Label testuale del codice tracking — preferisce parcel_id (più breve, più
 * leggibile per l'utente) rispetto al tracking_number lungo. Fallback "Traccia".
 */
export const getBrtTrackingLabel = (value: BrtTrackingSource = {}) => firstFilled(
	value.brt_parcel_id,
	value.parcel_id,
	value.brt_tracking_number,
	value.tracking_number,
) || 'Traccia'
