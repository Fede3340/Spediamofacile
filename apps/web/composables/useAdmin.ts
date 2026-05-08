/**
 * useAdmin — utility condivise pannello admin: loading/messaggi, format currency/date,
 * status-config (ordini/withdrawal/referral/proRequest), download etichette BRT.
 * ATTENZIONE cents vs euro: usa formatCents per valori dal DB (che sono in cents).
 * formatCurrency gestisce 3 formati (MyMoney object, stringa, numero).
 */
import { formatDateTimeIt } from '~/utils/date.js';
import { formatEuro } from '~/utils/price.js';

type LabelOrder = {
    id: string | number
    brt_parcel_id?: unknown
    brt_label_base64?: string | null
}

export const useAdmin = () => {
    /* Stato delle azioni admin (approvazione, eliminazione, ecc.) */
    const actionLoading = ref<string | number | null>(null);
    /* Feedback: delegato a useFlashMessage (pattern unico app-wide). */
    const flash = useFlashMessage();
    const actionMessage = flash.message;
    const showSuccess = flash.showSuccess;
    const showError = flash.showError;
    /* Formatta un valore come valuta con 2 decimali.
       Gestisce: oggetto MyMoney {amount (centesimi), formatted}, stringa formattata, numero in euro.
       Delega a formatEuro (~/utils/price.js) dove possibile. */
    const formatCurrency = (val: unknown) => {
        if (val == null)
            return '0,00';
        // Se e' un oggetto MyMoney serializzato {amount: 1250, formatted: "12,50 EUR"}
        if (typeof val === 'object' && 'amount' in val && val.amount !== undefined) {
            return formatEuro(Number(val.amount) / 100);
        }
        // Se e' una stringa formattata (es. "12,50 EUR" o "12,50")
        // Nota: \s in regex JavaScript include \u00A0 (non-breaking space) per spec ECMAScript,
        // quindi non serve aggiungerlo esplicitamente nella character class.
        if (typeof val === 'string') {
            const cleaned = val.replace(/[€\sEUR]/gi, '').replace(/\./g, '').replace(',', '.');
            const num = Number(cleaned);
            return Number.isNaN(num) ? '0,00' : formatEuro(num);
        }
        // Numero semplice: in euro (wallet, commissioni, prelievi, referral, COD)
        const num = Number(val);
        return Number.isNaN(num) ? '0,00' : formatEuro(num);
    };
    /* Formatta centesimi (da Transaction::sum('total') che restituisce centesimi dal DB).
       Delega a formatEuro (~/utils/price.js) per la formattazione. */
    const formatCents = (val: unknown) => formatEuro(Number(val || 0) / 100);
    /* Formatta una data nel formato italiano con ora */
    const formatDate = (dateStr: string | number | Date | null | undefined) => formatDateTimeIt(dateStr, '\u2014');
    /* Configurazione colori, icone e etichette per ogni stato ordine */
    const orderStatusConfig = {
        pending: { label: 'In attesa', bg: 'bg-amber-50', text: 'text-amber-700', icon: 'mdi:clock-outline' },
        awaiting_bank_transfer: { label: 'In attesa di bonifico', bg: 'bg-amber-50', text: 'text-amber-700', icon: 'mdi:bank-transfer-in' },
        processing: { label: 'In lavorazione', bg: 'bg-[#eef8fa]', text: 'text-[#095866]', icon: 'mdi:cog-outline' },
        label_generated: { label: 'Etichetta generata', bg: 'bg-[#eef8fa]', text: 'text-[#095866]', icon: 'mdi:label-outline' },
        completed: { label: 'Completato', bg: 'bg-[#f0fdf4]', text: 'text-[#0a8a7a]', icon: 'mdi:check-circle-outline' },
        paid: { label: 'Pagato', bg: 'bg-[#f0fdf4]', text: 'text-[#0a8a7a]', icon: 'mdi:credit-card-check-outline' },
        payment_failed: { label: 'Pagamento fallito', bg: 'bg-red-50', text: 'text-red-700', icon: 'mdi:credit-card-off-outline' },
        cancelled: { label: 'Annullato', bg: 'bg-gray-100', text: 'text-gray-600', icon: 'mdi:close-circle-outline' },
        in_transit: { label: 'In transito', bg: 'bg-[#eef8fa]', text: 'text-[#095866]', icon: 'mdi:truck-delivery-outline' },
        out_for_delivery: { label: 'In consegna', bg: 'bg-[#eef8fa]', text: 'text-[#095866]', icon: 'mdi:truck-fast-outline' },
        delivered: { label: 'Consegnato', bg: 'bg-[#eef8fa]', text: 'text-[#095866]', icon: 'mdi:package-variant-closed-check' },
        in_giacenza: { label: 'In giacenza', bg: 'bg-orange-50', text: 'text-orange-700', icon: 'mdi:package-variant' },
        returned: { label: 'Reso', bg: 'bg-gray-100', text: 'text-gray-600', icon: 'mdi:package-variant-remove' },
        refused: { label: 'Rifiutato', bg: 'bg-red-50', text: 'text-red-700', icon: 'mdi:close-octagon-outline' },
        refunded: { label: 'Rimborsato', bg: 'bg-gray-100', text: 'text-gray-600', icon: 'mdi:cash-refund' },
    };
    const withdrawalStatusConfig = {
        pending: { label: 'In attesa', icon: 'mdi:clock-outline', bg: 'bg-amber-50', text: 'text-amber-700' },
        approved: { label: 'Approvata', icon: 'mdi:check-circle-outline', bg: 'bg-[#f0fdf4]', text: 'text-[#0a8a7a]' },
        rejected: { label: 'Rifiutata', icon: 'mdi:close-circle-outline', bg: 'bg-red-50', text: 'text-red-700' },
    };
    const referralStatusConfig = {
        confirmed: { label: 'Confermata', bg: 'bg-[#f0fdf4]', text: 'text-[#0a8a7a]' },
        paid: { label: 'Pagata', bg: 'bg-[#eef8fa]', text: 'text-[#095866]' },
        pending: { label: 'In attesa', bg: 'bg-amber-50', text: 'text-amber-700' },
    };
    const proRequestStatusConfig = {
        pending: { label: 'In attesa', bg: 'bg-amber-50', text: 'text-amber-700', icon: 'mdi:clock-outline' },
        approved: { label: 'Approvata', bg: 'bg-[#f0fdf4]', text: 'text-[#0a8a7a]', icon: 'mdi:check-circle-outline' },
        rejected: { label: 'Rifiutata', bg: 'bg-red-50', text: 'text-red-700', icon: 'mdi:close-circle-outline' },
    };
    /* Scarica l'etichetta BRT di un ordine come file PDF */
    const downloadLabel = async (order: LabelOrder) => {
        if (!order.brt_parcel_id && !order.brt_label_base64)
            return;
        try {
            if (order.brt_label_base64) {
                const link = document.createElement('a');
                link.href = `data:application/pdf;base64,${order.brt_label_base64}`;
                link.download = `etichetta-ordine-${order.id}.pdf`;
                link.click();
                return;
            }
            const blob = await $fetch<Blob>(`/api/brt/label/${order.id}`, {
                method: 'GET',
                responseType: 'blob',
                credentials: 'include',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `etichetta-ordine-${order.id}.pdf`;
            document.body.appendChild(link);
            link.click();
            window.URL.revokeObjectURL(url);
            link.remove();
        }
        catch (e) {
            showError(e, "Errore durante il download dell'etichetta.");
        }
    };
    return {
        actionLoading,
        actionMessage,
        showSuccess,
        showError,
        formatCurrency,
        formatCents,
        formatDate,
        orderStatusConfig,
        withdrawalStatusConfig,
        referralStatusConfig,
        proRequestStatusConfig,
        downloadLabel,
    };
};
