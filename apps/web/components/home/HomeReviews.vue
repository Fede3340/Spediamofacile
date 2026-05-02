<template>
	<section class="reviews" aria-labelledby="reviews-title">
		<div class="container">
			<HomeSectionHead
				eyebrow="Recensioni"
				title="Migliaia di clienti soddisfatti"
				title-id="reviews-title"
				subtitle="Privati e aziende ci scelgono ogni giorno per spedire in Italia ed Europa." />

			<div class="reviews__grid">
				<figure class="review" data-reveal>
					<div class="review__stars" aria-label="5 stelle su 5">
						<span aria-hidden="true">*****</span>
					</div>
					<blockquote class="review__quote">
						"Prezzi imbattibili e ritiro puntuale. Spedisco ogni settimana per il mio
						e-commerce e non ho mai avuto problemi. Assistenza rapida e in italiano."
					</blockquote>
					<figcaption class="review__author">
						<span class="review__avatar" aria-hidden="true">MR</span>
						<span>
							<strong>Marta R.</strong>
							<span class="review__role">E-commerce, Milano</span>
						</span>
					</figcaption>
				</figure>

				<figure class="review" data-reveal>
					<div class="review__stars" aria-label="5 stelle su 5">
						<span aria-hidden="true">*****</span>
					</div>
					<blockquote class="review__quote">
						"Ho spedito un pacco fragile a Berlino, arrivato in 3 giorni e perfettamente
						integro. La piattaforma e chiarissima, anche per chi non e del settore."
					</blockquote>
					<figcaption class="review__author">
						<span class="review__avatar" aria-hidden="true">LB</span>
						<span>
							<strong>Luca B.</strong>
							<span class="review__role">Privato, Bologna</span>
						</span>
					</figcaption>
				</figure>

				<figure class="review" data-reveal>
					<div class="review__stars" aria-label="5 stelle su 5">
						<span aria-hidden="true">*****</span>
					</div>
					<blockquote class="review__quote">
						"Per la nostra azienda usavamo tre corrieri diversi: ora gestiamo tutto da
						qui. Tariffe trasparenti e fattura elettronica automatica."
					</blockquote>
					<figcaption class="review__author">
						<span class="review__avatar" aria-hidden="true">SC</span>
						<span>
							<strong>Sara C.</strong>
							<span class="review__role">PMI, Padova</span>
						</span>
					</figcaption>
				</figure>
			</div>
		</div>
	</section>
</template>

<style scoped>
.container {
	max-width: 1280px;
	margin-inline: auto;
	padding-inline: 14px;
}
@media (min-width: 1024px) {
	.container { padding-inline: 40px; }
}
.reviews { padding-block: 72px; }
.reviews__grid {
	display: grid;
	gap: 20px;
}
@media (min-width: 768px) { .reviews__grid { grid-template-columns: repeat(3, 1fr); gap: 24px; } }
.review {
	margin: 0;
	background: #ffffff;
	border: 1px solid #eef2f0;
	border-radius: 18px;
	padding: 26px 22px;
	transition: transform var(--sf-t1) var(--sf-ease), box-shadow var(--sf-t1) var(--sf-ease);
}
.review:hover {
	transform: translateY(-4px);
	box-shadow: 0 14px 28px -16px rgba(9, 88, 102, 0.18);
}
.review__stars {
	color: #E44203;
	font-size: 18px;
	letter-spacing: 3px;
	font-weight: 700;
}
.review__quote {
	margin: 12px 0 18px;
	font-size: 15.5px;
	line-height: 1.6;
	color: #2c3a3e;
	quotes: none;
}
.review__author {
	display: flex;
	align-items: center;
	gap: 12px;
	font-size: 14px;
	color: #475559;
}
.review__author strong { color: #0d3a44; display: block; }
.review__avatar {
	display: inline-grid;
	place-items: center;
	width: 36px;
	height: 36px;
	border-radius: 50%;
	background: #095866;
	color: #ffffff;
	font-size: 12px;
	font-weight: 700;
	letter-spacing: 0.04em;
}
.review__role {
	display: block;
	font-size: 12.5px;
	color: #6a7679;
	font-weight: 400;
}
</style>
