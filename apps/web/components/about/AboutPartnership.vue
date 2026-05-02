<template>
	<section class="about-partnership" aria-labelledby="about-partnership-title">
		<div class="my-container about-partnership__inner">
			<div class="about-partnership__logo" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 48" role="img" aria-label="Logo BRT">
					<rect x="0" y="0" width="120" height="48" rx="6" class="about-partnership__logo-bg" />
					<text
						x="60"
						y="32"
						text-anchor="middle"
						font-family="Inter, system-ui, sans-serif"
						font-weight="800"
						font-size="22"
						letter-spacing="2"
						class="about-partnership__logo-text">
						BRT
					</text>
				</svg>
			</div>
			<div class="about-partnership__copy">
				<p class="about-partnership__eyebrow">Partnership ufficiale</p>
				<h2 id="about-partnership-title" class="about-partnership__title">
					Intermediari BRT autorizzati
				</h2>
				<p class="about-partnership__text">
					SpedizioneFacile opera in qualità di intermediario BRT autorizzato. Le tariffe che
					vedi nel preventivo arrivano dal listino contrattualizzato direttamente con il
					corriere: nessun rivenditore di terze parti, nessun ricarico nascosto, supporto
					gestito dal nostro team in coordinamento con la rete BRT.
				</p>
				<p class="about-partnership__note">
					BRT è marchio registrato di BRT S.p.A. Logo a fini illustrativi.
				</p>
			</div>
		</div>
	</section>
</template>

<style scoped>
.about-partnership__inner {
	display: grid;
	grid-template-columns: 1fr;
	gap: 32px;
	align-items: center;
	max-width: 1000px;
	margin: 0 auto;
}

@media (min-width: 768px) {
	.about-partnership__inner {
		grid-template-columns: auto 1fr;
		gap: 48px;
	}
}

.about-partnership__logo {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 200px;
	height: 200px;
	border-radius: 24px;
	background: #ffffff;
	box-shadow: 0 4px 14px rgba(9, 88, 102, 0.08);
	margin: 0 auto;
}

.about-partnership__logo-bg {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 100%;
	height: 100%;
	border-radius: 16px;
	background: linear-gradient(135deg, #E44203 0%, #ff6a33 100%);
}

.about-partnership__logo-text {
	font-size: 2.5rem;
	font-weight: 800;
	color: #ffffff;
	letter-spacing: -0.04em;
}

.about-partnership__copy {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.about-partnership__eyebrow {
	font-size: 0.75rem;
	font-weight: 700;
	color: var(--color-brand-orange, #E44203);
	letter-spacing: 0.1em;
	text-transform: uppercase;
}

.about-partnership__title {
	font-size: clamp(1.5rem, 3vw, 2rem);
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
}

.about-partnership__text {
	font-size: 0.9375rem;
	color: var(--color-brand-text-secondary, #5f6470);
	line-height: 1.6;
	margin: 0;
}

.about-partnership__note {
	font-size: 0.8125rem;
	color: var(--color-brand-text-muted, #7a8190);
	font-style: italic;
	margin: 4px 0 0;
}
</style>
