<template>
	<section class="about-mission" aria-labelledby="about-mission-title">
		<div class="my-container about-mission__inner">
			<header class="about-mission__header">
				<span class="about-mission__accent" aria-hidden="true"/>
				<p class="about-mission__eyebrow">La nostra missione</p>
				<h2 id="about-mission-title" class="about-mission__title">
					Spedire deve essere un'azione quotidiana, non un problema da risolvere.
				</h2>
			</header>
			<div class="about-mission__grid">
				<p class="about-mission__paragraph">
					Siamo nati per togliere fatica al gesto più comune del commercio: mandare un pacco da A
					a B. Lo facciamo con la rete BRT, contratti diretti e un'interfaccia che mostra subito
					la tariffa migliore tra peso reale e peso volumetrico, senza giochi sui supplementi.
				</p>
				<p class="about-mission__paragraph">
					Crediamo in una logistica vicina alle persone: pochi click per il preventivo, un ritiro
					programmato in giornata, un'assistenza che risponde con un nome e un cognome. È così
					che vogliamo essere il punto di riferimento per chi cerca qualità e convenienza nello
					stesso servizio.
				</p>
			</div>
		</div>
	</section>
</template>

<style scoped>
.about-mission__grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 24px;
	max-width: 800px;
	margin: 0 auto;
	text-align: center;
}

.about-mission__paragraph {
	font-size: 1.0625rem;
	line-height: 1.6;
	color: var(--color-brand-text-secondary, #5f6470);
	margin: 0;
}

.about-mission__accent {
	width: 48px;
	height: 4px;
	background: var(--color-brand-orange, #E44203);
	border-radius: 2px;
	margin: 0 auto 16px;
}
</style>
