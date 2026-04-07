<!--
  PAGINA: Servizi (servizi/index.vue)
  Design allineato al Prototipo: Zap badge hero, stats strip 4 col,
  article cards con icon+category+time badge, teal gradient bottom CTA.
  API: GET /api/public/services — con fallback hardcoded.
-->
<script setup>
useSeoMeta({
	title: 'Servizi di Spedizione | SpediamoFacile',
	ogTitle: 'Servizi di Spedizione | SpediamoFacile',
	description: 'Scopri i servizi di spedizione SpediamoFacile: ritiro a domicilio, senza etichetta, contrassegno, assicurazione e supporto rapido.',
	ogDescription: 'Servizi chiari, attivabili in pochi secondi e pensati per spedire meglio senza complicazioni.',
});

// Mappa completa slug → metadati visuali (icon SVG path, accent color, category, readTime)
// Copre tutti i slug noti dall'API + fallback Prototipo
const serviceMeta = {
	'ritiro-a-domicilio': { accent: '#E44203', category: 'Servizio principale', readTime: '3 min', icon: 'M18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5M19.5,9.5L21.46,12H17V9.5M6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5M20,8H17V4H3C1.89,4 1,4.89 1,6V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V12L20,8Z' },
	'spedizione-senza-etichetta': { accent: '#095866', category: 'Servizio opzionale', readTime: '2 min', icon: 'M5.5,7A1.5,1.5 0 0,1 4,5.5A1.5,1.5 0 0,1 5.5,4A1.5,1.5 0 0,1 7,5.5A1.5,1.5 0 0,1 5.5,7M21.41,11.58L12.41,2.58C12.05,2.22 11.55,2 11,2H4C2.89,2 2,2.89 2,4V11C2,11.55 2.22,12.05 2.59,12.41L11.58,21.41C11.95,21.77 12.45,22 13,22C13.55,22 14.05,21.77 14.41,21.41L21.41,14.41C21.78,14.05 22,13.55 22,13C22,12.44 21.77,11.94 21.41,11.58Z' },
	'tracking-live': { accent: '#095866', category: 'Incluso', readTime: '2 min', icon: 'M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z' },
	'pagamento-alla-consegna': { accent: '#E44203', category: 'Servizio opzionale', readTime: '4 min', icon: 'M20,4H4A2,2 0 0,0 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6A2,2 0 0,0 20,4M20,11H4V8H20Z' },
	'assicurazione': { accent: '#095866', category: 'Servizio opzionale', readTime: '3 min', icon: 'M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M10,17L6,13L7.41,11.59L10,14.17L16.59,7.58L18,9L10,17Z' },
	'assicurazione-spedizione': { accent: '#095866', category: 'Servizio opzionale', readTime: '3 min', icon: 'M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M10,17L6,13L7.41,11.59L10,14.17L16.59,7.58L18,9L10,17Z' },
	'assistenza': { accent: '#095866', category: 'Incluso', readTime: '2 min', icon: 'M12,1C7,1 3,5 3,10V17A3,3 0 0,0 6,20H9V12H5V10A7,7 0 0,1 12,3A7,7 0 0,1 19,10V12H15V20H18A3,3 0 0,0 21,17V10C21,5 16.97,1 12,1Z' },
	'assistenza-rapida': { accent: '#095866', category: 'Incluso', readTime: '2 min', icon: 'M12,1C7,1 3,5 3,10V17A3,3 0 0,0 6,20H9V12H5V10A7,7 0 0,1 12,3A7,7 0 0,1 19,10V12H15V20H18A3,3 0 0,0 21,17V10C21,5 16.97,1 12,1Z' },
	'sponda-idraulica': { accent: '#095866', category: 'Servizio opzionale', readTime: '3 min', icon: 'M4,18V14H8V16H10V14H14V18H4M13,13V11H15V9H17V11H19V13H13M4,8V4H14V8H4M6,6V10H12V6H6Z' },
	'spedizione-programmata': { accent: '#095866', category: 'Servizio opzionale', readTime: '3 min', icon: 'M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.9 20.1,3 19,3H18V1M17,13H12V18H17V13Z' },
	'chiamata-pre-consegna': { accent: '#095866', category: 'Servizio opzionale', readTime: '2 min', icon: 'M6.62,10.79C8.06,13.62 10.38,15.94 13.21,17.38L15.41,15.18C15.69,14.9 16.08,14.82 16.43,14.93C17.55,15.3 18.75,15.5 20,15.5A1,1 0 0,1 21,16.5V20A1,1 0 0,1 20,21A17,17 0 0,1 3,4A1,1 0 0,1 4,3H7.5A1,1 0 0,1 8.5,4C8.5,5.25 8.7,6.45 9.07,7.57C9.18,7.92 9.1,8.31 8.82,8.59L6.62,10.79Z' },
	'punti-fedelta': { accent: '#E44203', category: 'Vantaggio', readTime: '2 min', icon: 'M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z' },
};

