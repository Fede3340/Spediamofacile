<!-- PAGINA: /account/fatture — storico fatture PDF per l'utente loggato -->
<script setup>
import '~/assets/css/account.css';
import '~/assets/css/account.css';
import { formatPriceSafe as formatPrice } from '~/utils/price.js';
import { formatDateIt } from '~/utils/date.js';

definePageMeta({ middleware: ['app-auth'] });

useSeoMeta({
	title: 'Le tue fatture',
	description: 'Storico fatture emesse per i tuoi ordini. Scarica il PDF e controlla lo stato SDI.',
	robots: 'noindex, nofollow',
});

const sanctum = useSanctumClient();

// --- Stato lista ---
const loading = ref(true);
const loadError = ref(null);
const orders = ref([]);

// L'endpoint dedicato /api/invoices non esiste lato backend: riusiamo /api/orders
// filtrando su stati con fattura emessa (completed, shipped, in_transit, delivered).
// La fattura PDF e' servita da /api/orders/{id}/invoice.pdf (M10 InvoicePdfController).
const loadInvoices = async () => {
	loading.value = true;
	loadError.value = null;
	try {
		const res = await sanctum('/api/orders', { query: { per_page: 100 } });
		const raw = res?.data || res || [];
		const arr = Array.isArray(raw) ? raw : (raw.data || []);
		// Tengo solo ordini pagati (hanno fattura)
		const paidStatuses = new Set(['paid', 'completed', 'shipped', 'in_transit', 'delivered', 'succeeded']);
		orders.value = arr.filter((o) => {
			const status = String(o.raw_status || o.status || '').toLowerCase();
			return paidStatuses.has(status) || Number(o.subtotal_cents) > 0;
		});
	} catch (err) {
		loadError.value = err?.data?.message || err?.message || 'Errore caricamento fatture.';
	} finally {
		loading.value = false;
	}
};

onMounted(() => {
	loadInvoices();
});

// --- Download PDF singola fattura ---
const downloadingId = ref(null);
const downloadError = ref(null);

const downloadInvoice = async (order) => {
	if (!order?.id) return;
	downloadingId.value = order.id;
	downloadError.value = null;
	try {
		const blob = await sanctum(`/api/orders/${order.id}/invoice.pdf`, { responseType: 'blob' });
		const url = URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = `fattura-SF-${String(order.id).padStart(6, '0')}.pdf`;
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	} catch (err) {
		downloadError.value = err?.data?.message || err?.message || 'Download fattura non riuscito.';
	} finally {
		downloadingId.value = null;
	}
};

// --- Helpers format ---
const orderCode = (id) => `SF-${String(id).padStart(6, '0')}`;

// formatDateIt gestisce sia string ISO che string italiano "DD/MM/YYYY HH:mm"
const formatDate = (raw) => formatDateIt(raw, '—');

// Stato SDI: inferito da campi backend sdi_status / sdi_sent_at. Fallback: "n/d" se mancanti.
const sdiBadge = (order) => {
	const s = String(order.sdi_status || '').toLowerCase();
	if (s === 'sent' || s === 'accepted') return { label: 'Trasmessa', tone: 'success' };
	if (s === 'rejected' || s === 'error') return { label: 'Rifiutata', tone: 'error' };
	if (s === 'pending' || s === 'queued') return { label: 'In coda', tone: 'warning' };
	return { label: 'N/D', tone: 'neutral' };
};

const empty = computed(() => !loading.value && !loadError.value && orders.value.length === 0);
</script>

