<template>
	<section class="trust" aria-label="Garanzie e certificazioni">
		<div class="container trust__inner">
			<p class="trust__label">Affidabilità garantita ad ogni spedizione</p>
			<ul class="trust__badges" role="list">
				<li class="trust-badge">
					<svg class="trust-badge__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M12 1l9 4v7c0 5.5-3.84 10.74-9 12-5.16-1.26-9-6.5-9-12V5l9-4z" />
						<path d="M9 12l2 2 4-4" />
					</svg>
					<div class="trust-badge__copy">
						<span class="trust-badge__title">Pagamenti sicuri</span>
						<span class="trust-badge__sub">Stripe SCA</span>
					</div>
				</li>
				<li class="trust-badge">
					<svg class="trust-badge__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<circle cx="12" cy="12" r="9" />
						<path d="M9 12l2 2 4-4" />
					</svg>
					<div class="trust-badge__copy">
						<span class="trust-badge__title">Conforme GDPR</span>
						<span class="trust-badge__sub">Privacy garantita</span>
					</div>
				</li>
				<li class="trust-badge">
					<svg class="trust-badge__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<rect x="5" y="11" width="14" height="10" rx="2" />
						<path d="M8 11V7a4 4 0 0 1 8 0v4" />
					</svg>
					<div class="trust-badge__copy">
						<span class="trust-badge__title">SSL 256-bit</span>
						<span class="trust-badge__sub">Connessione cifrata</span>
					</div>
				</li>
				<li class="trust-badge">
					<svg class="trust-badge__icon trust-badge__icon--accent" viewBox="0 0 120 24" fill="currentColor" aria-hidden="true">
						<path d="M11.5 1l3 7h7.5l-6 4.5 2.3 7.5-6.8-4.6-6.8 4.6 2.3-7.5-6-4.5h7.5z" />
						<path d="M35.5 1l3 7h7.5l-6 4.5 2.3 7.5-6.8-4.6-6.8 4.6 2.3-7.5-6-4.5h7.5z" />
						<path d="M59.5 1l3 7h7.5l-6 4.5 2.3 7.5-6.8-4.6-6.8 4.6 2.3-7.5-6-4.5h7.5z" />
						<path d="M83.5 1l3 7h7.5l-6 4.5 2.3 7.5-6.8-4.6-6.8 4.6 2.3-7.5-6-4.5h7.5z" />
						<path d="M107.5 1l3 7h7.5l-6 4.5 2.3 7.5-6.8-4.6-6.8 4.6 2.3-7.5-6-4.5h7.5z" />
					</svg>
					<div class="trust-badge__copy">
						<span class="trust-badge__title">4,7 / 5</span>
						<span class="trust-badge__sub">Recensioni clienti</span>
					</div>
				</li>
				<li class="trust-badge">
					<svg class="trust-badge__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<rect x="1" y="6" width="14" height="11" rx="1" />
						<path d="M15 10h4l3 3v4h-7z" />
						<circle cx="5.5" cy="18.5" r="2" />
						<circle cx="17.5" cy="18.5" r="2" />
					</svg>
					<div class="trust-badge__copy">
						<span class="trust-badge__title">Corriere BRT</span>
						<span class="trust-badge__sub">Partner ufficiale</span>
					</div>
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
	margin: 0 0 18px;
	font-size: 13px;
	letter-spacing: 0.06em;
	text-transform: uppercase;
	color: #5b6b6f;
	font-weight: 500;
}
.trust__badges {
	margin: 0;
	padding: 0;
	list-style: none;
	display: flex;
	flex-wrap: wrap;
	gap: 10px 14px;
	align-items: stretch;
	justify-content: center;
}
.trust-badge {
	display: inline-flex;
	align-items: center;
	gap: 10px;
	padding: 8px 16px;
	background: #ffffff;
	border: 1px solid #e2e8df;
	border-radius: 999px;
	box-shadow: 0 1px 2px rgba(9, 88, 102, 0.04);
}
.trust-badge__icon {
	width: 22px;
	height: 22px;
	color: #095866;
	flex-shrink: 0;
}
.trust-badge__icon--accent {
	width: 64px;
	height: 13px;
	color: #E44203;
}
.trust-badge__copy {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	line-height: 1.15;
}
.trust-badge__title {
	font-size: 13px;
	font-weight: 700;
	color: #0d3a44;
}
.trust-badge__sub {
	font-size: 11px;
	color: #5f6470;
	font-weight: 500;
}
</style>
