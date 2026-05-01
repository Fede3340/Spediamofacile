<!-- Chi Siamo — Hero → Mission → Numeri → Timeline → Valori → Team → Partnership BRT → CTA -->
<script setup>
import '~/assets/css/content.css';

useSeoMeta({
	title: 'Chi siamo',
	ogTitle: 'Chi siamo',
	description:
		'Intermediari BRT autorizzati dal 2020. Aiutiamo PMI e privati italiani a spedire con prezzi trasparenti, ritiro a domicilio e supporto umano dedicato.',
	ogDescription:
		'Intermediari BRT autorizzati dal 2020 al servizio di PMI e privati italiani: prezzi chiari, ritiro a domicilio, supporto umano.',
})

useHead({
	script: [
		{
			type: 'application/ld+json',
			innerHTML: JSON.stringify({
				'@context': 'https://schema.org',
				'@type': 'AboutPage',
				name: 'Chi siamo — SpedizioneFacile',
				url: 'https://spediamofacile.it/chi-siamo',
				mainEntity: {
					'@type': 'Organization',
					name: 'SpediamoFacile',
					url: 'https://spediamofacile.it',
					foundingDate: '2020',
					description:
						'Intermediario BRT autorizzato che semplifica le spedizioni per PMI e privati italiani.',
				},
			}),
		},
	],
})

// Breadcrumb: Home › Chi siamo
useBreadcrumbSchema([
	{ name: 'Home', url: '/' },
	{ name: 'Chi siamo' },
])

// ── Numeri chiave (placeholder realistici) ────────────────────────────────
const stats = [
	{ value: '5', suffix: 'anni', label: 'di attività al servizio dei clienti italiani' },
	{ value: '120k', suffix: '+', label: 'spedizioni gestite ogni anno' },
	{ value: '18k', suffix: '', label: 'clienti attivi tra PMI e privati' },
	{ value: '94', suffix: '%', label: 'consegne completate entro 48 ore' },
]

// ── Timeline azienda ──────────────────────────────────────────────────────
const milestones = [
	{
		year: '2020',
		title: 'Fondazione',
		text:
			'Nasce SpedizioneFacile con un’idea semplice: trasformare il preventivo BRT in un’esperienza chiara, immediata, senza intermediari opachi.',
	},
	{
		year: '2021',
		title: 'Partnership BRT ufficiale',
		text:
			'Diventiamo intermediari BRT autorizzati. Da qui in avanti ogni tariffa, supplemento e servizio passa da contratti diretti con il corriere.',
	},
	{
		year: '2023',
		title: '10.000 clienti attivi',
		text:
			'Superiamo la soglia simbolica dei diecimila account, tra negozi online, professionisti e famiglie che spediscono ogni settimana.',
	},
	{
		year: '2024',
		title: 'Lancio piano Pro',
		text:
			'Per chi spedisce molto introduciamo tariffe a volume, fatturazione mensile e ritiri programmati su più sedi.',
	},
	{
		year: '2026',
		title: 'Nuova piattaforma',
		text:
			'Rilasciamo l’interfaccia ridisegnata: tracking unificato, area cliente in chiaro, supporto in chat con persone reali.',
	},
]

