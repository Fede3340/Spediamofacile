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
	if (!confirm(`Sei sicuro di voler eliminare il servizio "${article.title}"?`)) return;
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
	<section class="sf-account-shell min-h-[600px] py-[24px] tablet:py-[28px] desktop:py-[28px]">
		<div class="my-container sf-stack-section">
			<AccountPageHeader
				eyebrow="Area amministrazione"
				title="Servizi"
				description="Catalogo servizi, visibilita e ordine in una lista piu pulita e coerente con il resto della console."
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Amministrazione', to: '/account/amministrazione' },
					{ label: 'Servizi' },
				]"
				back-to="/account/amministrazione"
				back-label="Torna all'amministrazione" />

			<AdminActionBanner :message="actionMessage?.text || ''" :tone="actionMessage?.type || ''" />

			<div class="grid grid-cols-2 tablet:grid-cols-4 gap-[14px]">
				<div class="rounded-[16px] bg-white px-[14px] py-[10px] ring-[1px] ring-[#DFE2E7]" style="box-shadow: 0 2px 8px rgba(9,88,102,0.06)">
					<div class="flex items-center gap-[10px]">
						<div class="w-[32px] h-[32px] rounded-[8px] bg-[#F0F7F8] flex items-center justify-center">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] text-[var(--color-brand-primary)]" fill="currentColor">
								<path d="M12,2L2,7L12,12L22,7L12,2M4,9.5V16.5L12,21L20,16.5V9.5L12,14L4,9.5Z" />
							</svg>
						</div>
						<div>
							<p class="text-[0.6875rem] font-semibold uppercase tracking-[0.5px] text-[#7A8392] mb-[2px]">Servizi</p>
							<p class="text-[1.25rem] font-bold text-[#1d2738] leading-tight">{{ articles.length }}</p>
						</div>
					</div>
				</div>

				<div class="rounded-[16px] bg-white px-[14px] py-[10px] ring-[1px] ring-[#DFE2E7]" style="box-shadow: 0 2px 8px rgba(9,88,102,0.06)">
					<div class="flex items-center gap-[10px]">
						<div class="w-[32px] h-[32px] rounded-[8px] bg-[#ECFDF3] flex items-center justify-center">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] text-[#047857]" fill="currentColor">
								<path d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z" />
							</svg>
						</div>
						<div>
							<p class="text-[0.6875rem] font-semibold uppercase tracking-[0.5px] text-[#7A8392] mb-[2px]">Pubblicati</p>
							<p class="text-[1.25rem] font-bold text-[#047857] leading-tight">{{ publishedArticles.length }}</p>
						</div>
					</div>
				</div>

				<div class="rounded-[16px] bg-white px-[14px] py-[10px] ring-[1px] ring-[#DFE2E7]" style="box-shadow: 0 2px 8px rgba(9,88,102,0.06)">
					<div class="flex items-center gap-[10px]">
						<div class="w-[32px] h-[32px] rounded-[8px] bg-[#FFF7F2] flex items-center justify-center">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] text-[#E44203]" fill="currentColor">
								<path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" />
							</svg>
						</div>
						<div>
							<p class="text-[0.6875rem] font-semibold uppercase tracking-[0.5px] text-[#7A8392] mb-[2px]">Bozze</p>
							<p class="text-[1.25rem] font-bold text-[#E44203] leading-tight">{{ draftArticles.length }}</p>
						</div>
					</div>
				</div>

				<div class="rounded-[16px] bg-white px-[14px] py-[10px] ring-[1px] ring-[#DFE2E7]" style="box-shadow: 0 2px 8px rgba(9,88,102,0.06)">
					<div class="flex items-center gap-[10px]">
						<div class="w-[32px] h-[32px] rounded-[8px] bg-[#F7FAFC] flex items-center justify-center">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] text-[#6A7486]" fill="currentColor">
								<path d="M19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M7,7H17V9H7V7M7,11H17V13H7V11M7,15H13V17H7V15Z" />
							</svg>
						</div>
						<div>
							<p class="text-[0.6875rem] font-semibold uppercase tracking-[0.5px] text-[#7A8392] mb-[2px]">Ordinati</p>
							<p class="text-[1.25rem] font-bold text-[#1d2738] leading-tight">{{ orderedArticles.length }}</p>
						</div>
					</div>
				</div>
			</div>

			<div v-if="isLoading" class="py-[32px] flex justify-center">
				<div class="w-[40px] h-[40px] border-3 border-[var(--color-brand-border)] border-t-[var(--color-brand-primary)] rounded-full animate-spin"></div>
			</div>

			<div
				v-else
				class="rounded-[18px] bg-white ring-[1px] ring-[#DFE2E7] overflow-hidden"
				style="box-shadow: 0 2px 12px rgba(9,88,102,0.08)">
				<div class="px-[18px] py-[18px] border-b border-[#EEF2F4] flex flex-col gap-[14px] desktop:flex-row desktop:items-center desktop:justify-between">
					<div class="max-w-[720px]">
						<p class="text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-[#6A7486] mb-[6px]">Catalogo</p>
						<h2 class="text-[1.125rem] font-bold text-[#1d2738] font-['Montserrat',sans-serif]">Catalogo servizi</h2>
						<p class="text-[0.875rem] text-[#5A6474] mt-[4px]">Ritiro, pagamento, coperture e opzioni extra in un solo pannello operativo, senza card giganti o azioni disperse.</p>
					</div>
					<NuxtLink to="/account/amministrazione/servizi/nuovo" class="btn-primary btn-compact inline-flex items-center justify-center gap-[6px] shrink-0">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px]" fill="currentColor">
							<path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" />
						</svg>
						Nuovo servizio
					</NuxtLink>
				</div>

				<div class="px-[18px] py-[18px] border-b border-[#EEF2F4] bg-[#FBFCFD]">
					<div class="grid grid-cols-1 desktop:grid-cols-[minmax(0,1fr)_auto] gap-[14px] items-start">
						<label class="relative block">
							<span class="sr-only">Cerca servizio</span>
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="absolute left-[12px] top-1/2 -translate-y-1/2 w-[16px] h-[16px] text-[#94A3B8]" fill="currentColor">
								<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
							</svg>
							<input
								v-model="searchQuery"
								type="text"
								placeholder="Cerca per nome, slug o descrizione..."
								class="w-full h-[44px] pl-[40px] pr-[14px] rounded-[14px] border border-[#DFE2E7] bg-white text-[0.875rem] text-[#1d2738] placeholder-[#94A3B8] outline-none transition focus:border-[#095866] focus:ring-[3px] focus:ring-[#095866]/10" />
						</label>

						<div class="flex flex-wrap items-center gap-[8px]">
							<button
								type="button"
								class="h-[38px] px-[14px] rounded-full text-[0.8125rem] font-semibold transition-colors"
								:class="statusFilter === 'all' ? 'bg-[#095866] text-white' : 'bg-white text-[#526071] ring-[1px] ring-[#DFE2E7] hover:bg-[#F5F8FA]'"
								@click="statusFilter = 'all'">
								Tutti {{ articles.length }}
							</button>
							<button
								type="button"
								class="h-[38px] px-[14px] rounded-full text-[0.8125rem] font-semibold transition-colors"
								:class="statusFilter === 'published' ? 'bg-[#095866] text-white' : 'bg-white text-[#526071] ring-[1px] ring-[#DFE2E7] hover:bg-[#F5F8FA]'"
								@click="statusFilter = 'published'">
								Pubblicati {{ publishedArticles.length }}
							</button>
							<button
								type="button"
								class="h-[38px] px-[14px] rounded-full text-[0.8125rem] font-semibold transition-colors"
								:class="statusFilter === 'draft' ? 'bg-[#095866] text-white' : 'bg-white text-[#526071] ring-[1px] ring-[#DFE2E7] hover:bg-[#F5F8FA]'"
								@click="statusFilter = 'draft'">
								Bozze {{ draftArticles.length }}
							</button>
						</div>
					</div>
				</div>

				<div v-if="!articles.length" class="px-[18px] py-[30px] text-center">
					<div class="w-[60px] h-[60px] mx-auto rounded-full flex items-center justify-center bg-[#F0F7F8] mb-[14px]">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[28px] h-[28px] text-[#7A8B96]" fill="currentColor">
							<path d="M12,2L2,7L12,12L22,7L12,2M4,9.5V16.5L12,21L20,16.5V9.5L12,14L4,9.5Z" />
						</svg>
					</div>
					<h3 class="text-[18px] font-bold text-[#1d2738] font-['Montserrat',sans-serif] mb-[8px]">Nessun servizio presente</h3>
					<p class="text-[14px] text-[#5A6474] max-w-[560px] mx-auto">Apri i servizi opzionali davvero utili al checkout e gestiscili da una console unica, ordinata e coerente col resto dell'admin.</p>
					<div class="mt-[16px] flex flex-wrap items-center justify-center gap-[8px]">
						<span class="inline-flex items-center px-[10px] py-[5px] rounded-full bg-[#F4FAFC] text-[var(--color-brand-primary)] text-[0.75rem] font-medium border border-[#D8E9F0]">Contrassegno</span>
						<span class="inline-flex items-center px-[10px] py-[5px] rounded-full bg-[#F8FAFC] text-[#5A6474] text-[0.75rem] font-medium border border-[#E5EAF0]">Ritiro dedicato</span>
						<span class="inline-flex items-center px-[10px] py-[5px] rounded-full bg-[#FFF7F2] text-[#A34B18] text-[0.75rem] font-medium border border-[#F2D6C6]">Assicurazione</span>
					</div>
					<NuxtLink to="/account/amministrazione/servizi/nuovo" class="btn-primary btn-compact inline-flex items-center justify-center gap-[6px] mt-[18px]">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px]" fill="currentColor">
							<path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" />
						</svg>
						Crea il primo servizio
					</NuxtLink>
				</div>

				<div v-else-if="!filteredArticles.length" class="px-[18px] py-[26px] text-center">
					<h3 class="text-[17px] font-bold text-[#1d2738] font-['Montserrat',sans-serif] mb-[8px]">Nessun servizio con i filtri correnti</h3>
					<p class="text-[14px] text-[#5A6474] max-w-[520px] mx-auto">Prova a cambiare stato o ricerca per ritrovare piu rapidamente i servizi del catalogo pubblico.</p>
				</div>

				<div v-else class="divide-y divide-[#EEF2F4]">
					<div
						v-for="article in filteredArticles"
						:key="article.id"
						class="px-[18px] py-[16px] hover:bg-[#FBFCFD] transition-colors">
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
			</div>
		</div>
	</section>
</template>

