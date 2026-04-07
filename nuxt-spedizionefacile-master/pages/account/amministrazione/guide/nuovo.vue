<!--
  FILE: pages/account/amministrazione/guide/nuovo.vue
  SCOPO: Pannello admin — creazione nuova guida con sezioni dinamiche (titolo, contenuto, immagine).
  API: POST /api/admin/guides — crea guida.
  ROUTE: /account/amministrazione/guide/nuovo (middleware sanctum:auth + admin).

  COLLEGAMENTI:
    - pages/account/amministrazione/guide/index.vue → torna alla lista guide.
-->
<script setup>
definePageMeta({
	middleware: ["app-auth", "admin"],
});

const sanctum = useSanctumClient();
const router = useRouter();
const { actionMessage, showSuccess, showError } = useAdmin();

const saving = ref(false);
const form = ref({
	title: '',
	slug: '',
	meta_description: '',
	intro: '',
	sections: [{ heading: '', text: '' }],
	is_published: false,
	type: 'guide',
});

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

const addSection = () => {
	form.value.sections.push({ heading: '', text: '' });
};

const removeSection = (idx) => {
	if (form.value.sections.length > 1) {
		form.value.sections.splice(idx, 1);
	}
};

const saveGuide = async () => {
	saving.value = true;
	try {
		await sanctum("/api/admin/articles", { method: "POST", body: form.value });
		showSuccess("Guida creata con successo.");
		setTimeout(() => { router.push('/account/amministrazione/guide'); }, 800);
	} catch { showError(e, "Errore durante la creazione della guida."); }
	finally { saving.value = false; }
};
</script>

