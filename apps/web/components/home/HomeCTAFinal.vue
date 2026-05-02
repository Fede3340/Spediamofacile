<template>
	<section class="cta-final" aria-labelledby="cta-title">
		<div class="container cta-final__inner" data-reveal>
			<div>
				<p class="cta-final__eyebrow">Preventivo istantaneo</p>
				<h2 id="cta-title" class="cta-final__title">
					Calcola il preventivo in 30 secondi.
				</h2>
				<p class="cta-final__sub">
					Tariffe trasparenti, nessun costo nascosto, nessuna registrazione obbligatoria.
				</p>
			</div>
			<SfButton to="/preventivo" size="lg">
				Inizia ora
			</SfButton>
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
.cta-final {
	background: linear-gradient(135deg, #095866 0%, #0d6f80 100%);
	color: #ffffff;
	padding-block: 60px;
	position: relative;
	overflow: hidden;
	border-bottom: 4px solid #E44203;
}
@media (min-width: 768px) {
	.cta-final { padding-block: 88px; }
}
.cta-final::after {
	content: '';
	position: absolute;
	top: -80px;
	right: -80px;
	width: 320px;
	height: 320px;
	border-radius: 50%;
	background: rgba(228, 66, 3, 0.16);
	pointer-events: none;
}
.cta-final__inner {
	position: relative;
	display: grid;
	gap: 24px;
	align-items: center;
}
@media (min-width: 768px) {
	.cta-final__inner { grid-template-columns: 1fr auto; gap: 40px; }
}
.cta-final__eyebrow {
	margin: 0 0 6px;
	font-size: 13px;
	font-weight: 600;
	letter-spacing: 0.08em;
	text-transform: uppercase;
	color: #ffd9c7;
}
.cta-final__title {
	margin: 0;
	font-size: 28px;
	line-height: 1.18;
	font-weight: 700;
	letter-spacing: -0.01em;
}
@media (min-width: 768px) { .cta-final__title { font-size: 36px; } }
.cta-final__sub {
	margin: 10px 0 0;
	font-size: 16px;
	color: rgba(255, 255, 255, 0.86);
	max-width: 560px;
}
</style>
