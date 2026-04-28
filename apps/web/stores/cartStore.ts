/**
 * cartStore — stato canonico cart + checkout (billing, wallet, ordine esistente).
 *
 * Estratto da composables/useCart.js (split atomico Pinia 2026-04-26).
 * Lo store contiene SOLO state + azioni neutre rispetto al ciclo di vita Vue.
 * I watcher legati a route/setup vivono nel composable wrapper `useCart()`.
 */
import { defineStore } from 'pinia';
import { centsToEuro, formatEuroAmount } from '~/utils/cartHelpers';
const EMPTY_FATTURA = Object.freeze({
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
export const useCartStore = defineStore('cart', () => {
    // ---------- STATO CART ----------
    const cart = ref({
        data: [],
        meta: { total: '0,00\u20AC', address_groups: [] },
    });
    const pageReady = ref(false);
    const existingOrder = ref(null);
    // ---------- BILLING ----------
    const fatturazioneType = ref('ricevuta');
    const invoiceSubjectType = ref('azienda');
    const fatturaData = ref({ ...EMPTY_FATTURA });
    // ---------- WALLET ----------
    const walletBalance = ref(0);
    const walletLoadedRef = ref(false);
    // ---------- UI CARRELLO (gruppi expanded persistenti) ----------
    const expandedGroups = ref({});
    // ---------- COMPUTED CART ----------
    const displayPackages = computed(() => {
        if (existingOrder.value)
            return existingOrder.value.packages || [];
        return cart.value?.data || [];
    });
    const existingOrderGrossTotal = computed(() => {
        if (!existingOrder.value)
            return 0;
        return centsToEuro(existingOrder.value.gross_subtotal_cents
            ?? existingOrder.value.subtotal_cents);
    });
    const existingOrderPayableTotal = computed(() => {
        if (!existingOrder.value)
            return 0;
        const payableCents = Number(existingOrder.value.payable_total_cents);
        if (Number.isFinite(payableCents))
            return centsToEuro(payableCents);
        const discountCents = Number(existingOrder.value.discount_amount_cents ?? 0);
        return Math.max(0, existingOrderGrossTotal.value - centsToEuro(discountCents));
    });
    const getTotal = computed(() => {
        if (existingOrder.value)
            return existingOrderGrossTotal.value;
        return cart.value?.meta?.total || '0,00\u20AC';
    });
    const totalPackages = computed(() => displayPackages.value.reduce((sum, item) => sum + (Number(item.quantity) || 1), 0));
    const contentDescription = computed(() => {
        if (!displayPackages.value.length)
            return '';
        const types = displayPackages.value
            .map((item) => item.package_type || 'Pacco')
            .filter(Boolean);
        return [...new Set(types)].join(', ');
    });
    const addressGroups = computed(() => cart.value?.meta?.address_groups || []);
    const hasMultipleGroups = computed(() => addressGroups.value.filter((g) => Number(g?.count ?? 0) >= 1).length > 1);
    const mergeGroupsCount = computed(() => addressGroups.value.length);
    // ---------- COMPUTED EXISTING ORDER ----------
    const existingOrderDiscountPreview = computed(() => {
        const context = existingOrder.value?.discount_context;
        if (!context || typeof context !== 'object')
            return null;
        const type = String(context.type || '').trim().toLowerCase();
        const code = String(context.code || '').trim().toUpperCase();
        if (!type || !code)
            return null;
        const ctx = context;
        const finalTotalRaw = Number(ctx.final_total_raw
            ?? existingOrderPayableTotal.value);
        return {
            type,
            code,
            discount_percent: Number(ctx.discount_percent ?? 0),
            discount_amount: Number(ctx.discount_amount ?? 0),
            subtotal_raw: Number(ctx.subtotal_raw ?? existingOrderGrossTotal.value),
            final_total_raw: finalTotalRaw,
            new_total_raw: finalTotalRaw,
            new_total: existingOrder.value?.payable_total
                || formatEuroAmount(existingOrderPayableTotal.value),
            pro_name: String(ctx.pro_name || '').trim(),
        };
    });
    const existingOrderCanAcceptDiscount = computed(() => {
        if (!existingOrder.value || existingOrderDiscountPreview.value)
            return false;
        const status = String(existingOrder.value.raw_status || '').trim().toLowerCase();
        return ['pending', 'payment_failed'].includes(status);
    });
    // ---------- COMPUTED BILLING ----------
    const billingShippingSource = computed(() => {
        const pkg = displayPackages.value?.[0];
        return (pkg?.origin_address || pkg?.destination_address || null);
    });
    const billingShippingAddressLine = computed(() => {
        const a = billingShippingSource.value;
        if (!a)
            return '';
        return [a.address, a.address_number].filter(Boolean).join(' ').trim();
    });
    const billingShippingFullAddress = computed(() => {
        const a = billingShippingSource.value;
        if (!a)
            return '';
        return [
            billingShippingAddressLine.value,
            [a.postal_code, a.city].filter(Boolean).join(' '),
            a.province ? `(${a.province})` : '',
        ].filter(Boolean).join(', ');
    });
    const billingPayload = computed(() => {
        if (fatturazioneType.value !== 'fattura')
            return { type: 'ricevuta' };
        const d = fatturaData.value;
        const isAzienda = invoiceSubjectType.value === 'azienda';
        const src = billingShippingSource.value;
        const same_as_shipping = !!src
            && d.indirizzo?.trim() === billingShippingAddressLine.value
            && d.city?.trim() === (src.city || '');
        return {
            type: 'fattura',
            subject_type: invoiceSubjectType.value,
            same_as_shipping,
            nome_completo: d.nome_completo?.trim() || null,
            ragione_sociale: d.ragione_sociale?.trim() || null,
            p_iva: d.p_iva?.trim() || null,
            codice_fiscale: d.codice_fiscale?.trim() || null,
            indirizzo: d.indirizzo?.trim() || null,
            city: d.city?.trim() || null,
            province: d.province?.trim() || null,
            postal_code: d.postal_code?.trim() || null,
            pec: isAzienda ? d.pec?.trim() || null : null,
            codice_sdi: isAzienda ? d.codice_sdi?.trim() || null : null,
            shipping_reference: src
                ? {
                    name: src.name || null,
                    address: billingShippingAddressLine.value || null,
                    city: src.city || null,
                    province: src.province || null,
                    postal_code: src.postal_code || null,
                }
                : null,
        };
    });
    // ---------- COMPUTED WALLET ----------
    const walletFormatted = computed(() => walletBalance.value.toFixed(2).replace('.', ',') + '\u00A0\u20AC');
    const walletLoaded = computed(() => walletLoadedRef.value);
    // ---------- AZIONI ----------
    function setCart(payload) {
        if (!payload)
            return;
        cart.value = {
            data: Array.isArray(payload.data) ? payload.data : [],
            meta: payload.meta || {},
        };
    }
    function setExistingOrder(order) {
        existingOrder.value = order;
    }
    function setPageReady(ready) {
        pageReady.value = ready;
    }
    function setWalletBalance(value, loaded = true) {
        walletBalance.value = Number.isFinite(Number(value)) ? Number(value) : 0;
        walletLoadedRef.value = loaded;
    }
    /**
     * Pre-popola i campi fattura con i dati dell'indirizzo spedizione.
     * Non sovrascrive input utente gia' presenti.
     */
    function applyShippingDataToBilling() {
        const a = billingShippingSource.value;
        if (!a)
            return;
        if (invoiceSubjectType.value === 'privato') {
            fatturaData.value.nome_completo = fatturaData.value.nome_completo || String(a.name || '');
        }
        else if (!fatturaData.value.ragione_sociale) {
            fatturaData.value.ragione_sociale = String(a.name || '');
        }
        fatturaData.value.indirizzo = fatturaData.value.indirizzo || billingShippingAddressLine.value;
        fatturaData.value.city = fatturaData.value.city || String(a.city || '');
        fatturaData.value.province = fatturaData.value.province || String(a.province || '');
        fatturaData.value.postal_code = fatturaData.value.postal_code || String(a.postal_code || '');
    }
    function clearInvoiceCompanyFields() {
        fatturaData.value.ragione_sociale = '';
        fatturaData.value.p_iva = '';
    }
    /**
     * Reset completo dello store (post pagamento riuscito).
     */
    function resetCart() {
        cart.value = { data: [], meta: { total: '0,00\u20AC', address_groups: [] } };
        pageReady.value = false;
        existingOrder.value = null;
        fatturazioneType.value = 'ricevuta';
        invoiceSubjectType.value = 'azienda';
        fatturaData.value = { ...EMPTY_FATTURA };
        walletBalance.value = 0;
        walletLoadedRef.value = false;
        expandedGroups.value = {};
    }
    return {
        // state
        cart,
        pageReady,
        existingOrder,
        fatturazioneType,
        invoiceSubjectType,
        fatturaData,
        walletBalance,
        expandedGroups,
        // computed cart
        displayPackages,
        getTotal,
        totalPackages,
        contentDescription,
        addressGroups,
        hasMultipleGroups,
        mergeGroupsCount,
        // computed existing order
        existingOrderGrossTotal,
        existingOrderPayableTotal,
        existingOrderDiscountPreview,
        existingOrderCanAcceptDiscount,
        // computed billing
        billingShippingSource,
        billingShippingAddressLine,
        billingShippingFullAddress,
        billingPayload,
        // computed wallet
        walletFormatted,
        walletLoaded,
        // actions
        setCart,
        setExistingOrder,
        setPageReady,
        setWalletBalance,
        applyShippingDataToBilling,
        clearInvoiceCompanyFields,
        resetCart,
    };
});
