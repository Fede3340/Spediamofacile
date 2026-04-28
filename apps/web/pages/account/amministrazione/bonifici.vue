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

// Riceve il reference dal modal AdminBankTransferConfirmModal (P5 estratto)
const confirmWithReference = async (referenceValue) => {
	if (!selected.value || confirming.value) return;
	confirming.value = true;
	feedback.value = '';
	try {
		await sanctum(`/api/admin/orders/${selected.value.id}/confirm-bank-transfer`, {
			method: 'POST',
			body: {
				bank_transfer_reference: referenceValue || null,
			},
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
	<section class="sf-account-shell min-h-[600px] py-[24px] tablet:py-[28px] desktop:py-[28px]">
		<div class="my-container">
			<AccountPageHeader
				eyebrow="Area amministrazione"
				title="Bonifici in attesa"
				description="Verifica in banca i bonifici pendenti e conferma la ricezione per far partire la spedizione."
				back-to="/account/amministrazione"
				back-label="Torna al pannello admin"
				:crumbs="[{ label: 'Account', to: '/account' }, { label: 'Amministrazione', to: '/account/amministrazione' }, { label: 'Bonifici' }]" />

			<div
				v-if="loadError || feedback"
				:class="['mb-[16px] ux-alert', (loadError || feedbackType === 'error') ? 'ux-alert--critical' : 'ux-alert--success']">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ux-alert__icon shrink-0" fill="currentColor">
					<path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
				</svg>
				<span>{{ loadError || feedback }}</span>
			</div>

			<div class="sf-account-summary-strip mb-[20px] sf-animate-in sf-animate-in-1">
				<div v-for="item in summaryItems" :key="item.key" class="sf-account-summary-item">
					<div class="sf-account-summary-item__icon">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[15px] w-[15px]" fill="currentColor" style="color: var(--color-brand-primary);">
							<path d="M5,6H23V18H5V6M14,9A3,3 0 0,1 17,12A3,3 0 0,1 14,15A3,3 0 0,1 11,12A3,3 0 0,1 14,9M9,8A2,2 0 0,1 7,10V14A2,2 0 0,1 9,16H19A2,2 0 0,1 21,14V10A2,2 0 0,1 19,8H9M1,10H3V20H19V22H1V10Z" />
						</svg>
					</div>
					<div class="sf-account-summary-item__body">
						<span class="sf-account-summary-item__value">{{ item.value }}</span>
						<span class="sf-account-summary-item__label">{{ item.label }}</span>
						<span class="sf-account-summary-item__meta">{{ item.meta }}</span>
					</div>
				</div>
			</div>

			<section class="sf-account-section sf-account-panel sf-animate-in sf-animate-in-2">
				<div class="sf-account-section__header">
					<div class="sf-account-section__title-wrap">
						<p class="text-[0.7rem] font-semibold uppercase tracking-[1px] text-[var(--color-brand-primary)]">Coda bonifici</p>
						<h2 class="sf-account-section__title">In attesa di ricezione</h2>
						<p class="sf-account-section__description">
							La causale del cliente è sempre <strong>ORD-{ID}</strong>. Verifica in banca e conferma per sbloccare la spedizione.
						</p>
					</div>
					<SfButton variant="secondary" size="sm" @click="fetchPending">
						<template #leading>
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[16px] w-[16px]" fill="currentColor">
								<path d="M17.65,6.35C16.2,4.9 14.21,4 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20C15.73,20 18.84,17.45 19.73,14H17.65C16.83,16.33 14.57,18 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6C13.66,6 15.14,6.69 16.22,7.78L13,11H20V4L17.65,6.35Z" />
							</svg>
						</template>
						Aggiorna
					</SfButton>
				</div>

				<div class="sf-account-section__body">
					<div v-if="loading" class="rounded-[16px] bg-[#F5F6F9] px-[18px] py-[20px] text-center">
						<p class="text-[0.9375rem] font-semibold text-[var(--color-brand-text)]">Caricamento bonifici pendenti...</p>
					</div>

					<div v-else-if="!orders.length" class="rounded-[16px] bg-[#F5F6F9] px-[18px] py-[22px] text-center">
						<div class="mx-auto mb-[12px] flex h-[46px] w-[46px] items-center justify-center rounded-full bg-[#E9F7EC] text-[#1F7A3A]">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[24px] w-[24px]" fill="currentColor">
								<path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M10,17L5,12L6.41,10.59L10,14.17L17.59,6.58L19,8L10,17Z" />
							</svg>
						</div>
						<p class="text-[0.9375rem] font-semibold text-[var(--color-brand-text)]">Nessun bonifico in attesa</p>
						<p class="mx-auto mt-[6px] max-w-[520px] text-[0.8125rem] leading-[1.6] text-[var(--color-brand-text-secondary)]">
							Tutti i pagamenti via bonifico sono stati riconciliati.
						</p>
					</div>

					<!-- P14: card lista coerente con pattern /account/spedizioni (sf-order-card)
					     stesso radius/border/padding/spacing per uniformità sitewide. -->
					<div v-else class="space-y-[10px]">
						<article
							v-for="order in orders"
							:key="order.id"
							class="overflow-hidden rounded-[12px] border border-[#E2E8EE] bg-white transition-colors duration-150 hover:bg-[#FBFCFD]">
							<div class="px-[14px] py-[12px] tablet:px-[16px] tablet:py-[14px]">
								<div class="flex flex-col gap-[10px] tablet:flex-row tablet:items-center tablet:justify-between">
									<div class="min-w-0 flex-1">
										<!-- Riga 1: #ID + prezzo + status -->
										<div class="flex flex-wrap items-center gap-[8px]">
											<span class="font-mono text-[0.875rem] font-bold text-[var(--color-brand-text)]">#{{ order.id }}</span>
											<span class="text-[0.875rem] font-bold text-[var(--color-brand-primary)]">
												{{ formatAmount(order.payable_total_cents ?? order.subtotal_cents ?? (order.subtotal?.amount ? Number(order.subtotal.amount) * 100 : null)) }}
											</span>
											<span class="inline-flex items-center rounded-full bg-[#FFF1E8] px-[8px] py-[2px] text-[0.6875rem] font-[700] text-[#B45309]">
												In attesa bonifico
											</span>
										</div>
										<!-- Riga 2: cliente -->
										<p class="mt-[4px] text-[0.8125rem] font-semibold text-[var(--color-brand-text)]">
											<template v-if="order.user">{{ order.user.name }} {{ order.user.surname }}</template>
											<template v-else>—</template>
										</p>
										<!-- Riga 3: meta -->
										<div class="mt-[2px] flex flex-wrap items-center gap-x-[8px] text-[0.6875rem] text-[var(--color-brand-text-muted)]">
											<span class="font-mono">Causale: ORD-{{ order.id }}</span>
											<span aria-hidden="true">·</span>
											<span>{{ formatDate(order.created_at) }}</span>
										</div>
									</div>
									<!-- Bottoni inline -->
									<div class="shrink-0 flex flex-wrap gap-[6px]">
										<SfButton variant="secondary" size="sm" class="text-[0.75rem]" :to="`/account/amministrazione/ordini?search=${order.id}`">
											Apri
										</SfButton>
										<SfButton size="sm" class="text-[0.75rem]" @click="openConfirm(order)">
											Conferma
										</SfButton>
									</div>
								</div>
							</div>
						</article>
					</div>
				</div>
			</section>
		</div>

		<!-- Confirm Modal — estratto come AdminBankTransferConfirmModal (P5) -->
		<AdminBankTransferConfirmModal
			:order="selected"
			:confirming="confirming"
			:format-amount="formatAmount"
			@close="closeConfirm"
			@confirm="(ref) => confirmWithReference(ref)" />
	</section>
</template>