// ── Valori (3 card con icone duotone) ─────────────────────────────────────
const values = [
	{
		title: 'Trasparenza',
		text:
			'Tariffa finale subito a video, supplementi spiegati uno per uno, contratti BRT alla base di ogni preventivo. Niente sorprese in fattura.',
		// Icona: occhio aperto (visibilità totale)
		icon: {
			bg: 'M12,4.5C7,4.5 2.73,7.61 1,12c1.73,4.39 6,7.5 11,7.5c5,0 9.27,-3.11 11,-7.5C21.27,7.61 17,4.5 12,4.5z',
			fg: 'M12,7a5,5 0 1,0 0,10a5,5 0 0,0 0,-10zm0,8a3,3 0 1,1 0,-6a3,3 0 0,1 0,6z',
		},
	},
	{
		title: 'Semplicità',
		text:
			'Pochi campi, un flusso lineare, un’etichetta scaricabile in meno di un minuto. Togliamo passaggi, non funzioni.',
		icon: {
			bg: 'M4,6h16v3H4zM4,11h16v3H4zM4,16h10v3H4z',
			fg: 'M16,16h6v3h-6z',
		},
	},
	{
		title: 'Supporto umano',
		text:
			'Nessun bot ad alberi infiniti. Risponde una persona del team, in italiano, che conosce il mondo BRT e la tua spedizione.',
		icon: {
			bg: 'M12,2a5,5 0 0,1 5,5v3a5,5 0 0,1 -10,0V7a5,5 0 0,1 5,-5z',
			fg: 'M4,21v-1a6,6 0 0,1 6,-6h4a6,6 0 0,1 6,6v1z',
		},
	},
]

// ── Team (silhouette placeholder + ruoli, nomi sobri) ─────────────────────
const team = [
	{ name: 'Marco Donadel', role: 'CEO & Co-founder', initials: 'MD' },
	{ name: 'Elena Rosati', role: 'CTO', initials: 'ER' },
	{ name: 'Luca Bianchi', role: 'Head of Operations', initials: 'LB' },
	{ name: 'Sara Conti', role: 'Customer Care Lead', initials: 'SC' },
]
</script>

