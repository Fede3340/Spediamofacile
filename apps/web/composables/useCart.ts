/**
 * Checkout cart boundary.
 *
 * `useCart()` is used by the payment step. It owns cart/order loading, billing
 * defaults, wallet balance and checkout promo preview. The `/carrello` page has
 * its own composable (`useCarrello`) because it has different UI concerns.
 */
import { computed, ref, watch, type Ref } from 'vue';
import type { Address, AddressGroup, CartItem, CartResponse, Order } from '~/types';
import { centsToEuro, parseEuroAmount, buildDiscountOrderContext } from '~/utils/cartHelpers';
import { useCheckoutPromoPreview } from '~/composables/useCheckoutPromoPreview';
import {
	deriveShipmentFlowStateFromUserStore,
	pickMostAdvancedShipmentFlowState,
	resolveShipmentFlowState,
} from '~/utils/shipment';

type ApiEnvelope<T> = T | { data?: T | null };
type SanctumClient = <T = unknown>(url: string, options?: { method?: string; body?: unknown }) => Promise<T>;
type SessionRef = Ref<{ data?: Record<string, unknown> } | null | undefined>;
type DiscountContext = Record<string, unknown>;
type CheckoutOrder = Omit<Order, 'packages'> & {
	packages?: CartItem[];
	gross_subtotal_cents?: number | null;
	payable_total_cents?: number | null;
	discount_amount_cents?: number | null;
	payable_total?: string | null;
	discount_context?: DiscountContext | null;
};
type FatturazioneType = 'ricevuta' | 'fattura';
type InvoiceSubjectType = 'azienda' | 'privato';
type FatturaForm = {
	nome_completo: string;
	ragione_sociale: string;
	p_iva: string;
	codice_fiscale: string;
	indirizzo: string;
	city: string;
	province: string;
	postal_code: string;
	pec: string;
	codice_sdi: string;
};

const unwrapApiData = <T>(payload: ApiEnvelope<T> | null | undefined): T | null => {
	if (!payload) return null;
	if (typeof payload === 'object' && 'data' in payload) return payload.data ?? null;
	return payload as T;
};

const emptyCart = (): CartResponse => ({
	data: [],
	meta: { total: '0,00 €', address_groups: [] },
});

const formatEuro = (num: number | string) => {
	const n = Number(num);
	return Number.isFinite(n) ? `${n.toFixed(2).replace('.', ',')}\u00A0€` : '0,00 €';
};

const asAddress = (value: Address | undefined | null) => value || null;

