<!-- Alla conferma bonifico l'ordine passa a completed e l'evento OrderPaid scatena la generazione etichetta BRT automatica. -->
<script setup>
import { formatDateTimeIt } from '~/utils/date.js';

definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Admin - Bonifici in attesa',
	robots: 'noindex, nofollow',
});

const sanctum = useSanctumClient();

const orders = ref([]);
const loading = ref(true);
const loadError = ref('');
const feedback = ref('');
const feedbackType = ref('success');

const selected = ref(null);
const confirming = ref(false);

const fetchPending = async () => {
	loading.value = true;
	loadError.value = '';
	try {
		const res = await sanctum('/api/admin/orders/awaiting-bank-transfer', { method: 'GET' });
		const list = Array.isArray(res?.data) ? res.data : Array.isArray(res) ? res : [];
		orders.value = list;
	} catch (error) {
		loadError.value = error?.response?._data?.message || error?.data?.message || 'Impossibile caricare gli ordini in attesa di bonifico.';
	} finally {
		loading.value = false;
	}
};

onMounted(fetchPending);

const formatDate = (value) => formatDateTimeIt(value, '—');

const formatAmount = (cents) => {
	if (cents === null || cents === undefined) return '—';
	const value = Number(cents);
	if (Number.isNaN(value)) return '—';
	return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(value / 100);
};

const openConfirm = (order) => {
	selected.value = order;
	feedback.value = '';
};

const closeConfirm = () => {
	if (confirming.value) return;
	selected.value = null;
};

const confirmWithReference = async (referenceValue) => {
	if (!selected.value || confirming.value) return;
	confirming.value = true;
	feedback.value = '';
	try {
		await sanctum(`/api/admin/orders/${selected.value.id}/confirm-bank-transfer`, {
			method: 'POST',
			body: { bank_transfer_reference: referenceValue || null },
		});
		feedback.value = `Bonifico confermato per ordine #${selected.value.id}. Etichetta BRT in generazione.`;
		feedbackType.value = 'success';
		selected.value = null;
		await fetchPending();
	} catch (error) {
		feedback.value = error?.response?._data?.message || error?.data?.message || 'Impossibile confermare il bonifico.';
		feedbackType.value = 'error';
	} finally {
		confirming.value = false;
	}
};

const summaryItems = computed(() => {
	const count = orders.value.length;
	const totalCents = orders.value.reduce((sum, o) => sum + Number(o.payable_total_cents ?? o.subtotal_cents ?? 0), 0);
	const oldestDays = orders.value.reduce((max, o) => {
		if (!o.created_at) return max;
		const days = Math.floor((Date.now() - new Date(o.created_at).getTime()) / (1000 * 60 * 60 * 24));
		return days > max ? days : max;
	}, 0);
	return [
		{ key: 'count', label: 'Bonifici pendenti', value: String(count), meta: count ? 'Da verificare in banca' : 'Tutto in pari' },
		{ key: 'amount', label: 'Importo totale', value: formatAmount(totalCents), meta: 'Somma ordini in attesa' },
		{ key: 'oldest', label: 'Piu datato', value: oldestDays ? `${oldestDays} g` : '—', meta: oldestDays > 3 ? 'Controllare subito' : 'Nessun ritardo' },
	];
});
</script>

<template>
	<AccountPageSection spacing="space-y-6 md:space-y-8">
		<AccountPageHeader
				eyebrow="Area amministrazione"
				title="Bonifici in attesa"
				description="Verifica in banca i bonifici pendenti e conferma la ricezione per far partire la spedizione."
				back-to="/account/amministrazione"
				back-label="Torna al pannello admin"
				:crumbs="[{ label: 'Account', to: '/account' }, { label: 'Amministrazione', to: '/account/amministrazione' }, { label: 'Bonifici' }]" />

			<SfAlert v-if="loadError || feedback" :tone="(loadError || feedbackType === 'error') ? 'danger' : 'success'">
				{{ loadError || feedback }}
			</SfAlert>

			<div class="grid grid-cols-1 tablet:grid-cols-3 gap-3">
				<SfStatCard
					v-for="item in summaryItems"
					:key="item.key"
					:label="item.label"
					:value="item.value"
					icon="mdi:bank-transfer"
					tone="primary"
					:trend-label="item.meta" />
			</div>

			<SfCard padding="md">
				<template #header>
					<div class="flex flex-col tablet:flex-row tablet:items-center tablet:justify-between gap-3">
						<div>
							<p class="text-[0.7rem] font-semibold uppercase tracking-wider text-brand-primary">Coda bonifici</p>
							<h2 class="font-display text-xl font-bold text-brand-text mt-1">In attesa di ricezione</h2>
							<p class="text-sm text-brand-text-secondary mt-1">
								La causale del cliente è sempre <strong>ORD-{ID}</strong>. Verifica in banca e conferma per sbloccare la spedizione.
							</p>
						</div>
						<SfButton variant="secondary" size="sm" @click="fetchPending">
							<template #leading><UIcon name="mdi:refresh" class="w-4 h-4" /></template>
							Aggiorna
						</SfButton>
					</div>
				</template>

				<div v-if="loading" class="space-y-2.5">
					<SfSkeleton v-for="n in 3" :key="n" height="64px" />
				</div>

				<SfEmptyState
					v-else-if="!orders.length"
					icon="mdi:check-circle"
					title="Nessun bonifico in attesa"
					description="Tutti i pagamenti via bonifico sono stati riconciliati." />

				<div v-else class="space-y-2.5">
					<article
						v-for="order in orders"
						:key="order.id"
						class="overflow-hidden rounded-card border border-brand-border bg-brand-card transition hover:bg-brand-bg-alt">
						<div class="px-3.5 py-3 tablet:px-4 tablet:py-3.5">
							<div class="flex flex-col gap-2.5 tablet:flex-row tablet:items-center tablet:justify-between">
								<div class="min-w-0 flex-1">
									<div class="flex flex-wrap items-center gap-2">
										<span class="font-mono text-sm font-bold text-brand-text">#{{ order.id }}</span>
										<span class="text-sm font-bold text-brand-primary">
											{{ formatAmount(order.payable_total_cents ?? order.subtotal_cents ?? (order.subtotal?.amount ? Number(order.subtotal.amount) * 100 : null)) }}
										</span>
										<span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[0.6875rem] font-bold text-amber-700">
											In attesa bonifico
										</span>
									</div>
									<p class="mt-1 text-sm font-semibold text-brand-text">
										<template v-if="order.user">{{ order.user.name }} {{ order.user.surname }}</template>
										<template v-else>—</template>
									</p>
									<div class="mt-0.5 flex flex-wrap items-center gap-x-2 text-[0.6875rem] text-brand-text-muted">
										<span class="font-mono">Causale: ORD-{{ order.id }}</span>
										<span aria-hidden="true">·</span>
										<span>{{ formatDate(order.created_at) }}</span>
									</div>
								</div>
								<div class="shrink-0 flex flex-wrap gap-1.5">
									<SfButton variant="secondary" size="sm" :to="`/account/amministrazione/ordini?search=${order.id}`">
										Apri
									</SfButton>
									<SfButton size="sm" @click="openConfirm(order)">
										Conferma
									</SfButton>
								</div>
							</div>
						</div>
					</article>
				</div>
			</SfCard>
		<AdminBankTransferConfirmModal
			:order="selected"
			:confirming="confirming"
			:format-amount="formatAmount"
			@close="closeConfirm"
			@confirm="(ref) => confirmWithReference(ref)" />
	</AccountPageSection>
</template>