// Icona/accent/categoria di default se il slug non è mappato
const defaultMeta = { accent: '#095866', category: 'Servizio', readTime: '3 min', icon: 'M18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5M19.5,9.5L21.46,12H17V9.5M6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5M20,8H17V4H3C1.89,4 1,4.89 1,6V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V12L20,8Z' };

const fallbackServices = [
	{ slug: 'ritiro-a-domicilio', title: 'Ritiro a domicilio', description: 'Prenoti online, scegli data e indirizzo e lasci che il corriere passi direttamente da te.' },
	{ slug: 'spedizione-senza-etichetta', title: 'Spedizione senza etichetta', description: 'Non hai una stampante? Il corriere porta e applica l\'etichetta al momento del ritiro.' },
	{ slug: 'tracking-live', title: 'Tracking in tempo reale', description: 'Segui la tua spedizione passo dopo passo con aggiornamenti in tempo reale fino alla consegna.' },
	{ slug: 'pagamento-alla-consegna', title: 'Contrassegno', description: 'Fai pagare il destinatario alla consegna. Incasso in contanti o assegno con rimborso automatico.' },
	{ slug: 'assicurazione', title: 'Assicurazione spedizione', description: 'Proteggi il valore della tua spedizione con la copertura completa contro danni e smarrimento.' },
	{ slug: 'assistenza', title: 'Assistenza dedicata', description: 'Supporto rapido e personalizzato dal preventivo alla consegna. Siamo sempre a disposizione.' },
];

// Stats strip data
const statsData = [
	{
		label: 'Corriere BRT',
		sub: 'Partner ufficiale',
		icon: 'M18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5M19.5,9.5L21.46,12H17V9.5M6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5M20,8H17V4H3C1.89,4 1,4.89 1,6V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V12L20,8Z',
	},
	{
		label: '15 Paesi',
		sub: 'Copertura europea',
		icon: 'M17.9,17.39C17.64,16.59 16.89,16 16,16H15V13A1,1 0 0,0 14,12H8V10H10A1,1 0 0,0 11,9V7H13A2,2 0 0,0 15,5V4.59C17.93,5.77 20,8.64 20,12C20,14.08 19.2,15.97 17.9,17.39M11,19.93C7.05,19.44 4,16.08 4,12C4,11.38 4.08,10.78 4.21,10.21L9,15V16A2,2 0 0,0 11,18M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z',
	},
	{
		label: 'Ritiro 24h',
		sub: 'Dal giorno dopo',
		icon: 'M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z',
	},
	{
		label: '4.8/5',
		sub: 'Valutazione clienti',
		icon: 'M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z',
	},
];

const sanctum = useSanctumClient();
const services = ref(fallbackServices);

onMounted(async () => {
	try {
		const res = await sanctum('/api/public/services');
		const data = res?.data || res;
		if (Array.isArray(data) && data.length > 0) services.value = data;
	} catch {}
});

// Restituisce i metadati visuali per un servizio dato lo slug
const getServiceMeta = (service) => serviceMeta[service.slug] || defaultMeta;
const getServiceDescription = (service) => service.description || service.meta_description || service.intro || '';
</script>

