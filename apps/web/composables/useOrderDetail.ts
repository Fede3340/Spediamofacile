/**
 * Account shipment detail boundary: fetch persisted order, expose readable
 * labels, and call BRT/pickup/cancellation endpoints.
 */
import type { Ref } from 'vue';
import type { CartItem, Order } from '~/types';
import { formatDateTimeIt } from '~/utils/date.js';

type ApiEnvelope<T> = T | { data?: T | null } | null | undefined;
type ApiMessageResponse = { message?: string; error?: string };
type ApiErrorPayload = { message?: string; error?: string };
type SanctumRequestOptions = { method?: string; body?: unknown; responseType?: string };
type SanctumClient = <T = unknown>(url: string, options?: SanctumRequestOptions) => Promise<T>;
type AddressLike = { city?: string | null; province?: string | null };
type OrderPackage = CartItem & { origin_address?: AddressLike | null; destination_address?: AddressLike | null };
type OrderDetail = Omit<Order, 'packages'> & {
	payable_total?: string | number | null;
	payable_total_cents?: number | null;
	packages?: OrderPackage[];
};
type ExecutionDetail = { bordero_document_filename?: string | null };
type ExecutionActionPayload = { endpoint: string; busyRef: Ref<boolean>; successMessage: string; body?: unknown };

const STATUS_TEAL = 'bg-[#eef8fa] text-[#095866]';
const STATUS_GREEN = 'bg-[#f0fdf4] text-[#0a8a7a]';
const STATUS_ORANGE = 'bg-orange-100 text-orange-700';
const statusClasses: Record<string, string> = {
	'In attesa': 'bg-yellow-100 text-yellow-700',
	'In lavorazione': STATUS_TEAL,
	Completato: STATUS_GREEN,
	Fallito: 'bg-red-100 text-red-700',
	Pagato: STATUS_GREEN,
	Annullato: 'bg-gray-200 text-gray-600',
	Rimborsato: STATUS_ORANGE,
	'In transito': STATUS_TEAL,
	Consegnato: STATUS_GREEN,
	'In giacenza': STATUS_ORANGE,
};
const paymentLabels: Record<string, string> = {
	stripe: 'Carta di credito (Stripe)',
	wallet: 'Portafoglio',
	bonifico: 'Bonifico',
};
const EMPTY_PACKAGE = { package_type: 'Pacco', quantity: 1, weight: '', first_size: '', second_size: '', third_size: '', content_description: '' };

const isRecord = (value: unknown): value is Record<string, unknown> => typeof value === 'object' && value !== null;
const unwrapData = <T>(payload: ApiEnvelope<T>): T | null => {
	if (!payload) return null;
	if (isRecord(payload) && Object.prototype.hasOwnProperty.call(payload, 'data')) return ((payload as Record<string, unknown>).data as T | null | undefined) ?? null;
	return payload as T;
};
const extractApiError = (error: unknown): ApiErrorPayload => {
	if (!isRecord(error)) return {};
	const responseData = isRecord(error.response) && isRecord(error.response._data) ? error.response._data : null;
	const data = isRecord(error.data) ? error.data : null;
	return (responseData ?? data ?? {}) as ApiErrorPayload;
};
const formatLocation = (addr?: AddressLike | null) => {
	const city = addr?.city || '';
	const province = addr?.province ? ` (${addr.province})` : '';
	return `${city}${province}`;
};

