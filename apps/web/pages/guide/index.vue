<!--
  PAGINA: Guide (guide/index.vue)
  Orchestratore thin: hero + filtri + lista + newsletter + CTA.
  Logica card + filtri in components/guide/*.
  API: GET /api/public/guides — con fallback hardcoded.
-->
<script setup>
useSeoMeta({
	title: 'Guide alle Spedizioni',
	ogTitle: 'Guide alle Spedizioni',
	description: 'Consulta le nostre guide pratiche su come preparare pacchi, scegliere il corriere giusto, risparmiare sulle spedizioni e molto altro.',
	ogDescription: 'Guide pratiche per spedire in modo semplice e conveniente con SpediamoFacile.',
});

useHead({
	script: [{
		type: 'application/ld+json',
		innerHTML: JSON.stringify({
			'@context': 'https://schema.org',
			'@type': 'CollectionPage',
			name: 'Guide alle Spedizioni - SpediamoFacile',
			url: 'https://spediamofacile.it/guide',
			description: 'Raccolta di guide pratiche per spedire pacchi in Italia e all\'estero.',
			mainEntity: { '@type': 'Organization', name: 'SpediamoFacile', url: 'https://spediamofacile.it' },
		}),
	}],
});

// Breadcrumb: Home › Guide
useBreadcrumbSchema([
	{ name: 'Home', url: '/' },
	{ name: 'Guide' },
]);

const fallbackGuides = [
	{ slug: 'come-preparare-un-pacco', title: 'Come preparare un pacco per la spedizione', meta_description: 'Scopri come preparare correttamente un pacco per garantire che arrivi a destinazione in perfette condizioni.' },
	{ slug: 'imballare-oggetti-fragili', title: 'Come imballare oggetti fragili', meta_description: 'Tecniche e materiali per proteggere al meglio oggetti delicati e fragili durante il trasporto.' },
	{ slug: 'dimensioni-pesi-massimi', title: 'Guida alle dimensioni e pesi massimi', meta_description: 'Tutto quello che devi sapere sui limiti di peso e dimensioni accettati dai principali corrieri.' },
	{ slug: 'tracciare-spedizione', title: 'Come tracciare la tua spedizione', meta_description: 'Impara a seguire il tuo pacco in tempo reale dalla partenza fino alla consegna.' },
	{ slug: 'pacco-danneggiato', title: 'Cosa fare se il pacco è danneggiato', meta_description: 'La procedura da seguire in caso di pacco danneggiato: documentazione, reclamo e rimborso.' },
	{ slug: 'spedire-elettronica', title: 'Spedire elettronica in sicurezza', meta_description: 'Come imballare e spedire dispositivi elettronici senza rischi di danni.' },
	{ slug: 'contrassegno', title: 'Guida al contrassegno', meta_description: 'Come funziona il pagamento alla consegna: vantaggi, limiti e procedura completa.' },
	{ slug: 'scegliere-corriere', title: 'Come scegliere il corriere giusto', meta_description: 'Criteri e consigli per selezionare il corriere più adatto alle tue esigenze di spedizione.' },
	{ slug: 'nazionali-vs-internazionali', title: 'Spedizioni nazionali vs internazionali', meta_description: 'Le differenze principali tra spedizioni in Italia e all\'estero: tempi, costi e documentazione.' },
	{ slug: 'risparmiare-spedizioni', title: 'Come risparmiare sulle spedizioni', meta_description: 'Strategie pratiche per ridurre i costi di spedizione senza rinunciare alla qualità del servizio.' },
	{ slug: 'documenti-necessari', title: 'Documenti necessari per la spedizione', meta_description: 'Quali documenti servono per spedire in Italia e all\'estero: guida completa.' },
	{ slug: 'cosa-non-spedire', title: 'Cosa non si può spedire', meta_description: 'L\'elenco degli oggetti vietati o soggetti a restrizioni nelle spedizioni nazionali e internazionali.' },
	{ slug: 'ritiro-domicilio', title: 'Come funziona il ritiro a domicilio', meta_description: 'Tutto sul servizio di ritiro a domicilio: come prenotarlo, preparare il pacco e cosa aspettarsi.' },
	{ slug: 'assicurazione-spedizione', title: 'Assicurazione sulla spedizione', meta_description: 'Quando conviene assicurare un pacco, quanto costa e come funziona la copertura.' },
	{ slug: 'faq-ecommerce', title: 'FAQ sulle spedizioni e-commerce', meta_description: 'Risposte alle domande più frequenti per chi gestisce un negozio online e deve spedire regolarmente.' },
];

const guideFallbackImage = '/images/placeholders/guide-cover.svg';
const guideImages = {};
const defaultImage = guideFallbackImage;

const guideCategories = {
	'come-preparare-un-pacco': 'Base',
	'imballare-oggetti-fragili': 'Base',
	'dimensioni-pesi-massimi': 'Logistica',
	'tracciare-spedizione': 'Logistica',
	'pacco-danneggiato': 'Supporto',
	'spedire-elettronica': 'Avanzato',
	'contrassegno': 'Avanzato',
	'scegliere-corriere': 'Base',
	'nazionali-vs-internazionali': 'Logistica',
	'risparmiare-spedizioni': 'Base',
	'documenti-necessari': 'Docs',
	'cosa-non-spedire': 'Normativa',
	'ritiro-domicilio': 'Logistica',
	'assicurazione-spedizione': 'Avanzato',
	'faq-ecommerce': 'E-commerce',
};

const guideTimes = {
	'come-preparare-un-pacco': '5 min', 'imballare-oggetti-fragili': '4 min',
	'dimensioni-pesi-massimi': '3 min', 'tracciare-spedizione': '2 min',
	'pacco-danneggiato': '4 min', 'spedire-elettronica': '5 min',
	'contrassegno': '6 min', 'scegliere-corriere': '4 min',
	'nazionali-vs-internazionali': '5 min', 'risparmiare-spedizioni': '3 min',
	'documenti-necessari': '4 min', 'cosa-non-spedire': '3 min',
	'ritiro-domicilio': '3 min', 'assicurazione-spedizione': '4 min',
	'faq-ecommerce': '6 min',
};

const categoryColors = {
	Base: { bg: '#095866', text: '#fff' },
	Logistica: { bg: '#0B7D92', text: '#fff' },
	Supporto: { bg: '#095866', text: '#fff' },
	Avanzato: { bg: '#1d2738', text: '#fff' },
	Docs: { bg: '#5C6473', text: '#fff' },
	Normativa: { bg: '#0E6B5E', text: '#fff' },
	'E-commerce': { bg: '#095866', text: '#fff' },
};

const getImage = (guide) => guideImages[guide.slug] || defaultImage;
const getCategory = (guide) => guide.type || guideCategories[guide.slug] || 'Guida';
const getTime = (guide) => guideTimes[guide.slug] || '4 min';
const getDescription = (guide) => guide.meta_description || guide.description || guide.intro || '';
const getCategoryColor = (guide) => {
	const cat = getCategory(guide);
	return categoryColors[cat] || { bg: '#095866', text: '#fff' };
};

const applyGuideFallback = (event) => {
	const target = event?.target;
	if (!(target instanceof HTMLImageElement)) return;
	if (target.dataset.fallbackApplied === 'true') return;
	target.dataset.fallbackApplied = 'true';
	target.src = guideFallbackImage;
};

const sanctum = useSanctumClient();
const guides = ref(fallbackGuides);
const searchQuery = ref('');
const activeCategory = ref('Tutte');

onMounted(async () => {
	try {
		const res = await sanctum('/api/public/guides');
		const data = res?.data || res;
		if (Array.isArray(data) && data.length > 0) guides.value = data;
	} catch (e) {
		// Guide opzionali: se l'endpoint fallisce restano i fallback statici. Log solo dev.
		if (import.meta.dev) console.warn('[guide] fetch /api/public/guides fallita', e);
	}
});

const allCategories = computed(() => {
	const cats = new Set();
	guides.value.forEach(g => cats.add(getCategory(g)));
	return ['Tutte', ...Array.from(cats)];
});

const filteredGuides = computed(() => {
	let result = guides.value;
	if (activeCategory.value !== 'Tutte') {
		result = result.filter(g => getCategory(g) === activeCategory.value);
	}
	if (searchQuery.value.trim()) {
		const q = searchQuery.value.toLowerCase().trim();
		result = result.filter(g =>
			g.title.toLowerCase().includes(q) ||
			getDescription(g).toLowerCase().includes(q)
		);
	}
	return result;
});

const featuredGuide = computed(() => filteredGuides.value[0] || null);
const remainingGuides = computed(() => filteredGuides.value.slice(1));

const resetFilters = () => {
	searchQuery.value = '';
	activeCategory.value = 'Tutte';
};
</script>

<template>
	<div class="guide-page">
		<PublicPageHeader
			:crumbs="[{ label: 'Home', to: '/' }, { label: 'Guide' }]"
			eyebrow="Guide e risorse"
			title="Guide e risorse"
			description="Tutorial pratici, regole utili e risposte rapide per spedire senza errori.">
			<template #icon>
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
					<path d="M19 3H5c-1.1 0-2 .9-2 2v14a2 2 0 0 0 2 2h11l5-5V5c0-1.1-.9-2-2-2Zm0 12h-4v4H5V5h14v10Z" />
				</svg>
			</template>
		</PublicPageHeader>

		<GuideFilters
			v-model:search-query="searchQuery"
			v-model:active-category="activeCategory"
			:all-categories="allCategories"
		/>

		<GuideList
			:featured-guide="featuredGuide"
			:remaining-guides="remainingGuides"
			:filtered-guides-length="filteredGuides.length"
			:get-image="getImage"
			:get-category="getCategory"
			:get-time="getTime"
			:get-description="getDescription"
			:get-category-color="getCategoryColor"
			:apply-fallback="applyGuideFallback"
			@reset-filters="resetFilters"
		/>

		<GuideNewsletter />
	</div>
</template>

<style scoped>
.guide-page {
	min-height: 100vh;
	background: var(--surface-muted, #f5f6f9);
}
</style>