export function useCart() {
	const route = useRoute();
	const sanctum = useSanctumClient() as SanctumClient;
	const { user, isAuthenticated } = useSanctumAuth();
	const { loadPriceBands, priceBands, promoSettings } = usePriceBands();
	const { session } = useSession() as { session: SessionRef };
	const userStore = useShipmentStore();

	const fallbackFlowRoute = computed(() => {
		const remoteFlowState = resolveShipmentFlowState(session.value?.data || {});
		const localFlowState = deriveShipmentFlowStateFromUserStore(userStore);
		return pickMostAdvancedShipmentFlowState(remoteFlowState, localFlowState).last_valid_route || '/preventivo';
	});

	const cart = ref<CartResponse>(emptyCart());
	const pageReady = ref(false);
	const existingOrder = ref<CheckoutOrder | null>(null);
	const existingOrderId = computed(() => {
		const raw = Array.isArray(route.query.order_id) ? route.query.order_id[0] : route.query.order_id;
		return raw ? String(raw) : null;
	});

	const existingOrderGrossTotal = computed(() => {
		if (!existingOrder.value) return 0;
		return centsToEuro(existingOrder.value.gross_subtotal_cents ?? existingOrder.value.subtotal_cents);
	});
	const existingOrderPayableTotal = computed(() => {
		if (!existingOrder.value) return 0;
		const payableCents = Number(existingOrder.value.payable_total_cents);
		if (Number.isFinite(payableCents)) return centsToEuro(payableCents);
		const discountCents = Number(existingOrder.value.discount_amount_cents ?? 0);
		return Math.max(0, existingOrderGrossTotal.value - centsToEuro(discountCents));
	});

	async function initCheckoutPage() {
		existingOrder.value = null;
		if (existingOrderId.value) {
			try {
				const res = await sanctum<ApiEnvelope<CheckoutOrder>>(`/api/orders/${existingOrderId.value}`);
				existingOrder.value = unwrapApiData(res);
				pageReady.value = Boolean(existingOrder.value);
				return pageReady.value;
			} catch {
				pageReady.value = false;
				return false;
			}
		}

		if (!isAuthenticated.value || !user.value) {
			pageReady.value = false;
			return false;
		}

		await refreshCart();
		const hasItems = cart.value.data.length > 0;
		pageReady.value = hasItems;
		return hasItems;
	}

	async function refreshCart() {
		try {
			const res = await sanctum<Partial<CartResponse>>('/api/cart');
			if (Array.isArray(res.data)) cart.value = { data: res.data, meta: res.meta || cart.value.meta };
		} catch {
			// Keep the previous cart state on transient API failures.
		}
	}

	const displayPackages = computed<CartItem[]>(() => existingOrder.value?.packages || cart.value.data || []);
	const getTotal = computed<string | number>(() => {
		if (existingOrder.value) return existingOrderGrossTotal.value;
		return cart.value.meta?.total || '0,00 €';
	});
	const getNumberTotal = computed(() => parseEuroAmount(getTotal.value));
	const totalPackages = computed(() => displayPackages.value.reduce((sum, item) => sum + (Number(item.quantity) || 1), 0));
	const contentDescription = computed(() => {
		if (!displayPackages.value.length) return '';
		const types = displayPackages.value.map((item) => item.package_type || 'Pacco').filter(Boolean);
		return [...new Set(types)].join(', ');
	});
	const addressGroups = computed<AddressGroup[]>(() => cart.value.meta?.address_groups || []);
	const hasMultipleGroups = computed(() => addressGroups.value.filter((group) => group.count >= 1).length > 1);
	const mergeGroupsCount = computed(() => addressGroups.value.length);

	function formatPrice(num: number | string) {
		return formatEuro(num);
	}

	const existingOrderDiscountPreview = computed(() => {
		const context = existingOrder.value?.discount_context;
		if (!context) return null;
		const type = String(context.type || '').trim().toLowerCase();
		const code = String(context.code || '').trim().toUpperCase();
		if (!type || !code) return null;
		return {
			type,
			code,
			discount_percent: Number(context.discount_percent ?? 0),
			discount_amount: Number(context.discount_amount ?? 0),
			subtotal_raw: Number(context.subtotal_raw ?? existingOrderGrossTotal.value),
			final_total_raw: Number(context.final_total_raw ?? existingOrderPayableTotal.value),
			new_total_raw: Number(context.final_total_raw ?? existingOrderPayableTotal.value),
			new_total: existingOrder.value?.payable_total || formatPrice(existingOrderPayableTotal.value),
			pro_name: String(context.pro_name || '').trim(),
		};
	});
	const existingOrderCanAcceptDiscount = computed(() => {
		if (!existingOrder.value || existingOrderDiscountPreview.value) return false;
		const status = String(existingOrder.value.raw_status || '').trim().toLowerCase();
		return ['pending', 'payment_failed'].includes(status);
	});

	const fatturazioneType = ref<FatturazioneType>('ricevuta');
	const invoiceSubjectType = ref<InvoiceSubjectType>('azienda');
	const fatturaData = ref<FatturaForm>({
		nome_completo: '',
		ragione_sociale: '',
		p_iva: '',
		codice_fiscale: '',
		indirizzo: '',
		city: '',
		province: '',
		postal_code: '',
		pec: '',
		codice_sdi: '',
	});

	const billingShippingSource = computed<Address | null>(() => {
		const pkg = displayPackages.value[0];
		return asAddress(pkg?.origin_address) || asAddress(pkg?.destination_address);
	});
	const billingShippingAddressLine = computed(() => {
		const address = billingShippingSource.value;
		return address ? [address.address, address.address_number].filter(Boolean).join(' ').trim() : '';
	});
	const billingShippingFullAddress = computed(() => {
		const address = billingShippingSource.value;
		if (!address) return '';
		return [
			billingShippingAddressLine.value,
			[address.postal_code, address.city].filter(Boolean).join(' '),
			address.province ? `(${address.province})` : '',
		]
			.filter(Boolean)
			.join(', ');
	});

	function applyShippingDataToBilling() {
		const address = billingShippingSource.value;
		if (!address) return;
		if (invoiceSubjectType.value === 'privato') {
			fatturaData.value.nome_completo = fatturaData.value.nome_completo || address.name || '';
		} else if (!fatturaData.value.ragione_sociale) {
			fatturaData.value.ragione_sociale = address.name || '';
		}
		fatturaData.value.indirizzo = fatturaData.value.indirizzo || billingShippingAddressLine.value;
		fatturaData.value.city = fatturaData.value.city || address.city || '';
		fatturaData.value.province = fatturaData.value.province || address.province || '';
		fatturaData.value.postal_code = fatturaData.value.postal_code || address.postal_code || '';
	}

	watch([invoiceSubjectType, billingShippingSource], applyShippingDataToBilling, { immediate: true });
	watch(invoiceSubjectType, (subjectType) => {
		if (subjectType === 'privato') {
			fatturaData.value.ragione_sociale = '';
			fatturaData.value.p_iva = '';
		}
		applyShippingDataToBilling();
	});
	watch(fatturazioneType, (type) => {
		if (type === 'fattura') applyShippingDataToBilling();
	});

	const billingPayload = computed<Record<string, unknown>>(() => {
		if (fatturazioneType.value !== 'fattura') return { type: 'ricevuta' };
		const data = fatturaData.value;
		const isAzienda = invoiceSubjectType.value === 'azienda';
		const source = billingShippingSource.value;
		return {
			type: 'fattura',
			subject_type: invoiceSubjectType.value,
			same_as_shipping: false,
			nome_completo: data.nome_completo.trim() || null,
			ragione_sociale: data.ragione_sociale.trim() || null,
			p_iva: data.p_iva.trim() || null,
			codice_fiscale: data.codice_fiscale.trim() || null,
			indirizzo: data.indirizzo.trim() || null,
			city: data.city.trim() || null,
			province: data.province.trim() || null,
			postal_code: data.postal_code.trim() || null,
			pec: isAzienda ? data.pec.trim() || null : null,
			codice_sdi: isAzienda ? data.codice_sdi.trim() || null : null,
			shipping_reference: source
				? {
						name: source.name || null,
						address: billingShippingAddressLine.value || null,
						city: source.city || null,
						province: source.province || null,
						postal_code: source.postal_code || null,
					}
				: null,
		};
	});

	const walletBalance = ref(0);
	const walletLoadedRef = ref(false);
	async function loadWalletBalance() {
		if (walletLoadedRef.value) return;
		try {
			const result = await sanctum<{ balance?: number | string }>('/api/wallet/balance');
			walletBalance.value = Number(result.balance ?? 0);
		} catch {
			walletBalance.value = 0;
		} finally {
			walletLoadedRef.value = true;
		}
	}
	const walletFormatted = computed(() => formatEuro(walletBalance.value));
	const walletLoaded = computed(() => walletLoadedRef.value);

	const {
		autoApplyReferral,
		couponApplied,
		couponCode,
		couponError,
		couponLoading,
		couponPanelOpen,
		discountedTotal,
		removeCoupon,
		validateCoupon,
	} = useCheckoutPromoPreview({ sanctum, total: getNumberTotal });

	const displayedCouponApplied = computed(() => existingOrderDiscountPreview.value || couponApplied.value);
	function validateCheckoutCoupon() {
		if (existingOrder.value && !existingOrderCanAcceptDiscount.value) {
			couponError.value = "Il totale di questo ordine e' gia' bloccato. Crea un nuovo preventivo per usare un altro codice.";
			return;
		}
		return validateCoupon();
	}
	function removeDisplayedCoupon() {
		if (!existingOrderDiscountPreview.value) removeCoupon();
	}

	const finalTotal = computed(() => {
		if (existingOrderDiscountPreview.value) return existingOrderPayableTotal.value;
		if (existingOrder.value && couponApplied.value) return discountedTotal.value;
		if (existingOrder.value) return existingOrderPayableTotal.value;
		return discountedTotal.value;
	});
	const finalTotalFormatted = computed(() => {
		if (existingOrder.value?.payable_total && !couponApplied.value) return existingOrder.value.payable_total;
		return formatEuro(finalTotal.value);
	});
	const walletSufficient = computed(() => walletBalance.value >= finalTotal.value);
	const discountContext = computed(() =>
		buildDiscountOrderContext({
			preview: displayedCouponApplied.value,
			subtotal: getNumberTotal.value,
			finalTotal: finalTotal.value,
		}),
	);

	return {
		cart,
		pageReady,
		existingOrderId,
		existingOrder,
		initCheckoutPage,
		refreshCart,
		sanctum,
		userStore,
		user,
		fallbackFlowRoute,
		loadPriceBands,
		priceBands,
		promoSettings,
		displayPackages,
		addressGroups,
		hasMultipleGroups,
		mergeGroupsCount,
		getTotal,
		getNumberTotal,
		totalPackages,
		contentDescription,
		formatPrice,
		finalTotal,
		finalTotalFormatted,
		discountContext,
		fatturazioneType,
		invoiceSubjectType,
		fatturaData,
		billingShippingFullAddress,
		billingPayload,
		walletBalance,
		walletFormatted,
		walletLoaded,
		walletSufficient,
		loadWalletBalance,
		couponCode,
		couponLoading,
		couponError,
		couponApplied: displayedCouponApplied,
		couponPanelOpen,
		validateCoupon: validateCheckoutCoupon,
		removeCoupon: removeDisplayedCoupon,
		autoApplyReferral,
	};
}

export function useCartOperations() {
	return useCart();
}
