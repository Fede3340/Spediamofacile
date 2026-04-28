import { describe, expect, it } from 'vitest'
import {
	buildCartDiscountPreviewState,
	buildDiscountOrderContext,
	buildDiscountPreviewState,
	calculateDiscountAmount,
	parseEuroAmount,
} from '~/utils/discountPreview'

describe('discountPreview utils', () => {
	it('parseEuroAmount normalizes italian formatted totals', () => {
		expect(parseEuroAmount('1.234,50 EUR')).toBe(1234.5)
		expect(parseEuroAmount('90,00 EUR')).toBe(90)
	})

	it('buildDiscountPreviewState normalizes backend preview payloads', () => {
		const preview = buildDiscountPreviewState({
			result: {
				type: 'referral',
				referral_code: 'PROABCD1',
				discount_percent: 5,
				pro_name: 'Mario Rossi',
			},
			total: 100,
			typeFallback: 'referral',
		})

		expect(preview.type).toBe('referral')
		expect(preview.code).toBe('PROABCD1')
		expect(preview.discount_percent).toBe(5)
		expect(preview.discount_amount).toBe(5)
		expect(preview.new_total_raw).toBe(95)
		expect(preview.pro_name).toBe('Mario Rossi')
	})

	it('buildCartDiscountPreviewState keeps the cart public API compatible', () => {
		const state = buildCartDiscountPreviewState({
			result: {
				percentage: 10,
				discount_amount: 8,
				new_total: '72,00 EUR',
			},
			total: 80,
			codeFallback: 'SAVE10',
		})

		expect(state.couponApplied).toBe(true)
		expect(state.couponDiscount).toBe(10)
		expect(state.appliedTotal).toBe('72,00 EUR')
		expect(state.preview.code).toBe('SAVE10')
	})

	it('buildDiscountOrderContext bridges preview state into checkout payload context', () => {
		const context = buildDiscountOrderContext({
			preview: {
				type: 'referral',
				code: 'PROSAVE1',
				discount_percent: 5,
				discount_amount: 1,
				pro_name: 'Mario Rossi',
			},
			subtotal: 20,
			finalTotal: 19,
		})

		expect(context).toEqual({
			type: 'referral',
			code: 'PROSAVE1',
			discount_percent: 5,
			discount_amount: 1,
			subtotal_raw: 20,
			final_total_raw: 19,
			pro_name: 'Mario Rossi',
		})
	})

	it('buildDiscountOrderContext returns null when no preview is active', () => {
		expect(buildDiscountOrderContext({ preview: null, subtotal: 20, finalTotal: 20 })).toBeNull()
	})

	it('calculateDiscountAmount keeps two decimals rounding', () => {
		expect(calculateDiscountAmount(19.9, 5)).toBe(1)
	})
})
