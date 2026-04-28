/**
 * @file useCart — Composable useCart.
 */
// composables/useCart.js
// Tre composable per contesti diversi:
//   - useCart()           â†’ checkout (ShipmentStepPagamento + useCheckout)
//   - useCartOperations() â†’ alias retro-compat con extra per facade useCheckout
//   - useCarrello()       â†’ pagina /carrello (filtri, raggruppamento, coupon UI)
import { formatPrice as formatPriceCents } from '~/utils/price';
import { formatDateIt } from '~/utils/date';
import { buildDiscountOrderContext, parseEuroAmount, } from '~/utils/discountPreview';
import { useCartPromoPreview } from '~/composables/useCartPromoPreview';
import { useCheckoutPromoPreview } from '~/composables/useCheckoutPromoPreview';
import { deriveShipmentFlowStateFromUserStore, pickMostAdvancedShipmentFlowState, resolveShipmentFlowState, } from '~/utils/shipment';
// ============================================================================
// SEZIONE 1 â€” useCart(): stato checkout, billing, wallet, coupon
// ============================================================================
/**
 * Composable principale: cart + billing + wallet + coupon per la pagina pagamento.
 * Tutti i dati reattivi per ShipmentStepPagamento.vue sono esposti qui.
 * @returns stato reattivo e azioni (init, refresh, validateCoupon, loadWalletBalance, ...)
 */
