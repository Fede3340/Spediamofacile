import { computed, ref } from 'vue'

import { buildDiscountPreviewState } from '~/utils/cartHelpers'

type ValueRef<T> = { value: T }
type PromoResponse = {
	success?: boolean
	has_discount?: boolean
	referral_code?: string
	error?: string
	message?: string
	response?: { _data?: { error?: string; message?: string } }
	data?: { error?: string; message?: string }
	[key: string]: unknown
}
type PromoPreview = ReturnType<typeof buildDiscountPreviewState>
type UseCheckoutPromoPreviewOptions = {
	sanctum: <T = PromoResponse>(url: string, options?: Record<string, unknown>) => Promise<T>
	total?: ValueRef<unknown>
}

const getPromoError = (error: unknown): string => {
	if (!error || typeof error !== 'object') return 'Codice non valido.'
	const source = error as PromoResponse
	return source.response?._data?.error
		|| source.response?._data?.message
		|| source.data?.error
		|| source.data?.message
		|| source.error
		|| source.message
		|| 'Codice non valido.'
}

export function useCheckoutPromoPreview({ sanctum, total }: UseCheckoutPromoPreviewOptions) {
	const couponCode = ref('')
	const couponLoading = ref(false)
	const couponError = ref('')
	const couponApplied = ref<PromoPreview | null>(null)
	const couponPanelOpen = ref(false)
	const referralAutoloadTried = ref(false)

	const currentTotal = () => Number(total?.value || 0)
	const discountedTotal = computed(() => couponApplied.value?.new_total_raw ?? currentTotal())

	const validateCoupon = async () => {
		const code = couponCode.value.trim().toUpperCase()
		if (!code || code.length < 2) return

		couponLoading.value = true
		couponError.value = ''
		couponApplied.value = null

		try {
			const result = await sanctum<PromoResponse>('/api/calculate-coupon', {
				method: 'POST',
				body: { coupon: code, total: currentTotal() },
			})

			if (result.success) {
				couponApplied.value = buildDiscountPreviewState({
					result,
					total: currentTotal(),
					codeFallback: code,
					typeFallback: 'coupon',
				})
				couponPanelOpen.value = true
			}
		} catch (e) {
			couponError.value = getPromoError(e)
			couponPanelOpen.value = true
		} finally {
			couponLoading.value = false
		}
	}

	const autoApplyReferral = async () => {
		if (couponApplied.value || referralAutoloadTried.value) return

		referralAutoloadTried.value = true

		try {
			const result = await sanctum<PromoResponse>('/api/referral/my-discount')
			if (result.has_discount && result.referral_code) {
				couponCode.value = result.referral_code
				couponApplied.value = buildDiscountPreviewState({
					result,
					total: currentTotal(),
					codeFallback: result.referral_code,
					typeFallback: 'referral',
				})
			}
		} catch {
			// Referral autoload is optional.
		}
	}

	const removeCoupon = () => {
		couponApplied.value = null
		couponCode.value = ''
		couponError.value = ''
		couponPanelOpen.value = false
	}

	return {
		autoApplyReferral,
		couponApplied,
		couponCode,
		couponError,
		couponLoading,
		couponPanelOpen,
		discountedTotal,
		removeCoupon,
		validateCoupon,
	}
}
