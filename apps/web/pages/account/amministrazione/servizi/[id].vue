<!-- FILE: pages/account/amministrazione/servizi/[id].vue -->
<script setup>
definePageMeta({
	middleware: ["app-auth", "admin"],
});

useSeoMeta({
	title: 'Admin - Modifica Servizio',
	robots: 'noindex, nofollow',
});

const route = useRoute();
const sanctum = useSanctumClient();
const { actionMessage, showSuccess, showError } = useAdmin();

const isLoading = ref(true);
const saving = ref(false);
const uploading = ref(false);
const fileInputRef = ref(null);
const form = ref({
	title: '',
	slug: '',
	meta_description: '',
	intro: '',
	sections: [{ heading: '', text: '' }],
	faqs: [{ title: '', text: '' }],
	is_published: false,
	type: 'service',
	image_url: null,
});

const fetchArticle = async () => {
	isLoading.value = true;
	try {
		const res = await sanctum(`/api/admin/articles/${route.params.id}`);
		const data = res?.data || res;
		form.value = {
			title: data.title || '',
			slug: data.slug || '',
			meta_description: data.meta_description || '',
			intro: data.intro || '',
			sections: data.sections?.length ? data.sections : [{ heading: '', text: '' }],
			faqs: data.faqs?.length ? data.faqs : [{ title: '', text: '' }],
			is_published: !!data.is_published,
			type: data.type || 'service',
			image_url: data.image_url || null,
		};
	} catch (e) { showError(e, "Errore nel caricamento del servizio."); }
	finally { isLoading.value = false; }
};

const generateSlug = () => {
	form.value.slug = form.value.title
		.toLowerCase()
		.replace(/[àáâãäå]/g, 'a')
		.replace(/[èéêë]/g, 'e')
		.replace(/[ìíîï]/g, 'i')
		.replace(/[òóôõö]/g, 'o')
		.replace(/[ùúûü]/g, 'u')
		.replace(/[^a-z0-9\s-]/g, '')
		.replace(/\s+/g, '-')
		.replace(/-+/g, '-')
		.replace(/^-|-$/g, '');
};

const addSection = () => { form.value.sections.push({ heading: '', text: '' }); };
const removeSection = (idx) => { if (form.value.sections.length > 1) form.value.sections.splice(idx, 1); };
const addFaq = () => { form.value.faqs.push({ title: '', text: '' }); };
const removeFaq = (idx) => { if (form.value.faqs.length > 1) form.value.faqs.splice(idx, 1); };

const saveService = async () => {
	saving.value = true;
	try {
		await sanctum(`/api/admin/articles/${route.params.id}`, { method: "PUT", body: form.value });
		showSuccess("Servizio aggiornato con successo.");
	} catch (e) { showError(e, "Errore durante il salvataggio."); }
	finally { saving.value = false; }
};

const triggerImageUpload = () => { fileInputRef.value?.click(); };

const uploadImage = async (event) => {
	const file = event.target.files?.[0];
	if (!file) return;
	uploading.value = true;
	try {
		const formData = new FormData();
		formData.append('image', file);
		const res = await sanctum(`/api/admin/articles/${route.params.id}/upload-image`, {
			method: "POST",
			body: formData,
		});
		form.value.image_url = res?.image_url || res?.data?.image_url;
		showSuccess("Immagine caricata con successo.");
	} catch (e) { showError(e, "Errore durante il caricamento dell'immagine."); }
	finally { uploading.value = false; }
};

