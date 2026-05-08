<!-- FILE: pages/account/amministrazione/ordini/[id].vue -->
<script setup>
/**
 * Dettaglio ordine admin.
 * Carica /api/admin/orders/{id} (controller OrderManagementController@show)
 * e mostra: dati ordine, cliente, pagamento, tracking BRT.
 */
definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Admin · Dettaglio ordine',
	robots: 'noindex, nofollow',
});

const route = useRoute();
const sanctum = useSanctumClient();
const { formatCents, formatDate, orderStatusConfig } = useAdmin();

const orderId = computed(() => route.params.id);

const order = ref(null);
const pending = ref(true);
const fetchError = ref('');

const fetchOrder = async () => {
	pending.value = true;
	fetchError.value = '';
	try {
		const res = await sanctum(`/api/admin/orders/${orderId.value}`, { method: 'GET' });
		order.value = res?.data || res || null;
	} catch (e) {
		const status = e?.response?.status || e?.statusCode;
		if (status === 404) {
			order.value = null;
		} else {
			fetchError.value = e?.response?._data?.message || e?.data?.message || e?.message || 'Errore caricamento ordine.';
		}
	} finally {
		pending.value = false;
	}
};

useHead({
	title: () => (order.value ? `Ordine #${order.value.id} · Admin` : 'Dettaglio ordine · Admin'),
});

/* Computed di comodo per leggere i campi possibili (subtotal MyMoney/total_cents/numero) */
const totalCents = computed(() => {
	if (!order.value) return 0;
	if (typeof order.value.total_cents === 'number') return order.value.total_cents;
	const sub = order.value.subtotal;
	if (sub && typeof sub === 'object' && 'amount' in sub) return Number(sub.amount) || 0;
	return Number(sub) || 0;
});

const customerFullName = computed(() => {
	const u = order.value?.user;
	if (!u) return '—';
	return [u.name, u.surname].filter(Boolean).join(' ') || u.email || '—';
});

const firstPackage = computed(() => order.value?.packages?.[0] || null);

onMounted(fetchOrder);
</script>

<template>
	<AccountPageSection padding="py-6 tablet:py-7">
		<AccountPageHeader
				eyebrow="Area amministrazione"
				:title="`Ordine #${orderId}`"
				description="Dettaglio ordine cliente con pagamento, indirizzi e tracking BRT."
				back-to="/account/amministrazione/ordini"
				back-label="Torna all'elenco ordini"
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Amministrazione', to: '/account/amministrazione' },
					{ label: 'Ordini', to: '/account/amministrazione/ordini' },
					{ label: `#${orderId}` },
				]" />

			<SfAlert v-if="fetchError" tone="danger">
				{{ fetchError }}
			</SfAlert>

			<div v-if="pending" class="space-y-4">
				<SfSkeleton variant="card" />
				<SfSkeleton variant="card" />
				<SfSkeleton variant="card" />
			</div>

			<template v-else-if="order">
				<SfCard title="Dati ordine" description="Informazioni principali e stato corrente.">
					<dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
						<div>
							<dt class="text-brand-text-muted">ID ordine</dt>
							<dd class="font-semibold text-brand-text">#{{ order.id }}</dd>
						</div>
						<div>
							<dt class="text-brand-text-muted">Stato</dt>
							<dd>
								<span
									v-if="orderStatusConfig[order.status]"
									class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill text-xs font-semibold"
									:class="[orderStatusConfig[order.status].bg, orderStatusConfig[order.status].text]">
									<UIcon :name="orderStatusConfig[order.status].icon" class="w-3.5 h-3.5" />
									{{ orderStatusConfig[order.status].label }}
								</span>
								<SfStatusPill v-else :status="order.status" />
							</dd>
						</div>
						<div>
							<dt class="text-brand-text-muted">Totale</dt>
							<dd class="font-semibold text-brand-text">{{ formatCents(totalCents) }}</dd>
						</div>
						<div>
							<dt class="text-brand-text-muted">Data creazione</dt>
							<dd class="text-brand-text">{{ formatDate(order.created_at) }}</dd>
						</div>
						<div>
							<dt class="text-brand-text-muted">Metodo pagamento</dt>
							<dd class="text-brand-text">{{ order.payment_method || '—' }}</dd>
						</div>
						<div>
							<dt class="text-brand-text-muted">Contrassegno</dt>
							<dd class="text-brand-text">
								<span v-if="order.is_cod">Sì — {{ formatCents(order.cod_amount || 0) }}</span>
								<span v-else>No</span>
							</dd>
						</div>
					</dl>
				</SfCard>

				<SfCard title="Cliente" description="Dati anagrafici dell'utente che ha effettuato l'ordine.">
					<dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
						<div>
							<dt class="text-brand-text-muted">Nominativo</dt>
							<dd class="font-semibold text-brand-text">{{ customerFullName }}</dd>
						</div>
						<div>
							<dt class="text-brand-text-muted">Email</dt>
							<dd class="text-brand-text">{{ order.user?.email || '—' }}</dd>
						</div>
						<div>
							<dt class="text-brand-text-muted">Tipo utente</dt>
							<dd class="text-brand-text">{{ order.user?.user_type || '—' }}</dd>
						</div>
						<div>
							<dt class="text-brand-text-muted">Ruolo</dt>
							<dd class="text-brand-text">{{ order.user?.role || '—' }}</dd>
						</div>
					</dl>
				</SfCard>

				<SfCard title="Spedizione & tracking BRT" description="Riferimenti corriere e dati indirizzi del primo collo.">
					<dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
						<div>
							<dt class="text-brand-text-muted">Parcel ID BRT</dt>
							<dd class="text-brand-text font-mono">{{ order.brt_parcel_id || '—' }}</dd>
						</div>
						<div>
							<dt class="text-brand-text-muted">Tracking number</dt>
							<dd class="text-brand-text font-mono">{{ order.brt_tracking_number || '—' }}</dd>
						</div>
						<div class="md:col-span-2">
							<dt class="text-brand-text-muted">URL tracking</dt>
							<dd class="text-brand-text">
								<a
									v-if="order.brt_tracking_url"
									:href="order.brt_tracking_url"
									target="_blank"
									rel="noopener noreferrer"
									class="text-brand-primary hover:underline break-all">
									{{ order.brt_tracking_url }}
								</a>
								<span v-else>—</span>
							</dd>
						</div>
						<div v-if="firstPackage">
							<dt class="text-brand-text-muted">Origine</dt>
							<dd class="text-brand-text">
								{{ firstPackage.originAddress?.city || firstPackage.origin_city || '—' }}
								<span v-if="firstPackage.originAddress?.postal_code" class="text-brand-text-muted">
									({{ firstPackage.originAddress.postal_code }})
								</span>
							</dd>
						</div>
						<div v-if="firstPackage">
							<dt class="text-brand-text-muted">Destinazione</dt>
							<dd class="text-brand-text">
								{{ firstPackage.destinationAddress?.city || firstPackage.destination_city || '—' }}
								<span v-if="firstPackage.destinationAddress?.postal_code" class="text-brand-text-muted">
									({{ firstPackage.destinationAddress.postal_code }})
								</span>
							</dd>
						</div>
					</dl>
				</SfCard>
			</template>

			<SfEmptyState
				v-else
				icon="mdi:package-variant-remove"
				title="Ordine non trovato"
				description="L'ordine richiesto non esiste o è stato eliminato.">
				<template #cta>
					<SfButton variant="secondary" :to="'/account/amministrazione/ordini'">
						Torna all'elenco ordini
					</SfButton>
				</template>
		</SfEmptyState>
	</AccountPageSection>
</template>
