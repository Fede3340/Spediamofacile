<!-- Limite backend: max 5 indirizzi per utente. -->
<script setup>
import '~/assets/css/pages/account-indirizzi.css';
definePageMeta({ middleware: ['app-auth'] });

useSeoMeta({
	title: 'I tuoi indirizzi | SpediamoFacile',
	ogTitle: 'I tuoi indirizzi | SpediamoFacile',
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

const activeTab = ref('all'); // 'all' | 'origin' | 'destination'
const searchQuery = ref('');

// ─────────── Loaders ───────────
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

// ─────────── Stats ───────────
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

// ─────────── Flash ───────────
const showFlashSuccess = (msg) => {
	flashSuccess.value = msg;
	setTimeout(() => { flashSuccess.value = null; }, 4000);
};
const showFlashError = (msg) => {
	flashError.value = msg;
	setTimeout(() => { flashError.value = null; }, 5000);
};

// ─────────── Apertura modale ───────────
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

// ─────────── Submit ───────────
const handleSubmit = async (payload) => {
	submitting.value = true;
	serverError.value = null;
	try {
		// Estraiamo _meta (campi extra solo frontend) dal payload da inviare al backend
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

// ─────────── Set default ───────────
const setDefault = async (address) => {
	try {
		await sanctum(`/api/user-addresses/${address.id}`, { method: 'PATCH', body: { default: true } });
		await fetchAddresses();
		showFlashSuccess(`"${address.label || address.name}" è ora l'indirizzo predefinito.`);
	} catch (err) {
		showFlashError(err?.data?.message || 'Impossibile aggiornare il predefinito.');
	}
};

// ─────────── Delete ───────────
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
	<section class="sf-account-shell sf-addr-page">
		<div class="my-container max-w-[1280px]">
			<!-- HEADER unificato (P5 design system - prima era custom .sf-addr-page__header) -->
			<AccountPageHeader
				eyebrow="Rubrica"
				title="I tuoi indirizzi"
				:description="`Salva indirizzi di partenza e destinazione per spedire con un solo tap. ${stats.total} di ${stats.max} indirizzi salvati.`"
				current="Indirizzi">
				<template #actions>
					<button
						type="button"
						class="btn btn-cta sf-addr-page__cta"
						:disabled="reachedLimit"
						@click="openCreate">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
							<path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" />
						</svg>
						Nuovo indirizzo
					</button>
				</template>
			</AccountPageHeader>

			<!-- FLASH -->
			<div v-if="flashSuccess" class="sf-addr-flash sf-addr-flash--success" role="status">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
					<path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" />
				</svg>
				<span>{{ flashSuccess }}</span>
			</div>
			<div v-if="flashError" class="sf-addr-flash sf-addr-flash--error" role="alert">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
					<path d="M11,15H13V17H11V15M11,7H13V13H11V7M12,2A10,10 0 1,0 22,12A10,10 0 0,0 12,2Z" />
				</svg>
				<span>{{ flashError }}</span>
			</div>

			<!-- TOOLBAR: TABS + RICERCA -->
			<div class="sf-addr-toolbar">
				<div class="sf-addr-tabs" role="tablist">
					<button
						v-for="tab in tabs"
						:key="tab.key"
						role="tab"
						:aria-selected="activeTab === tab.key"
						:class="['sf-addr-tab', activeTab === tab.key ? 'sf-addr-tab--active' : '']"
						type="button"
						@click="activeTab = tab.key"
					>
						<span>{{ tab.label }}</span>
						<span class="sf-addr-tab__badge">{{ tab.count }}</span>
					</button>
				</div>

				<div class="sf-addr-search">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
						<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,10.89 15.56,12.18 14.81,13.24L20.29,18.71L18.88,20.12L13.41,14.64C12.35,15.4 11.05,15.83 9.67,15.83A6.5,6.5 0 0,1 3.17,9.33A6.5,6.5 0 0,1 9.5,3M9.5,5A4.5,4.5 0 0,0 5,9.5A4.5,4.5 0 0,0 9.5,14A4.5,4.5 0 0,0 14,9.5A4.5,4.5 0 0,0 9.5,5Z" />
					</svg>
					<input
						v-model="searchQuery"
						type="search"
						placeholder="Cerca per etichetta, nome, città…"
						class="sf-addr-search__input"
						aria-label="Cerca indirizzo"
					/>
					<button v-if="searchQuery" type="button" class="sf-addr-search__clear" @click="searchQuery = ''" aria-label="Azzera ricerca">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
							<path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" />
						</svg>
					</button>
				</div>
			</div>

			<!-- LOADING -->
			<div v-if="loading" class="sf-addr-grid">
				<div v-for="n in 3" :key="n" class="sf-addr-skeleton">
					<div class="sf-addr-skeleton__chip"></div>
					<div class="sf-addr-skeleton__title"></div>
					<div class="sf-addr-skeleton__line"></div>
					<div class="sf-addr-skeleton__line sf-addr-skeleton__line--short"></div>
				</div>
			</div>

			<!-- ERROR LOAD -->
			<div v-else-if="loadError" class="sf-addr-empty sf-addr-empty--error">
				<p>{{ loadError }}</p>
				<button type="button" class="btn btn-cta" @click="fetchAddresses">Riprova</button>
			</div>

			<!-- EMPTY -->
			<div v-else-if="addresses.length === 0" class="sf-addr-empty">
				<div class="sf-addr-empty__illustration" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96" fill="none">
						<circle cx="48" cy="48" r="46" fill="#F0F6F7" />
						<path d="M48 22a14 14 0 0 0-14 14c0 10.5 14 26 14 26s14-15.5 14-26a14 14 0 0 0-14-14Zm0 19a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z" fill="#095866" />
						<circle cx="68" cy="64" r="14" fill="#E44203" />
						<path d="M62 64h12M68 58v12" stroke="#fff" stroke-width="2.5" stroke-linecap="round" />
					</svg>
				</div>
				<h2 class="sf-addr-empty__title">La tua rubrica è ancora vuota</h2>
				<p class="sf-addr-empty__text">Salva i tuoi indirizzi più usati per spedire più velocemente. Puoi aggiungerne fino a {{ stats.max }}.</p>
				<button type="button" class="btn btn-cta" @click="openCreate">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
						<path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" />
					</svg>
					Aggiungi il primo indirizzo
				</button>
			</div>

			<!-- EMPTY filtri -->
			<div v-else-if="filteredAddresses.length === 0" class="sf-addr-empty sf-addr-empty--compact">
				<p class="sf-addr-empty__title sf-addr-empty__title--small">Nessun indirizzo corrisponde ai filtri</p>
				<p class="sf-addr-empty__text">Prova a cambiare scheda o azzera la ricerca.</p>
				<button type="button" class="sf-addr-empty__reset" @click="() => { searchQuery = ''; activeTab = 'all'; }">Mostra tutti</button>
			</div>

			<!-- GRID -->
			<div v-else class="sf-addr-grid">
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
					@cancel-delete="cancelDelete"
				/>
			</div>
		</div>

		<!-- MODALE -->
		<AddressFormModal
			v-model="modalOpen"
			:mode="modalMode"
			:initial="modalInitial"
			:submitting="submitting"
			:server-error="serverError"
			@submit="handleSubmit"
		/>
	</section>
</template>