export default function useOrderDetail(orderId: string | number) {
	const sanctum = useSanctumClient() as SanctumClient;
	const { data: order, status: orderStatus, refresh } = useSanctumFetch<ApiEnvelope<OrderDetail>>(`/api/orders/${orderId}`, undefined, { lazy: true });
	const { data: execution, status: executionStatus, refresh: refreshExecution } = useSanctumFetch<ApiEnvelope<ExecutionDetail>>(`/api/orders/${orderId}/execution`, undefined, { lazy: true });

	const fallback = 'bg-gray-100 text-gray-700';
	const statusColor = (status: unknown) => (typeof status === 'string' ? statusClasses[status] ?? fallback : fallback);
	const paymentMethodLabel = (method: unknown) => (typeof method === 'string' && method ? paymentLabels[method] ?? method : 'Non specificato');

	const downloadFile = (blob: Blob, filename: string) => {
		const url = window.URL.createObjectURL(blob);
		const link = document.createElement('a');
		link.href = url;
		link.download = filename;
		document.body.appendChild(link);
		link.click();
		window.URL.revokeObjectURL(url);
		link.remove();
	};

	const orderData = computed(() => unwrapData<OrderDetail>(order.value));
	const executionData = computed(() => unwrapData<ExecutionDetail>(execution.value));

	const orderSubtotalLabel = computed(() => {
		const payable = orderData.value?.payable_total;
		if (typeof payable === 'string' && payable.trim()) return payable.replace(/\s*EUR$/i, 'EUR');
		return formatPrice(orderData.value?.payable_total_cents ?? orderData.value?.subtotal_cents ?? 0);
	});
	const orderRouteLabel = computed(() => {
		const pkg = orderData.value?.packages?.[0];
		return pkg ? `${formatLocation(pkg.origin_address)} -> ${formatLocation(pkg.destination_address)}` : '-';
	});
	const orderPackageCountLabel = computed(() => {
		const count = Number(orderData.value?.packages?.length || 0);
		return !count ? 'Nessun collo' : count === 1 ? '1 collo' : `${count} colli`;
	});
	const isPendingPayment = computed(() => ['pending', 'payment_failed'].includes(String(orderData.value?.raw_status)));
	const isCancellable = computed(() => orderData.value?.cancellable === true);
	const isCancelledOrRefunded = computed(() => ['cancelled', 'refunded'].includes(String(orderData.value?.raw_status)));

	const showAddPackageForm = ref(false);
	const addingPackage = ref(false);
	const addPackageError = ref<string | null>(null);
	const addPackageSuccess = ref(false);
	const newPackage = ref({ ...EMPTY_PACKAGE });

	const submitAddPackage = async () => {
		addPackageError.value = null;
		addPackageSuccess.value = false;
		addingPackage.value = true;
		try {
			await sanctum(`/api/orders/${orderId}/add-package`, { method: 'POST', body: newPackage.value });
			addPackageSuccess.value = true;
			showAddPackageForm.value = false;
			newPackage.value = { ...EMPTY_PACKAGE };
			await refresh();
		} catch (error) {
			const data = extractApiError(error);
			addPackageError.value = data.error || data.message || "Errore durante l'aggiunta del collo.";
		} finally {
			addingPackage.value = false;
		}
	};

	const regenerating = ref(false);
	const regenerateError = ref<string | null>(null);
	const regenerateSuccess = ref(false);
	const downloadError = ref<string | null>(null);

	const downloadLabel = async () => {
		if (!orderData.value?.id) return;
		downloadError.value = null;
		try {
			const blob = await sanctum<Blob>(`/api/brt/label/${orderData.value.id}`, { method: 'GET', responseType: 'blob' });
			downloadFile(blob, `etichetta-brt-${orderData.value.id}.pdf`);
		} catch (error) {
			const data = extractApiError(error);
			downloadError.value = data.error || data.message || 'Download etichetta non riuscito. Riprova.';
		}
	};

	const regenerateLabel = async () => {
		if (!orderData.value?.id) return;
		regenerating.value = true;
		regenerateError.value = null;
		regenerateSuccess.value = false;
		try {
			await sanctum('/api/brt/create-shipment', { method: 'POST', body: { order_id: orderData.value.id } });
			regenerateSuccess.value = true;
			await refresh();
		} catch (error) {
			regenerateError.value = extractApiError(error).error || "Errore durante la rigenerazione dell'etichetta.";
		} finally {
			regenerating.value = false;
		}
	};

	const showCancelModal = ref(false);
	const refundEligibility = ref<unknown>(null);
	const loadingEligibility = ref(false);
	const cancelling = ref(false);
	const cancelError = ref<string | null>(null);
	const cancelSuccess = ref<string | null>(null);
	const cancelReason = ref('');

	const openCancelModal = async () => {
		cancelError.value = null;
		cancelSuccess.value = null;
		loadingEligibility.value = true;
		showCancelModal.value = true;
		try {
			refundEligibility.value = await sanctum(`/api/orders/${orderId}/refund-eligibility`, { method: 'GET' });
		} catch (error) {
			cancelError.value = extractApiError(error).error || "Errore nel controllo dell'idoneita' al rimborso.";
		} finally {
			loadingEligibility.value = false;
		}
	};

	const confirmCancellation = async () => {
		cancelling.value = true;
		cancelError.value = null;
		try {
			const result = await sanctum<ApiMessageResponse>(`/api/orders/${orderId}/cancel`, { method: 'POST', body: { reason: cancelReason.value || undefined } });
			cancelSuccess.value = result.message || 'Ordine annullato con successo.';
			showCancelModal.value = false;
			cancelReason.value = '';
			await refresh();
		} catch (error) {
			const data = extractApiError(error);
			cancelError.value = data.error || data.message || "Errore durante l'annullamento dell'ordine.";
		} finally {
			cancelling.value = false;
		}
	};

	const pickupBusy = ref(false);
	const borderoBusy = ref(false);
	const documentsBusy = ref(false);
	const downloadBorderoBusy = ref(false);
	const executionError = ref<string | null>(null);
	const executionSuccess = ref<string | null>(null);

	const refreshOrderExecutionState = async () => {
		await Promise.allSettled([refresh(), refreshExecution()]);
	};

	const runExecutionAction = async ({ endpoint, busyRef, successMessage, body }: ExecutionActionPayload) => {
		busyRef.value = true;
		executionError.value = null;
		executionSuccess.value = null;
		try {
			const result = await sanctum<ApiMessageResponse>(endpoint, { method: 'POST', body });
			executionSuccess.value = result.message || successMessage;
			await refreshOrderExecutionState();
		} catch (error) {
			const data = extractApiError(error);
			executionError.value = data.message || data.error || "Errore durante l'aggiornamento operativo della spedizione.";
		} finally {
			busyRef.value = false;
		}
	};

	const requestPickup = (pickupRequest: unknown = null) =>
		runExecutionAction({
			endpoint: `/api/orders/${orderId}/pickup`,
			busyRef: pickupBusy,
			successMessage: 'Richiesta ritiro elaborata.',
			body: pickupRequest ? { pickup_request: pickupRequest } : undefined,
		});
	const createBordero = () =>
		runExecutionAction({ endpoint: `/api/orders/${orderId}/bordero`, busyRef: borderoBusy, successMessage: 'Bordero generato.' });
	const sendDocuments = () =>
		runExecutionAction({ endpoint: `/api/orders/${orderId}/send-documents`, busyRef: documentsBusy, successMessage: 'Documenti inviati.' });

	const downloadBordero = async () => {
		if (!orderData.value?.id) return;
		downloadBorderoBusy.value = true;
		executionError.value = null;
		executionSuccess.value = null;
		try {
			const blob = await sanctum<Blob>(`/api/orders/${orderId}/bordero/download`, { method: 'GET', responseType: 'blob' });
			downloadFile(blob, executionData.value?.bordero_document_filename || `bordero-${orderId}.pdf`);
			executionSuccess.value = 'Bordero scaricato.';
		} catch (error) {
			const data = extractApiError(error);
			executionError.value = data.message || data.error || 'Bordero non disponibile per il download.';
		} finally {
			downloadBorderoBusy.value = false;
		}
	};

	const openBordero = () => {
		if (!orderData.value?.id || typeof window === 'undefined') return;
		executionError.value = null;
		executionSuccess.value = null;
		const previewWindow = window.open(`/api/orders/${orderId}/bordero/download?inline=1`, '_blank', 'noopener');
		if (!previewWindow) {
			executionError.value = "Il browser ha bloccato l'apertura del bordero. Usa il download diretto.";
			return;
		}
		executionSuccess.value = 'Bordero aperto in una nuova scheda.';
	};

	return {
		order, orderStatus, orderData, refresh,
		execution, executionStatus, executionData, refreshExecution,
		orderSubtotalLabel, orderRouteLabel, orderPackageCountLabel,
		isPendingPayment, isCancellable, isCancelledOrRefunded,
		formatDate: (dateStr: string | null | undefined) => formatDateTimeIt(dateStr, '-'),
		statusColor, formatPrice, paymentMethodLabel,
		showAddPackageForm, addingPackage, addPackageError, addPackageSuccess, newPackage, submitAddPackage,
		regenerating, regenerateError, regenerateSuccess, downloadError, downloadLabel, regenerateLabel,
		showCancelModal, refundEligibility, loadingEligibility, cancelling, cancelError, cancelSuccess, cancelReason,
		openCancelModal, confirmCancellation,
		pickupBusy, borderoBusy, documentsBusy, downloadBorderoBusy, executionError, executionSuccess,
		requestPickup, createBordero, sendDocuments, downloadBordero, openBordero,
	};
}
