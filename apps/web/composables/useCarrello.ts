/**
 * @file useCarrello — composable pagina /carrello.
 * Distinto da useCart (checkout) perche' la pagina ha contesto e API diverse.
 */
import { computed, onMounted, ref, watch, type Ref } from 'vue';
import type { AddressGroup, CartItem, CartResponse } from '~/types';
import type { AuthModalTab } from '~/stores/authStore';
import { formatPrice } from '~/utils/price';
import { formatDateIt } from '~/utils/date';
import { useCartPromoPreview } from '~/composables/useCartPromoPreview';

type SanctumClient = <T = unknown>(url: string, options?: { method?: string; body?: unknown }) => Promise<T>;
type DisplayGroupEntry = {
    type: 'group';
    groupIndex: number;
    group: AddressGroup;
    items: CartItem[];
    totalCents: number;
    color: string;
};
type DisplaySingleEntry = { type: 'single'; groupIndex: number; item: CartItem };
type DisplayEntry = DisplayGroupEntry | DisplaySingleEntry;

// SEZIONE 3 â€” useCarrello(): logica pagina /carrello
// ============================================================================
// State e API distinti da useCart perche' la pagina /carrello ha:
//  - couponApplied SEMANTICA DIVERSA (boolean vs object di useCart)
//  - filtri indirizzi, raggruppamento, quantita' inline, auth gate guest
//  - usa useCartFetch (reattivo a prerender/route) invece di fetch diretto
// Tentare di unificare rompeva la retro-compat dei consumer in pages/carrello.vue.
/** Composable carrello: filtri, raggruppamento indirizzi, coupon, auth gate checkout per guest. */
export function useCarrello() {
    const { cart: rawCart, refresh, status } = useCartFetch();
    const cart = rawCart as Ref<CartResponse | null | undefined>;
    const { isAuthenticated } = useSanctumAuth();
    const { openAuthModal } = useAuthStore();
    const sanctum = useSanctumClient() as SanctumClient;
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
    const openCheckoutWithAuthGate = (tab: AuthModalTab = 'login') => {
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
            const mergedGroups = cart.value.meta.address_groups.filter((g: AddressGroup) => g.package_ids?.length > 1);
            if (mergedGroups.length > 0) {
                const totalMerged = mergedGroups.reduce((sum: number, g: AddressGroup) => sum + g.package_ids.length, 0);
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
        let items: CartItem[] = [...cart.value.data];
        if (filterProvenienza.value) {
            items = items.filter((item: CartItem) => item.origin_address?.city?.toLowerCase().includes(filterProvenienza.value.toLowerCase()));
        }
        if (filterRiferimento.value) {
            items = items.filter((item: CartItem) => String(item.id).includes(filterRiferimento.value) ||
                (item.origin_address?.name || '').toLowerCase().includes(filterRiferimento.value.toLowerCase()) ||
                (item.destination_address?.name || '').toLowerCase().includes(filterRiferimento.value.toLowerCase()));
        }
        return items;
    });
    const uniqueCities = computed(() => {
        if (!cart.value?.data)
            return [];
        const cities = cart.value.data.map((item: CartItem) => item.origin_address?.city).filter(Boolean);
        return [...new Set(cities)];
    });
    // --- ELIMINAZIONE SINGOLA SPEDIZIONE ---
    const showDeleteConfirm = ref(false);
    const deleteTargetId = ref<number | string | null>(null);
    const deleteLoading = ref(false);
    const askDelete = (id: number | string) => {
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
        catch {
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
        catch {
            uiFeedback.error('Errore durante lo svuotamento del carrello', 'Riprova.');
        }
        finally {
            emptyCartLoading.value = false;
        }
    };
    const unitPrice = (item: CartItem) => {
        const total = Number(item.single_price) || 0;
        const qty = Math.max(1, Number(item.quantity) || 1);
        return total / qty;
    };
    // --- AGGIORNAMENTO QUANTITA' ---
    const quantityUpdating = ref<number | string | null>(null);
    const updateQuantity = async (itemId: number | string, newQty: number) => {
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
        catch {
            uiFeedback.error('Errore nell\'aggiornamento della quantit\u00E0', 'Riprova.');
        }
        finally {
            quantityUpdating.value = null;
        }
    };
    const formatDate = (item: CartItem) => {
        if (item.created_at) {
            return formatDateIt(item.created_at, new Date().toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' }));
        }
        return new Date().toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
    };
    const getPackageIcon = (item: CartItem) => {
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
    const addressGroups = computed<AddressGroup[]>(() => cart.value?.meta?.address_groups || []);
    const defaultGroupColor = '#095866';
    const groupColors = [defaultGroupColor, '#E44203', '#6B21A8', '#0369A1', '#B45309'];
    const expandedGroups = ref<Record<number, boolean>>({});
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
    const toggleGroup = (groupIdx: number) => {
        expandedGroups.value[groupIdx] = !isGroupExpanded(groupIdx);
    };
    const isGroupExpanded = (groupIdx: number) => {
        return expandedGroups.value[groupIdx] !== false;
    };
    const displayEntries = computed<DisplayEntry[]>(() => {
        const items = filteredCartItems.value;
        if (!items.length)
            return [];
        const filteredIds = new Set(items.map((item: CartItem) => item.id));
        const usedIds = new Set<number>();
        const entries: DisplayEntry[] = [];
        for (let gIdx = 0; gIdx < addressGroups.value.length; gIdx++) {
            const group = addressGroups.value[gIdx];
            if (!group) continue;
            const groupItems = (group.package_ids || [])
                .filter((id: number) => filteredIds.has(id) && !usedIds.has(id))
                .map((id: number) => items.find((item: CartItem) => item.id === id))
                .filter((item): item is CartItem => Boolean(item));
            if (groupItems.length === 0)
                continue;
            groupItems.forEach((item: CartItem) => usedIds.add(item.id));
            if (groupItems.length > 1) {
                const groupTotal = groupItems.reduce((sum: number, item: CartItem) => sum + (Number(item.single_price) || 0), 0);
                entries.push({
                    type: 'group',
                    groupIndex: gIdx,
                    group,
                    items: groupItems,
                    totalCents: groupTotal,
                    color: groupColors[gIdx % groupColors.length] || defaultGroupColor,
                });
            }
            else {
                const firstItem = groupItems[0];
                if (!firstItem) continue;
                entries.push({
                    type: 'single',
                    groupIndex: gIdx,
                    item: firstItem,
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
