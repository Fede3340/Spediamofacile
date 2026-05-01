<script setup>
definePageMeta({ middleware: ['app-auth'] });

useSeoMeta({
	title: 'I tuoi indirizzi',
	ogTitle: 'I tuoi indirizzi',
	description: 'Gestisci la rubrica indirizzi di partenza e destinazione dal tuo account SpediamoFacile.',
	robots: 'noindex, nofollow',
});

const sanctum = useSanctumClient();

const addresses = ref([]);
const loading = ref(true);
const loadError = ref(null);

const flashSuccess = ref(null);
const flashError = ref(null);

const modalOpen = ref(false);
const modalMode = ref('create');
const modalInitial = ref(null);
const submitting = ref(false);
const serverError = ref(null);

const deleteConfirmId = ref(null);
const deleting = ref(false);

const activeTab = ref('all');
const searchQuery = ref('');

const fetchAddresses = async () => {
	loading.value = true;
	loadError.value = null;
	try {
		const res = await sanctum('/api/user-addresses', { method: 'GET' });
		const list = Array.isArray(res?.data) ? res.data : Array.isArray(res) ? res : [];
		addresses.value = list;
	} catch (err) {
		loadError.value = err?.data?.message || 'Impossibile caricare la rubrica. Riprova.';
		addresses.value = [];
	} finally {
		loading.value = false;
	}
};

await fetchAddresses();

const stats = computed(() => {
	const total = addresses.value.length;
	const origin = addresses.value.filter((a) => String(a.type || '').toLowerCase() === 'origin').length;
	const destination = total - origin;
	return { total, origin, destination, max: 5 };
});

const filteredAddresses = computed(() => {
	const q = searchQuery.value.trim().toLowerCase();
	let list = addresses.value;

	if (activeTab.value === 'origin') {
		list = list.filter((a) => String(a.type || '').toLowerCase() === 'origin');
	} else if (activeTab.value === 'destination') {
		list = list.filter((a) => String(a.type || '').toLowerCase() !== 'origin');
	}

	if (q) {
		list = list.filter((a) => {
			const haystack = [a.label, a.name, a.company_name, a.address, a.city, a.postal_code, a.province, a.email, a.telephone_number]
				.filter(Boolean)
				.join(' ')
				.toLowerCase();
			return haystack.includes(q);
		});
	}

	return [...list].sort((a, b) => {
		if (a.default && !b.default) return -1;
		if (!a.default && b.default) return 1;
		return String(a.label || a.name || '').localeCompare(String(b.label || b.name || ''), 'it');
	});
});

const reachedLimit = computed(() => stats.value.total >= stats.value.max);

let flashSuccessTimer = null;
let flashErrorTimer = null;
const showFlashSuccess = (msg) => {
	flashSuccess.value = msg;
	if (flashSuccessTimer) clearTimeout(flashSuccessTimer);
	flashSuccessTimer = setTimeout(() => { flashSuccess.value = null; flashSuccessTimer = null; }, 4000);
};
const showFlashError = (msg) => {
	flashError.value = msg;
	if (flashErrorTimer) clearTimeout(flashErrorTimer);
	flashErrorTimer = setTimeout(() => { flashError.value = null; flashErrorTimer = null; }, 5000);
};
onBeforeUnmount(() => {
	if (flashSuccessTimer) clearTimeout(flashSuccessTimer);
	if (flashErrorTimer) clearTimeout(flashErrorTimer);
});

const openCreate = () => {
	if (reachedLimit.value) {
		showFlashError(`Hai raggiunto il limite di ${stats.value.max} indirizzi salvati. Eliminane uno per aggiungerne altri.`);
		return;
	}
	modalMode.value = 'create';
	modalInitial.value = null;
	serverError.value = null;
	modalOpen.value = true;
};

const openEdit = (address) => {
	modalMode.value = 'edit';
	modalInitial.value = { ...address };
	serverError.value = null;
	modalOpen.value = true;
};

const handleSubmit = async (payload) => {
	submitting.value = true;
	serverError.value = null;
	try {
		const { _meta, ...bodyForBackend } = payload;

		if (modalMode.value === 'create') {
			await sanctum('/api/user-addresses', { method: 'POST', body: bodyForBackend });
			showFlashSuccess('Indirizzo aggiunto con successo.');
		} else {
			await sanctum(`/api/user-addresses/${modalInitial.value.id}`, { method: 'PATCH', body: bodyForBackend });
			showFlashSuccess('Indirizzo aggiornato.');
		}
		modalOpen.value = false;
		await fetchAddresses();
	} catch (err) {
		serverError.value = err?.data?.message || 'Errore durante il salvataggio. Riprova.';
	} finally {
		submitting.value = false;
	}
};

