<template>
	<section class="services" aria-labelledby="services-title">
		<div class="container">
			<HomeSectionHead
				eyebrow="Servizi"
				title="Una soluzione per ogni spedizione"
				title-id="services-title"
				subtitle="Italia, Europa o ritiro in PUDO: scegli la formula piu adatta a te." />

			<div class="services__grid">
				<article class="service-card" data-reveal>
					<div class="service-card__head">
						<span class="service-card__tag">Italia</span>
						<svg class="service-card__icon" viewBox="0 0 32 32" aria-hidden="true">
							<path d="M16 3l11 6v8c0 7-5 11-11 13-6-2-11-6-11-13V9l11-6z" fill="#e6f1f3" stroke="#095866" stroke-width="2" />
							<path d="M11 16l4 4 7-7" stroke="#E44203" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
						</svg>
					</div>
					<h3 class="service-card__title">Spedizioni nazionali</h3>
					<p class="service-card__text">
						Consegna in 24/48 h in tutta Italia. Tariffe da 5,90 €, ritiro gratuito a domicilio.
					</p>
					<NuxtLink to="/servizi" class="service-card__cta">
						Scopri
						<span aria-hidden="true">-&gt;</span>
					</NuxtLink>
				</article>

				<article class="service-card service-card--featured" data-reveal>
					<div class="service-card__head">
						<span class="service-card__tag service-card__tag--accent">Europa</span>
						<svg class="service-card__icon" viewBox="0 0 32 32" aria-hidden="true">
							<circle cx="16" cy="16" r="13" fill="#fff5f0" stroke="#E44203" stroke-width="2" />
							<path d="M3 16h26M16 3c4 4 4 22 0 26M16 3c-4 4-4 22 0 26" stroke="#095866" stroke-width="2" fill="none" />
						</svg>
					</div>
					<h3 class="service-card__title">Spedizioni in Europa</h3>
					<p class="service-card__text">
						Oltre 30 paesi europei serviti. Sdoganamento incluso, tracking unico fino a destinazione.
					</p>
					<NuxtLink to="/servizi" class="service-card__cta">
						Scopri
						<span aria-hidden="true">-&gt;</span>
					</NuxtLink>
				</article>

				<article class="service-card" data-reveal>
					<div class="service-card__head">
						<span class="service-card__tag">PUDO</span>
						<svg class="service-card__icon" viewBox="0 0 32 32" aria-hidden="true">
							<rect x="5" y="11" width="22" height="16" rx="2" fill="#e6f1f3" stroke="#095866" stroke-width="2" />
							<path d="M5 11l11-7 11 7" fill="none" stroke="#095866" stroke-width="2" stroke-linejoin="round" />
							<rect x="13" y="17" width="6" height="10" fill="#E44203" />
						</svg>
					</div>
					<h3 class="service-card__title">Ritiro in PUDO</h3>
					<p class="service-card__text">
						Risparmia fino al 25% scegliendo il ritiro o la consegna in uno dei 1.500 punti BRT.
					</p>
					<NuxtLink to="/servizi" class="service-card__cta">
						Scopri
						<span aria-hidden="true">-&gt;</span>
					</NuxtLink>
				</article>
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
.services { padding-block: 72px; background: #f7faf9; }
.services__grid {
	display: grid;
	gap: 20px;
}
@media (min-width: 768px) { .services__grid { grid-template-columns: repeat(3, 1fr); gap: 24px; } }
.service-card {
	background: #ffffff;
	border: 1px solid #eef2f0;
	border-radius: 18px;
	padding: 28px 24px;
	display: flex;
	flex-direction: column;
	gap: 12px;
	transition: transform var(--sf-t1) var(--sf-ease), box-shadow var(--sf-t1) var(--sf-ease), border-color var(--sf-t1) var(--sf-ease);
}
.service-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 14px 28px -16px rgba(9, 88, 102, 0.22);
	border-color: #d2dcd9;
}
.service-card--featured { border-color: #f3c8b3; background: #fffaf6; }
.service-card__head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
}
.service-card__tag {
	display: inline-flex;
	align-items: center;
	padding: 4px 10px;
	border-radius: 999px;
	font-size: 12px;
	font-weight: 600;
	letter-spacing: 0.04em;
	background: #e6f1f3;
	color: #095866;
}
.service-card__tag--accent { background: #fde2d4; color: #b03000; }
.service-card__icon { width: 40px; height: 40px; }
.service-card__icon svg { width: 100%; height: 100%; display: block; }
.service-card__title {
	margin: 4px 0 0;
	font-size: 19px;
	font-weight: 700;
	color: #0d3a44;
}
.service-card__text {
	margin: 0;
	font-size: 15px;
	line-height: 1.55;
	color: #475559;
	flex: 1;
}
.service-card__cta {
	margin-top: 4px;
	display: inline-flex;
	align-items: center;
	gap: 6px;
	color: #095866;
	font-weight: 600;
	font-size: 15px;
	text-decoration: none;
	border-bottom: 1px solid transparent;
	width: max-content;
	padding-bottom: 2px;
	transition: color var(--sf-t1) var(--sf-ease), border-color var(--sf-t1) var(--sf-ease), transform var(--sf-t1) var(--sf-ease);
}
.service-card__cta:hover { color: #E44203; border-color: #E44203; transform: translateX(2px); }
.service-card__cta:focus-visible {
	outline: none;
	color: #E44203;
	border-color: #E44203;
	box-shadow: 0 0 0 3px rgba(228, 66, 3, 0.18);
	border-radius: 4px;
}
</style>