export function useCart() {
    const route = useRoute();
    const sanctum = useSanctumClient();
    const { user, isAuthenticated } = useSanctumAuth();
    // usePriceBands carica /api/promo (price bands + promo flags).
    const { loadPriceBands, priceBands, promoSettings } = usePriceBands();
    // ---------- FALLBACK ROUTE ----------
    // Usato da useCartOperations()/useCheckout quando il cart risulta vuoto,
    // per riportare l'utente alla rotta piu' avanzata del flusso spedizione.
    const { session } = useSession();
    const userStore = useShipmentFlowStore();
    const fallbackFlowRoute = computed(() => {
        const remoteFlowState = resolveShipmentFlowState(session.value?.data || {});
        const localFlowState = deriveShipmentFlowStateFromUserStore(userStore);
        return pickMostAdvancedShipmentFlowState(remoteFlowState, localFlowState).last_valid_route || '/preventivo';
    });
    // ---------- STATO CART ----------
    // cart.data = array pacchi; cart.meta = { total, address_groups }
    const cart = ref({ data: [], meta: { total: '0,00â‚¬', address_groups: [] } });
    const pageReady = ref(false);
    const existingOrder = ref(null);
    /** ID ordine letto dalla query string `?order_id=123` (null se checkout nuovo). */
    const existingOrderId = computed(() => {
        const raw = Array.isArray(route.query.order_id) ? route.query.order_id[0] : route.query.order_id;
        return raw ? String(raw) : null;
    });
    const centsToEuro = (value) => {
        const cents = Number(value);
        return Number.isFinite(cents) ? Math.max(0, cents) / 100 : 0;
    };
    const existingOrderGrossTotal = computed(() => {
        if (!existingOrder.value)
            return 0;
        return centsToEuro(existingOrder.value.gross_subtotal_cents ?? existingOrder.value.subtotal_cents);
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
    /**
     * Carica cart o ordine esistente + verifica auth.
     * Chiamato al mount di ShipmentStepPagamento.vue.
     * @returns {Promise<boolean>} true se OK, false se auth manca o caricamento fallisce.
     */
    async function initCheckoutPage() {
        existingOrder.value = null;
        // Caso A: rifatturazione ordine esistente (retry pagamento).
        if (existingOrderId.value) {
            try {
                const res = await sanctum(`/api/orders/${existingOrderId.value}`);
                existingOrder.value = res?.data ?? res ?? null;
                pageReady.value = true;
                return true;
            }
            catch {
                existingOrder.value = null;
                pageReady.value = false;
                return false;
            }
        }
        if (!isAuthenticated.value || !user.value) {
            pageReady.value = false;
            return false;
        }
        // Caso B: checkout standard da carrello.
        await refreshCart();
        const hasItems = Array.isArray(cart.value?.data) && cart.value.data.length > 0;
        pageReady.value = hasItems;
        return hasItems;
    }
    /** Ricarica il carrello dal backend. Silenzia errori: in caso KO il cart resta quello precedente. */
    async function refreshCart() {
        try {
            const res = await sanctum('/api/cart');
            cart.value = res?.data ? { data: res.data, meta: res.meta || {} } : (res || cart.value);
        }
        catch {
            // cart precedente mantenuto
        }
    }
    // ---------- PACCHI & TOTALI ----------
    /** Packages da mostrare: se rifatturazione usa existingOrder.packages, altrimenti cart.data. */
    const displayPackages = computed(() => {
        if (existingOrder.value)
            return existingOrder.value.packages || [];
        return cart.value?.data || [];
    });
    /** Totale "raw" dal backend (stringa "20,00â‚¬" o numero). */
    const getTotal = computed(() => {
        if (existingOrder.value)
            return existingOrderGrossTotal.value;
        return cart.value?.meta?.total || '0,00â‚¬';
    });
    /** Totale numerico (euro float). Utile per calcoli coupon. */
    const getNumberTotal = computed(() => {
        return parseEuroAmount(getTotal.value);
    });
    const totalPackages = computed(() => displayPackages.value.reduce((sum, item) => sum + (Number(item.quantity) || 1), 0));
    /** Descrizione contenuto: "Pacco, Pallet" (tipi unici, joined). */
    const contentDescription = computed(() => {
        if (!displayPackages.value.length)
            return '';
        const types = displayPackages.value.map((item) => item.package_type || 'Pacco').filter(Boolean);
        return [...new Set(types)].join(', ');
    });
    const addressGroups = computed(() => cart.value?.meta?.address_groups || []);
    const hasMultipleGroups = computed(() => addressGroups.value.filter((g) => g.count >= 1).length > 1);
    const mergeGroupsCount = computed(() => addressGroups.value.length);
    /**
     * Formatter "0,00 â‚¬". Accetta euro-float (es. 20 â†’ "20,00 â‚¬").
     * Per centesimi usa `formatPrice` da utils/price (quello importato qui formatta cents).
     * Questa versione lavora in EURO per i totali già decimali del cart.
     * @param {number|string} num
     * @returns {string}
     */
    function formatPrice(num) {
        const n = Number(num);
        if (!Number.isFinite(n))
            return '0,00 \u20AC';
        return n.toFixed(2).replace('.', ',') + '\u00A0\u20AC';
    }
    const existingOrderDiscountPreview = computed(() => {
        const context = existingOrder.value?.discount_context;
        if (!context || typeof context !== 'object')
            return null;
        const type = String(context.type || '').trim().toLowerCase();
        const code = String(context.code || '').trim().toUpperCase();
        if (!type || !code)
            return null;
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
        if (!existingOrder.value || existingOrderDiscountPreview.value)
            return false;
        const status = String(existingOrder.value.raw_status || '').trim().toLowerCase();
        return ['pending', 'payment_failed'].includes(status);
    });
    // ---------- BILLING / FATTURAZIONE ----------
    const fatturazioneType = ref('ricevuta'); // 'ricevuta' | 'fattura'
    const invoiceSubjectType = ref('azienda'); // 'azienda' | 'privato'
    const fatturaData = ref({
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
    /** Primo indirizzo utile dal carrello: preferisce origin, fallback destination. */
    const billingShippingSource = computed(() => {
        const pkg = displayPackages.value?.[0];
        return pkg?.origin_address || pkg?.destination_address || null;
    });
    const billingShippingAddressLine = computed(() => {
        const a = billingShippingSource.value;
        if (!a)
            return '';
        return [a.address, a.address_number].filter(Boolean).join(' ').trim();
    });
    /** Indirizzo completo formattato per display: "Via Roma 10, 00100 Roma (RM)". */
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
    /** Pre-popola i campi fattura vuoti con i dati spedizione. Non sovrascrive input utente. */
    function applyShippingDataToBilling() {
        const a = billingShippingSource.value;
        if (!a)
            return;
        if (invoiceSubjectType.value === 'privato') {
            fatturaData.value.nome_completo = fatturaData.value.nome_completo || a.name || '';
        }
        else if (!fatturaData.value.ragione_sociale) {
            fatturaData.value.ragione_sociale = a.name || '';
        }
        fatturaData.value.indirizzo = fatturaData.value.indirizzo || billingShippingAddressLine.value;
        fatturaData.value.city = fatturaData.value.city || a.city || '';
        fatturaData.value.province = fatturaData.value.province || a.province || '';
        fatturaData.value.postal_code = fatturaData.value.postal_code || a.postal_code || '';
    }
    // Ogni volta che cambia il tipo soggetto o l'indirizzo, riapplichiamo i default.
    watch([invoiceSubjectType, billingShippingSource], applyShippingDataToBilling, { immediate: true });
    // Passando privato â†’ azienda o viceversa, pulisci i campi non pertinenti.
    watch(invoiceSubjectType, (subjectType) => {
        if (subjectType === 'privato') {
            fatturaData.value.ragione_sociale = '';
            fatturaData.value.p_iva = '';
        }
        applyShippingDataToBilling();
    });
    watch(fatturazioneType, (type) => {
        if (type === 'fattura')
            applyShippingDataToBilling();
    });
    /** Payload per checkout: ricevuta = minimale, fattura = dati completi. */
    const billingPayload = computed(() => {
        if (fatturazioneType.value !== 'fattura')
            return { type: 'ricevuta' };
        const d = fatturaData.value;
        const isAzienda = invoiceSubjectType.value === 'azienda';
        const src = billingShippingSource.value;
        return {
            type: 'fattura',
            subject_type: invoiceSubjectType.value,
            same_as_shipping: false,
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
    // ---------- WALLET ----------
    const walletBalance = ref(0);
    const walletLoadedRef = ref(false);
    /** Carica saldo wallet (idempotente: non re-fetcha se già caricato). */
    async function loadWalletBalance() {
        if (walletLoadedRef.value)
            return;
        try {
            const result = await sanctum('/api/wallet/balance');
            walletBalance.value = Number(result?.balance ?? 0);
        }
        catch {
            walletBalance.value = 0;
        }
        finally {
            walletLoadedRef.value = true;
        }
    }
    const walletFormatted = computed(() => walletBalance.value.toFixed(2).replace('.', ',') + '\u00A0\u20AC');
    const walletLoaded = computed(() => walletLoadedRef.value);
    // ---------- DISCOUNT PREVIEW (coupon / referral) ----------
    const { autoApplyReferral, couponApplied, couponCode, couponError, couponLoading, couponPanelOpen, discountedTotal, removeCoupon, validateCoupon, } = useCheckoutPromoPreview({
        sanctum,
        total: getNumberTotal,
    });
    const displayedCouponApplied = computed(() => existingOrderDiscountPreview.value || couponApplied.value);
    function validateCheckoutCoupon() {
        if (existingOrder.value && !existingOrderCanAcceptDiscount.value) {
            couponError.value = 'Il totale di questo ordine e\' gia\' bloccato. Crea un nuovo preventivo per usare un altro codice.';
            return;
        }
        return validateCoupon();
    }
    function removeDisplayedCoupon() {
        if (existingOrderDiscountPreview.value)
            return;
        removeCoupon();
    }
    // ---------- TOTALI FINALI (post coupon) ----------
    /**
     * Totale preview post-sconto mostrato in checkout.
     * Boundary importante:
     * - serve alla UI e ai controlli locali
     * - non implica automaticamente che lo sconto sia gia' persistito sull'ordine backend
     */
    const finalTotal = computed(() => {
        if (existingOrderDiscountPreview.value)
            return existingOrderPayableTotal.value;
        if (existingOrder.value && couponApplied.value)
            return discountedTotal.value;
        if (existingOrder.value)
            return existingOrderPayableTotal.value;
        return discountedTotal.value;
    });
    /** Totale finale formattato italiano con non-breaking space: "20,00 â‚¬". */
    const finalTotalFormatted = computed(() => {
        if (existingOrder.value?.payable_total && !couponApplied.value)
            return existingOrder.value.payable_total;
        return Number(finalTotal.value).toFixed(2).replace('.', ',') + '\u00A0\u20AC';
    });
    const walletSufficient = computed(() => walletBalance.value >= finalTotal.value);
    /**
     * Bridge canonico frontend tra preview sconto UI e boundary ordine/pagamento.
     * Non rende ancora lo sconto persistito lato backend, ma fa viaggiare lo stesso
     * contesto preview nei payload checkout per evitare drift tra step diversi.
     */
    const discountContext = computed(() => buildDiscountOrderContext({
        preview: displayedCouponApplied.value,
        subtotal: getNumberTotal.value,
        finalTotal: finalTotal.value,
    }));
    return {
        // cart & page state
        cart,
        pageReady,
        existingOrderId,
        existingOrder,
        initCheckoutPage,
        refreshCart,
        // dipendenze esposte per compatibilita' con codice storico (useCheckout facade
        // archiviato 2026-04-20 in _archive/cleanup-features-2026-04-20/composables-consolidati-payment/)
        sanctum,
        userStore,
        user,
        fallbackFlowRoute,
        // promo
        loadPriceBands,
        priceBands,
        promoSettings,
        // pacchi & totali
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
        // billing
        fatturazioneType,
        invoiceSubjectType,
        fatturaData,
        billingShippingFullAddress,
        billingPayload,
        // wallet
        walletBalance,
        walletFormatted,
        walletLoaded,
        walletSufficient,
        loadWalletBalance,
        // coupon
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
// ============================================================================
// SEZIONE 2 â€” useCartOperations(): ALIAS retro-compat di useCart()
// ============================================================================
// Nota storica: era un file a se stante (useCartOperations.js, 359 LOC) che
// duplicava ~95% della logica di useCart. L'unica chiamata rimasta e' da
// useCheckout.js (facade). Consolidato qui come re-export per preservare
// l'API pubblica senza toccare il facade. I consumer continuano a chiamare
// `const cartOps = useCartOperations()` e ricevono esattamente lo stesso
// shape dell'originale.
export function useCartOperations() {
    return useCart();
}
// ============================================================================

// useCarrello estratto in composables/useCarrello.js
