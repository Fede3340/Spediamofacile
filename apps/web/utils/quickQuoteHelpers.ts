/**
 * quickQuoteHelpers — utility pure per il widget di preventivo veloce e snapshot.
 *
 * Sezioni:
 *   1. Cloni canonici (shipment details + packages) per signature comparabile
 *   2. Signature & extraction (per dedup e replay sessione)
 *   3. Format helpers UI (location label, prezzo live)
 *   4. Validazione package (misure mancanti)
 */

type QuoteDetailsInput = Partial<Record<
	| 'origin_city'
	| 'origin_postal_code'
	| 'origin_country_code'
	| 'origin_country'
	| 'destination_city'
	| 'destination_postal_code'
	| 'destination_country_code'
	| 'destination_country'
	| 'date',
	string | number | null | undefined
>>
type QuotePackageInput = Partial<Record<
	'package_type' | 'quantity' | 'weight' | 'first_size' | 'second_size' | 'third_size',
	string | number | null | undefined
>>
type QuoteComparablePayload = {
	shipment_details?: QuoteDetailsInput
	packages?: QuotePackageInput[]
}
type QuoteStoreLike = {
	shipmentDetails?: QuoteDetailsInput
	packages?: QuotePackageInput[]
}
type PackageLike = {
	weight?: unknown
	first_size?: unknown
	second_size?: unknown
	third_size?: unknown
}

// ─── 1. Cloni canonici ────────────────────────────────────────────

export const cloneShipmentDetailsForQuote = (details: QuoteDetailsInput = {}) => ({
	origin_city: String(details.origin_city || ''),
	origin_postal_code: String(details.origin_postal_code || ''),
	origin_country_code: String(details.origin_country_code || 'IT').trim().toUpperCase() || 'IT',
	origin_country: String(details.origin_country || 'Italia').trim() || 'Italia',
	destination_city: String(details.destination_city || ''),
	destination_postal_code: String(details.destination_postal_code || ''),
	destination_country_code: String(details.destination_country_code || 'IT').trim().toUpperCase() || 'IT',
	destination_country: String(details.destination_country || 'Italia').trim() || 'Italia',
	date: String(details.date || ''),
})

export const clonePackageForQuote = (pack: QuotePackageInput = {}) => ({
	package_type: String(pack.package_type || ''),
	quantity: Number(pack.quantity) || 1,
	weight: String(pack.weight || ''),
	first_size: String(pack.first_size || ''),
	second_size: String(pack.second_size || ''),
	third_size: String(pack.third_size || ''),
})

export const clonePackagesForQuote = (packages: unknown[] = []) =>
	Array.isArray(packages)
		? packages.map((pack) => clonePackageForQuote(pack && typeof pack === 'object' ? pack as QuotePackageInput : {}))
		: []

// ─── 2. Signature & extraction ────────────────────────────────────

export const buildQuoteComparableSignature = (payload: QuoteComparablePayload = {}) => JSON.stringify({
	shipment_details: cloneShipmentDetailsForQuote(payload.shipment_details),
	packages: clonePackagesForQuote(payload.packages || []).map((pack) => ({
		package_type: pack.package_type,
		quantity: Number(pack.quantity) || 0,
		weight: pack.weight,
		first_size: pack.first_size,
		second_size: pack.second_size,
		third_size: pack.third_size,
	})),
})

export const extractSessionComparablePayload = (sessionData: QuoteComparablePayload = {}) => ({
	shipment_details: sessionData.shipment_details || {},
	packages: sessionData.packages || [],
})

/** Snapshot canonico di tutto lo store preventivo (per persist sessione). */
export function buildQuotePayloadSnapshotFor(shipmentFlowStore: QuoteStoreLike) {
	return {
		shipment_details: cloneShipmentDetailsForQuote(shipmentFlowStore?.shipmentDetails),
		packages: clonePackagesForQuote(shipmentFlowStore?.packages),
	}
}

// ─── 3. Format helpers UI ─────────────────────────────────────────

export const formatResolvedLocation = (city = '', cap = '') => {
	const trimmedCity = String(city || '').trim()
	const trimmedCap = String(cap || '').trim()
	if (trimmedCity && trimmedCap) return `${trimmedCity} - ${trimmedCap}`
	return trimmedCity || trimmedCap || ''
}

/** Prezzo formattato per widget live preventivo (no spazi). */
export function formatLivePrice(amount: unknown): string {
	return new Intl.NumberFormat('it-IT', {
		style: 'currency',
		currency: 'EUR',
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	}).format(Number(amount) || 0).replace(/\s/g, '')
}

// ─── 4. Validazione package ───────────────────────────────────────

export function packageMissingMeasurements(pack: PackageLike): boolean {
	return !pack?.weight || !pack?.first_size || !pack?.second_size || !pack?.third_size
}
