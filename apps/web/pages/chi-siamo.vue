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
					<span class="about-mission__accent" aria-hidden="true"></span>
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
					<span class="about-timeline__accent" aria-hidden="true"></span>
					<p class="about-timeline__eyebrow">La nostra storia</p>
					<h2 id="about-timeline-title" class="about-timeline__title">Cinque tappe in cinque anni</h2>
				</header>
				<ol class="about-timeline__list">
					<li v-for="m in milestones" :key="m.year" class="about-timeline__item">
						<div class="about-timeline__marker" aria-hidden="true">
							<span class="about-timeline__dot"></span>
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
					<span class="about-values__accent" aria-hidden="true"></span>
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
					<span class="about-team__accent" aria-hidden="true"></span>
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

