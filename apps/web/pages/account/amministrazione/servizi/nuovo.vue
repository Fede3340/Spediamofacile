<!-- FILE: pages/account/amministrazione/servizi/nuovo.vue -->
<script setup>
definePageMeta({
	middleware: ["app-auth", "admin"],
});

useSeoMeta({
	title: 'Admin - Nuovo Servizio',
	robots: 'noindex, nofollow',
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
	faqs: [{ title: '', text: '' }],
	is_published: false,
	type: 'service',
});

const hasFilledText = (value) => Boolean(String(value || '').trim());

const sectionCount = computed(() => form.value.sections.length);
const faqCount = computed(() => form.value.faqs.length);
const publishStateLabel = computed(() => (form.value.is_published ? 'Pubblicato' : 'Bozza'));
const publishStateHint = computed(() =>
	form.value.is_published
		? 'Il servizio sara visibile appena completi il salvataggio.'
		: 'Puoi salvarlo ora e rifinire contenuti e FAQ con calma.',
);
const checklistItems = computed(() => [
	{
		label: 'Titolo e URL pronti',
		done: hasFilledText(form.value.title) && hasFilledText(form.value.slug),
	},
	{
		label: 'Introduzione compilata',
		done: hasFilledText(form.value.intro),
	},
	{
		label: 'Almeno una sezione completa',
		done: form.value.sections.some((section) => hasFilledText(section.heading) && hasFilledText(section.text)),
	},
	{
		label: 'Almeno una FAQ completa',
		done: form.value.faqs.some((faq) => hasFilledText(faq.title) && hasFilledText(faq.text)),
	},
]);
const completedChecklistCount = computed(() => checklistItems.value.filter((item) => item.done).length);

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
		await sanctum("/api/admin/articles", { method: "POST", body: form.value });
		showSuccess("Servizio creato con successo.");
		setTimeout(() => { router.push('/account/amministrazione/servizi'); }, 800);
	} catch (e) { showError(e, "Errore durante la creazione del servizio."); }
	finally { saving.value = false; }
};

// Icone SVG per i due stack (sezioni / faq)
const SECTIONS_ICON_PATH = 'M7,5H21V7H7V5M7,13V11H21V13H7M4,4.5A1.5,1.5 0 0,1 5.5,6A1.5,1.5 0 0,1 4,7.5A1.5,1.5 0 0,1 2.5,6A1.5,1.5 0 0,1 4,4.5M4,10.5A1.5,1.5 0 0,1 5.5,12A1.5,1.5 0 0,1 4,13.5A1.5,1.5 0 0,1 2.5,12A1.5,1.5 0 0,1 4,10.5M7,19V17H21V19H7M4,16.5A1.5,1.5 0 0,1 5.5,18A1.5,1.5 0 0,1 4,19.5A1.5,1.5 0 0,1 2.5,18A1.5,1.5 0 0,1 4,16.5Z';
const FAQ_ICON_PATH = 'M18,15H6L2,19V3A1,1 0 0,1 3,2H18A1,1 0 0,1 19,3V14A1,1 0 0,1 18,15M23,9V23L19,19H8A1,1 0 0,1 7,18V17H21V8H22A1,1 0 0,1 23,9Z';
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-6 tablet:py-7 desktop:py-7">
		<div class="my-container sf-stack-section">
			<AccountPageHeader
				eyebrow="Area amministrazione"
				title="Nuovo servizio"
				description="Crea un servizio con sezioni e FAQ, salva in bozza o pubblica."
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Amministrazione', to: '/account/amministrazione' },
					{ label: 'Servizi', to: '/account/amministrazione/servizi' },
					{ label: 'Nuovo servizio' },
				]"
				back-to="/account/amministrazione/servizi"
				back-label="Torna ai servizi" />

			<ServizioFormOverview
				:publish-state-label="publishStateLabel"
				:publish-state-hint="publishStateHint"
				:section-count="sectionCount"
				:faq-count="faqCount" />

			<AdminActionBanner :message="actionMessage?.text || ''" :tone="actionMessage?.type || ''" />

			<div class="grid grid-cols-1 desktop:grid-cols-[minmax(0,1fr)_320px] gap-5 items-start mt-2">
				<div class="grid gap-5 min-w-0">
					<ServizioFormBase
						:title="form.title"
						:slug="form.slug"
						:meta-description="form.meta_description"
						:intro="form.intro"
						:is-published="form.is_published"
						:publish-state-hint="publishStateHint"
						@update:title="form.title = $event"
						@update:slug="form.slug = $event"
						@update:meta-description="form.meta_description = $event"
						@update:intro="form.intro = $event"
						@update:is-published="form.is_published = $event"
						@slug-from-title="generateSlug" />

					<ServizioFormStack
						:items="form.sections"
						panel-title="Sezioni"
						panel-description="Organizza il contenuto in blocchi leggibili."
						index-label="Sezione"
						add-button-label="Aggiungi sezione"
						heading-placeholder="Titolo sezione"
						text-placeholder="Contenuto della sezione"
						:text-rows="5"
						:icon-path="SECTIONS_ICON_PATH"
						field-key="heading"
						@update:items="form.sections = $event"
						@add="addSection"
						@remove="removeSection" />

					<ServizioFormStack
						:items="form.faqs"
						panel-title="FAQ"
						panel-description="Risposte brevi ai dubbi ricorrenti."
						index-label="FAQ"
						add-button-label="Aggiungi FAQ"
						heading-placeholder="Domanda"
						text-placeholder="Risposta"
						:text-rows="4"
						:icon-path="FAQ_ICON_PATH"
						field-key="title"
						@update:items="form.faqs = $event"
						@add="addFaq"
						@remove="removeFaq" />
				</div>

				<ServizioFormSummary
					:checklist-items="checklistItems"
					:completed-count="completedChecklistCount"
					:saving="saving"
					submit-label="Crea servizio"
					@save="saveService" />
			</div>
		</div>
	</section>
</template>
