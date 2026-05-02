<script setup>
const values = [
	{
		title: 'Trasparenza',
		text:
			'Tariffa finale subito a video, supplementi spiegati uno per uno, contratti BRT alla base di ogni preventivo. Niente sorprese in fattura.',
		icon: {
			bg: 'M12,4.5C7,4.5 2.73,7.61 1,12c1.73,4.39 6,7.5 11,7.5c5,0 9.27,-3.11 11,-7.5C21.27,7.61 17,4.5 12,4.5z',
			fg: 'M12,7a5,5 0 1,0 0,10a5,5 0 0,0 0,-10zm0,8a3,3 0 1,1 0,-6a3,3 0 0,1 0,6z',
		},
	},
	{
		title: 'Semplicità',
		text:
			'Pochi campi, un flusso lineare, un’etichetta scaricabile in meno di un minuto. Togliamo passaggi, non funzioni.',
		icon: {
			bg: 'M4,6h16v3H4zM4,11h16v3H4zM4,16h10v3H4z',
			fg: 'M16,16h6v3h-6z',
		},
	},
	{
		title: 'Supporto umano',
		text:
			'Nessun bot ad alberi infiniti. Risponde una persona del team, in italiano, che conosce il mondo BRT e la tua spedizione.',
		icon: {
			bg: 'M12,2a5,5 0 0,1 5,5v3a5,5 0 0,1 -10,0V7a5,5 0 0,1 5,-5z',
			fg: 'M4,21v-1a6,6 0 0,1 6,-6h4a6,6 0 0,1 6,6v1z',
		},
	},
];
</script>

<template>
	<section class="about-values" aria-labelledby="about-values-title">
		<div class="my-container">
			<header class="about-values__header">
				<span class="about-values__accent" aria-hidden="true"/>
				<p class="about-values__eyebrow">Cosa ci guida</p>
				<h2 id="about-values-title" class="about-values__title">Tre principi non negoziabili</h2>
			</header>
			<div class="about-values__grid">
				<article v-for="v in values" :key="v.title" class="about-values__card">
					<span class="about-values__icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28">
							<path :d="v.icon.bg" class="about-values__icon-bg" />
							<path :d="v.icon.fg" class="about-values__icon-fg" />
						</svg>
					</span>
					<h3 class="about-values__card-title">{{ v.title }}</h3>
					<p class="about-values__card-text">{{ v.text }}</p>
				</article>
			</div>
		</div>
	</section>
</template>

<style scoped>
.about-values__grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
	gap: 20px;
}

.about-values__card {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 24px;
	background: #ffffff;
	border-radius: 14px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.05), 0 0 0 1px rgba(9, 88, 102, 0.06);
	transition: box-shadow 200ms ease, transform 200ms ease;
}

.about-values__card:hover {
	box-shadow: 0 6px 18px rgba(9, 88, 102, 0.08), 0 0 0 1px rgba(9, 88, 102, 0.12);
	transform: translateY(-2px);
}

.about-values__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 48px;
	height: 48px;
	border-radius: 12px;
}

.about-values__icon-bg {
	background: rgba(9, 88, 102, 0.08);
}

.about-values__card-title {
	font-size: 1.0625rem;
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
}

.about-values__card-text {
	font-size: 0.875rem;
	color: var(--color-brand-text-secondary, #5f6470);
	line-height: 1.55;
	margin: 0;
}

.about-values__accent {
	width: 36px;
	height: 3px;
	background: var(--color-brand-orange, #E44203);
	border-radius: 2px;
	margin: 8px auto 0;
}
</style>
