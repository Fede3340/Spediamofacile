/**
 * @file useCarrello — composable pagina /carrello.
 * Estratto da useCart.js. Logica filtri, raggruppamento, coupon UI.
 * Distinto da useCart (checkout) perche' la pagina ha contesto e API diverse.
 */
import { formatPrice as formatPriceCents } from '~/utils/price';
import { formatDateIt } from '~/utils/date';
import { buildDiscountOrderContext, parseEuroAmount } from '~/utils/discountPreview';
import { useCartPromoPreview } from '~/composables/useCartPromoPreview';
import { useCheckoutPromoPreview } from '~/composables/useCheckoutPromoPreview';

// SEZIONE 3 â€” useCarrello(): logica pagina /carrello
// ============================================================================
// State e API distinti da useCart perche' la pagina /carrello ha:
//  - couponApplied SEMANTICA DIVERSA (boolean vs object di useCart)
//  - filtri indirizzi, raggruppamento, quantita' inline, auth gate guest
//  - usa useCartFetch (reattivo a prerender/route) invece di fetch diretto
// Tentare di unificare rompeva la retro-compat dei consumer in pages/carrello.vue.
/**
 * @typedef {import('~/types').CartItem} CartItem
 * @typedef {import('~/types').AddressGroup} AddressGroup
 * @typedef {{ type: 'success' | 'error', text: string }} CouponMessage
 * @typedef {{ success?: boolean, percentage?: number, new_total?: number }} CouponResponse
 * @typedef {{ type: 'group', groupIndex: number, group: AddressGroup, items: CartItem[], totalCents: number, color: string }} DisplayGroupEntry
 * @typedef {{ type: 'single', groupIndex: number, item: CartItem }} DisplaySingleEntry
 * @typedef {DisplayGroupEntry | DisplaySingleEntry} DisplayEntry
 */
