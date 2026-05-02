<script setup>
const milestones = [
	{
		year: '2020',
		title: 'Fondazione',
		text:
			'Nasce SpedizioneFacile con un’idea semplice: trasformare il preventivo BRT in un’esperienza chiara, immediata, senza intermediari opachi.',
	},
	{
		year: '2021',
		title: 'Partnership BRT ufficiale',
		text:
			'Diventiamo intermediari BRT autorizzati. Da qui in avanti ogni tariffa, supplemento e servizio passa da contratti diretti con il corriere.',
	},
	{
		year: '2023',
		title: '10.000 clienti attivi',
		text:
			'Superiamo la soglia simbolica dei diecimila account, tra negozi online, professionisti e famiglie che spediscono ogni settimana.',
	},
	{
		year: '2024',
		title: 'Lancio piano Pro',
		text:
			'Per chi spedisce molto introduciamo tariffe a volume, fatturazione mensile e ritiri programmati su più sedi.',
	},
	{
		year: '2026',
		title: 'Nuova piattaforma',
		text:
			'Rilasciamo l’interfaccia ridisegnata: tracking unificato, area cliente in chiaro, supporto in chat con persone reali.',
	},
];
</script>

<template>
	<section class="about-timeline" aria-labelledby="about-timeline-title">
		<div class="my-container">
			<header class="about-timeline__header">
				<span class="about-timeline__accent" aria-hidden="true"/>
				<p class="about-timeline__eyebrow">La nostra storia</p>
				<h2 id="about-timeline-title" class="about-timeline__title">Cinque tappe in cinque anni</h2>
			</header>
			<ol class="about-timeline__list">
				<li v-for="m in milestones" :key="m.year" class="about-timeline__item">
					<div class="about-timeline__marker" aria-hidden="true">
						<span class="about-timeline__dot"/>
					</div>
					<div class="about-timeline__body">
						<span class="about-timeline__year">{{ m.year }}</span>
						<h3 class="about-timeline__title-item">{{ m.title }}</h3>
						<p class="about-timeline__text">{{ m.text }}</p>
					</div>
				</li>
			</ol>
		</div>
	</section>
</template>

<style scoped>
.about-timeline__list {
	display: flex;
	flex-direction: column;
	gap: 16px;
	max-width: 720px;
	margin: 0 auto;
	position: relative;
	padding-left: 32px;
}

.about-timeline__list::before {
	content: '';
	position: absolute;
	left: 11px;
	top: 6px;
	bottom: 6px;
	width: 2px;
	background: rgba(9, 88, 102, 0.15);
}

.about-timeline__item {
	display: grid;
	grid-template-columns: auto 1fr;
	gap: 16px;
	align-items: start;
	position: relative;
}

.about-timeline__marker {
	position: absolute;
	left: -32px;
	top: 4px;
	width: 24px;
	height: 24px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.about-timeline__dot {
	width: 12px;
	height: 12px;
	border-radius: 50%;
	background: var(--color-brand-primary);
	box-shadow: 0 0 0 4px #ffffff, 0 0 0 5px var(--color-brand-primary);
}

.about-timeline__year {
	font-size: 0.875rem;
	font-weight: 700;
	color: var(--color-brand-orange, #E44203);
	min-width: 60px;
}

.about-timeline__body {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.about-timeline__title-item {
	font-size: 1rem;
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
}

.about-timeline__text {
	font-size: 0.875rem;
	color: var(--color-brand-text-secondary, #5f6470);
	line-height: 1.5;
	margin: 0;
}

.about-timeline__accent {
	width: 36px;
	height: 3px;
	background: var(--color-brand-primary);
	border-radius: 2px;
	margin: 8px auto 0;
}
</style>
