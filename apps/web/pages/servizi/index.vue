<!--
  PAGINA: Servizi (servizi/index.vue)
  Orchestratore thin: hero + grid + cta + faq.
  Logica card + cta + faq in components/servizi/*.
  API: GET /api/public/services — con fallback hardcoded.
-->
<script setup>
// CSS split route-specific: servizi.css usato solo in /servizi/* + /chi-siamo.
import '~/assets/css/servizi.css';

useSeoMeta({
	title: 'Servizi di Spedizione',
	ogTitle: 'Servizi di Spedizione',
	description: 'Scopri i servizi di spedizione SpediamoFacile: ritiro a domicilio, senza etichetta, contrassegno, assicurazione e supporto rapido.',
	ogDescription: 'Servizi chiari, attivabili in pochi secondi e pensati per spedire meglio senza complicazioni.',
});

// Breadcrumb: Home › Servizi
useBreadcrumbSchema([
	{ name: 'Home', url: '/' },
	{ name: 'Servizi' },
]);

/* Image + badge metadata per slug */
const serviceMeta = {
	'pagamento-alla-consegna': {
		badge: 'Opzionale', badgeColor: '#5F7C84', readTime: '4 min', visualTone: 'accent',
		highlights: ['Incasso alla consegna', 'Rimborso tracciato'],
	},
	'spedizione-senza-etichetta': {
		badge: 'Opzionale', badgeColor: '#5F7C84', readTime: '2 min', visualTone: 'primary',
		highlights: ['Nessuna stampante', 'Etichetta gestita dal corriere'],
	},
	'ritiro-a-domicilio': {
		badge: 'Servizio principale', badgeColor: '#095866', readTime: '3 min', visualTone: 'primary',
		highlights: ['Data e fascia ritiro', 'Gestione da casa o ufficio'],
	},
	'assicurazione': {
		badge: 'Opzionale', badgeColor: '#5F7C84', readTime: '3 min', visualTone: 'soft',
		highlights: ['Valore protetto', 'Copertura su danni e smarrimento'],
	},
	'assicurazione-spedizione': {
		badge: 'Opzionale', badgeColor: '#5F7C84', readTime: '3 min', visualTone: 'soft',
		highlights: ['Valore protetto', 'Copertura su danni e smarrimento'],
	},
	'sponda-idraulica': {
		badge: 'Opzionale', badgeColor: '#5F7C84', readTime: '3 min', visualTone: 'soft',
		highlights: ['Colli pesanti', 'Carico e scarico assistiti'],
	},
	'spedizione-programmata': {
		badge: 'Opzionale', badgeColor: '#5F7C84', readTime: '3 min', visualTone: 'soft',
		highlights: ['Ritiro pianificato', 'Più controllo sul flusso'],
	},
	'tracking-live': {
		badge: 'Incluso', badgeColor: '#095866', readTime: '2 min', visualTone: 'primary',
		highlights: ['Aggiornamenti live', 'Stato sempre visibile'],
	},
	'assistenza': {
		badge: 'Incluso', badgeColor: '#095866', readTime: '2 min', visualTone: 'soft',
		highlights: ['Supporto rapido', 'Risposte tracciabili'],
	},
	'assistenza-rapida': {
		badge: 'Incluso', badgeColor: '#095866', readTime: '2 min', visualTone: 'soft',
		highlights: ['Supporto rapido', 'Risposte tracciabili'],
	},
	'chiamata-pre-consegna': {
		badge: 'Opzionale', badgeColor: '#5F7C84', readTime: '2 min', visualTone: 'soft',
		highlights: ['Preavviso consegna', 'Meno tentativi a vuoto'],
	},
	'punti-fedelta': {
		badge: 'Vantaggio', badgeColor: '#095866', readTime: '2 min', visualTone: 'accent',
		highlights: ['Programma punti', 'Vantaggi ricorrenti'],
	},
};

const defaultMeta = {
	badge: 'Servizio', badgeColor: '#095866', readTime: '3 min', visualTone: 'primary',
	highlights: ['Attivabile nel preventivo', 'Solo BRT'],
};