const setDefault = async (address) => {
	try {
		await sanctum(`/api/user-addresses/${address.id}`, { method: 'PATCH', body: { default: true } });
		await fetchAddresses();
		showFlashSuccess(`"${address.label || address.name}" è ora l'indirizzo predefinito.`);
	} catch (err) {
		showFlashError(err?.data?.message || 'Impossibile aggiornare il predefinito.');
	}
};

const requestDelete = (id) => { deleteConfirmId.value = id; };
const cancelDelete = () => { deleteConfirmId.value = null; };

const confirmDelete = async (id) => {
	deleting.value = true;
	try {
		await sanctum(`/api/user-addresses/${id}`, { method: 'DELETE' });
		await fetchAddresses();
		deleteConfirmId.value = null;
		showFlashSuccess('Indirizzo eliminato.');
	} catch (err) {
		showFlashError(err?.data?.message || 'Errore durante l\'eliminazione.');
	} finally {
		deleting.value = false;
	}
};

const tabs = computed(() => [
	{ key: 'all', label: 'Tutti', count: stats.value.total },
	{ key: 'origin', label: 'Partenza', count: stats.value.origin },
	{ key: 'destination', label: 'Destinazione', count: stats.value.destination },
]);
</script>

<template>
	<section class="w-full py-5 tablet:py-6 desktop:py-7">
		<div class="my-container max-w-7xl">
			<AccountPageHeader
				eyebrow="Rubrica"
				title="I tuoi indirizzi"
				:description="`Salva indirizzi di partenza e destinazione per spedire con un solo tap. ${stats.total} di ${stats.max} indirizzi salvati.`"
				current="Indirizzi">
				<template #actions>
					<SfButton
						variant="primary"
						:disabled="reachedLimit"
						@click="openCreate">
						<template #leading>
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
								<path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" />
							</svg>
						</template>
						Nuovo indirizzo
					</SfButton>
				</template>
			</AccountPageHeader>

			<div v-if="flashSuccess" class="mb-3 flex items-center gap-2 rounded-card border border-brand-success/30 bg-brand-success-bg px-3.5 py-2.5 text-sm font-semibold text-brand-success-fg" role="status">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" class="shrink-0">
					<path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" />
				</svg>
				<span>{{ flashSuccess }}</span>
			</div>
			<div v-if="flashError" class="mb-3 flex items-center gap-2 rounded-card border border-status-failed-fg/30 bg-status-failed-bg px-3.5 py-2.5 text-sm font-semibold text-status-failed-fg" role="alert">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" class="shrink-0">
					<path d="M11,15H13V17H11V15M11,7H13V13H11V7M12,2A10,10 0 1,0 22,12A10,10 0 0,0 12,2Z" />
				</svg>
				<span>{{ flashError }}</span>
			</div>

			<div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
				<div class="flex flex-wrap gap-1.5" role="tablist">
					<button
						v-for="tab in tabs"
						:key="tab.key"
						role="tab"
						type="button"
						:aria-selected="activeTab === tab.key"
						:class="[
							'inline-flex items-center gap-2 rounded-full border px-3.5 py-2 text-[0.8125rem] font-semibold transition-colors',
							activeTab === tab.key
								? 'border-brand-primary bg-brand-primary text-white'
								: 'border-brand-border bg-white text-brand-text-secondary hover:border-brand-primary hover:bg-brand-primary/[0.04] hover:text-brand-primary',
						]"
						@click="activeTab = tab.key">
						<span>{{ tab.label }}</span>
						<span :class="[
							'inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-[0.6875rem] font-bold',
							activeTab === tab.key ? 'bg-white/20 text-white' : 'bg-brand-bg-alt text-brand-text-muted',
						]">{{ tab.count }}</span>
					</button>
				</div>

				<div class="relative w-full lg:w-[320px]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-text-muted">
						<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,10.89 15.56,12.18 14.81,13.24L20.29,18.71L18.88,20.12L13.41,14.64C12.35,15.4 11.05,15.83 9.67,15.83A6.5,6.5 0 0,1 3.17,9.33A6.5,6.5 0 0,1 9.5,3M9.5,5A4.5,4.5 0 0,0 5,9.5A4.5,4.5 0 0,0 9.5,14A4.5,4.5 0 0,0 14,9.5A4.5,4.5 0 0,0 9.5,5Z" />
					</svg>
					<input
						v-model="searchQuery"
						type="search"
						placeholder="Cerca per etichetta, nome, città…"
						aria-label="Cerca indirizzo"
						class="w-full rounded-full border border-brand-border bg-white py-2.5 pl-10 pr-10 text-sm text-brand-text transition focus:border-brand-primary focus:shadow-[0_0_0_3px_rgba(9,88,102,0.1)] focus:outline-none">
					<button v-if="searchQuery" type="button" aria-label="Azzera ricerca" class="absolute right-2.5 top-1/2 -translate-y-1/2 inline-flex h-7 w-7 items-center justify-center rounded-full text-brand-text-muted transition-colors hover:bg-brand-bg-alt hover:text-brand-primary" @click="searchQuery = ''">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
							<path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" />
						</svg>
					</button>
				</div>
			</div>

			<div v-if="loading" class="grid gap-3.5 sm:grid-cols-2 xl:grid-cols-3">
				<div v-for="n in 3" :key="n" class="animate-pulse space-y-3 rounded-card border border-brand-border bg-brand-card p-4">
					<div class="h-5 w-20 rounded-full bg-brand-bg-alt" />
					<div class="h-4 w-3/5 rounded-full bg-brand-bg-alt" />
					<div class="h-3 w-full rounded-full bg-brand-bg-alt" />
					<div class="h-3 w-2/5 rounded-full bg-brand-bg-alt" />
				</div>
			</div>

			<div v-else-if="loadError" class="flex flex-col items-center gap-3 rounded-card border border-status-failed-fg/30 bg-status-failed-bg p-8 text-center text-status-failed-fg">
				<p>{{ loadError }}</p>
				<SfButton @click="fetchAddresses">Riprova</SfButton>
			</div>

			<div v-else-if="addresses.length === 0" class="flex flex-col items-center gap-3.5 rounded-card border border-brand-border bg-brand-card p-12 text-center">
				<div aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96" fill="none">
						<circle cx="48" cy="48" r="46" fill="#F0F6F7" />
						<path d="M48 22a14 14 0 0 0-14 14c0 10.5 14 26 14 26s14-15.5 14-26a14 14 0 0 0-14-14Zm0 19a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z" fill="#095866" />
						<circle cx="68" cy="64" r="14" fill="#E44203" />
						<path d="M62 64h12M68 58v12" stroke="#fff" stroke-width="2" stroke-linecap="round" />
					</svg>
				</div>
				<h2 class="font-display text-xl font-extrabold text-brand-primary">La tua rubrica è ancora vuota</h2>
				<p class="max-w-md text-sm leading-relaxed text-brand-text-secondary">Salva i tuoi indirizzi più usati per spedire più velocemente. Puoi aggiungerne fino a {{ stats.max }}.</p>
				<SfButton variant="primary" @click="openCreate">
					<template #leading>
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
							<path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" />
						</svg>
					</template>
					Aggiungi il primo indirizzo
				</SfButton>
			</div>

			<div v-else-if="filteredAddresses.length === 0" class="flex flex-col items-center gap-2 rounded-card border border-brand-border bg-brand-card p-8 text-center">
				<p class="font-semibold text-brand-text">Nessun indirizzo corrisponde ai filtri</p>
				<p class="text-sm text-brand-text-secondary">Prova a cambiare scheda o azzera la ricerca.</p>
				<button type="button" class="mt-2 text-sm font-semibold text-brand-primary underline transition-opacity hover:opacity-80" @click="() => { searchQuery = ''; activeTab = 'all'; }">Mostra tutti</button>
			</div>

			<div v-else class="grid gap-3.5 sm:grid-cols-2 xl:grid-cols-3">
				<AddressCard
					v-for="address in filteredAddresses"
					:key="address.id"
					:address="address"
					:confirm-delete="deleteConfirmId === address.id"
					:deleting="deleting && deleteConfirmId === address.id"
					@edit="openEdit"
					@set-default="setDefault"
					@request-delete="requestDelete"
					@confirm-delete="confirmDelete"
					@cancel-delete="cancelDelete" />
			</div>
		</div>

		<AddressFormModal
			v-model="modalOpen"
			:mode="modalMode"
			:initial="modalInitial"
			:submitting="submitting"
			:server-error="serverError"
			@submit="handleSubmit" />
	</section>
</template>