/** Composable carrello: filtri, raggruppamento indirizzi, coupon, auth gate checkout per guest. */
export function useCarrello() {
    const { cart, refresh, status } = useCartFetch();
    const { isAuthenticated } = useSanctumAuth();
    const { openAuthModal } = useAuthModalStore();
    const sanctum = useSanctumClient();
    const route = useRoute();
    const uiFeedback = useUiFeedback();
    // Promo settings per banner e badge
    const { loadPriceBands, promoSettings } = usePriceBands();
    onMounted(async () => { await loadPriceBands(); });
    // Endpoint diverso per svuotare il carrello in base a se l'utente e' loggato o ospite
    const endpoint = computed(() => (isAuthenticated.value ? '/api/empty-cart' : '/api/empty-guest-cart'));
    // Redirect post-login quando il guest avvia checkout dal carrello.
    // /checkout resta trampoline legacy inbound; i flussi vivi usano la rotta canonica del funnel.
    const authCheckoutRedirect = '/la-tua-spedizione/2?step=pagamento';
    // Apertura modal auth unificato (AuthOverlayModal globale). Se gia' autenticato
    // naviga direttamente al checkout. Altrimenti apre il modal con tab richiesto
    // (default: login) e redirect post-auth = /checkout.
    const openCheckoutWithAuthGate = (tab = 'login') => {
        if (isAuthenticated.value) {
            navigateTo(authCheckoutRedirect);
            return;
        }
        openAuthModal({
            tab,
            redirect: authCheckoutRedirect,
        });
    };
    // Aggiorna i dati del carrello ogni volta che la pagina viene visitata
    onMounted(async () => {
        if (route.query.updated) {
            clearNuxtData('cart');
        }
        await refresh();
        if (cart.value?.meta?.address_groups) {
            const mergedGroups = cart.value.meta.address_groups.filter((g) => g.package_ids?.length > 1);
            if (mergedGroups.length > 0) {
                const totalMerged = mergedGroups.reduce((sum, g) => sum + g.package_ids.length, 0);
                uiFeedback.info(`${totalMerged} pacchi identici sono stati uniti automaticamente`, '', { timeout: 5000 });
            }
        }
    });
    // --- FILTRI ---
    const filterProvenienza = ref('');
    const filterRiferimento = ref('');
    const filteredCartItems = computed(() => {
        if (!cart.value?.data)
            return [];
        let items = [...cart.value.data];
        if (filterProvenienza.value) {
            items = items.filter((item) => item.origin_address?.city?.toLowerCase().includes(filterProvenienza.value.toLowerCase()));
        }
        if (filterRiferimento.value) {
            items = items.filter((item) => String(item.id).includes(filterRiferimento.value) ||
                (item.origin_address?.name || '').toLowerCase().includes(filterRiferimento.value.toLowerCase()) ||
                (item.destination_address?.name || '').toLowerCase().includes(filterRiferimento.value.toLowerCase()));
        }
        return items;
    });
    const uniqueCities = computed(() => {
        if (!cart.value?.data)
            return [];
        const cities = cart.value.data.map((item) => item.origin_address?.city).filter(Boolean);
        return [...new Set(cities)];
    });
    // --- ELIMINAZIONE SINGOLA SPEDIZIONE ---
    const showDeleteConfirm = ref(false);
    const deleteTargetId = ref(null);
    const deleteLoading = ref(false);
    const askDelete = (id) => {
        deleteTargetId.value = id;
        showDeleteConfirm.value = true;
    };
    const confirmDelete = async () => {
        deleteLoading.value = true;
        try {
            await sanctum(`/api/cart/${deleteTargetId.value}`, { method: 'DELETE' });
            clearNuxtData('cart');
            await refreshNuxtData('cart');
            uiFeedback.success('Spedizione rimossa dal carrello.');
        }
        catch (e) {
            uiFeedback.error('Errore durante la rimozione', 'Riprova.');
        }
        finally {
            deleteLoading.value = false;
            showDeleteConfirm.value = false;
            deleteTargetId.value = null;
        }
    };
    // --- SVUOTA CARRELLO ---
    const showEmptyConfirm = ref(false);
    const emptyCartLoading = ref(false);
    const emptyCart = async () => {
        emptyCartLoading.value = true;
        try {
            await sanctum(endpoint.value, { method: 'DELETE' });
            clearNuxtData('cart');
            await refreshNuxtData('cart');
            showEmptyConfirm.value = false;
            uiFeedback.success('Carrello svuotato.');
        }
        catch (error) {
            uiFeedback.error('Errore durante lo svuotamento del carrello', 'Riprova.');
        }
        finally {
            emptyCartLoading.value = false;
        }
    };
    // formatPrice: usa la utility centsâ†’â‚¬ da utils/price (import in cima al file).
    // Alias locale per non scontrarsi con la formatPrice(num: euro) definita in useCart().
    const formatPrice = formatPriceCents;
    const unitPrice = (item) => {
        const total = Number(item.single_price) || 0;
        const qty = Math.max(1, Number(item.quantity) || 1);
        return total / qty;
    };
    // --- AGGIORNAMENTO QUANTITA' ---
    const quantityUpdating = ref(null);
    const updateQuantity = async (itemId, newQty) => {
        if (newQty < 1)
            newQty = 1;
        if (newQty > 100)
            newQty = 100;
        quantityUpdating.value = itemId;
        try {
            await sanctum(`/api/cart/${itemId}/quantity`, {
                method: 'PATCH',
                body: { quantity: newQty },
            });
            clearNuxtData('cart');
            await refreshNuxtData('cart');
        }
        catch (e) {
            uiFeedback.error('Errore nell\'aggiornamento della quantit\u00E0', 'Riprova.');
        }
        finally {
            quantityUpdating.value = null;
        }
    };
    const formatDate = (item) => {
        if (item.created_at) {
            return formatDateIt(item.created_at, new Date().toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' }));
        }
        return new Date().toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
    };
    const getPackageIcon = (item) => {
        const type = item.package_type?.toLowerCase() || '';
        if (type.includes('pallet'))
            return '/img/quote/first-step/pallet.png';
        if (type.includes('busta'))
            return '/img/quote/first-step/envelope.png';
        if (type.includes('valigia'))
            return '/img/quote/first-step/suitcase.png';
        return '/img/quote/first-step/pack.png';
    };
    // --- RAGGRUPPAMENTO PER INDIRIZZO ---
    const addressGroups = computed(() => cart.value?.meta?.address_groups || []);
    const groupColors = ['#095866', '#E44203', '#6B21A8', '#0369A1', '#B45309'];
    const expandedGroups = ref({});
    onMounted(() => {
        const saved = sessionStorage.getItem('cart_expanded_groups');
        if (saved) {
            try {
                expandedGroups.value = JSON.parse(saved);
            }
            catch (e) {
                // sessionStorage corrotto: ripartiamo dal default. Log solo in dev per diagnosi.
                if (import.meta.dev)
                    console.warn('[useCart] cart_expanded_groups JSON malformato, reset al default', e);
                sessionStorage.removeItem('cart_expanded_groups');
            }
        }
    });
    watch(expandedGroups, (newVal) => {
        sessionStorage.setItem('cart_expanded_groups', JSON.stringify(newVal));
    }, { deep: true });
    const toggleGroup = (groupIdx) => {
        expandedGroups.value[groupIdx] = !isGroupExpanded(groupIdx);
    };
    const isGroupExpanded = (groupIdx) => {
        return expandedGroups.value[groupIdx] !== false;
    };
    const displayEntries = computed(() => {
        const items = filteredCartItems.value;
        if (!items.length)
            return [];
        const filteredIds = new Set(items.map((i) => i.id));
        const usedIds = new Set();
        const entries = [];
        for (let gIdx = 0; gIdx < addressGroups.value.length; gIdx++) {
            const group = addressGroups.value[gIdx];
            const groupItems = (group.package_ids || [])
                .filter((id) => filteredIds.has(id) && !usedIds.has(id))
                .map((id) => items.find((i) => i.id === id))
                .filter(Boolean);
            if (groupItems.length === 0)
                continue;
            groupItems.forEach((i) => usedIds.add(i.id));
            if (groupItems.length > 1) {
                const groupTotal = groupItems.reduce((sum, i) => sum + (Number(i.single_price) || 0), 0);
                entries.push({
                    type: 'group',
                    groupIndex: gIdx,
                    group,
                    items: groupItems,
                    totalCents: groupTotal,
                    color: groupColors[gIdx % groupColors.length],
                });
            }
            else {
                entries.push({
                    type: 'single',
                    groupIndex: gIdx,
                    item: groupItems[0],
                });
            }
        }
        for (const item of items) {
            if (!usedIds.has(item.id)) {
                entries.push({
                    type: 'single',
                    groupIndex: -1,
                    item,
                });
            }
        }
        return entries;
    });
    // --- COUPON / CODICE SCONTO (boundary canonico UI carrello) ---
    const { appliedTotal, applyCoupon, couponApplied, couponCode, couponDiscount, couponMessage, discountedTotal, removeCoupon, showCouponField, showCouponPanel, } = useCartPromoPreview({
        sanctum,
        total: computed(() => cart.value?.meta?.total || '0,00€'),
    });
    const displayTotal = computed(() => {
        return couponApplied.value && discountedTotal.value ? discountedTotal.value : cart.value?.meta?.total;
    });
    // CSS classes for quantity buttons
    const quantityButtonClass = "w-[32px] h-[32px] tablet:w-[24px] tablet:h-[24px] flex items-center justify-center rounded-full bg-[#EEF2F3] text-[#252B42] text-[0.875rem] tablet:text-[0.75rem] font-bold hover:bg-[#DDE5E7] disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed transition-[background-color,transform] duration-200 active:scale-[0.97]";
    const quantityButtonCompactClass = "w-[22px] h-[22px] flex items-center justify-center rounded-full bg-[#EEF2F3] text-[#252B42] text-[0.75rem] font-bold hover:bg-[#DDE5E7] disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed transition-[background-color,transform] duration-200 active:scale-[0.97]";
    const quantityButtonMobileClass = "w-[36px] h-[36px] flex items-center justify-center rounded-full bg-[#EEF2F3] text-[#252B42] text-[0.875rem] font-bold hover:bg-[#DDE5E7] disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed transition-[background-color,transform] duration-200 active:scale-[0.97]";
    return {
        // Cart data
        cart,
        refresh,
        status,
        isAuthenticated,
        // Promo
        promoSettings,
        // Filters
        filterProvenienza,
        filterRiferimento,
        filteredCartItems,
        uniqueCities,
        // Delete
        showDeleteConfirm,
        deleteLoading,
        askDelete,
        confirmDelete,
        // Empty cart
        showEmptyConfirm,
        emptyCartLoading,
        emptyCart,
        // Prices
        formatPrice,
        unitPrice,
        formatDate,
        getPackageIcon,
        // Quantity
        quantityUpdating,
        updateQuantity,
        quantityButtonClass,
        quantityButtonCompactClass,
        quantityButtonMobileClass,
        // Grouping
        addressGroups,
        groupColors,
        expandedGroups,
        toggleGroup,
        isGroupExpanded,
        displayEntries,
        // Coupon
        couponCode,
        couponMessage,
        couponApplied,
        couponDiscount,
        appliedTotal,
        showCouponField,
        showCouponPanel,
        applyCoupon,
        removeCoupon,
        displayTotal,
        // Auth gate (unificato su AuthOverlayModal globale)
        authCheckoutRedirect,
        openCheckoutWithAuthGate,
    };
}
