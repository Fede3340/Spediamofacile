<script setup>
useSeoMeta({
	title: 'Cookie Policy',
	ogTitle: 'Cookie Policy | SpedizioneFacile',
	description:
		'Cookie Policy di SpedizioneFacile: tipologie, finalità, durata e modalità di gestione delle preferenze. Conforme al Provvedimento Garante 10/06/2021.',
});

const lastUpdate = '18/04/2026';

// LEGAL-TODO: contatti privacy in app.config.ts -> legal
const appConfig = useAppConfig();
const legal = appConfig.legal;

// Helper per aprire il banner consensi (se gestito da plugin/composable dedicato)
function openCookieBanner() {
	if (import.meta.client) {
		// LEGAL-TODO: collegare al composable/store reale del banner consensi
		// Esempio: useCookieConsent().openPreferences()
		window.dispatchEvent(new CustomEvent('cookie-banner:open'));
	}
}

const sections = [
	{
		id: 'cosa-sono',
		title: '1. Cosa sono i cookie',
		paragraphs: [
			'I <strong>cookie</strong> sono piccoli file di testo che i siti web visitati inviano al browser dell\'utente, dove vengono memorizzati per essere ritrasmessi al sito alla visita successiva. Tecnologie analoghe (web beacon, pixel, local storage, fingerprinting) vengono parificate ai cookie ai fini della presente informativa.',
			'I cookie possono essere classificati in base alla durata (di sessione, eliminati alla chiusura del browser; persistenti, conservati fino a una data di scadenza), in base alla provenienza (di prima parte, installati direttamente dal sito; di terze parti, installati da domini diversi) e in base alla finalità (tecnici, analitici, marketing).',
			'L\'utente può in ogni momento rivedere le proprie scelte cliccando il pulsante in fondo a questa pagina o il link "Gestisci cookie" presente nel footer di ogni pagina del sito.',
		],
	},
	{
		id: 'tecnici',
		title: '2. Cookie tecnici (sempre attivi)',
		paragraphs: [
			'I cookie tecnici sono <strong>indispensabili al corretto funzionamento del sito</strong> e all\'erogazione del servizio richiesto dall\'utente. Ai sensi del Provvedimento del Garante Privacy del 10/06/2021 e dell\'art. 122 del Codice Privacy, l\'installazione di tali cookie <strong>non richiede consenso</strong>.',
		],
		cookies: [
			{
				name: 'session_id',
				purpose: 'Autenticazione utente tramite Laravel Sanctum (session cookie SPA).',
				duration: 'Sessione (120 minuti di inattività)',
				owner: 'Prima parte',
			},
			{
				name: 'XSRF-TOKEN',
				purpose: 'Protezione contro attacchi Cross-Site Request Forgery (CSRF).',
				duration: 'Sessione',
				owner: 'Prima parte',
			},
			{
				name: 'cookie_consent',
				purpose: 'Memorizzazione delle preferenze dell\'utente espresse nel banner cookie.',
				duration: '12 mesi',
				owner: 'Prima parte',
			},
			{
				name: 'sf_locale',
				purpose: 'Memorizzazione della lingua selezionata e delle preferenze di interfaccia.',
				duration: '12 mesi',
				owner: 'Prima parte',
			},
			{
				name: 'sf_guest_cart',
				purpose: 'Mantenimento del carrello per utenti non autenticati.',
				duration: '7 giorni',
				owner: 'Prima parte',
			},
		],
	},
	{
		id: 'analitici',
		title: '3. Cookie analitici (richiede consenso)',
		paragraphs: [
			'I cookie analitici raccolgono informazioni in forma aggregata sul numero di utenti, sulle pagine più visitate e sui percorsi di navigazione, per consentirci di migliorare continuamente il sito.',
			'<strong>Plausible Analytics</strong> è uno strumento di analisi statistica cookie-less ospitato in UE (Germania): non installa cookie persistenti né raccoglie dati identificativi. Per questo motivo, ai sensi del Provv. Garante 10/06/2021 (FAQ analytics), può essere considerato esente da consenso.',
			'<strong>Google Analytics 4</strong> viene attivato esclusivamente previo consenso esplicito e richiede l\'installazione di cookie di terze parti.',
		],
		cookies: [
			{
				name: '_ga (GA4)',
				purpose: 'Identificazione anonima dei visitatori unici per analisi statistiche aggregate. IP-anonymization attivo.',
				duration: '13 mesi',
				owner: 'Google Ireland Ltd. — terza parte',
			},
			{
				name: '_ga_<container-id> (GA4)',
				purpose: 'Persistenza dello stato della sessione GA4 per il container configurato.',
				duration: '13 mesi',
				owner: 'Google Ireland Ltd. — terza parte',
			},
			{
				name: 'plausible_ignore (opzionale)',
				purpose: 'Esclusione del proprio traffico dalle statistiche Plausible (opt-out volontario).',
				duration: 'Persistente fino a cancellazione',
				owner: 'Prima parte',
			},
		],
	},
	{
		id: 'marketing',
		title: '4. Cookie di marketing e profilazione (richiede consenso)',
		paragraphs: [
			'I cookie di marketing vengono utilizzati per mostrare contenuti pubblicitari o comunicazioni promozionali pertinenti agli interessi dell\'utente, sia all\'interno del sito sia su piattaforme di terze parti tramite remarketing.',
			'L\'attivazione di questi cookie avviene <strong>solo dopo il consenso esplicito</strong> espresso tramite il banner. Il consenso è sempre revocabile dalle preferenze cookie senza pregiudizio per la liceità del trattamento precedente.',
		],
		cookies: [
			{
				name: '_fbp',
				purpose: 'Meta (Facebook) Pixel: tracciamento conversioni e remarketing su Facebook/Instagram.',
				duration: '3 mesi',
				owner: 'Meta Platforms Ireland Ltd. — terza parte',
			},
			{
				name: 'IDE',
				purpose: 'Google Ads: misurazione conversioni e remarketing sulla rete Google Display.',
				duration: '13 mesi',
				owner: 'Google Ireland Ltd. — terza parte',
			},
			{
				name: 'sf_ref_attribution',
				purpose: 'Tracciamento del programma di referral interno (ultimo referrer, fonte di acquisizione).',
				duration: '90 giorni',
				owner: 'Prima parte',
			},
		],
	},
	{
		id: 'terze-parti',
		title: '5. Cookie di terze parti necessari',
		paragraphs: [
			'Alcune funzionalità del sito si appoggiano a servizi di terze parti che possono installare cookie tecnici. Tali cookie sono necessari al funzionamento delle relative integrazioni e sono soggetti alle policy dei rispettivi titolari.',
		],
		cookies: [
			{
				name: '__stripe_mid, __stripe_sid',
				purpose: 'Stripe Payments Europe Ltd.: identificatori antifrode e gestione 3D Secure in fase di pagamento.',
				duration: '__stripe_mid: 1 anno — __stripe_sid: 30 minuti',
				owner: 'Stripe — terza parte. <a class="lp-link" href="https://stripe.com/cookies-policy/legal" target="_blank" rel="noopener noreferrer">Policy</a>',
			},
			{
				name: '_GRECAPTCHA',
				purpose: 'Google reCAPTCHA Enterprise: distinzione tra utenti umani e bot in form sensibili.',
				duration: '6 mesi',
				owner: 'Google Ireland Ltd. — terza parte. <a class="lp-link" href="https://policies.google.com/technologies/cookies" target="_blank" rel="noopener noreferrer">Policy</a>',
			},
			{
				name: 'BRT_TRACK_*',
				purpose: 'BRT S.p.A. tracking widget: visualizzazione dello stato spedizione integrato (solo nelle pagine di tracking).',
				duration: 'Sessione',
				owner: 'BRT S.p.A. — terza parte. <a class="lp-link" href="https://www.brt.it/it/privacy" target="_blank" rel="noopener noreferrer">Policy</a>',
			},
		],
	},
	{
		id: 'gestione',
		title: '6. Come gestire i cookie',
		paragraphs: [
			'L\'utente può gestire le proprie preferenze cookie in due modi:',
		],
		list: [
			'<strong>Tramite il banner SpedizioneFacile:</strong> al primo accesso o cliccando il pulsante "Gestisci preferenze cookie" qui sotto, è possibile accettare, rifiutare o personalizzare le categorie di cookie non strettamente necessari.',
			'<strong>Tramite le impostazioni del browser:</strong> ogni browser consente di bloccare tutti i cookie, eliminarli al termine della sessione o eliminarli manualmente. Si segnala che la disattivazione dei cookie tecnici può compromettere il funzionamento del sito (es. impossibilità di mantenere la sessione di login).',
		],
		links: [
			{ text: 'Google Chrome', url: 'https://support.google.com/chrome/answer/95647' },
			{ text: 'Mozilla Firefox', url: 'https://support.mozilla.org/it/kb/protezione-antitracciamento-avanzata-firefox-desktop' },
			{ text: 'Apple Safari', url: 'https://support.apple.com/it-it/guide/safari/sfri11471/mac' },
			{ text: 'Microsoft Edge', url: 'https://support.microsoft.com/it-it/microsoft-edge/eliminare-i-cookie-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09' },
			{ text: 'Opera', url: 'https://help.opera.com/en/latest/web-preferences/' },
		],
	},
	{
		id: 'contatti',
		title: '7. Contatti e aggiornamenti',
		paragraphs: [
			`Per qualsiasi richiesta di chiarimento sull\'utilizzo dei cookie o per esercitare i diritti previsti dal GDPR, è possibile scrivere a <a class="lp-link" href="mailto:${legal.dpoEmail}">${legal.dpoEmail}</a>.`,
			'Per il trattamento generale dei dati personali si rimanda alla nostra <a class="lp-link" href="/privacy-policy">Privacy Policy</a>.',
			'La presente Cookie Policy può essere aggiornata periodicamente per riflettere modifiche tecniche, normative o l\'aggiunta di nuovi servizi di terze parti. La data dell\'ultimo aggiornamento è indicata in cima alla pagina.',
		],
	},
];

