<template>
	<section class="how" aria-labelledby="how-title">
		<div class="container">
			<HomeSectionHead
				eyebrow="Come funziona"
				title="Spedire non è mai stato così semplice"
				title-id="how-title"
				subtitle="Tre passaggi, nessuna complicazione: dal preventivo alla consegna." />

			<ol class="steps" role="list">
				<li class="step" data-reveal>
					<div class="step__icon" aria-hidden="true">
						<svg viewBox="0 0 48 48" focusable="false">
							<circle cx="24" cy="24" r="22" fill="#e6f1f3" />
							<path d="M14 28h20M14 22h20M14 16h12" stroke="#095866" stroke-width="2" stroke-linecap="round" />
							<circle cx="34" cy="34" r="6" fill="#E44203" />
							<path d="M31 34l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
					</div>
					<div class="step__num">01</div>
					<h3 class="step__title">Calcola</h3>
					<p class="step__text">Inserisci CAP, peso e dimensioni. Vedi subito il prezzo trasparente, senza sorprese.</p>
				</li>
				<li class="step" data-reveal>
					<div class="step__icon" aria-hidden="true">
						<svg viewBox="0 0 48 48" focusable="false">
							<circle cx="24" cy="24" r="22" fill="#e6f1f3" />
							<rect x="14" y="14" width="20" height="22" rx="3" fill="#fff" stroke="#095866" stroke-width="2" />
							<path d="M18 20h12M18 24h12M18 28h8" stroke="#095866" stroke-width="2" stroke-linecap="round" />
							<circle cx="34" cy="14" r="6" fill="#E44203" />
							<path d="M34 11v6M31 14h6" stroke="#fff" stroke-width="2" stroke-linecap="round" />
						</svg>
					</div>
					<div class="step__num">02</div>
					<h3 class="step__title">Prenota</h3>
					<p class="step__text">Compila i dati di mittente e destinatario, paga in modo sicuro con Stripe o bonifico.</p>
				</li>
				<li class="step" data-reveal>
					<div class="step__icon" aria-hidden="true">
						<svg viewBox="0 0 48 48" focusable="false">
							<circle cx="24" cy="24" r="22" fill="#e6f1f3" />
							<path d="M8 30h20l4-8h8l-4 8v4H8z" fill="#fff" stroke="#095866" stroke-width="2" stroke-linejoin="round" />
							<circle cx="16" cy="36" r="3" fill="#095866" />
							<circle cx="34" cy="36" r="3" fill="#095866" />
							<circle cx="14" cy="14" r="6" fill="#E44203" />
							<path d="M11 14l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
					</div>
					<div class="step__num">03</div>
					<h3 class="step__title">Spediamo</h3>
					<p class="step__text">BRT ritira il pacco a casa o in azienda. Tu segui il viaggio in tempo reale dal tracking.</p>
				</li>
			</ol>
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
.how { padding-block: 72px; }
.steps {
	margin: 0;
	padding: 0;
	list-style: none;
	display: grid;
	gap: 20px;
}
@media (min-width: 768px) { .steps { grid-template-columns: repeat(3, 1fr); gap: 24px; } }
.step {
	background: #ffffff;
	border: 1px solid #eef2f0;
	border-radius: 18px;
	padding: 28px 24px;
	transition: transform var(--sf-t1) var(--sf-ease), box-shadow var(--sf-t1) var(--sf-ease), border-color var(--sf-t1) var(--sf-ease);
	position: relative;
}
.step:hover {
	transform: translateY(-4px);
	box-shadow: 0 14px 28px -16px rgba(9, 88, 102, 0.22);
	border-color: #d2dcd9;
}
.step__icon { width: 56px; height: 56px; }
.step__icon svg { width: 100%; height: 100%; display: block; }
.step__num {
	position: absolute;
	top: 24px;
	right: 24px;
	font-size: 13px;
	font-weight: 700;
	color: #b8c4c2;
	letter-spacing: 0.1em;
}
.step__title {
	margin: 18px 0 6px;
	font-size: 19px;
	font-weight: 700;
	color: #0d3a44;
}
.step__text {
	margin: 0;
	font-size: 15px;
	line-height: 1.55;
	color: #475559;
}
</style>