<template>
	<div style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%); min-height: 100vh">
		<div class="my-container pt-[24px] sm:pt-[40px] pb-[80px]">

			<!-- Hero -->
			<div class="text-center mb-[40px] sm:mb-[56px]">
				<div class="inline-flex items-center gap-[6px] h-[32px] px-[14px] rounded-full bg-[#095866]/[0.08] text-[#095866] text-[12px] mb-[16px]" style="font-weight:700">
					<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
						<path d="M7,2V13H10V22L17,10H13L17,2H7Z" />
					</svg>
					I nostri servizi
				</div>
				<h1 class="text-[#1d2738] text-[28px] sm:text-[40px] tracking-[-0.8px] max-w-[600px] mx-auto font-montserrat" style="font-weight:800">
					Tutto quello che ti serve per spedire al meglio
				</h1>
				<p class="text-[#777] text-[15px] sm:text-[16px] mt-[10px] max-w-[480px] mx-auto leading-[1.6]">
					Servizi semplici e trasparenti. Attivabili in pochi secondi durante il preventivo.
				</p>
			</div>

			<!-- Stats strip -->
			<div class="grid grid-cols-2 sm:grid-cols-4 gap-[10px] mb-[36px]">
				<div
					v-for="stat in statsData"
					:key="stat.label"
					class="flex items-center gap-[10px] rounded-[16px] p-[14px] transition-all duration-[350ms] hover:ring-[2px] hover:ring-[#095866]/50 hover:shadow-[0_4px_16px_rgba(9,88,102,0.06)]"
					style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%); ring: 1.5px solid #DFE2E7; box-shadow: 0 0 0 1.5px #DFE2E7"
				>
					<div class="w-[36px] h-[36px] rounded-[10px] bg-[#095866]/[0.08] flex items-center justify-center shrink-0">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#095866">
							<path :d="stat.icon" />
						</svg>
					</div>
					<div>
						<span class="text-[#1d2738] text-[13px] block" style="font-weight:700">{{ stat.label }}</span>
						<span class="text-[#999] text-[11px]" style="font-weight:500">{{ stat.sub }}</span>
					</div>
				</div>
			</div>

			<!-- Griglia servizi -->
			<div class="grid grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-3 gap-[16px]">
				<NuxtLink
					v-for="service in services"
					:key="service.slug"
					:to="`/servizi/${service.slug}`"
					class="group flex flex-col rounded-[22px] overflow-hidden min-h-[220px] transition-all duration-[350ms] hover:ring-[2px] hover:ring-[#095866]/50 hover:shadow-[0_4px_16px_rgba(9,88,102,0.06)] no-underline"
					style="box-shadow: 0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04)"
				>
					<div
						class="flex flex-col flex-1 p-[20px] sm:p-[24px]"
						style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)"
					>
						<!-- Icon + Category -->
						<div class="flex items-center gap-[12px] mb-[12px]">
							<div
								class="w-[44px] h-[44px] rounded-[12px] flex items-center justify-center shrink-0 transition-transform duration-[400ms] group-hover:scale-[1.06]"
								:style="{ background: getServiceMeta(service).accent + '1a' }"
							>
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
									:fill="getServiceMeta(service).accent">
									<path :d="getServiceMeta(service).icon" />
								</svg>
							</div>
							<div>
								<span
									class="text-[10px] uppercase tracking-[0.4px] px-[7px] py-[1px] rounded-full"
									:style="{ fontWeight: 700, color: getServiceMeta(service).accent, background: getServiceMeta(service).accent + '1a' }"
								>
									{{ service.type || getServiceMeta(service).category }}
								</span>
							</div>
						</div>

						<!-- Titolo -->
						<h3
							class="text-[#1d2738] text-[16px] sm:text-[17px] tracking-[-0.2px] mb-[6px] group-hover:text-[#095866] transition-colors duration-[350ms]"
							style="font-weight:700"
						>
							{{ service.title }}
						</h3>
						<!-- Descrizione -->
						<p class="text-[#777] text-[13px] leading-[1.55] flex-1">
							{{ getServiceDescription(service) }}
						</p>

						<!-- Footer card -->
						<div class="flex items-center justify-between mt-[16px] pt-[12px] border-t border-[#DFE2E7]">
							<span class="text-[12px] text-[#999] flex items-center gap-[3px]" style="font-weight:500">
								<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
									<path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" />
								</svg>
								{{ getServiceMeta(service).readTime }}
							</span>
							<span
								class="text-[#095866] text-[13px] flex items-center gap-[4px] group-hover:gap-[8px] transition-all duration-[350ms]"
								style="font-weight:600"
							>
								Leggi
								<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
									<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
								</svg>
							</span>
						</div>
					</div>
				</NuxtLink>
			</div>

			<!-- Bottom CTA teal gradient -->
			<div class="mt-[48px] rounded-[22px] p-[28px] sm:p-[40px] text-center"
				style="background: linear-gradient(135deg, #095866, #0a7489)">
				<h2 class="text-white text-[22px] sm:text-[28px] tracking-[-0.5px] font-montserrat" style="font-weight:800">
					Inizia subito a spedire
				</h2>
				<p class="text-white/60 text-[14px] sm:text-[15px] mt-[8px] max-w-[440px] mx-auto">
					Calcola il preventivo in 30 secondi. Tutti i servizi sono attivabili durante il processo.
				</p>
				<NuxtLink
					to="/preventivo"
					class="inline-flex items-center gap-[8px] h-[52px] px-[28px] rounded-full text-white text-[15px] mt-[20px] transition-all duration-[350ms] hover:shadow-[0_8px_28px_rgba(228,66,3,0.35)] hover:-translate-y-[1px]"
					style="font-weight:700; background: #E44203; box-shadow: 0 6px 24px rgba(228,66,3,0.35)"
				>
					Calcola preventivo
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
					</svg>
				</NuxtLink>
			</div>

		</div>
	</div>
</template>