function scrollToTop() {
	if (import.meta.client) {
		window.scrollTo({ top: 0, behavior: 'smooth' });
	}
}
</script>

<template>
	<section class="lp-page" aria-labelledby="lp-title">
		<a href="#lp-content" class="lp-skiplink">Salta al contenuto principale</a>

		<div class="lp-container">
			<PublicPageHeader
				eyebrow="Cookie"
				title="Cookie Policy"
				description="Informazioni complete sui cookie e le tecnologie di tracciamento di SpedizioneFacile. Conforme al Provvedimento Garante Privacy 10/06/2021 e all'art. 13 GDPR."
				:crumbs="[{ label: 'Home', to: '/' }, { label: 'Cookie Policy' }]">
				<p class="lp-hero-meta"><span class="lp-hero-meta__label">Ultimo aggiornamento:</span> <time datetime="2026-04-18">{{ lastUpdate }}</time></p>
			</PublicPageHeader>

			<div class="lp-layout">
				<aside class="lp-toc" aria-labelledby="lp-toc-title">
					<div class="lp-toc__inner">
						<h2 id="lp-toc-title" class="lp-toc__title">In questa pagina</h2>
						<nav aria-label="Indice della pagina">
							<ol class="lp-toc__list">
								<li v-for="s in sections" :key="s.id" class="lp-toc__item">
									<a :href="`#${s.id}`" class="lp-toc__link">{{ s.title }}</a>
								</li>
							</ol>
						</nav>
					</div>
				</aside>

				<main id="lp-content" class="lp-content" tabindex="-1">
					<article
						v-for="s in sections"
						:key="s.id"
						:id="s.id"
						class="lp-section"
						:aria-labelledby="`${s.id}-h`"
					>
						<h2 :id="`${s.id}-h`" class="lp-section__title">{{ s.title }}</h2>
						<p
							v-for="(p, i) in s.paragraphs"
							:key="`p-${i}`"
							class="lp-section__text"
							v-html="p"
						/>

						<!-- Tabella cookie (per sezioni con elenchi tecnici) -->
						<div v-if="s.cookies" class="lp-cookie-table" role="table" :aria-label="`Elenco cookie sezione ${s.title}`">
							<div class="lp-cookie-row lp-cookie-row--head" role="row">
								<span role="columnheader">Nome</span>
								<span role="columnheader">Finalità</span>
								<span role="columnheader">Durata</span>
								<span role="columnheader">Titolare</span>
							</div>
							<div
								v-for="c in s.cookies"
								:key="c.name"
								class="lp-cookie-row"
								role="row"
							>
								<span class="lp-cookie-cell lp-cookie-cell--name" role="cell" data-label="Nome">{{ c.name }}</span>
								<span class="lp-cookie-cell" role="cell" data-label="Finalità">{{ c.purpose }}</span>
								<span class="lp-cookie-cell" role="cell" data-label="Durata">{{ c.duration }}</span>
								<span class="lp-cookie-cell" role="cell" data-label="Titolare" v-html="c.owner" />
							</div>
						</div>

						<!-- Lista bullet -->
						<ul v-if="s.list" class="lp-section__list">
							<li v-for="(item, i) in s.list" :key="`l-${i}`" v-html="item" />
						</ul>

						<!-- Link esterni guide browser -->
						<ul v-if="s.links" class="lp-section__list lp-section__list--links">
							<li v-for="link in s.links" :key="link.url">
								<a :href="link.url" target="_blank" rel="noopener noreferrer" class="lp-link">{{ link.text }}</a>
							</li>
						</ul>

						<!-- CTA gestione preferenze nella sezione "gestione" -->
						<div v-if="s.id === 'gestione'" class="lp-section__cta">
							<SfButton @click="openCookieBanner">
								Gestisci preferenze cookie
							</SfButton>
						</div>
					</article>

					<div class="lp-backtop">
						<SfButton variant="secondary" aria-label="Torna all'inizio della pagina" @click="scrollToTop">
							Torna in alto
						</SfButton>
					</div>
				</main>
			</div>
		</div>
	</section>
</template>

<!-- Stili condivisi in assets/css/legal-pages.css (classi .lp-*) -->
