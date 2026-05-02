<template>
	<section class="trust" aria-label="Partner e certificazioni">
		<div class="container trust__inner">
			<p class="trust__label">In partnership con il leader italiano del corriere espresso</p>
			<ul class="trust__logos" role="list">
				<li class="trust__logo">
					<svg viewBox="0 0 120 32" aria-label="BRT Corriere Espresso" role="img">
						<rect x="0" y="0" width="120" height="32" rx="6" fill="#095866" />
						<text x="60" y="21" text-anchor="middle" font-family="Inter, sans-serif" font-weight="700" font-size="14" fill="#ffffff" letter-spacing="2">BRT</text>
					</svg>
				</li>
				<li class="trust__logo">
					<svg viewBox="0 0 120 32" aria-label="Pagamenti sicuri Stripe" role="img">
						<rect x="0" y="0" width="120" height="32" rx="6" fill="#ffffff" stroke="#e2e8df" />
						<text x="60" y="21" text-anchor="middle" font-family="Inter, sans-serif" font-weight="600" font-size="13" fill="#095866">Stripe</text>
					</svg>
				</li>
				<li class="trust__logo">
					<svg viewBox="0 0 120 32" aria-label="Conforme GDPR" role="img">
						<rect x="0" y="0" width="120" height="32" rx="6" fill="#ffffff" stroke="#e2e8df" />
						<text x="60" y="21" text-anchor="middle" font-family="Inter, sans-serif" font-weight="600" font-size="13" fill="#095866">GDPR</text>
					</svg>
				</li>
				<li class="trust__logo">
					<svg viewBox="0 0 120 32" aria-label="Connessione SSL sicura" role="img">
						<rect x="0" y="0" width="120" height="32" rx="6" fill="#ffffff" stroke="#e2e8df" />
						<text x="60" y="21" text-anchor="middle" font-family="Inter, sans-serif" font-weight="600" font-size="13" fill="#095866">SSL 256-bit</text>
					</svg>
				</li>
				<li class="trust__logo">
					<svg viewBox="0 0 120 32" aria-label="Trustpilot 4.7 su 5" role="img">
						<rect x="0" y="0" width="120" height="32" rx="6" fill="#ffffff" stroke="#e2e8df" />
						<text x="60" y="21" text-anchor="middle" font-family="Inter, sans-serif" font-weight="600" font-size="13" fill="#E44203">4,7 / 5</text>
					</svg>
				</li>
			</ul>
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
.trust {
	background: #fafbfa;
	border-block: 1px solid #eef2f0;
}
.trust__inner {
	padding-block: 28px;
	text-align: center;
}
.trust__label {
	margin: 0 0 16px;
	font-size: 13px;
	letter-spacing: 0.06em;
	text-transform: uppercase;
	color: #5b6b6f;
	font-weight: 500;
}
.trust__logos {
	margin: 0;
	padding: 0;
	list-style: none;
	display: flex;
	flex-wrap: wrap;
	gap: 16px 28px;
	align-items: center;
	justify-content: center;
}
.trust__logo svg { height: 32px; width: auto; display: block; }
</style>
