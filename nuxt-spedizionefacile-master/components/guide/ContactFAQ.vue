<script setup>
import '~/assets/css/components/sf-contact-faq.css';

const faqs = [
	{
		q: 'Qual e il costo di una spedizione?',
		a: 'Il costo dipende da peso, destinazione e servizio. Usa il preventivo rapido per una stima istantanea — i prezzi partono da 8,90 € per pacchi fino a 5 kg.',
		icon: 'M12,1L3,5v6c0,5.55 3.84,10.74 9,12 5.16-1.26 9-6.45 9-12V5L12,1M12,7A2,2 0 0,1 14,9A2,2 0 0,1 12,11A2,2 0 0,1 10,9A2,2 0 0,1 12,7M16,15H8V13.57C8,12.64 9.89,12 12,12S16,12.64 16,13.57V15Z',
	},
	{
		q: 'Quanto tempo impiega la consegna?',
		a: 'Standard BRT: 1-3 giorni lavorativi per l\'Italia, 5-7 per l\'Europa. Il tracking aggiornato in tempo reale ti dira dove si trova il pacco.',
		icon: 'M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z',
	},
	{
		q: 'Come traccio la mia spedizione?',
		a: 'Vai alla pagina Traccia, inserisci il numero BRT e vedrai lo stato aggiornato ogni poche ore, con dettagli per fase.',
		icon: 'M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3Z',
	},
	{
		q: 'Accettate il contrassegno?',
		a: 'Si. Selezionalo durante il preventivo: il destinatario paghera al ricevimento, in contanti o assegno.',
		icon: 'M21,18V19A2,2 0 0,1 19,21H5C3.89,21 3,20.1 3,19V5A2,2 0 0,1 5,3H19A2,2 0 0,1 21,5V6H12C10.89,6 10,6.9 10,8V16A2,2 0 0,0 12,18M12,16H22V8H12M16,13.5A1.5,1.5 0 0,1 14.5,12A1.5,1.5 0 0,1 16,10.5A1.5,1.5 0 0,1 17.5,12A1.5,1.5 0 0,1 16,13.5Z',
	},
	{
		q: 'Ho un reclamo o una spedizione smarrita.',
		a: 'Apri un ticket da Assistenza (richiede login) oppure contattaci via email/telefono. Ti assistiamo fino alla risoluzione.',
		icon: 'M12,2C6.47,2 2,6.47 2,12C2,17.53 6.47,22 12,22C17.53,22 22,17.53 22,12C22,6.47 17.53,2 12,2M13,17H11V15H13V17M13,13H11V7H13V13Z',
	},
];

const openIdx = ref(-1);
const toggle = (i) => {
	openIdx.value = openIdx.value === i ? -1 : i;
};
</script>

<template>
	<div class="contact-faq-items">
		<div
			v-for="(faq, i) in faqs"
			:key="i"
			class="contact-faq-item"
			:class="{ 'is-open': openIdx === i }">
			<button
				type="button"
				class="contact-faq-toggle"
				:aria-expanded="openIdx === i"
				@click="toggle(i)">
				<span class="contact-faq-pill" aria-hidden="true">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
						<path :d="faq.icon" />
					</svg>
				</span>
				<span class="contact-faq-question">{{ faq.q }}</span>
				<svg
					width="16"
					height="16"
					viewBox="0 0 24 24"
					fill="none"
					stroke="currentColor"
					stroke-width="2.5"
					stroke-linecap="round"
					stroke-linejoin="round"
					class="contact-faq-chevron">
					<polyline points="6 9 12 15 18 9"></polyline>
				</svg>
			</button>
			<div class="contact-faq-content">
				<p>{{ faq.a }}</p>
			</div>
		</div>
	</div>
</template>