const fallbackServices = [
	{ slug: 'ritiro-a-domicilio', title: 'Ritiro a domicilio', description: 'Prenoti online, scegli data e indirizzo e lasci che il corriere passi direttamente da te.' },
	{ slug: 'spedizione-senza-etichetta', title: 'Spedizione senza etichetta', description: 'Non hai una stampante? Il corriere porta e applica l\'etichetta al momento del ritiro.' },
	{ slug: 'pagamento-alla-consegna', title: 'Contrassegno', description: 'Fai pagare il destinatario alla consegna. Incasso in contanti o assegno con rimborso automatico.' },
	{ slug: 'assicurazione', title: 'Assicurazione spedizione', description: 'Proteggi il valore della tua spedizione con la copertura completa contro danni e smarrimento.' },
	{ slug: 'sponda-idraulica', title: 'Sponda idraulica', description: 'Servizio aggiuntivo per spedizioni pesanti che necessitano di carico e scarico con sponda.' },
	{ slug: 'spedizione-programmata', title: 'Spedizione programmata', description: 'Scegli il giorno esatto in cui vuoi che il corriere ritiri il tuo pacco. Massima flessibilita.' },
];

const faqItems = ref([
	{
		question: 'Posso attivare i servizi dopo aver creato la spedizione?',
		answer: 'Tutti i servizi opzionali sono selezionabili durante il processo di preventivo. Una volta confermata la spedizione, i servizi attivi non possono essere modificati.',
		open: false,
	},
	{
		question: 'Il ritiro a domicilio ha costi aggiuntivi?',
		answer: 'Il ritiro a domicilio e incluso nel prezzo base della spedizione. Non ci sono costi nascosti o supplementi per il servizio di ritiro.',
		open: false,
	},
	{
		question: 'Come funziona l\'assicurazione?',
		answer: 'L\'assicurazione copre danni e smarrimento fino al valore dichiarato del contenuto. In caso di sinistro, la pratica viene gestita direttamente da noi con il corriere.',
		open: false,
	},
]);

const sanctum = useSanctumClient();
const services = ref(fallbackServices);

onMounted(async () => {
	try {
		const res = await sanctum('/api/public/services');
		const data = res?.data || res;
		if (Array.isArray(data) && data.length > 0) services.value = data;
	} catch (e) {
		// Servizi opzionali: se l'endpoint fallisce restano i fallback statici. Log solo dev.
		if (import.meta.dev) console.warn('[servizi] fetch /api/public/services fallita', e);
	}
});

const getServiceMeta = (service) => serviceMeta[service.slug] || defaultMeta;
const getServiceDescription = (service) => service.description || service.meta_description || service.intro || '';
const getServiceHighlights = (service) => getServiceMeta(service).highlights || defaultMeta.highlights;
const getServiceVisualTone = (service) => getServiceMeta(service).visualTone || defaultMeta.visualTone;
const toggleFaq = (index) => { faqItems.value[index].open = !faqItems.value[index].open; };
</script>

<template>
	<div class="sv-page">
		<PublicPageHeader
			:crumbs="[{ label: 'Home', to: '/' }, { label: 'Servizi' }]"
			eyebrow="Servizi di spedizione"
			title="I nostri servizi"
			description="Ritiro, tracking, contrassegno e supporto: tutto attivabile direttamente nel preventivo.">
			<template #icon>
				<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
					<path d="M18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5M19.5,9.5L21.46,12H17V9.5M6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5M20,8H17V4H3C1.89,4 1,4.89 1,6V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V12L20,8Z"/>
				</svg>
			</template>
		</PublicPageHeader>

		<ServizioGrid
			:services="services"
			:get-meta="getServiceMeta"
			:get-highlights="getServiceHighlights"
			:get-visual-tone="getServiceVisualTone"
			:get-description="getServiceDescription"
		/>

		<ServizioFaq :items="faqItems" @toggle="toggleFaq" />
	</div>
</template>

<style scoped>
.sv-page {
	min-height: 100vh;
	background: var(--surface-muted, #f5f6f9);
}
</style>
