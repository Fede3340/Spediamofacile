import { describe, expect, it } from 'vitest'
import {
	getBrtTrackingLabel,
	getBrtTrackingReference,
	getBrtTrackingSearchHref,
	getBrtTrackingUrl,
} from '~/utils/brtTracking'

describe('brtTracking utils', () => {
	it('prefers explicit backend tracking urls when present', () => {
		expect(getBrtTrackingUrl({
			brt_tracking_url: 'https://example.test/tracking/ABC',
			brt_parcel_id: 'PARCEL-1',
		})).toBe('https://example.test/tracking/ABC')
	})

	it('builds the canonical BRT fallback url from the best reference', () => {
		expect(getBrtTrackingUrl({
			brt_tracking_number: 'TRACK-123',
			brt_parcel_id: 'PARCEL-123',
		})).toBe('https://vas.brt.it/vas/sped_det_show.hsm?refnr=TRACK-123')
	})

	it('uses parcel id when no tracking number is available', () => {
		expect(getBrtTrackingReference({
			brt_parcel_id: 'PARCEL-456',
		})).toBe('PARCEL-456')
		expect(getBrtTrackingLabel({
			brt_parcel_id: 'PARCEL-456',
		})).toBe('PARCEL-456')
	})

	it('falls back to numeric sender reference when carrier refs are missing', () => {
		expect(getBrtTrackingReference({
			brt_numeric_sender_reference: 'SF-12345',
		})).toBe('SF-12345')
		expect(getBrtTrackingSearchHref({
			brt_numeric_sender_reference: 'SF-12345',
		})).toBe('/traccia-spedizione?code=SF-12345')
	})

	it('builds the canonical internal tracking search href', () => {
		expect(getBrtTrackingSearchHref({
			brt_tracking_number: 'TRACK 789',
		})).toBe('/traccia-spedizione?code=TRACK%20789')
	})

	it('returns null when no tracking reference exists', () => {
		expect(getBrtTrackingUrl({})).toBeNull()
		expect(getBrtTrackingSearchHref({})).toBeNull()
	})
})
