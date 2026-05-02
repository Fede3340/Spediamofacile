<script setup>
definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Admin - Servizi',
	robots: 'noindex, nofollow',
});

const sanctum = useSanctumClient();
const { actionLoading, actionMessage, showSuccess, showError, formatDate } = useAdmin();
const confirmDialog = useConfirmDialogStore();

const isLoading = ref(true);
const articles = ref([]);
const searchQuery = ref('');
const statusFilter = ref('all');

const publishedArticles = computed(() => articles.value.filter((article) => Boolean(article?.is_published)));
const draftArticles = computed(() => articles.value.filter((article) => !article?.is_published));
const orderedArticles = computed(() =>
	articles.value.filter((article) => article?.sort_order !== null && article?.sort_order !== undefined && article?.sort_order !== '')
);

const filteredArticles = computed(() => {
	const query = searchQuery.value.trim().toLowerCase();

	return articles.value.filter((article) => {
		const matchesStatus = statusFilter.value === 'all'
			? true
			: statusFilter.value === 'published'
				? Boolean(article?.is_published)
				: !article?.is_published;

		if (!matchesStatus) return false;
		if (!query) return true;

		const haystack = [
			article?.title,
			article?.slug,
			article?.intro,
			article?.meta_description,
		]
			.filter(Boolean)
			.join(' ')
			.toLowerCase();

		return haystack.includes(query);
	});
});

const servicePreview = (article) =>
	article?.intro?.trim() || article?.meta_description?.trim() || 'Apri il servizio per completare descrizione, vantaggi e contenuti del catalogo pubblico.';

const statusFilters = computed(() => [
	{ value: 'all', label: 'Tutti', count: articles.value.length },
	{ value: 'published', label: 'Pubblicati', count: publishedArticles.value.length },
	{ value: 'draft', label: 'Bozze', count: draftArticles.value.length },
]);

const fetchArticles = async () => {
	isLoading.value = true;
	try {
		const res = await sanctum('/api/admin/articles?type=service');
		articles.value = res?.data || res || [];
	} catch (e) {
		articles.value = [];
		showError(e, 'Errore nel caricamento dei servizi.');
	} finally {
		isLoading.value = false;
	}
};

const togglePublished = async (article) => {
	actionLoading.value = `toggle-${article.id}`;
	try {
		await sanctum(`/api/admin/articles/${article.id}`, {
			method: 'PUT',
			body: { ...article, is_published: !article.is_published },
		});
		article.is_published = !article.is_published;
		showSuccess(`Servizio "${article.title}" ${article.is_published ? 'pubblicato' : 'salvato come bozza'}.`);
	} catch (e) {
		showError(e, "Errore durante l'aggiornamento.");
	} finally {
		actionLoading.value = null;
	}
};

const deleteArticle = async (article) => {
	const ok = await confirmDialog.confirm({
		title: 'Eliminare il servizio?',
		message: `"${article.title}" verra' rimosso dal catalogo. L'azione non e' reversibile.`,
		confirmText: 'Elimina',
		cancelText: 'Annulla',
		tone: 'danger',
	});
	if (!ok) return;
	actionLoading.value = `delete-${article.id}`;
	try {
		await sanctum(`/api/admin/articles/${article.id}`, { method: 'DELETE' });
		showSuccess(`Servizio "${article.title}" eliminato.`);
		await fetchArticles();
	} catch (e) {
		showError(e, "Errore durante l'eliminazione.");
	} finally {
		actionLoading.value = null;
	}
};

