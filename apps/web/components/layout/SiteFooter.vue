<!-- COMPONENTE: SiteFooter (components/layout/SiteFooter.vue) -->
<script setup>
import '~/assets/css/layout.css';

const currentYear = 2026;

// Legge i dati legali da app.config: se sono placeholder ([INSERIRE_*])
// li nasconde dal footer pubblico per non mostrarli all'utente finale.
const appConfig = useAppConfig();
const legal = computed(() => appConfig?.legal || {});
const isPlaceholder = (value) => !value || /^\[INSERIRE_/i.test(String(value)) || /^0+$/.test(String(value)) || /^X+$/i.test(String(value));
const piva = computed(() => isPlaceholder(legal.value.vatNumber) ? '' : legal.value.vatNumber);
const sdi = computed(() => isPlaceholder(legal.value.sdi) ? '' : legal.value.sdi);
const legalLine = computed(() => {
	const parts = [];
	if (piva.value) parts.push(`P.IVA ${piva.value}`);
	if (sdi.value) parts.push(`SDI ${sdi.value}`);
	parts.push(`© ${currentYear} SpediamoFacile. Tutti i diritti riservati.`);
	return parts.join(' · ');
});

// Riapri il banner cookie via state condiviso (stesso pattern del vecchio Footer.vue)
const reopenCookieBanner = useState('reopenCookieBanner', () => false);
const openCookiePreferences = () => {
	reopenCookieBanner.value = true;
};

// Colonne link footer — testo italiano, link interni Nuxt
const linkColumns = [
	{
		title: 'Servizi',
		links: [
			// Slug devono esistere in /api/public/services. Le voci "concettuali"
			// (Italia/Europa/PUDO) erano broken (404) — le sostituisco con slug reali.
			{ label: 'Tutti i servizi', to: '/servizi' },
			{ label: 'Pro business', to: '/account/account-pro' },
			{ label: 'Contrassegno', to: '/servizi/pagamento-alla-consegna' },
			{ label: 'Senza etichetta', to: '/servizi/spedizione-senza-etichetta' },
			{ label: 'Assicurazione', to: '/servizi/assicurazione-spedizione' },
			{ label: 'Ritiro a domicilio', to: '/servizi/ritiro-a-domicilio' },
		],
	},
	{
		title: 'Azienda',
		links: [
			{ label: 'Chi siamo', to: '/chi-siamo' },
			{ label: 'Contatti', to: '/contatti' },
			{ label: 'Lavora con noi', to: '/lavora-con-noi' },
		],
	},
	{
		title: 'Supporto',
		links: [
			{ label: 'FAQ', to: '/faq' },
			{ label: 'Traccia spedizione', to: '/traccia' },
			// -- ARCHIVIATO 2026-04-20: Reclami (_archive/frontend-simplification-2026-04-20/features/reclami-dedicato) --
			// { label: 'Reclami', to: '/reclami' },
			{ label: 'Guide', to: '/guide' },
			{ label: 'Centro assistenza', to: '/account/assistenza' },
		],
	},
	{
		title: 'Legale',
		links: [
			{ label: 'Privacy', to: '/privacy-policy' },
			{ label: 'Termini', to: '/termini-e-condizioni' },
			{ label: 'Cookie', to: '/cookie-policy' },
			{ label: 'GDPR export', to: '/account/privacy/export' },
			{
				label: 'Condizioni di trasporto BRT',
				href: 'https://www.brt.it/it/footer/condizioni_generali_di_contratto',
				external: true,
			},
		],
	},
];

// Social — SVG inline stroke 1.75, no fill (icon set custom monoline)
const socials = [
	{
		label: 'LinkedIn',
		href: 'https://www.linkedin.com/',
		path: 'M4.5 4.5h4v15h-4zM6.5 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM10.5 9.5h3.8v2.05h.05c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.78 2.65 4.78 6.1V19.5h-4v-3.4c0-.81 0-1.85-1.13-1.85-1.13 0-1.3.88-1.3 1.79V19.5h-4z',
	},
	{
		label: 'Instagram',
		href: 'https://www.instagram.com/',
		// Camera body + lens + flash dot — useremo composto SVG direttamente nel template
		variant: 'instagram',
	},
	{
		label: 'Facebook',
		href: 'https://www.facebook.com/',
		path: 'M14 21v-7h2.5l.4-3H14V9.2c0-.86.24-1.45 1.5-1.45H17V5.05A21 21 0 0 0 14.85 5C12.7 5 11.25 6.27 11.25 8.6V11H8.75v3h2.5v7z',
	},
];
</script>

<template>
	<footer class="site-footer" role="contentinfo">
		<!-- Corpo footer — teal scuro -->
		<div class="site-footer__body">
			<div class="site-footer__body-shell">
				<div class="site-footer__grid">
					<!-- a) Brand -->
					<div class="site-footer__brand">
						<NuxtLink to="/" class="site-footer__logo" aria-label="SpediamoFacile, torna alla home">
							<Logo :is-navbar="false" />
						</NuxtLink>
						<p class="site-footer__payoff">
							Intermediari BRT. Spedisci semplice.
						</p>
						<ul class="site-footer__social" aria-label="Profili social">
							<li v-for="social in socials" :key="social.label">
								<a
									:href="social.href"
									target="_blank"
									rel="noopener"
									:aria-label="`Seguici su ${social.label}`"
									class="site-footer__social-link"
								>
									<!-- Instagram: glifo composto -->
									<svg
										v-if="social.variant === 'instagram'"
										width="20"
										height="20"
										viewBox="0 0 24 24"
										fill="none"
										stroke="currentColor"
										stroke-width="2"
										stroke-linecap="round"
										stroke-linejoin="round"
										aria-hidden="true"
									>
										<rect x="3" y="3" width="18" height="18" rx="5" />
										<circle cx="12" cy="12" r="4" />
										<circle cx="17.2" cy="6.8" r="0.9" fill="currentColor" stroke="none" />
									</svg>
									<svg
										v-else
										width="20"
										height="20"
										viewBox="0 0 24 24"
										fill="none"
										stroke="currentColor"
										stroke-width="2"
										stroke-linecap="round"
										stroke-linejoin="round"
										aria-hidden="true"
									>
										<path :d="social.path" />
									</svg>
								</a>
							</li>
						</ul>
					</div>

					<!-- b/c/d/e) Colonne link — desktop standard, mobile <details> accordion -->
					<details
						v-for="column in linkColumns"
						:key="column.title"
						class="site-footer__column"
						:open="true"
					>
						<summary class="site-footer__column-title">
							{{ column.title }}
							<svg
								class="site-footer__column-chevron"
								width="14"
								height="14"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								stroke-width="2"
								stroke-linecap="round"
								stroke-linejoin="round"
								aria-hidden="true"
							>
								<polyline points="6 9 12 15 18 9" />
							</svg>
						</summary>
						<ul class="site-footer__list">
							<li v-for="link in column.links" :key="link.label">
								<a
									v-if="link.external"
									:href="link.href"
									target="_blank"
									rel="noopener"
									class="site-footer__link"
								>
									{{ link.label }}
								</a>
								<NuxtLink v-else :to="link.to" class="site-footer__link">
									{{ link.label }}
								</NuxtLink>
							</li>
						</ul>
					</details>
				</div>
			</div>
		</div>

		<!-- 3) Bottom bar -->
		<div class="site-footer__bottom">
			<div class="site-footer__bottom-shell">
				<p class="site-footer__legal">
					{{ legalLine }}
				</p>
				<button
					type="button"
					class="site-footer__cookie-btn"
					@click="openCookiePreferences"
				>
					Preferenze cookie
				</button>
			</div>
		</div>
	</footer>
</template>
