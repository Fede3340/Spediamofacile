/**
 * @file discountPreview — Utility discountPreview.
 */
export function parseEuroAmount(value) {
	if (typeof value === 'number' && Number.isFinite(value)) return value

	const normalized = String(value ?? '')
		.replace(/[€\sEUR\u00A0]/gi, '')
		.replace(/\./g, '')
		.replace(',', '.')

	const parsed = Number(normalized)
	return Number.isFinite(parsed) ? parsed : 0
}

export function calculateDiscountAmount(total, percentage) {
	return Math.round(parseEuroAmount(total) * (Number(percentage || 0) / 100) * 100) / 100
}

export function calculateDiscountedTotal(total, discountAmount) {
	return Math.max(0, Math.round((parseEuroAmount(total) - Number(discountAmount || 0)) * 100) / 100)
}

export function formatPreviewEuroAmount(value) {
	return `${parseEuroAmount(value).toFixed(2).replace('.', ',')}\u00A0€`
}

export function buildDiscountPreviewState({ result, total, codeFallback = '', typeFallback = 'coupon' } = {}) {
	const subtotal = parseEuroAmount(total)
	const percentage = Number(result?.percentage ?? result?.discount_percent ?? 0)
	const discountAmountCandidate = Number(result?.discount_amount)
	const discountAmount = Number.isFinite(discountAmountCandidate)
		? discountAmountCandidate
		: calculateDiscountAmount(subtotal, percentage)
	const finalTotalCandidate = Number(result?.new_total_raw)
	const finalTotal = Number.isFinite(finalTotalCandidate)
		? finalTotalCandidate
		: calculateDiscountedTotal(subtotal, discountAmount)

	return {
		type: result?.type || typeFallback,
		code: String(result?.referral_code || result?.code || codeFallback || '').trim().toUpperCase(),
		discount_percent: percentage,
		discount_amount: discountAmount,
		new_total_raw: finalTotal,
		new_total: result?.new_total || formatPreviewEuroAmount(finalTotal),
		pro_name: result?.pro_user_name || result?.pro_name || '',
	}
}

export function buildCartDiscountPreviewState(options = {}) {
	const preview = buildDiscountPreviewState(options)

	return {
		couponApplied: true,
		couponDiscount: preview.discount_percent || null,
		appliedTotal: preview.new_total,
		preview,
	}
}

export function buildDiscountOrderContext({ preview, subtotal, finalTotal } = {}) {
	if (!preview || typeof preview !== 'object') return null

	const code = String(preview.code || preview.referral_code || '').trim().toUpperCase()
	const type = String(preview.type || '').trim().toLowerCase()

	if (!code || !type) return null

	const subtotalAmount = parseEuroAmount(subtotal)
	const discountAmount = Number(preview.discount_amount)
	const normalizedDiscountAmount = Number.isFinite(discountAmount)
		? discountAmount
		: calculateDiscountAmount(subtotalAmount, preview.discount_percent ?? preview.percentage ?? 0)
	const explicitFinalTotal = Number(finalTotal)
	const normalizedFinalTotal = Number.isFinite(explicitFinalTotal)
		? explicitFinalTotal
		: calculateDiscountedTotal(subtotalAmount, normalizedDiscountAmount)
	const discountPercent = Number(preview.discount_percent ?? preview.percentage ?? 0)

	return {
		type,
		code,
		discount_percent: Number.isFinite(discountPercent) ? discountPercent : 0,
		discount_amount: normalizedDiscountAmount,
		subtotal_raw: subtotalAmount,
		final_total_raw: normalizedFinalTotal,
		pro_name: String(preview.pro_name || preview.pro_user_name || '').trim(),
	}
}