onMounted(() => {
	fetchArticles();
});
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-6 tablet:py-7 desktop:py-7">
		<div class="my-container sf-stack-section">
			<AccountPageHeader
				eyebrow="Area amministrazione"
				title="Servizi"
				description="Catalogo servizi, visibilita e ordine in una lista coerente con il resto della console."
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Amministrazione', to: '/account/amministrazione' },
					{ label: 'Servizi' },
				]"
				back-to="/account/amministrazione"
				back-label="Torna all'amministrazione" />

			<AdminActionBanner :message="actionMessage?.text || ''" :tone="actionMessage?.type || ''" />

			<div class="grid grid-cols-2 tablet:grid-cols-4 gap-3.5">
				<SfStatCard label="Servizi" :value="articles.length" icon="mdi:cube-outline" tone="primary" :loading="isLoading" />
				<SfStatCard label="Pubblicati" :value="publishedArticles.length" icon="mdi:check-circle-outline" tone="success" :loading="isLoading" />
				<SfStatCard label="Bozze" :value="draftArticles.length" icon="mdi:clock-outline" tone="accent" :loading="isLoading" />
				<SfStatCard label="Ordinati" :value="orderedArticles.length" icon="mdi:sort-numeric-ascending" tone="primary" :loading="isLoading" />
			</div>

			<div v-if="isLoading" class="py-8 flex justify-center">
				<div class="w-10 h-10 border-3 border-brand-border border-t-brand-primary rounded-full animate-spin" />
			</div>

			<SfCard v-else padding="none" shadow="sf">
				<template #header>
					<div class="flex flex-col tablet:flex-row tablet:items-start tablet:justify-between gap-4 w-full">
						<div class="max-w-[720px]">
							<p class="text-xs font-semibold uppercase tracking-wider text-brand-text-muted mb-1.5">Catalogo</p>
							<h2 class="font-display text-lg font-bold text-brand-text">Catalogo servizi</h2>
							<p class="text-sm text-brand-text-secondary mt-1">Ritiro, pagamento, coperture e opzioni extra in un solo pannello operativo.</p>
						</div>
						<SfButton to="/account/amministrazione/servizi/nuovo" variant="primary" size="sm">
							<template #leading><UIcon name="mdi:plus" class="w-4 h-4" /></template>
							Nuovo servizio
						</SfButton>
					</div>
				</template>

				<div class="px-5 md:px-6 py-4 border-b border-brand-border bg-brand-bg-alt/60">
					<div class="grid grid-cols-1 desktop:grid-cols-[minmax(0,1fr)_auto] gap-3.5 items-start">
						<SfInput
							v-model="searchQuery"
							placeholder="Cerca per nome, slug o descrizione..."
							leading-icon="mdi:magnify" />

						<div class="flex flex-wrap items-center gap-2">
							<button
								v-for="filter in statusFilters"
								:key="filter.value"
								type="button"
								:class="[
									'h-9 px-3.5 rounded-pill text-xs font-semibold transition-colors border',
									statusFilter === filter.value
										? 'bg-brand-primary text-white border-brand-primary'
										: 'bg-brand-card text-brand-text-secondary border-brand-border hover:bg-brand-bg-alt',
								]"
								@click="statusFilter = filter.value">
								{{ filter.label }} {{ filter.count }}
							</button>
						</div>
					</div>
				</div>

				<div v-if="!articles.length" class="px-5 md:px-6 py-2">
					<SfEmptyState
						icon="mdi:cube-outline"
						title="Nessun servizio presente"
						description="Apri i servizi opzionali davvero utili al checkout e gestiscili da una console unica e coerente con l'admin.">
						<template #cta>
							<SfButton to="/account/amministrazione/servizi/nuovo" variant="primary" size="sm">
								<template #leading><UIcon name="mdi:plus" class="w-4 h-4" /></template>
								Crea il primo servizio
							</SfButton>
						</template>
					</SfEmptyState>
				</div>

				<div v-else-if="!filteredArticles.length" class="px-5 md:px-6 py-2">
					<SfEmptyState
						icon="mdi:filter-variant"
						title="Nessun servizio con i filtri correnti"
						description="Prova a cambiare stato o ricerca per ritrovare i servizi del catalogo." />
				</div>

				<div v-else class="divide-y divide-brand-border">
					<div
						v-for="article in filteredArticles"
						:key="article.id"
						class="px-5 md:px-6 py-4 hover:bg-brand-bg-alt/40 transition-colors">
						<AdminContentCatalogRow
							:article="article"
							:preview-text="servicePreview(article)"
							:edit-to="`/account/amministrazione/servizi/${article.id}`"
							kind="service"
							published-label="Pubblicato"
							draft-label="Bozza"
							created-label="Creato"
							updated-label="Aggiornato"
							:format-date="formatDate"
							:is-toggling="actionLoading === `toggle-${article.id}`"
							:is-deleting="actionLoading === `delete-${article.id}`"
							@toggle="togglePublished(article)"
							@delete="deleteArticle(article)" />
					</div>
				</div>
			</SfCard>
		</div>
	</section>
</template>