<template>
	<section class="sf-account-shell sf-fatture-page min-h-[600px] py-[20px] tablet:py-[24px] desktop:py-[28px]">
		<div class="my-container max-w-[1280px]">
			<AccountPageHeader
				eyebrow="Fatture"
				title="Le tue fatture"
				description="Storico delle fatture emesse per i tuoi ordini, con stato SDI e download PDF."
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Fatture' },
				]" />

			<!-- LOADING -->
			<div v-if="loading" class="sf-fatture__skeleton" aria-busy="true">
				<div v-for="n in 4" :key="n" class="sf-fatture__skel-row"></div>
			</div>

			<!-- ERRORE -->
			<div v-else-if="loadError" class="sf-fatture__error" role="alert">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="12" cy="12" r="10" />
					<line x1="12" y1="8" x2="12" y2="12" />
					<line x1="12" y1="16" x2="12.01" y2="16" />
				</svg>
				<p>{{ loadError }}</p>
				<SfButton variant="secondary" @click="loadInvoices">Riprova</SfButton>
			</div>

			<!-- EMPTY -->
			<div v-else-if="empty" class="sf-fatture__empty">
				<div class="sf-fatture__empty-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
						<polyline points="14 2 14 8 20 8" />
						<line x1="16" y1="13" x2="8" y2="13" />
						<line x1="16" y1="17" x2="8" y2="17" />
						<polyline points="10 9 9 9 8 9" />
					</svg>
				</div>
				<h2 class="sf-fatture__empty-title">Nessuna fattura ancora</h2>
				<p class="sf-fatture__empty-text">Le fatture vengono emesse automaticamente dopo il pagamento di un ordine. Effettua la tua prima spedizione per vederle qui.</p>
				<SfButton to="/preventivo" variant="primary">Calcola un preventivo</SfButton>
			</div>

			<!-- LISTA -->
			<div v-else class="sf-fatture__list">
				<p v-if="downloadError" role="alert" class="sf-fatture__download-error">{{ downloadError }}</p>

				<!-- Tabella desktop -->
				<div class="sf-fatture__table-wrap">
					<table class="sf-fatture__table">
						<thead>
							<tr>
								<th scope="col">Numero</th>
								<th scope="col">Data emissione</th>
								<th scope="col">Importo</th>
								<th scope="col">Stato SDI</th>
								<th scope="col" class="sf-fatture__th-actions">Azioni</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="o in orders" :key="o.id">
								<td>
									<span class="sf-fatture__code">{{ orderCode(o.id) }}</span>
								</td>
								<td>{{ formatDate(o.created_at) }}</td>
								<td class="sf-fatture__amount">{{ formatPrice(o.payable_total_cents ?? o.subtotal_cents ?? 0) }}</td>
								<td>
									<span class="sf-fatture__status" :data-tone="sdiBadge(o).tone">{{ sdiBadge(o).label }}</span>
								</td>
								<td class="sf-fatture__actions-cell">
									<SfButton
										variant="secondary"
										size="sm"
										class="text-[0.75rem]"
										:loading="downloadingId === o.id"
										@click="downloadInvoice(o)">
										<svg v-if="downloadingId !== o.id" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
											<polyline points="7 10 12 15 17 10" />
											<line x1="12" y1="15" x2="12" y2="3" />
										</svg>
										<span v-if="downloadingId === o.id">Invio...</span>
										<span v-else>Scarica PDF</span>
									</SfButton>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<!-- Card mobile -->
				<div class="sf-fatture__cards">
					<article v-for="o in orders" :key="`m-${o.id}`" class="sf-fatture__card">
						<header class="sf-fatture__card-head">
							<span class="sf-fatture__code">{{ orderCode(o.id) }}</span>
							<span class="sf-fatture__status" :data-tone="sdiBadge(o).tone">{{ sdiBadge(o).label }}</span>
						</header>
						<dl class="sf-fatture__card-defs">
							<div>
								<dt>Emessa il</dt>
								<dd>{{ formatDate(o.created_at) }}</dd>
							</div>
							<div>
								<dt>Importo</dt>
								<dd class="sf-fatture__amount">{{ formatPrice(o.payable_total_cents ?? o.subtotal_cents ?? 0) }}</dd>
							</div>
						</dl>
						<SfButton
							variant="primary"
							block
							:loading="downloadingId === o.id"
							loading-text="Invio in corso..."
							@click="downloadInvoice(o)">
							<template #leading>
								<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
									<polyline points="7 10 12 15 17 10" />
									<line x1="12" y1="15" x2="12" y2="3" />
								</svg>
							</template>
							Scarica fattura PDF
						</SfButton>
					</article>
				</div>
			</div>
		</div>
	</section>
</template>