onMounted(() => { fetchArticle(); });
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-6 tablet:py-7 desktop:py-7">
		<div class="my-container sf-stack-section">
			<AccountPageHeader
				eyebrow="Area amministrazione"
				title="Modifica servizio"
				description="Aggiorna contenuti, sezioni, FAQ e immagine del servizio."
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Amministrazione', to: '/account/amministrazione' },
					{ label: 'Servizi', to: '/account/amministrazione/servizi' },
					{ label: 'Modifica servizio' },
				]"
				back-to="/account/amministrazione/servizi"
				back-label="Torna ai servizi" />

			<AdminActionBanner :message="actionMessage?.text || ''" :tone="actionMessage?.type || ''" />

			<div v-if="isLoading" class="py-8 flex justify-center">
				<div class="w-10 h-10 border-3 border-brand-border border-t-brand-primary rounded-full animate-spin" />
			</div>

			<template v-else>
				<div class="grid gap-5">
					<!-- Info base -->
					<SfCard padding="md">
						<template #icon>
							<UIcon name="mdi:file-document-outline" class="w-5 h-5 text-brand-primary" />
						</template>
						<template #header>
							<h2 class="font-display text-lg font-bold text-brand-text">Informazioni base</h2>
							<p class="text-sm text-brand-text-secondary mt-0.5">Naming, URL e testo introduttivo del servizio.</p>
						</template>

						<div class="grid grid-cols-1 desktop:grid-cols-2 gap-4">
							<SfFormGroup label="Titolo">
								<SfInput v-model="form.title" placeholder="Titolo del servizio" @input="generateSlug" />
							</SfFormGroup>
							<SfFormGroup label="Slug (URL)" hint="Si aggiorna dal titolo, puoi rifinirlo a mano.">
								<SfInput v-model="form.slug" placeholder="titolo-del-servizio" />
							</SfFormGroup>
							<div class="desktop:col-span-2">
								<SfFormGroup label="Meta description">
									<SfTextarea v-model="form.meta_description" :rows="2" placeholder="Descrizione per i motori di ricerca" />
								</SfFormGroup>
							</div>
							<div class="desktop:col-span-2">
								<SfFormGroup label="Introduzione">
									<SfTextarea v-model="form.intro" :rows="3" placeholder="Paragrafo introduttivo del servizio" />
								</SfFormGroup>
							</div>
						</div>

						<div class="flex flex-col tablet:flex-row tablet:items-center tablet:justify-between gap-3 pt-4 mt-4 border-t border-brand-border">
							<div class="grid gap-1 max-w-[34rem]">
								<span class="text-xs font-extrabold uppercase tracking-wider text-brand-text-muted">Visibilita'</span>
								<p class="text-sm text-brand-text-secondary">{{ form.is_published ? 'Il servizio e\' visibile nel catalogo pubblico.' : 'Il servizio e\' una bozza non visibile pubblicamente.' }}</p>
							</div>
							<div class="inline-flex items-center gap-2.5 flex-wrap tablet:justify-end">
								<button
									type="button"
									role="switch"
									:aria-checked="form.is_published ? 'true' : 'false'"
									aria-label="Attiva o disattiva pubblicazione"
									:class="['relative w-13 h-7.5 rounded-full transition-colors cursor-pointer', form.is_published ? 'bg-brand-primary' : 'bg-brand-border']"
									@click="form.is_published = !form.is_published">
									<span :class="['absolute top-[3px] left-[3px] w-6 h-6 rounded-full bg-white shadow-md transition-transform', form.is_published ? 'translate-x-[22px]' : 'translate-x-0']" />
								</button>
								<SfBadge :tone="form.is_published ? 'primary' : 'neutral'" size="sm">
									{{ form.is_published ? 'Pubblicato' : 'Bozza' }}
								</SfBadge>
							</div>
						</div>
					</SfCard>

					<!-- Immagine -->
					<SfCard padding="md">
						<template #icon>
							<UIcon name="mdi:image-outline" class="w-5 h-5 text-brand-primary" />
						</template>
						<template #header>
							<h2 class="font-display text-lg font-bold text-brand-text">Immagine</h2>
							<p class="text-sm text-brand-text-secondary mt-0.5">Anteprima visuale per il catalogo pubblico.</p>
						</template>

						<div class="grid gap-3 max-w-[700px]">
							<div v-if="form.image_url">
								<img :src="form.image_url" alt="Immagine servizio" loading="lazy" decoding="async" width="320" height="180" class="max-w-full max-h-[180px] rounded-control object-cover border border-brand-border">
							</div>
							<div>
								<SfButton variant="secondary" size="sm" :loading="uploading" loading-text="Caricamento..." :disabled="uploading" @click="triggerImageUpload">
									<template #leading><UIcon name="mdi:upload" class="w-4 h-4" /></template>
									Carica immagine
								</SfButton>
								<input ref="fileInputRef" type="file" accept="image/*" class="hidden" :disabled="uploading" @change="uploadImage">
							</div>
						</div>
					</SfCard>

					<!-- Sezioni -->
					<SfCard padding="md">
						<template #icon>
							<UIcon name="mdi:format-list-bulleted" class="w-5 h-5 text-brand-primary" />
						</template>
						<template #header>
							<h2 class="font-display text-lg font-bold text-brand-text">Sezioni</h2>
							<p class="text-sm text-brand-text-secondary mt-0.5">Organizza il contenuto in blocchi leggibili.</p>
						</template>
						<template #actions>
							<SfButton variant="secondary" size="sm" @click="addSection">
								<template #leading><UIcon name="mdi:plus" class="w-4 h-4" /></template>
								Aggiungi
							</SfButton>
						</template>

						<div class="grid gap-3">
							<div v-for="(section, idx) in form.sections" :key="idx" class="p-4 rounded-control bg-brand-bg-alt border border-brand-border">
								<div class="flex items-center justify-between mb-3">
									<span class="text-xs font-extrabold uppercase tracking-wider text-brand-text-muted">Sezione {{ idx + 1 }}</span>
									<SfButton v-if="form.sections.length > 1" variant="danger" size="sm" aria-label="Rimuovi sezione" @click="removeSection(idx)">
										<UIcon name="mdi:close" class="w-4 h-4" />
									</SfButton>
								</div>
								<div class="grid gap-2.5">
									<SfInput v-model="section.heading" placeholder="Titolo sezione" />
									<SfTextarea v-model="section.text" :rows="4" placeholder="Contenuto della sezione" />
								</div>
							</div>
						</div>
					</SfCard>

					<!-- FAQ -->
					<SfCard padding="md">
						<template #icon>
							<UIcon name="mdi:comment-question-outline" class="w-5 h-5 text-brand-primary" />
						</template>
						<template #header>
							<h2 class="font-display text-lg font-bold text-brand-text">FAQ</h2>
							<p class="text-sm text-brand-text-secondary mt-0.5">Risposte brevi ai dubbi ricorrenti.</p>
						</template>
						<template #actions>
							<SfButton variant="secondary" size="sm" @click="addFaq">
								<template #leading><UIcon name="mdi:plus" class="w-4 h-4" /></template>
								Aggiungi
							</SfButton>
						</template>

						<div class="grid gap-3">
							<div v-for="(faq, idx) in form.faqs" :key="idx" class="p-4 rounded-control bg-brand-bg-alt border border-brand-border">
								<div class="flex items-center justify-between mb-3">
									<span class="text-xs font-extrabold uppercase tracking-wider text-brand-text-muted">FAQ {{ idx + 1 }}</span>
									<SfButton v-if="form.faqs.length > 1" variant="danger" size="sm" aria-label="Rimuovi FAQ" @click="removeFaq(idx)">
										<UIcon name="mdi:close" class="w-4 h-4" />
									</SfButton>
								</div>
								<div class="grid gap-2.5">
									<SfInput v-model="faq.title" placeholder="Domanda" />
									<SfTextarea v-model="faq.text" :rows="3" placeholder="Risposta" />
								</div>
							</div>
						</div>
					</SfCard>

					<!-- Save -->
					<div class="flex justify-end">
						<SfButton variant="primary" size="md" :loading="saving" loading-text="Salvataggio..." :disabled="saving" @click="saveService">
							<template #leading><UIcon name="mdi:content-save" class="w-4 h-4" /></template>
							Salva modifiche
						</SfButton>
					</div>
				</div>
			</template>
		</div>
	</section>
</template>
