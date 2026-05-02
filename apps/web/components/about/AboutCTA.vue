<template>
	<section class="about-cta" aria-labelledby="about-cta-title">
		<div class="my-container about-cta__inner">
			<div class="about-cta__copy">
				<p class="about-cta__eyebrow">Lavora con noi</p>
				<h2 id="about-cta-title" class="about-cta__title">
					Vuoi entrare nel team o proporci una collaborazione?
				</h2>
				<p class="about-cta__text">
					Scriviamoci. Cerchiamo persone che mettano la stessa cura nei dettagli che mettiamo nel
					prodotto: stesura candidature spontanee, partnership commerciali, idee per il prossimo
					passo.
				</p>
			</div>
			<div class="about-cta__actions">
				<SfButton to="/contatti" size="lg">Lavora con noi</SfButton>
				<SfButton to="/preventivo" variant="secondary" size="lg">Calcola un preventivo</SfButton>
			</div>
		</div>
	</section>
</template>

<style scoped>
.about-cta__inner {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 24px;
	max-width: 720px;
	margin: 0 auto;
	padding: 48px 24px;
	background: linear-gradient(135deg, rgba(9, 88, 102, 0.04) 0%, rgba(228, 66, 3, 0.04) 100%);
	border-radius: 20px;
	text-align: center;
}

.about-cta__copy {
	display: flex;
	flex-direction: column;
	gap: 8px;
	align-items: center;
}

.about-cta__eyebrow {
	font-size: 0.75rem;
	font-weight: 700;
	color: var(--color-brand-orange, #E44203);
	letter-spacing: 0.1em;
	text-transform: uppercase;
}

.about-cta__title {
	font-size: clamp(1.5rem, 3vw, 2rem);
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
}

.about-cta__text {
	font-size: 1rem;
	color: var(--color-brand-text-secondary, #5f6470);
	margin: 0;
}

.about-cta__actions {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
	justify-content: center;
}
</style>
