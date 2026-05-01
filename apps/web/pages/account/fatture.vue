<script setup>
import { formatPriceSafe as formatPrice } from '~/utils/price.js';
import { formatDateIt } from '~/utils/date.js';

definePageMeta({ middleware: ['app-auth'] });

useSeoMeta({
	title: 'Le tue fatture',
	description: 'Storico fatture emesse per i tuoi ordini. Scarica il PDF e controlla lo stato SDI.',
	robots: 'noindex, nofollow',
});

const sanctum = useSanctumClient();

const loading = ref(true);
const loadError = ref(null);
const orders = ref([]);

const loadInvoices = async () => {
	loading.value = true;
	loadError.value = null;
	try {
		const res = await sanctum('/api/orders', { query: { per_page: 100 } });
		const raw = res?.data || res || [];
		const arr = Array.isArray(raw) ? raw : (raw.data || []);
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

const orderCode = (id) => `SF-${String(id).padStart(6, '0')}`;
const formatDate = (raw) => formatDateIt(raw, '—');

const sdiBadge = (order) => {
	const s = String(order.sdi_status || '').toLowerCase();
	if (s === 'sent' || s === 'accepted') return { label: 'Trasmessa', tone: 'success' };
	if (s === 'rejected' || s === 'error') return { label: 'Rifiutata', tone: 'error' };
	if (s === 'pending' || s === 'queued') return { label: 'In coda', tone: 'warning' };
	return { label: 'N/D', tone: 'neutral' };
};

const sdiBadgeClass = (tone) => {
	if (tone === 'success') return 'bg-brand-success-bg text-brand-success-fg border-brand-success/30';
	if (tone === 'error') return 'bg-status-failed-bg text-status-failed-fg border-status-failed-fg/30';
	if (tone === 'warning') return 'bg-status-pending-bg text-status-pending-fg border-status-pending-fg/30';
	return 'bg-brand-bg-alt text-brand-text-secondary border-brand-border';
};

const empty = computed(() => !loading.value && !loadError.value && orders.value.length === 0);
</script>

<template>
	<section class="w-full min-h-[600px] py-5 tablet:py-6 desktop:py-7">
		<div class="my-container max-w-7xl">
			<AccountPageHeader
				eyebrow="Fatture"
				title="Le tue fatture"
				description="Storico delle fatture emesse per i tuoi ordini, con stato SDI e download PDF."
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Fatture' },
				]" />

			<div v-if="loading" class="mt-[18px] flex flex-col gap-2.5" aria-busy="true">
				<div v-for="n in 4" :key="n" class="h-[62px] animate-pulse rounded-card bg-gradient-to-r from-brand-bg-alt via-brand-border to-brand-bg-alt" />
			</div>

			<div v-else-if="loadError" class="mt-[18px] flex flex-col items-center gap-3 rounded-card border border-status-failed-fg/30 bg-status-failed-bg p-8 text-center text-status-failed-fg" role="alert">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="12" cy="12" r="10" />
					<line x1="12" y1="8" x2="12" y2="12" />
					<line x1="12" y1="16" x2="12.01" y2="16" />
				</svg>
				<p>{{ loadError }}</p>
				<SfButton variant="secondary" @click="loadInvoices">Riprova</SfButton>
			</div>

			<div v-else-if="empty" class="mx-auto mt-[18px] max-w-[560px] rounded-card border border-brand-border bg-brand-card p-12 text-center">
				<div class="mb-3.5 inline-flex rounded-full bg-brand-primary/10 p-3.5 text-brand-primary" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
						<polyline points="14 2 14 8 20 8" />
						<line x1="16" y1="13" x2="8" y2="13" />
						<line x1="16" y1="17" x2="8" y2="17" />
						<polyline points="10 9 9 9 8 9" />
					</svg>
				</div>
				<h2 class="mb-2 font-display text-[1.375rem] font-extrabold text-brand-primary">Nessuna fattura ancora</h2>
				<p class="mb-5 text-[0.9375rem] leading-relaxed text-brand-text-secondary">Le fatture vengono emesse automaticamente dopo il pagamento di un ordine. Effettua la tua prima spedizione per vederle qui.</p>
				<SfButton to="/preventivo" variant="primary">Calcola un preventivo</SfButton>
			</div>

			<div v-else class="mt-[18px]">
				<p v-if="downloadError" role="alert" class="mb-3 rounded-[10px] border border-status-failed-fg/30 bg-status-failed-bg px-3 py-2.5 text-sm font-semibold text-status-failed-fg">{{ downloadError }}</p>

				<div class="hidden overflow-hidden rounded-card border border-brand-border bg-brand-card md:block">
					<table class="w-full border-collapse text-sm">
						<thead class="bg-surface-raised">
							<tr>
								<th scope="col" class="border-b border-brand-border px-[18px] py-3.5 text-left text-[0.6875rem] font-bold uppercase tracking-[0.12em] text-brand-text-muted">Numero</th>
								<th scope="col" class="border-b border-brand-border px-[18px] py-3.5 text-left text-[0.6875rem] font-bold uppercase tracking-[0.12em] text-brand-text-muted">Data emissione</th>
								<th scope="col" class="border-b border-brand-border px-[18px] py-3.5 text-left text-[0.6875rem] font-bold uppercase tracking-[0.12em] text-brand-text-muted">Importo</th>
								<th scope="col" class="border-b border-brand-border px-[18px] py-3.5 text-left text-[0.6875rem] font-bold uppercase tracking-[0.12em] text-brand-text-muted">Stato SDI</th>
								<th scope="col" class="border-b border-brand-border px-[18px] py-3.5 text-right text-[0.6875rem] font-bold uppercase tracking-[0.12em] text-brand-text-muted">Azioni</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="o in orders" :key="o.id" class="hover:bg-surface-raised">
								<td class="border-b border-brand-border/60 px-[18px] py-3.5 align-middle text-brand-text last:border-0">
									<span class="rounded-md bg-brand-primary/10 px-2 py-[3px] font-mono text-[0.8125rem] font-bold tracking-wide text-brand-primary">{{ orderCode(o.id) }}</span>
								</td>
								<td class="border-b border-brand-border/60 px-[18px] py-3.5 align-middle text-brand-text last:border-0">{{ formatDate(o.created_at) }}</td>
								<td class="border-b border-brand-border/60 px-[18px] py-3.5 align-middle font-bold text-brand-primary last:border-0">{{ formatPrice(o.payable_total_cents ?? o.subtotal_cents ?? 0) }}</td>
								<td class="border-b border-brand-border/60 px-[18px] py-3.5 align-middle last:border-0">
									<span :class="['inline-flex rounded-full border px-2.5 py-1 text-[0.6875rem] font-bold uppercase tracking-wider', sdiBadgeClass(sdiBadge(o).tone)]">{{ sdiBadge(o).label }}</span>
								</td>
								<td class="border-b border-brand-border/60 px-[18px] py-3.5 text-right align-middle last:border-0">
									<SfButton
										variant="secondary"
										size="sm"
										class="text-xs"
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

				<div class="flex flex-col gap-3 md:hidden">
					<article v-for="o in orders" :key="`m-${o.id}`" class="rounded-card border border-brand-border bg-brand-card p-4">
						<header class="mb-3 flex items-center justify-between gap-2.5">
							<span class="rounded-md bg-brand-primary/10 px-2 py-[3px] font-mono text-[0.8125rem] font-bold tracking-wide text-brand-primary">{{ orderCode(o.id) }}</span>
							<span :class="['inline-flex rounded-full border px-2.5 py-1 text-[0.6875rem] font-bold uppercase tracking-wider', sdiBadgeClass(sdiBadge(o).tone)]">{{ sdiBadge(o).label }}</span>
						</header>
						<dl class="m-0 mb-3.5 grid grid-cols-2 gap-2.5">
							<div class="flex flex-col gap-0.5">
								<dt class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">Emessa il</dt>
								<dd class="m-0 text-sm font-semibold text-brand-text">{{ formatDate(o.created_at) }}</dd>
							</div>
							<div class="flex flex-col gap-0.5">
								<dt class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">Importo</dt>
								<dd class="m-0 text-sm font-bold text-brand-primary">{{ formatPrice(o.payable_total_cents ?? o.subtotal_cents ?? 0) }}</dd>
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
