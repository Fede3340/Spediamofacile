<!--
  PAGINA: Guide (guide/index.vue)
  Pagina che elenca tutte le guide disponibili per gli utenti di SpediamoFacile.
  Design allineato al Prototipo: card gradient bg, icon box, time/category badges.
  API: GET /api/public/guides — con fallback hardcoded.
-->
<script setup>
useSeoMeta({
	title: 'Guide alle Spedizioni | SpediamoFacile',
	ogTitle: 'Guide alle Spedizioni | SpediamoFacile',
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

// Fill-based SVG paths (24x24 viewBox) per uso in icon boxes con sfondo tintato
const guideIconPaths = {
	'come-preparare-un-pacco': 'M21,16.5C21,16.88 20.79,17.21 20.47,17.38L12.57,21.82C12.41,21.94 12.21,22 12,22C11.79,22 11.59,21.94 11.43,21.82L3.53,17.38C3.21,17.21 3,16.88 3,16.5V7.5C3,7.12 3.21,6.79 3.53,6.62L11.43,2.18C11.59,2.06 11.79,2 12,2C12.21,2 12.41,2.06 12.57,2.18L20.47,6.62C20.79,6.79 21,7.12 21,7.5V16.5M12,4.15L6.04,7.5L12,10.85L17.96,7.5L12,4.15M5,15.91L11,19.29V12.58L5,9.21V15.91M19,15.91V9.21L13,12.58V19.29L19,15.91Z',
	'imballare-oggetti-fragili': 'M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M10,17L6,13L7.41,11.59L10,14.17L16.59,7.58L18,9L10,17Z',
	'dimensioni-pesi-massimi': 'M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3M9,17H7V10H9V17M13,17H11V7H13V17M17,17H15V13H17V17Z',
	'tracciare-spedizione': 'M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z',
	'pacco-danneggiato': 'M11,15H13V17H11V15M11,7H13V13H11V7M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20Z',
	'spedire-elettronica': 'M20,18C21.1,18 22,17.1 22,16V6C22,4.89 21.1,4 20,4H4C2.89,4 2,4.89 2,6V16A2,2 0 0,0 4,18H0V20H24V18M4,6H20V16H4Z',
	'contrassegno': 'M20,4H4A2,2 0 0,0 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6A2,2 0 0,0 20,4M20,11H4V8H20Z',
	'scegliere-corriere': 'M18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5M19.5,9.5L21.46,12H17V9.5M6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5M20,8H17V4H3C1.89,4 1,4.89 1,6V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V12L20,8Z',
	'nazionali-vs-internazionali': 'M17.9,17.39C17.64,16.59 16.89,16 16,16H15V13A1,1 0 0,0 14,12H8V10H10A1,1 0 0,0 11,9V7H13A2,2 0 0,0 15,5V4.59C17.93,5.77 20,8.64 20,12C20,14.08 19.2,15.97 17.9,17.39M11,19.93C7.05,19.44 4,16.08 4,12C4,11.38 4.08,10.78 4.21,10.21L9,15V16A2,2 0 0,0 11,18M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z',
	'risparmiare-spedizioni': 'M11.8,10.9C9.53,10.31 8.8,9.7 8.8,8.75C8.8,7.66 9.81,6.9 11.5,6.9C13.28,6.9 13.94,7.75 14,9H16.21C16.14,7.28 15.09,5.7 13,5.19V3H10V5.16C8.06,5.58 6.5,6.84 6.5,8.77C6.5,11.08 8.41,12.23 11.2,12.9C13.7,13.5 14.2,14.38 14.2,15.31C14.2,16 13.71,17.1 11.5,17.1C9.44,17.1 8.63,16.18 8.5,15H6.32C6.44,17.19 8.08,18.42 10,18.83V21H13V18.85C14.95,18.5 16.5,17.35 16.5,15.3C16.5,12.46 14.07,11.49 11.8,10.9Z',
	'documenti-necessari': 'M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M9,13V19H7V13H9M15,15V19H17V15H15M11,11V19H13V11H11Z',
	'cosa-non-spedire': 'M12,2C17.53,2 22,6.47 22,12C22,17.53 17.53,22 12,22C6.47,22 2,17.53 2,12C2,6.47 6.47,2 12,2M15.59,7L12,10.59L8.41,7L7,8.41L10.59,12L7,15.59L8.41,17L12,13.41L15.59,17L17,15.59L13.41,12L17,8.41L15.59,7Z',
	'ritiro-domicilio': 'M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z',
	'assicurazione-spedizione': 'M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M10,17L6,13L7.41,11.59L10,14.17L16.59,7.58L18,9L10,17Z',
	'faq-ecommerce': 'M20,2H4A2,2 0 0,0 2,4V22L6,18H20A2,2 0 0,0 22,16V4A2,2 0 0,0 20,2M11,14H9V12H11V14M11,10H9C9,6.75 12,7 12,5A2,2 0 0,0 8,5H6A2,2 0 0,0 8,9H8C8,12.25 5,12 5,14H7A2,2 0 0,0 11,14M15,14H13V12H15V14M15,10H13V8H15V10Z',
};

const guideColors = {
	'come-preparare-un-pacco': '#E44203',
	'imballare-oggetti-fragili': '#095866',
	'dimensioni-pesi-massimi': '#095866',
	'tracciare-spedizione': '#E44203',
	'pacco-danneggiato': '#095866',
	'spedire-elettronica': '#095866',
	'contrassegno': '#E44203',
	'scegliere-corriere': '#095866',
	'nazionali-vs-internazionali': '#095866',
	'risparmiare-spedizioni': '#E44203',
	'documenti-necessari': '#095866',
	'cosa-non-spedire': '#E44203',
	'ritiro-domicilio': '#095866',
	'assicurazione-spedizione': '#095866',
	'faq-ecommerce': '#E44203',
};

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
	'come-preparare-un-pacco': '5 min',
	'imballare-oggetti-fragili': '4 min',
	'dimensioni-pesi-massimi': '3 min',
	'tracciare-spedizione': '2 min',
	'pacco-danneggiato': '4 min',
	'spedire-elettronica': '5 min',
	'contrassegno': '6 min',
	'scegliere-corriere': '4 min',
	'nazionali-vs-internazionali': '5 min',
	'risparmiare-spedizioni': '3 min',
	'documenti-necessari': '4 min',
	'cosa-non-spedire': '3 min',
	'ritiro-domicilio': '3 min',
	'assicurazione-spedizione': '4 min',
	'faq-ecommerce': '6 min',
};

const sanctum = useSanctumClient();
const guides = ref(fallbackGuides);

onMounted(async () => {
	try {
		const res = await sanctum('/api/public/guides');
		const data = res?.data || res;
		if (Array.isArray(data) && data.length > 0) guides.value = data;
	} catch {}
});

const getGuideColor = (guide) => guideColors[guide.slug] || '#095866';
const getGuideCategory = (guide) => guide.type || guideCategories[guide.slug] || 'Guida';
const getGuideTime = (guide) => guideTimes[guide.slug] || '4 min';
const getGuideIconPath = (guide) => guideIconPaths[guide.slug] || guideIconPaths['documenti-necessari'];
const getDescription = (guide) => guide.meta_description || guide.description || guide.intro || '';
</script>

<template>
	<div class="py-[32px] sm:py-[48px]" style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%); min-height: 100vh">
		<div class="my-container">

			<!-- Breadcrumb -->
			<nav aria-label="Breadcrumb" class="flex items-center gap-[6px] text-[0.8125rem] text-[var(--color-brand-text-muted)] mb-[24px]">
				<NuxtLink to="/" class="transition-colors hover:text-[#095866]">Home</NuxtLink>
				<span aria-hidden="true">/</span>
				<span class="font-semibold text-[#095866]">Guide</span>
			</nav>

			<!-- Header centrato -->
			<div class="text-center max-w-[600px] mx-auto mb-[32px] sm:mb-[40px]">
				<div class="w-[48px] h-[48px] rounded-full flex items-center justify-center mx-auto mb-[14px]"
					style="background: linear-gradient(135deg, #095866, #0a7489); box-shadow: 0 4px 14px rgba(9,88,102,0.2)">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="white">
						<path d="M19,2L14,6.5V17.5L19,13V2M6.5,5C4.55,5 2.45,5.4 1,6.5V21.16C1,21.41 1.25,21.66 1.5,21.66C1.6,21.66 1.65,21.59 1.75,21.59C3.1,20.94 5.05,20.5 6.5,20.5C8.45,20.5 10.55,20.9 12,22C13.35,21.15 15.8,20.5 17.5,20.5C19.15,20.5 20.85,20.81 22.25,21.56C22.35,21.61 22.4,21.59 22.5,21.59C22.75,21.59 23,21.34 23,21.09V6.5C22.4,6.05 21.75,5.75 21,5.5V19C19.9,18.65 18.7,18.5 17.5,18.5C15.8,18.5 13.35,19.15 12,20V6.5C10.55,5.4 8.45,5 6.5,5Z" />
					</svg>
				</div>
				<h1 class="text-[#1d2738] text-[28px] sm:text-[36px] tracking-[-0.8px] font-montserrat" style="font-weight:800">
					Guide e risorse
				</h1>
				<p class="text-[#777] text-[15px] sm:text-[16px] mt-[8px] leading-[1.5]">
					Tutto quello che ti serve sapere per spedire in modo efficiente e senza stress.
				</p>
			</div>

			<!-- Grid guide -->
			<div v-if="guides.length" class="grid grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-3 gap-[16px] mb-[36px]">
				<NuxtLink
					v-for="guide in guides"
					:key="guide.slug"
					:to="`/guide/${guide.slug}`"
					class="rounded-[22px] overflow-hidden cursor-pointer group transition-all duration-[350ms] hover:ring-[2px] hover:ring-[#095866]/50 hover:shadow-[0_4px_16px_rgba(9,88,102,0.06)] no-underline block"
					style="box-shadow: 0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04)"
				>
					<div class="p-[22px] sm:p-[24px] flex flex-col h-full"
						style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)">
						<!-- Riga icon + badges -->
						<div class="flex items-center justify-between mb-[14px]">
							<div
								class="w-[44px] h-[44px] rounded-[12px] flex items-center justify-center shrink-0"
								:style="{ background: getGuideColor(guide) + '1a' }"
							>
								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
									:fill="getGuideColor(guide)">
									<path :d="getGuideIconPath(guide)" />
								</svg>
							</div>
							<div class="flex items-center gap-[6px]">
								<span class="text-[#999] text-[11px] flex items-center gap-[3px]" style="font-weight:500">
									<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
										<path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" />
									</svg>
									{{ getGuideTime(guide) }}
								</span>
								<span
									class="text-[10px] px-[8px] py-[3px] rounded-full text-[#777]"
									style="font-weight:600; background:#E6E9EE">
									{{ getGuideCategory(guide) }}
								</span>
							</div>
						</div>
						<!-- Titolo -->
						<h3 class="text-[#1d2738] text-[16px] tracking-[-0.2px] mb-[6px] group-hover:text-[#095866] transition-colors duration-[350ms]"
							style="font-weight:700">
							{{ guide.title }}
						</h3>
						<!-- Descrizione -->
						<p class="text-[#999] text-[13px] leading-[1.5] mb-[14px] flex-1">{{ getDescription(guide) }}</p>
						<!-- Link -->
						<span
							class="inline-flex items-center gap-[4px] text-[#095866] text-[13px] group-hover:gap-[8px] transition-all duration-[350ms]"
							style="font-weight:600">
							Leggi guida
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
								<polyline points="9 18 15 12 9 6" />
							</svg>
						</span>
					</div>
				</NuxtLink>
			</div>

			<!-- Empty state -->
			<div v-else class="rounded-[22px] border border-dashed border-[#D6E3E7] bg-white px-[20px] py-[28px] text-center mb-[36px]"
				style="box-shadow: 0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06)">
				<h2 class="font-montserrat text-[1.2rem] text-[#1d2738]" style="font-weight:800">Le guide sono in preparazione</h2>
				<p class="mx-auto mt-[10px] max-w-[56ch] text-[0.9rem] leading-[1.65] text-[#777]">
					Le guide pratiche compariranno qui non appena verranno pubblicate dal pannello amministrativo.
				</p>
			</div>

			<!-- CTA bottom -->
			<div class="text-center mb-[16px]">
				<NuxtLink
					to="/preventivo"
					class="inline-flex items-center gap-[8px] h-[50px] px-[28px] rounded-full text-white text-[15px] transition-all duration-[350ms] hover:shadow-[0_8px_28px_rgba(228,66,3,0.3)] hover:-translate-y-[1px]"
					style="font-weight:700; background: linear-gradient(135deg, #E44203, #c73600); box-shadow: 0 4px 16px rgba(228,66,3,0.22)"
				>
					Calcola preventivo
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
					</svg>
				</NuxtLink>
			</div>

		</div>
	</div>
</template>