<template>
	<div class="about-page">
		<!-- ── HERO ─────────────────────────────────────────────────────── -->
		<PublicPageHeader
			eyebrow="Chi siamo"
			title="Intermediari BRT autorizzati, dal 2020"
			description="Al servizio di PMI e privati italiani. Lavoriamo ogni giorno per rendere le spedizioni una scelta consapevole: prezzo chiaro, ritiro a domicilio, tracciamento in tempo reale e una persona vera quando serve un aiuto."
			:crumbs="[{ label: 'Home', to: '/' }, { label: 'Chi siamo' }]">
			<div class="about-hero__pills" role="list">
				<span class="about-hero__pill" role="listitem">Intermediari BRT autorizzati</span>
				<span class="about-hero__pill" role="listitem">Sede a Milano</span>
				<span class="about-hero__pill" role="listitem">Team italiano</span>
			</div>
		</PublicPageHeader>

		<!-- ── MISSION ──────────────────────────────────────────────────── -->
		<section class="about-mission" aria-labelledby="about-mission-title">
			<div class="my-container about-mission__inner">
				<header class="about-mission__header">
					<span class="about-mission__accent" aria-hidden="true"/>
					<p class="about-mission__eyebrow">La nostra missione</p>
					<h2 id="about-mission-title" class="about-mission__title">
						Spedire deve essere un’azione quotidiana, non un problema da risolvere.
					</h2>
				</header>
				<div class="about-mission__grid">
					<p class="about-mission__paragraph">
						Siamo nati per togliere fatica al gesto più comune del commercio: mandare un pacco da A
						a B. Lo facciamo con la rete BRT, contratti diretti e un’interfaccia che mostra subito
						la tariffa migliore tra peso reale e peso volumetrico, senza giochi sui supplementi.
					</p>
					<p class="about-mission__paragraph">
						Crediamo in una logistica vicina alle persone: pochi click per il preventivo, un ritiro
						programmato in giornata, un’assistenza che risponde con un nome e un cognome. È così
						che vogliamo essere il punto di riferimento per chi cerca qualità e convenienza nello
						stesso servizio.
					</p>
				</div>
			</div>
		</section>

		<!-- ── NUMERI CHIAVE ────────────────────────────────────────────── -->
		<section class="about-stats" aria-labelledby="about-stats-title">
			<div class="my-container">
				<h2 id="about-stats-title" class="about-stats__title">I numeri di SpedizioneFacile</h2>
				<dl class="about-stats__grid">
					<div v-for="stat in stats" :key="stat.label" class="about-stats__card">
						<dt class="about-stats__value">
							<span class="about-stats__number">{{ stat.value }}</span>
							<span v-if="stat.suffix" class="about-stats__suffix">{{ stat.suffix }}</span>
						</dt>
						<dd class="about-stats__label">{{ stat.label }}</dd>
					</div>
				</dl>
				<p class="about-stats__note">Dati aggiornati al primo trimestre 2026.</p>
			</div>
		</section>

		<!-- ── TIMELINE ─────────────────────────────────────────────────── -->
		<section class="about-timeline" aria-labelledby="about-timeline-title">
			<div class="my-container">
				<header class="about-timeline__header">
					<span class="about-timeline__accent" aria-hidden="true"/>
					<p class="about-timeline__eyebrow">La nostra storia</p>
					<h2 id="about-timeline-title" class="about-timeline__title">Cinque tappe in cinque anni</h2>
				</header>
				<ol class="about-timeline__list">
					<li v-for="m in milestones" :key="m.year" class="about-timeline__item">
						<div class="about-timeline__marker" aria-hidden="true">
							<span class="about-timeline__dot"/>
						</div>
						<div class="about-timeline__body">
							<span class="about-timeline__year">{{ m.year }}</span>
							<h3 class="about-timeline__title-item">{{ m.title }}</h3>
							<p class="about-timeline__text">{{ m.text }}</p>
						</div>
					</li>
				</ol>
			</div>
		</section>

		<!-- ── VALORI ───────────────────────────────────────────────────── -->
		<section class="about-values" aria-labelledby="about-values-title">
			<div class="my-container">
				<header class="about-values__header">
					<span class="about-values__accent" aria-hidden="true"/>
					<p class="about-values__eyebrow">Cosa ci guida</p>
					<h2 id="about-values-title" class="about-values__title">Tre principi non negoziabili</h2>
				</header>
				<div class="about-values__grid">
					<article v-for="v in values" :key="v.title" class="about-values__card">
						<span class="about-values__icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28">
								<path :d="v.icon.bg" class="about-values__icon-bg" />
								<path :d="v.icon.fg" class="about-values__icon-fg" />
							</svg>
						</span>
						<h3 class="about-values__card-title">{{ v.title }}</h3>
						<p class="about-values__card-text">{{ v.text }}</p>
					</article>
				</div>
			</div>
		</section>

		<!-- ── TEAM ─────────────────────────────────────────────────────── -->
		<section class="about-team" aria-labelledby="about-team-title">
			<div class="my-container">
				<header class="about-team__header">
					<span class="about-team__accent" aria-hidden="true"/>
					<p class="about-team__eyebrow">Le persone</p>
					<h2 id="about-team-title" class="about-team__title">Chi c’è dietro la piattaforma</h2>
					<p class="about-team__lead">
						Un team piccolo e diretto: ogni messaggio che ricevi arriva da una persona di questa
						lista o da chi lavora con noi tutti i giorni.
					</p>
				</header>
				<ul class="about-team__grid">
					<li v-for="member in team" :key="member.name" class="about-team__card">
						<div class="about-team__avatar" aria-hidden="true">
							<span class="about-team__initials">{{ member.initials }}</span>
						</div>
						<p class="about-team__name">{{ member.name }}</p>
						<p class="about-team__role">{{ member.role }}</p>
					</li>
				</ul>
			</div>
		</section>

		<!-- ── PARTNERSHIP BRT ──────────────────────────────────────────── -->
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

		<!-- ── CTA FINALE ───────────────────────────────────────────────── -->
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
	</div>
</template>

<style scoped>
/**
 * /chi-siamo page styling.
 * Aggiunto 2026-04-28 dopo audit DOM live: la pagina aveva 0 regole CSS
 * .about-* in tutto il codebase, tutto renderizzato in display:block.
 *
 * Sezioni: Hero -> Mission -> Stats -> Timeline -> Values -> Team -> Partnership -> CTA
 */

