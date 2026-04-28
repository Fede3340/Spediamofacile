/**
 * Composable: useOrderDetail
 * Logica completa per la pagina dettaglio ordine /account/spedizioni/[id].
 *
 * Gestisce: fetch ordine, formattazione dati, etichette BRT, annullamento/rimborso,
 * aggiunta collo, label download/rigenerazione.
 */
import { formatDateTimeIt } from '~/utils/date.js';

export default function useOrderDetail(orderId) {
	const sanctum = useSanctumClient();

	/* --- Fetch ordine --- */
	const { data: order, status: orderStatus, refresh } = useSanctumFetch(`/api/orders/${orderId}`, { lazy: true });
	const {
		data: execution,
		status: executionStatus,
		refresh: refreshExecution,
	} = useSanctumFetch(`/api/orders/${orderId}/execution`, { lazy: true });

	/* --- Helpers di formattazione --- */
	const formatDate = (dateStr) => formatDateTimeIt(dateStr, '—');

	const statusColor = (status) => {
		const map = {
			'In attesa': 'bg-yellow-100 text-yellow-700',
			'In lavorazione': 'bg-[#eef8fa] text-[#095866]',
			Completato: 'bg-[#f0fdf4] text-[#0a8a7a]',
			Fallito: 'bg-red-100 text-red-700',
			Pagato: 'bg-[#f0fdf4] text-[#0a8a7a]',
			Annullato: 'bg-gray-200 text-gray-600',
			Rimborsato: 'bg-orange-100 text-orange-700',
			'In transito': 'bg-[#eef8fa] text-[#095866]',
			Consegnato: 'bg-[#f0fdf4] text-[#0a8a7a]',
			'In giacenza': 'bg-orange-100 text-orange-700',
		};
		return map[status] || 'bg-gray-100 text-gray-700';
	};

	// formatPrice auto-importato da utils/price.js

	const paymentMethodLabel = (method) => {
		const map = { stripe: 'Carta di credito (Stripe)', wallet: 'Portafoglio', bonifico: 'Bonifico' };
		return map[method] || method || 'Non specificato';
	};

	const downloadFile = (blob, filename) => {
		const url = window.URL.createObjectURL(blob);
		const link = document.createElement('a');
		link.href = url;
		link.download = filename;
		document.body.appendChild(link);
		link.click();
		window.URL.revokeObjectURL(url);
		link.remove();
	};

	/* --- Computed derivati dall'ordine --- */
	const orderData = computed(() => order.value?.data || order.value || null);
	const executionData = computed(() => execution.value?.data || execution.value || null);

	const orderSubtotalLabel = computed(() => {
		const payable = orderData.value?.payable_total;
		if (typeof payable === 'string' && payable.trim()) return payable.replace(/\s*EUR$/i, '€');
		return formatPrice(orderData.value?.payable_total_cents ?? orderData.value?.subtotal_cents ?? 0);
	});

	const orderRouteLabel = computed(() => {
		const firstPackage = orderData.value?.packages?.[0];
		if (!firstPackage) return '—';
		const oc = firstPackage.origin_address?.city || '';
		const op = firstPackage.origin_address?.province || '';
		const dc = firstPackage.destination_address?.city || '';
		const dp = firstPackage.destination_address?.province || '';
		return `${oc}${op ? ` (${op})` : ''} → ${dc}${dp ? ` (${dp})` : ''}`;
	});

	const orderPackageCountLabel = computed(() => {
		const count = Number(orderData.value?.packages?.length || 0);
		if (!count) return 'Nessun collo';
		return count === 1 ? '1 collo' : `${count} colli`;
	});

	const isPendingPayment = computed(() => {
		const raw = orderData.value?.raw_status;
		return raw === 'pending' || raw === 'payment_failed';
	});

	const isCancellable = computed(() => orderData.value?.cancellable === true);

	const isCancelledOrRefunded = computed(() => {
		const raw = orderData.value?.raw_status;
		return raw === 'cancelled' || raw === 'refunded';
	});

	/* --- Aggiungi collo --- */
	const showAddPackageForm = ref(false);
	const addingPackage = ref(false);
	const addPackageError = ref(null);
	const addPackageSuccess = ref(false);
	const newPackage = ref({
		package_type: 'Pacco',
		quantity: 1,
		weight: '',
		first_size: '',
		second_size: '',
		third_size: '',
		content_description: '',
	});

	const submitAddPackage = async () => {
		addPackageError.value = null;
		addPackageSuccess.value = false;
		addingPackage.value = true;
		try {
			await sanctum(`/api/orders/${orderId}/add-package`, { method: 'POST', body: newPackage.value });
			addPackageSuccess.value = true;
			showAddPackageForm.value = false;
			newPackage.value = {
				package_type: 'Pacco',
				quantity: 1,
				weight: '',
				first_size: '',
				second_size: '',
				third_size: '',
				content_description: '',
			};
			await refresh();
		} catch (e) {
			const data = e?.response?._data || e?.data;
			addPackageError.value = data?.error || data?.message || "Errore durante l'aggiunta del collo.";
		} finally {
			addingPackage.value = false;
		}
	};

	/* --- BRT etichetta --- */
	const regenerating = ref(false);
	const regenerateError = ref(null);
	const regenerateSuccess = ref(false);
	/* Errore download visibile all'utente: prima un catch silenzioso lasciava il click "morto". */
	const downloadError = ref(null);

	const downloadLabel = async () => {
		if (!orderData.value?.id) return;
		downloadError.value = null;
		try {
			const blob = await sanctum(`/api/brt/label/${orderData.value.id}`, {
				method: 'GET',
				responseType: 'blob',
			});
			downloadFile(blob, `etichetta-brt-${orderData.value.id}.pdf`);
		} catch (e) {
			const data = e?.response?._data || e?.data;
			downloadError.value = data?.error || data?.message || 'Download etichetta non riuscito. Riprova.';
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
		} catch (e) {
			const data = e?.response?._data || e?.data;
			regenerateError.value = data?.error || "Errore durante la rigenerazione dell'etichetta.";
		} finally {
			regenerating.value = false;
		}
	};

	/* --- Annullamento e rimborso --- */
	const showCancelModal = ref(false);
	const refundEligibility = ref(null);
	const loadingEligibility = ref(false);
	const cancelling = ref(false);
	const cancelError = ref(null);
	const cancelSuccess = ref(null);
	const cancelReason = ref('');

	const openCancelModal = async () => {
		cancelError.value = null;
		cancelSuccess.value = null;
		loadingEligibility.value = true;
		showCancelModal.value = true;
		try {
			refundEligibility.value = await sanctum(`/api/orders/${orderId}/refund-eligibility`, { method: 'GET' });
		} catch (e) {
			const data = e?.response?._data || e?.data;
			cancelError.value = data?.error || "Errore nel controllo dell'idoneita' al rimborso.";
		} finally {
			loadingEligibility.value = false;
		}
	};

	const confirmCancellation = async () => {
		cancelling.value = true;
		cancelError.value = null;
		try {
			const result = await sanctum(`/api/orders/${orderId}/cancel`, {
				method: 'POST',
				body: { reason: cancelReason.value || undefined },
			});
			cancelSuccess.value = result?.message || 'Ordine annullato con successo.';
			showCancelModal.value = false;
			cancelReason.value = '';
			await refresh();
		} catch (e) {
			const data = e?.response?._data || e?.data;
			cancelError.value = data?.error || data?.message || "Errore durante l'annullamento dell'ordine.";
		} finally {
			cancelling.value = false;
		}
	};

	/* --- Esecuzione spedizione: pickup / bordero / documenti --- */
	const pickupBusy = ref(false);
	const borderoBusy = ref(false);
	const documentsBusy = ref(false);
	const downloadBorderoBusy = ref(false);
	const executionError = ref(null);
	const executionSuccess = ref(null);

	const refreshOrderExecutionState = async () => {
		await Promise.allSettled([refresh(), refreshExecution()]);
	};

	const runExecutionAction = async ({ endpoint, busyRef, successMessage, body = undefined }) => {
		busyRef.value = true;
		executionError.value = null;
		executionSuccess.value = null;

		try {
			const result = await sanctum(endpoint, { method: 'POST', body });
			executionSuccess.value = result?.message || successMessage;
			await refreshOrderExecutionState();
		} catch (e) {
			const data = e?.response?._data || e?.data;
			executionError.value = data?.message || data?.error || "Errore durante l'aggiornamento operativo della spedizione.";
		} finally {
			busyRef.value = false;
		}
	};

	const requestPickup = async (pickupRequest = null) => {
		await runExecutionAction({
			endpoint: `/api/orders/${orderId}/pickup`,
			busyRef: pickupBusy,
			successMessage: 'Richiesta ritiro elaborata.',
			body: pickupRequest ? { pickup_request: pickupRequest } : undefined,
		});
	};

	const createBordero = async () => {
		await runExecutionAction({
			endpoint: `/api/orders/${orderId}/bordero`,
			busyRef: borderoBusy,
			successMessage: 'Borderò generato.',
		});
	};

	const sendDocuments = async () => {
		await runExecutionAction({
			endpoint: `/api/orders/${orderId}/send-documents`,
			busyRef: documentsBusy,
			successMessage: 'Documenti inviati.',
		});
	};

	const downloadBordero = async () => {
		if (!orderData.value?.id) return;
		downloadBorderoBusy.value = true;
		executionError.value = null;
		executionSuccess.value = null;

		try {
			const blob = await sanctum(`/api/orders/${orderId}/bordero/download`, {
				method: 'GET',
				responseType: 'blob',
			});
			downloadFile(blob, executionData.value?.bordero_document_filename || `bordero-${orderId}.pdf`);
			executionSuccess.value = 'Bordero scaricato.';
		} catch (e) {
			const data = e?.response?._data || e?.data;
			executionError.value = data?.message || data?.error || 'Bordero non disponibile per il download.';
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
			executionError.value = 'Il browser ha bloccato l’apertura del bordero. Usa il download diretto.';
			return;
		}

		executionSuccess.value = 'Bordero aperto in una nuova scheda.';
	};

	return {
		// Data
		order,
		orderStatus,
		orderData,
		refresh,
		execution,
		executionStatus,
		executionData,
		refreshExecution,
		// Labels
		orderSubtotalLabel,
		orderRouteLabel,
		orderPackageCountLabel,
		// State flags
		isPendingPayment,
		isCancellable,
		isCancelledOrRefunded,
		// Formatters
		formatDate: (dateStr) => formatDateTimeIt(dateStr, '\u2014'),
		statusColor,
		formatPrice,
		paymentMethodLabel,
		// Add package
		showAddPackageForm,
		addingPackage,
		addPackageError,
		addPackageSuccess,
		newPackage,
		submitAddPackage,
		// BRT label
		regenerating,
		regenerateError,
		regenerateSuccess,
		downloadError,
		downloadLabel,
		regenerateLabel,
		// Cancellation
		showCancelModal,
		refundEligibility,
		loadingEligibility,
		cancelling,
		cancelError,
		cancelSuccess,
		cancelReason,
		openCancelModal,
		confirmCancellation,
		// Shipment execution
		pickupBusy,
		borderoBusy,
		documentsBusy,
		downloadBorderoBusy,
		executionError,
		executionSuccess,
		requestPickup,
		createBordero,
		sendDocuments,
		downloadBordero,
		openBordero,
	};
}