<template>
	<section class="min-h-[600px] py-[40px] desktop:py-[60px] desktop-xl:py-[80px]">
		<div class="my-container">
			<AccountPageHeader
				eyebrow="Admin"
				title="Nuova guida"
				description="Crea una nuova guida con titolo, testo, immagine e sezioni dinamiche, mantenendo il lavoro in bozza fino alla pubblicazione."
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Amministrazione', to: '/account/amministrazione' },
					{ label: 'Guide', to: '/account/amministrazione/guide' },
					{ label: 'Nuova guida' },
				]"
				back-to="/account/amministrazione/guide"
				back-label="Torna alle guide" />

			<!-- Action message -->
			<div
				v-if="actionMessage"
				:class="[
					'mb-[20px] px-[16px] py-[12px] rounded-[12px] text-[0.875rem] font-medium flex items-center gap-[8px]',
					actionMessage.type === 'success' ? 'bg-[#f0fdf4] text-[#166534] ring-[1px] ring-[#166534]/10' : 'bg-[#FFF5F2] text-[#E44203] ring-[1px] ring-[#E44203]/10',
				]">
				<template v-if="actionMessage.type === 'success'"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] shrink-0" fill="currentColor"><path d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z"/></svg></template>
				<template v-else><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] shrink-0" fill="currentColor"><path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/></svg></template>
				{{ actionMessage.text }}
			</div>

			<div class="space-y-[20px]">
				<!-- Info base -->
				<div class="bg-white rounded-[12px] p-[24px] desktop:p-[32px] shadow-sm border border-[#E9EBEC]">
					<h2 class="text-[1.125rem] font-bold text-[#252B42] mb-[20px] flex items-center gap-[8px]">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[20px] h-[20px] text-[#095866]" fill="currentColor"><path d="M14,17H7V15H14M17,13H7V11H17M17,9H7V7H17M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3Z"/></svg> Informazioni base
					</h2>
					<div class="space-y-[16px] max-w-[700px]">
						<div>
							<label class="block text-[0.8125rem] font-medium text-[#404040] mb-[6px]">Titolo</label>
							<input v-model="form.title" @input="generateSlug" type="text" class="form-input" placeholder="Titolo della guida" />
						</div>
						<div>
							<label class="block text-[0.8125rem] font-medium text-[#404040] mb-[6px]">Slug (URL)</label>
							<input v-model="form.slug" type="text" class="form-input font-mono" placeholder="titolo-della-guida" />
						</div>
						<div>
							<label class="block text-[0.8125rem] font-medium text-[#404040] mb-[6px]">Meta description</label>
							<textarea v-model="form.meta_description" rows="2" class="form-input min-h-[88px] resize-none" placeholder="Descrizione per i motori di ricerca"></textarea>
						</div>
						<div>
							<label class="block text-[0.8125rem] font-medium text-[#404040] mb-[6px]">Introduzione</label>
							<textarea v-model="form.intro" rows="3" class="form-input min-h-[120px] resize-none" placeholder="Paragrafo introduttivo della guida"></textarea>
						</div>
						<div class="flex items-center gap-[12px]">
							<button type="button" @click="form.is_published = !form.is_published" :class="['sf-toggle', form.is_published && 'is-active']">
								<span class="sf-toggle__thumb"></span>
							</button>
							<span class="text-[0.875rem] text-[#404040]">{{ form.is_published ? 'Pubblicata' : 'Bozza (non visibile)' }}</span>
						</div>
					</div>
				</div>

				<!-- Sezioni -->
				<div class="bg-white rounded-[12px] p-[24px] desktop:p-[32px] shadow-sm border border-[#E9EBEC]">
					<div class="flex items-center justify-between mb-[20px]">
						<h2 class="text-[1.125rem] font-bold text-[#252B42] flex items-center gap-[8px]">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[20px] h-[20px] text-[#095866]" fill="currentColor"><path d="M7,5H21V7H7V5M7,13V11H21V13H7M4,4.5A1.5,1.5 0 0,1 5.5,6A1.5,1.5 0 0,1 4,7.5A1.5,1.5 0 0,1 2.5,6A1.5,1.5 0 0,1 4,4.5M4,10.5A1.5,1.5 0 0,1 5.5,12A1.5,1.5 0 0,1 4,13.5A1.5,1.5 0 0,1 2.5,12A1.5,1.5 0 0,1 4,10.5M7,19V17H21V19H7M4,16.5A1.5,1.5 0 0,1 5.5,18A1.5,1.5 0 0,1 4,19.5A1.5,1.5 0 0,1 2.5,18A1.5,1.5 0 0,1 4,16.5Z"/></svg> Sezioni
						</h2>
						<button type="button" @click="addSection" class="btn-secondary btn-compact inline-flex items-center gap-[4px]">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[16px] h-[16px]" fill="currentColor"><path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg> Aggiungi sezione
						</button>
					</div>

					<div class="space-y-[16px]">
						<div v-for="(section, idx) in form.sections" :key="idx" class="p-[16px] rounded-[12px] border border-[#E9EBEC] bg-[#FAFBFC]">
							<div class="flex items-center justify-between mb-[12px]">
								<span class="text-[0.8125rem] font-semibold text-[#252B42]">Sezione {{ idx + 1 }}</span>
								<button type="button" v-if="form.sections.length > 1"  @click="removeSection(idx)" class="btn-danger btn-compact inline-flex items-center justify-center !px-0 !py-0 !w-[32px] !h-[32px]">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[16px] h-[16px]" fill="currentColor"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/></svg>
								</button>
							</div>
							<div class="space-y-[10px]">
								<input v-model="section.heading" type="text" class="form-input" placeholder="Titolo sezione" />
								<textarea v-model="section.text" rows="4" class="form-input resize-none" placeholder="Contenuto della sezione"></textarea>
							</div>
						</div>
					</div>
				</div>

				<!-- Save -->
				<div class="flex justify-end">
					<button type="button" @click="saveGuide" :disabled="saving" class="btn-cta btn-compact inline-flex items-center gap-[8px] disabled:opacity-50">
						<svg v-if="saving" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] animate-spin" fill="currentColor"><path d="M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z"/></svg>
						<svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px]" fill="currentColor"><path d="M15,9H5V5H15M12,19A3,3 0 0,1 9,16A3,3 0 0,1 12,13A3,3 0 0,1 15,16A3,3 0 0,1 12,19M17,3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3Z"/></svg>
						{{ saving ? "Salvataggio..." : "Crea guida" }}
					</button>
				</div>
			</div>
		</div>
	</section>
</template>