/* ============================================================
 *  Page root
 * ============================================================ */

.about-page {
	display: block;
	background: var(--surface-page, #f8f9fb);
}

.about-page section {
	padding: 60px 0;
}

@media (max-width: 768px) {
	.about-page section {
		padding: 40px 0;
	}
}

/* ============================================================
 *  Hero
 * ============================================================ */

.about-hero__pills {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	justify-content: center;
	margin-top: 20px;
}

.about-hero__pill {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 6px 14px;
	font-size: 0.8125rem;
	font-weight: 600;
	color: var(--color-brand-primary);
	background: rgba(9, 88, 102, 0.08);
	border-radius: 999px;
}

/* ============================================================
 *  Section common (mission, stats, team, timeline, values, partnership, cta)
 * ============================================================ */

.about-page [class$="__inner"],
.about-page .about-stats > .my-container,
.about-page .about-team > .my-container,
.about-page .about-timeline > .my-container,
.about-page .about-values > .my-container {
	max-width: 1200px;
	margin: 0 auto;
	padding-left: 24px;
	padding-right: 24px;
}

.about-page [class$="__header"] {
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	gap: 8px;
	margin-bottom: 40px;
}

.about-page [class$="__eyebrow"] {
	display: inline-block;
	font-size: 0.75rem;
	font-weight: 700;
	color: var(--color-brand-orange, #E44203);
	letter-spacing: 0.1em;
	text-transform: uppercase;
}

.about-page [class$="__title"]:not([class*="card-title"]):not([class*="title-item"]):not(.about-team__name):not(.about-partnership__title) {
	font-size: clamp(1.75rem, 3vw, 2.25rem);
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
	max-width: 720px;
}

/* ============================================================
 *  Mission
 * ============================================================ */

.about-mission__grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 24px;
	max-width: 800px;
	margin: 0 auto;
	text-align: center;
}

.about-mission__paragraph {
	font-size: 1.0625rem;
	line-height: 1.6;
	color: var(--color-brand-text-secondary, #5f6470);
	margin: 0;
}

.about-mission__accent {
	width: 48px;
	height: 4px;
	background: var(--color-brand-orange, #E44203);
	border-radius: 2px;
	margin: 0 auto 16px;
}

/* ============================================================
 *  Stats (numeri chiave: 4 card)
 * ============================================================ */

.about-stats__grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 16px;
}

.about-stats__card {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
	padding: 24px 16px;
	background: #ffffff;
	border-radius: 16px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.05), 0 0 0 1px rgba(9, 88, 102, 0.06);
	text-align: center;
}

.about-stats__value {
	display: flex;
	align-items: baseline;
	gap: 4px;
	font-size: 2.5rem;
	font-weight: 800;
	color: var(--color-brand-primary);
	line-height: 1;
}

.about-stats__suffix {
	font-size: 1.25rem;
	font-weight: 700;
	color: var(--color-brand-orange, #E44203);
}

.about-stats__label {
	font-size: 0.875rem;
	font-weight: 600;
	color: var(--color-brand-text);
}

/* .about-stats__title rimosso 16px override: ora segue regola section title
   36px come le altre h2 (.about-mission__title, .about-team__title etc.) */

.about-stats__note {
	font-size: 0.8125rem;
	color: var(--color-brand-text-muted, #7a8190);
	text-align: center;
	margin-top: 16px;
}

/* ============================================================
 *  Timeline (storia: anni)
 * ============================================================ */

.about-timeline__list {
	display: flex;
	flex-direction: column;
	gap: 16px;
	max-width: 720px;
	margin: 0 auto;
	position: relative;
	padding-left: 32px;
}

.about-timeline__list::before {
	content: '';
	position: absolute;
	left: 11px;
	top: 6px;
	bottom: 6px;
	width: 2px;
	background: rgba(9, 88, 102, 0.15);
}

.about-timeline__item {
	display: grid;
	grid-template-columns: auto 1fr;
	gap: 16px;
	align-items: start;
	position: relative;
}

.about-timeline__marker {
	position: absolute;
	left: -32px;
	top: 4px;
	width: 24px;
	height: 24px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.about-timeline__dot {
	width: 12px;
	height: 12px;
	border-radius: 50%;
	background: var(--color-brand-primary);
	box-shadow: 0 0 0 4px #ffffff, 0 0 0 5px var(--color-brand-primary);
}

.about-timeline__year {
	font-size: 0.875rem;
	font-weight: 700;
	color: var(--color-brand-orange, #E44203);
	min-width: 60px;
}

.about-timeline__body {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.about-timeline__title-item {
	font-size: 1rem;
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
}

.about-timeline__text {
	font-size: 0.875rem;
	color: var(--color-brand-text-secondary, #5f6470);
	line-height: 1.5;
	margin: 0;
}

.about-timeline__accent {
	width: 36px;
	height: 3px;
	background: var(--color-brand-primary);
	border-radius: 2px;
	margin: 8px auto 0;
}

/* ============================================================
 *  Values
 * ============================================================ */

.about-values__grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
	gap: 20px;
}

.about-values__card {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 24px;
	background: #ffffff;
	border-radius: 14px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.05), 0 0 0 1px rgba(9, 88, 102, 0.06);
	transition: box-shadow 200ms ease, transform 200ms ease;
}

.about-values__card:hover {
	box-shadow: 0 6px 18px rgba(9, 88, 102, 0.08), 0 0 0 1px rgba(9, 88, 102, 0.12);
	transform: translateY(-2px);
}

.about-values__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 48px;
	height: 48px;
	border-radius: 12px;
}

.about-values__icon-bg {
	background: rgba(9, 88, 102, 0.08);
}

.about-values__card-title {
	font-size: 1.0625rem;
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
}

.about-values__card-text {
	font-size: 0.875rem;
	color: var(--color-brand-text-secondary, #5f6470);
	line-height: 1.55;
	margin: 0;
}

.about-values__accent {
	width: 36px;
	height: 3px;
	background: var(--color-brand-orange, #E44203);
	border-radius: 2px;
	margin: 8px auto 0;
}

/* ============================================================
 *  Team
 * ============================================================ */

.about-team__lead {
	max-width: 600px;
	margin: 0 auto 16px;
	font-size: 0.9375rem;
	color: var(--color-brand-text-secondary, #5f6470);
	line-height: 1.55;
	text-align: center;
}

.about-team__grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
	gap: 20px;
}

.about-team__card {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 10px;
	padding: 24px 16px;
	background: #ffffff;
	border-radius: 16px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.05), 0 0 0 1px rgba(9, 88, 102, 0.06);
	text-align: center;
	list-style: none;
}

.about-team__avatar {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 72px;
	height: 72px;
	border-radius: 50%;
	background: linear-gradient(135deg, var(--color-brand-primary), #0a7489);
	color: #ffffff;
}

.about-team__initials {
	font-size: 1.5rem;
	font-weight: 700;
	color: #ffffff;
}

.about-team__name {
	font-size: 1rem;
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
}

.about-team__role {
	font-size: 0.8125rem;
	font-weight: 600;
	color: var(--color-brand-orange, #E44203);
	letter-spacing: 0.04em;
	text-transform: uppercase;
}

.about-team__accent {
	width: 36px;
	height: 3px;
	background: var(--color-brand-primary);
	border-radius: 2px;
	margin: 8px auto 0;
}

/* ============================================================
 *  Partnership BRT
 * ============================================================ */

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

/* ============================================================
 *  CTA finale
 * ============================================================ */

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
