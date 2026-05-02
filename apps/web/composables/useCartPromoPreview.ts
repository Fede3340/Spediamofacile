import { computed, ref } from 'vue'

import { buildCartDiscountPreviewState, parseEuroAmount } from '~/utils/cartHelpers'

type ValueRef<T> = { value: T }
type CouponApiResponse = {
	success?: boolean
	percentage?: unknown
	[key: string]: unknown
}
type CartCouponPreview = ReturnType<typeof buildCartDiscountPreviewState> & {
	type: string
	code: string
	discountAmount: number
	discountedTotal: string
}
type CouponMessage = { type: 'success' | 'error'; text: string }
type UseCartPromoPreviewOptions = {
	sanctum: <T = CouponApiResponse>(url: string, options?: Record<string, unknown>) => Promise<T>
	total?: ValueRef<unknown>
}

export function useCartPromoPreview({ sanctum, total }: UseCartPromoPreviewOptions) {
	const couponCode = ref('')
	const couponMessage = ref<CouponMessage | null>(null)
	const couponPreview = ref<CartCouponPreview | null>(null)
	const showCouponField = ref(false)

	const couponApplied = computed(() => Boolean(couponPreview.value))
	const couponDiscount = computed(() => couponPreview.value?.couponDiscount ?? null)
	const appliedTotal = computed(() => couponPreview.value?.discountAmount ?? null)
	const discountedTotal = computed(() => couponPreview.value?.discountedTotal ?? null)
	const showCouponPanel = computed(() => showCouponField.value)

	const applyCoupon = async () => {
		if (!couponCode.value.trim()) return

		couponMessage.value = null
		showCouponField.value = true

		try {
			const numericTotal = parseEuroAmount(total?.value)
			const data = await sanctum<CouponApiResponse>('/api/calculate-coupon', {
				method: 'POST',
				body: { coupon: couponCode.value, total: numericTotal },
			})

			if (data.success) {
				const previewState = buildCartDiscountPreviewState({
					result: data,
					total: numericTotal,
					codeFallback: couponCode.value,
					typeFallback: 'coupon',
				})

				couponPreview.value = {
					...previewState,
					type: previewState.preview.type,
					code: previewState.preview.code,
					discountAmount: previewState.preview.discount_amount,
					discountedTotal: previewState.appliedTotal,
				}
				couponMessage.value = { type: 'success', text: `Sconto del ${Number(data.percentage || 0)}% applicato!` }
				return
			}

			couponMessage.value = { type: 'error', text: 'Coupon non valido.' }
		} catch {
			couponMessage.value = { type: 'error', text: 'Errore nella verifica del coupon.' }
		}
	}

	const removeCoupon = () => {
		couponCode.value = ''
		couponPreview.value = null
		couponMessage.value = null
		showCouponField.value = false
	}

	return {
		appliedTotal,
		applyCoupon,
		couponApplied,
		couponCode,
		couponDiscount,
		couponMessage,
		discountedTotal,
		removeCoupon,
		showCouponField,
		showCouponPanel,
	}
}
