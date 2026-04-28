/**
 * @file brtTracking — Utility brtTracking.
 */
const firstFilled = (...values) => {
	for (const value of values) {
		if (typeof value === 'string' && value.trim()) {
			return value.trim()
		}
	}

	return null
}

export const getBrtTrackingReference = (value = {}) => firstFilled(
	value?.tracking_number,
	value?.brt_tracking_number,
	value?.parcel_id,
	value?.brt_parcel_id,
	value?.brt_numeric_sender_reference,
)

export const getBrtTrackingUrl = (value = {}) => {
	const explicitUrl = firstFilled(
		value?.tracking_url,
		value?.brt_tracking_url,
	)

	if (explicitUrl) {
		return explicitUrl
	}

	const reference = getBrtTrackingReference(value)

	return reference
		? `https://vas.brt.it/vas/sped_det_show.hsm?refnr=${encodeURIComponent(reference)}`
		: null
}

export const getBrtTrackingSearchHref = (value = {}) => {
	const reference = getBrtTrackingReference(value)

	return reference
		? `/traccia-spedizione?code=${encodeURIComponent(reference)}`
		: null
}

export const getBrtTrackingLabel = (value = {}) => firstFilled(
	value?.brt_parcel_id,
	value?.parcel_id,
	value?.brt_tracking_number,
	value?.tracking_number,
) || 'Traccia'
