/**
 * @file useCartPromoPreview — Composable useCartPromoPreview.
 */
import { computed, ref } from "vue";

import { buildCartDiscountPreviewState, parseEuroAmount } from "~/utils/discountPreview";

/**
 * Boundary canonico della preview coupon nella pagina carrello.
 *
 * Dove entra:
 * - `useCarrello()` nella pagina /carrello
 *
 * Cosa decide:
 * - validazione codice promo in pagina carrello
 * - shape UI della preview sconto lato carrello
 *
 * Cosa NON decide:
 * - persistenza dello sconto sull'ordine
 * - reward referral / wallet
 * - pricing finale del checkout
 */
export function useCartPromoPreview({ sanctum, total }) {
	const couponCode = ref("");
	const couponMessage = ref(null);
	const couponPreview = ref(null);
	const showCouponField = ref(false);

	const couponApplied = computed(() => Boolean(couponPreview.value));
	const couponDiscount = computed(() => couponPreview.value?.couponDiscount ?? null);
	const appliedTotal = computed(() => couponPreview.value?.discountAmount ?? null);
	const discountedTotal = computed(() => couponPreview.value?.discountedTotal ?? null);
	const showCouponPanel = computed(() => showCouponField.value);

	const applyCoupon = async () => {
		if (!couponCode.value.trim()) return;

		couponMessage.value = null;
		showCouponField.value = true;

		try {
			const numericTotal = parseEuroAmount(total?.value);
			const data = await sanctum("/api/calculate-coupon", {
				method: "POST",
				body: { coupon: couponCode.value, total: numericTotal },
			});

			if (data?.success) {
				const previewState = buildCartDiscountPreviewState({
					result: data,
					total: numericTotal,
					codeFallback: couponCode.value,
					typeFallback: "coupon",
				});

				couponPreview.value = {
					type: previewState.preview.type,
					code: previewState.preview.code,
					couponDiscount: previewState.couponDiscount,
					discountAmount: previewState.preview.discount_amount,
					discountedTotal: previewState.appliedTotal,
					preview: previewState.preview,
				};
				couponMessage.value = { type: "success", text: `Sconto del ${data.percentage}% applicato!` };
				return;
			}

			couponMessage.value = { type: "error", text: "Coupon non valido." };
		} catch {
			couponMessage.value = { type: "error", text: "Errore nella verifica del coupon." };
		}
	};

	const removeCoupon = () => {
		couponCode.value = "";
		couponPreview.value = null;
		couponMessage.value = null;
		showCouponField.value = false;
	};

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
	};
}
