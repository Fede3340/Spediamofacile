/**
 * promosStore — settings promozione globale (badge, etichetta, immagine).
 *
 * Estratto dalla sezione "promo" di composables/useAdminPricing.js
 * (split atomico Pinia 2026-04-26). Endpoint dedicati:
 *   - GET  /api/admin/promo-settings
 *   - POST /api/admin/promo-settings
 *   - POST /api/admin/promo-settings/upload-image
 */
import { defineStore } from 'pinia';
import { ADMIN_DEFAULT_PROMO } from '~/utils/adminPrezziHelpers';
const VALID_IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
const MAX_IMAGE_SIZE_BYTES = 2 * 1024 * 1024;
export const useAdminPromosStore = defineStore('admin-promos', () => {
    // ---------- STATE ----------
    const promo = ref({ ...ADMIN_DEFAULT_PROMO });
    const promoLoading = ref(false);
    const promoSaving = ref(false);
    const promoImageUploading = ref(false);
    // ---------- ACTIONS ----------
    const fetchPromoSettings = async () => {
        const sanctum = useSanctumClient();
        promoLoading.value = true;
        try {
            const res = await sanctum('/api/admin/promo-settings') | null;
            const payload = res?.data ?? res ?? {};
            const data = (payload);
            promo.value = {
                active: data.promo_active === 'true' || data.promo_active === true,
                label_text: data.promo_label_text || '',
                label_color: data.promo_label_color || '#E44203',
                label_image: (data.promo_label_image) || null,
                show_badges: data.promo_show_badges === 'true' || data.promo_show_badges === true,
                description: data.promo_description || '',
            };
        }
        catch {
            // Default values already set
        }
        finally {
            promoLoading.value = false;
        }
    };
    const savePromo = async (handlers) => {
        const sanctum = useSanctumClient();
        promoSaving.value = true;
        try {
            await sanctum('/api/admin/promo-settings', {
                method: 'POST',
                body: {
                    promo_active: promo.value.active ? 'true' : 'false',
                    promo_label_text: promo.value.label_text,
                    promo_label_color: promo.value.label_color,
                    promo_show_badges: promo.value.show_badges ? 'true' : 'false',
                    promo_description: promo.value.description,
                },
            });
            handlers.showSuccess('Impostazioni promozione salvate con successo.');
            await handlers.reloadPublicPriceBands();
        }
        catch (e) {
            handlers.showError(e, 'Errore durante il salvataggio della promozione.');
        }
        finally {
            promoSaving.value = false;
        }
    };
    const uploadPromoImage = async (event, handlers) => {
        const input = event.target;
        const file = input.files?.[0];
        if (!file)
            return;
        if (!VALID_IMAGE_TYPES.includes(file.type)) {
            handlers.showError(null, 'Formato file non valido. Usa JPG, PNG, GIF o WebP.');
            input.value = '';
            return;
        }
        if (file.size > MAX_IMAGE_SIZE_BYTES) {
            handlers.showError(null, 'File troppo grande. Dimensione massima: 2MB.');
            input.value = '';
            return;
        }
        const sanctum = useSanctumClient();
        promoImageUploading.value = true;
        try {
            const formData = new FormData();
            formData.append('image', file);
            const res = await sanctum('/api/admin/promo-settings/upload-image', {
                method: 'POST',
                body,
            });
            promo.value.label_image = res?.image_url || null;
            handlers.showSuccess('Immagine promo caricata.');
        }
        catch (e) {
            handlers.showError(e, "Errore durante l'upload dell'immagine.");
        }
        finally {
            promoImageUploading.value = false;
            input.value = '';
        }
    };
    return {
        promo,
        promoLoading,
        promoSaving,
        promoImageUploading,
        fetchPromoSettings,
        savePromo,
        uploadPromoImage,
    };
});
