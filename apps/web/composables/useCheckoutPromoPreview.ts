/**
 * @file useCheckoutPromoPreview — Composable useCheckoutPromoPreview.
 */
import { computed, ref } from "vue";

import { buildDiscountPreviewState } from "~/utils/discountPreview";

/**
 * Boundary canonico della preview coupon/referral nel checkout.
 *
 * Dove entra:
 * - `useCart()` nello step pagamento
 *
 * Cosa decide:
 * - validazione codice promo nel checkout
 * - autoload del referral attivo dell'utente
 * - shape UI canonica della preview sconto
 *
 * Cosa NON decide:
 * - pricing finale persistito dell'ordine
 * - accredito commissione referral
 * - reward/wallet post-ordine
 */
export function useCheckoutPromoPreview({ sanctum, total }) {
	const couponCode = ref("");
	const couponLoading = ref(false);
	const couponError = ref("");
	const couponApplied = ref(null);
	const couponPanelOpen = ref(false);
	const referralAutoloadTried = ref(false);

	const discountedTotal = computed(() => {
		if (couponApplied.value && Number.isFinite(Number(couponApplied.value.new_total_raw))) {
			return Number(couponApplied.value.new_total_raw);
		}

		return Number(total?.value || 0);
	});

	const validateCoupon = async () => {
		const code = couponCode.value?.trim().toUpperCase();
		if (!code || code.length < 2) return;

		couponLoading.value = true;
		couponError.value = "";
		couponApplied.value = null;

		try {
			const result = await sanctum("/api/calculate-coupon", {
				method: "POST",
				body: { coupon: code, total: Number(total?.value || 0) },
			});

			if (result?.success) {
				couponApplied.value = buildDiscountPreviewState({
					result,
					total: Number(total?.value || 0),
					codeFallback: code,
					typeFallback: "coupon",
				});
				couponPanelOpen.value = true;
			}
		} catch (e) {
			const data = e?.response?._data || e?.data;
			couponError.value = data?.error || data?.message || "Codice non valido.";
			couponPanelOpen.value = true;
		} finally {
			couponLoading.value = false;
		}
	};

	const autoApplyReferral = async () => {
		if (couponApplied.value || referralAutoloadTried.value) return;

		referralAutoloadTried.value = true;

		try {
			const result = await sanctum("/api/referral/my-discount");
			if (result?.has_discount && result?.referral_code) {
				couponCode.value = result.referral_code;
				couponApplied.value = buildDiscountPreviewState({
					result,
					total: Number(total?.value || 0),
					codeFallback: result.referral_code,
					typeFallback: "referral",
				});
			}
		} catch {
			// Silent: il referral attivo e' opzionale.
		}
	};

	const removeCoupon = () => {
		couponApplied.value = null;
		couponCode.value = "";
		couponError.value = "";
		couponPanelOpen.value = false;
	};

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
	};
}
