<script setup>
const currentYear = 2026;

// I details accordion sono closed by default su mobile (compact),
// open su desktop. Bind open via ref controllato da matchMedia
// in onMounted (SSR-safe: server render closed, client opens after mount).
const isDesktop = ref(false);
onMounted(() => {
	const mq = window.matchMedia('(min-width: 768px)');
	isDesktop.value = mq.matches;
	const handler = (e) => { isDesktop.value = e.matches; };
	mq.addEventListener('change', handler);
	onBeforeUnmount(() => mq.removeEventListener('change', handler));
});

const appConfig = useAppConfig();
const legal = computed(() => appConfig?.legal || {});
const isPlaceholder = (v) => !v || /^\[INSERIRE_/i.test(String(v)) || /^0+$/.test(String(v)) || /^X+$/i.test(String(v));
const piva = computed(() => isPlaceholder(legal.value.vatNumber) ? '' : legal.value.vatNumber);
const sdi = computed(() => isPlaceholder(legal.value.sdi) ? '' : legal.value.sdi);
const legalLine = computed(() => {
	const parts = [];
	if (piva.value) parts.push(`P.IVA ${piva.value}`);
	if (sdi.value) parts.push(`SDI ${sdi.value}`);
	parts.push(`© ${currentYear} SpediamoFacile. Tutti i diritti riservati.`);
	return parts.join(' · ');
});

const reopenCookieBanner = useState('reopenCookieBanner', () => false);
const openCookiePreferences = () => { reopenCookieBanner.value = true; };

const linkColumns = [
	{ title: 'Servizi', links: [
		{ label: 'Tutti i servizi', to: '/servizi' },
		{ label: 'Pro business', to: '/account/account-pro' },
		{ label: 'Contrassegno', to: '/servizi/pagamento-alla-consegna' },
		{ label: 'Senza etichetta', to: '/servizi/spedizione-senza-etichetta' },
		{ label: 'Assicurazione', to: '/servizi/assicurazione-spedizione' },
		{ label: 'Ritiro a domicilio', to: '/servizi/ritiro-a-domicilio' },
	] },
	{ title: 'Azienda', links: [
		{ label: 'Chi siamo', to: '/chi-siamo' },
		{ label: 'Contatti', to: '/contatti' },
		{ label: 'Lavora con noi', to: '/lavora-con-noi' },
	] },
	{ title: 'Supporto', links: [
		{ label: 'FAQ', to: '/faq' },
		{ label: 'Traccia spedizione', to: '/traccia' },
		{ label: 'Guide', to: '/guide' },
		{ label: 'Centro assistenza', to: '/account/assistenza' },
	] },
	{ title: 'Legale', links: [
		{ label: 'Privacy', to: '/privacy-policy' },
		{ label: 'Termini', to: '/termini-e-condizioni' },
		{ label: 'Cookie', to: '/cookie-policy' },
		{ label: 'GDPR export', to: '/account/privacy/export' },
		{ label: 'Condizioni di trasporto BRT', href: 'https://www.brt.it/it/footer/condizioni_generali_di_contratto', external: true },
	] },
];

const socials = [
	{ label: 'LinkedIn', href: 'https://www.linkedin.com/', path: 'M4.5 4.5h4v15h-4zM6.5 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM10.5 9.5h3.8v2.05h.05c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.78 2.65 4.78 6.1V19.5h-4v-3.4c0-.81 0-1.85-1.13-1.85-1.13 0-1.3.88-1.3 1.79V19.5h-4z' },
	{ label: 'Instagram', href: 'https://www.instagram.com/', variant: 'instagram' },
	{ label: 'Facebook', href: 'https://www.facebook.com/', path: 'M14 21v-7h2.5l.4-3H14V9.2c0-.86.24-1.45 1.5-1.45H17V5.05A21 21 0 0 0 14.85 5C12.7 5 11.25 6.27 11.25 8.6V11H8.75v3h2.5v7z' },
];
</script>

<template>
	<footer class="site-footer" role="contentinfo">
		<div class="site-footer__body">
			<div class="site-footer__body-shell">
				<div class="site-footer__grid">
					<div class="site-footer__brand">
						<NuxtLink to="/" class="site-footer__logo" aria-label="SpediamoFacile, torna alla home">
							<Logo :is-navbar="false" />
						</NuxtLink>
						<p class="site-footer__payoff">Intermediari BRT. Spedisci semplice.</p>
						<ul class="site-footer__social" aria-label="Profili social">
							<li v-for="social in socials" :key="social.label">
								<a :href="social.href" target="_blank" rel="noopener" :aria-label="`Seguici su ${social.label}`" class="site-footer__social-link">
									<svg v-if="social.variant === 'instagram'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
										<rect x="3" y="3" width="18" height="18" rx="5" />
										<circle cx="12" cy="12" r="4" />
										<circle cx="17.2" cy="6.8" r="0.9" fill="currentColor" stroke="none" />
									</svg>
									<svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
										<path :d="social.path" />
									</svg>
								</a>
							</li>
						</ul>
					</div>

					<!-- Mobile: collassati di default per ridurre scroll. Desktop: aperti via isDesktop computed -->
					<details v-for="column in linkColumns" :key="column.title" class="site-footer__column" :open="isDesktop">
						<summary class="site-footer__column-title">
							{{ column.title }}
							<svg class="site-footer__column-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<polyline points="6 9 12 15 18 9" />
							</svg>
						</summary>
						<ul class="site-footer__list">
							<li v-for="link in column.links" :key="link.label">
								<a v-if="link.external" :href="link.href" target="_blank" rel="noopener" class="site-footer__link">{{ link.label }}</a>
								<NuxtLink v-else :to="link.to" class="site-footer__link">{{ link.label }}</NuxtLink>
							</li>
						</ul>
					</details>
				</div>
			</div>
		</div>

		<div class="site-footer__bottom">
			<div class="site-footer__bottom-shell">
				<p class="site-footer__legal">{{ legalLine }}</p>
				<button type="button" class="site-footer__cookie-btn" @click="openCookiePreferences">Preferenze cookie</button>
			</div>
		</div>
	</footer>
</template>

<style scoped>
.site-footer { width: 100%; font-family: 'Inter', sans-serif; color: #cfe0e4; }

.site-footer__body { background: #072e38; }
.site-footer__body-shell { max-width: 1280px; margin: 0 auto; padding: 36px 24px 24px; }
.site-footer__grid { display: grid; grid-template-columns: 1.6fr 1fr 1fr 1fr 1fr; gap: 28px; }

.site-footer__brand { display: flex; flex-direction: column; gap: 16px; max-width: 320px; }
.site-footer__logo { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; color: #fff; }
.site-footer__payoff { margin: 0; font-size: 0.9375rem; line-height: 1.5; color: #cfe0e4; }
.site-footer__social { display: flex; gap: 12px; margin: 4px 0 0; padding: 0; list-style: none; }
.site-footer__social-link {
	display: inline-flex; align-items: center; justify-content: center;
	width: 40px; height: 40px; border-radius: 999px;
	background: rgba(255, 255, 255, 0.06); color: #cfe0e4; text-decoration: none;
	transition: background-color var(--sf-t1) var(--sf-ease), color var(--sf-t1) var(--sf-ease), transform var(--sf-t1) var(--sf-ease);
}
.site-footer__social-link:hover { color: #ffffff; background: #095866; transform: translateY(-1px); }
.site-footer__social-link:focus-visible { outline: 2px solid #095866; outline-offset: 2px; }

.site-footer__column { min-width: 0; }
.site-footer__column-title {
	font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;
	color: #E44203; margin: 0 0 10px;
	display: flex; align-items: center; justify-content: space-between;
	cursor: default; list-style: none;
}
.site-footer__column-title::-webkit-details-marker { display: none; }
.site-footer__column-chevron { display: none; color: #cfe0e4; transition: transform var(--sf-t1) var(--sf-ease); }
.site-footer__list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.site-footer__link {
	display: inline-block; padding-block: 8px;
	font-size: 0.9375rem; line-height: 1.4; color: #cfe0e4; text-decoration: none;
	transition: color var(--sf-t1) var(--sf-ease);
}
.site-footer__link:hover, .site-footer__link:focus-visible { color: #fff; text-decoration: underline; text-underline-offset: 3px; }
.site-footer__link:focus-visible { outline: 2px solid #fff; outline-offset: 2px; border-radius: 4px; }

.site-footer__bottom { background: #072e38; border-top: 1px solid rgba(255, 255, 255, 0.08); }
.site-footer__bottom-shell {
	max-width: 1280px; margin: 0 auto; padding: 12px 24px;
	display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
}
.site-footer__legal { margin: 0; font-size: 0.75rem; color: #9bb4ba; line-height: 1.5; }
.site-footer__cookie-btn {
	background: transparent; border: none; color: #9bb4ba;
	font-size: 0.75rem; font-family: inherit; cursor: pointer; padding: 6px 0;
	text-decoration: underline; text-underline-offset: 3px;
	transition: color var(--sf-t1) var(--sf-ease);
}
.site-footer__cookie-btn:hover { color: #fff; }
.site-footer__cookie-btn:focus-visible { outline: 2px solid #E44203; outline-offset: 3px; border-radius: 4px; }

@media (max-width: 1023px) {
	.site-footer__grid { grid-template-columns: 1fr 1fr; gap: 32px; }
	.site-footer__brand { grid-column: 1 / -1; max-width: none; }
}

@media (max-width: 767px) {
	.site-footer__body-shell { padding: 24px 20px 18px; }
	.site-footer__grid { grid-template-columns: 1fr; gap: 8px; }
	.site-footer__brand { grid-column: auto; margin-bottom: 16px; }
	.site-footer__social { gap: 10px; }
	.site-footer__social-link { width: 36px; height: 36px; }
	.site-footer__column { border-top: 1px solid rgba(255, 255, 255, 0.08); padding: 14px 0; }
	.site-footer__column[open] { padding-bottom: 16px; }
	.site-footer__column-title { cursor: pointer; margin-bottom: 0; padding: 4px 0; }
	.site-footer__column[open] .site-footer__column-title { margin-bottom: 12px; }
	.site-footer__column-chevron { display: inline-block; }
	.site-footer__column[open] .site-footer__column-chevron { transform: rotate(180deg); }
	.site-footer__bottom-shell { padding: 10px 20px 14px; flex-direction: column; align-items: flex-start; gap: 10px; }
	.site-footer__legal { font-size: 0.6875rem; }
}

@media (min-width: 768px) {
	.site-footer__column { display: block; }
	.site-footer__list { display: flex !important; }
}

@media (prefers-reduced-motion: reduce) {
	.site-footer__social-link, .site-footer__link, .site-footer__column-chevron, .site-footer__cookie-btn { transition: none; }
	.site-footer__social-link:hover { transform: none; }
}
</style>
