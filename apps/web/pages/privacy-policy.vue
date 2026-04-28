<!-- Privacy Policy — GDPR (Reg. UE 2016/679) + CCPA. Hero + TOC sticky desktop + 10 sezioni. -->
<script setup>
useSeoMeta({
	title: 'Privacy Policy',
	ogTitle: 'Privacy Policy | SpedizioneFacile',
	description:
		'Informativa privacy SpedizioneFacile ai sensi del GDPR (Reg. UE 2016/679) e CCPA. Trattamento dati per servizi di intermediazione spedizioni con corriere BRT.',
});

const lastUpdate = '18/04/2026';

// LEGAL-TODO: dati identificativi del Titolare centralizzati in app.config.ts -> legal.
// Sostituire i placeholder [INSERIRE_*] con i dati reali prima del go-live.
const appConfig = useAppConfig();
const legal = appConfig.legal;

// Sezioni (ID stabili usati nelle ancore TOC)
const sections = [
	{
		id: 'titolare',
		title: '1. Titolare del trattamento',
		paragraphs: [
			// LEGAL-TODO: aggiornare denominazione, P.IVA, sede, REA in app.config.ts
			`Titolare del trattamento dei dati personali è ${legal.companyName}, P.IVA ${legal.vatNumber}, con sede legale in ${legal.registeredOffice} (REA ${legal.rea}, capitale sociale ${legal.shareCapital} interamente versato). Rappresentante legale: ${legal.legalRepresentative}.`,
			`Per qualsiasi richiesta relativa al trattamento dei propri dati personali è possibile scrivere a <a class="lp-link" href="mailto:${legal.dpoEmail}">${legal.dpoEmail}</a>. Eventuale Data Protection Officer (DPO) nominato: ${legal.dpoName}. Tempo massimo di risposta alle richieste degli interessati: 30 giorni (Art. 12.3 GDPR), prorogabile di ulteriori 60 giorni in caso di particolare complessità, previa comunicazione motivata.`,
		],
	},
	{
		id: 'dati-raccolti',
		title: '2. Dati personali raccolti',
		paragraphs: [
			'Il sito raccoglie e tratta esclusivamente le categorie di dati strettamente necessarie alle finalità di seguito indicate. In nessun caso vengono richiesti né trattati dati appartenenti a categorie particolari (art. 9 GDPR: salute, opinioni politiche, religiose, ecc.) salvo esplicito conferimento volontario dell\'interessato.',
		],
		list: [
			'<strong>Dati identificativi:</strong> nome, cognome, ragione sociale, codice fiscale, partita IVA.',
			'<strong>Dati di contatto:</strong> indirizzo e-mail, numero di telefono fisso e mobile, indirizzo postale.',
			'<strong>Dati di fatturazione:</strong> ragione sociale, sede, codice destinatario SDI o PEC per fatturazione elettronica.',
			'<strong>Dati di spedizione:</strong> indirizzi di ritiro e consegna, riferimenti del destinatario, peso, dimensioni e descrizione del contenuto del collo.',
			'<strong>Dati di navigazione:</strong> indirizzo IP, user agent, pagine visitate, timestamp di accesso, referrer (raccolti in forma pseudonimizzata).',
			'<strong>Dati di pagamento:</strong> i numeri di carta non transitano né sono conservati sui nostri server. Vengono trattati direttamente da Stripe Payments Europe Ltd. (PCI-DSS Level 1) tramite tokenizzazione.',
		],
	},
	{
		id: 'finalita',
		title: '3. Finalità del trattamento',
		paragraphs: [
			'I dati personali raccolti vengono trattati esclusivamente per le seguenti finalità, ciascuna corrispondente a una specifica base giuridica indicata nella sezione 4.',
		],
		list: [
			'<strong>Esecuzione del contratto:</strong> elaborazione del preventivo, gestione dell\'ordine, prenotazione del ritiro presso BRT, emissione della lettera di vettura, monitoraggio della consegna, assistenza post-vendita.',
			'<strong>Adempimenti fiscali e contabili:</strong> emissione di fattura elettronica, conservazione sostitutiva ai sensi del DPR 633/72 e del D.M. 17/06/2014.',
			'<strong>Comunicazioni di servizio:</strong> notifiche transazionali relative allo stato della spedizione (presa in carico, transito, consegna, anomalie).',
			'<strong>Marketing diretto:</strong> invio di newsletter e comunicazioni commerciali su prodotti e servizi analoghi, esclusivamente previo consenso esplicito e revocabile in qualsiasi momento.',
			'<strong>Miglioramento del servizio:</strong> analisi statistiche aggregate e anonime sull\'utilizzo della piattaforma, A/B testing, ricerca utenti.',
			'<strong>Prevenzione frodi:</strong> verifica antifrode in fase di pagamento (Stripe Radar) e tutela contro accessi non autorizzati.',
		],
	},
	{
		id: 'base-giuridica',
		title: '4. Base giuridica del trattamento',
		paragraphs: [
			'Ogni operazione di trattamento si fonda su una specifica base giuridica prevista dall\'art. 6 GDPR.',
		],
		list: [
			'<strong>Esecuzione di un contratto</strong> o misure precontrattuali (art. 6.1.b GDPR): per l\'erogazione del servizio di intermediazione spedizioni e la gestione dell\'account.',
			'<strong>Adempimento di un obbligo legale</strong> (art. 6.1.c GDPR): per fatturazione, conservazione documentale e comunicazioni alle autorità competenti su richiesta.',
			'<strong>Consenso dell\'interessato</strong> (art. 6.1.a GDPR): per finalità di marketing diretto, profilazione, cookie non tecnici. Il consenso è sempre libero, specifico, informato e revocabile.',
			'<strong>Legittimo interesse del titolare</strong> (art. 6.1.f GDPR): per analisi statistiche aggregate, prevenzione frodi e sicurezza informatica, previo bilanciamento con i diritti dell\'interessato.',
		],
	},
	{
		id: 'destinatari',
		title: '5. Destinatari dei dati (sub-processor)',
		paragraphs: [
			'I dati personali possono essere comunicati ai seguenti destinatari, designati responsabili del trattamento ex art. 28 GDPR mediante apposito DPA (Data Processing Agreement). Non avviene alcuna vendita né cessione a terzi per finalità commerciali estranee al servizio.',
		],
		list: [
			'<strong>BRT S.p.A.</strong> (Bartolini, vettore principale): dati di mittente, destinatario, peso, dimensioni e indirizzi necessari all\'esecuzione del trasporto.',
			'<strong>Stripe Payments Europe Ltd.</strong>: dati di pagamento (in via tokenizzata) e antifrode.',
			'<strong>Resend, Inc.</strong> (sub-processor AWS SES): invio email transazionali (conferme ordine, tracking, fatture).',
			'<strong>Render Services Inc.</strong>: hosting infrastruttura applicativa.',
			'<strong>Backblaze, Inc.</strong>: backup cifrati del database.',
			'<strong>Sentry</strong>: error monitoring tecnico (con scrubbing dei PII configurato).',
			'<strong>Plausible Analytics</strong>: statistiche aggregate cookie-less, hosting UE.',
			'<strong>Google Ireland Ltd.</strong>: Google Analytics 4, attivato esclusivamente previo consenso esplicito.',
			'<strong>Consulenti fiscali e legali esterni</strong>: in qualità di responsabili autonomi, vincolati al segreto professionale.',
			'<strong>Autorità giudiziarie e di pubblica sicurezza</strong>: solo su richiesta formale e nei limiti previsti dalla legge.',
		],
	},
	{
		id: 'trasferimento-extra-ue',
		title: '6. Trasferimento dei dati verso paesi extra-UE',
		paragraphs: [
			'Alcuni sub-processor (Stripe, Resend, Render, Sentry, Google) possono trattare i dati anche in paesi al di fuori dello Spazio Economico Europeo, in particolare negli Stati Uniti d\'America.',
			'Per garantire un livello di protezione adeguato, tutti i trasferimenti extra-UE avvengono nel rispetto delle <strong>Standard Contractual Clauses</strong> (SCC) approvate dalla Commissione Europea con Decisione di esecuzione 2021/914/UE, integrate ove pertinente dall\'<strong>EU-US Data Privacy Framework</strong> (decisione di adeguatezza del 10/07/2023).',
			`L\'elenco completo e aggiornato dei DPA sottoscritti con i sub-processor è disponibile su richiesta scrivendo a <a class="lp-link" href="mailto:${legal.dpoEmail}">${legal.dpoEmail}</a>.`,
		],
	},
	{
		id: 'conservazione',
		title: '7. Periodo di conservazione',
		paragraphs: [
			'I dati personali vengono conservati per il tempo strettamente necessario alle finalità per cui sono stati raccolti e nel rispetto dei termini imposti dalla normativa.',
		],
		list: [
			'<strong>Dati di fatturazione e contabilità:</strong> 10 anni dalla data di emissione (art. 2220 c.c. e DPR 633/72).',
			'<strong>Dati relativi all\'account utente:</strong> per tutta la durata del rapporto contrattuale e per ulteriori 24 mesi dalla cessazione, salvo diversa richiesta dell\'interessato.',
			'<strong>Dati di spedizione:</strong> 5 anni dalla consegna, per finalità di reclamo e contenzioso col vettore.',
			'<strong>Log di sicurezza e accesso:</strong> 24 mesi (provvedimento Garante n. 13/2008 e successivi aggiornamenti).',
			'<strong>Dati raccolti previo consenso (marketing):</strong> fino alla revoca del consenso o, in mancanza, 24 mesi dall\'ultima interazione.',
			'<strong>Cookie:</strong> secondo le durate indicate nella <a class="lp-link" href="/cookie-policy">Cookie Policy</a>.',
		],
	},
	{
		id: 'diritti',
		title: '8. Diritti dell\'interessato',
		paragraphs: [
			'L\'interessato può esercitare in qualsiasi momento, senza formalità e a titolo gratuito, i seguenti diritti previsti dagli articoli 15-22 GDPR.',
		],
		list: [
			'<strong>Diritto di accesso</strong> (art. 15): ottenere conferma del trattamento e copia dei dati.',
			'<strong>Diritto di rettifica</strong> (art. 16): correggere dati inesatti o incompleti.',
			'<strong>Diritto alla cancellazione</strong> o "all\'oblio" (art. 17): ottenere la cancellazione quando non più necessari o trattati illecitamente.',
			'<strong>Diritto di limitazione</strong> (art. 18): chiedere la sospensione del trattamento in determinati casi.',
			'<strong>Diritto di opposizione</strong> (art. 21): opporsi al trattamento per motivi connessi alla propria situazione, in particolare per marketing diretto.',
			'<strong>Diritto alla portabilità</strong> (art. 20): ricevere i dati in formato strutturato, di uso comune e leggibile da dispositivo automatico.',
			'<strong>Diritto di revoca del consenso</strong> (art. 7.3): revocabile in qualsiasi momento senza pregiudicare la liceità del trattamento precedente.',
			'<strong>Diritto di reclamo al Garante</strong> per la Protezione dei Dati Personali (<a class="lp-link" href="https://www.garanteprivacy.it" target="_blank" rel="noopener noreferrer">garanteprivacy.it</a>) o all\'autorità di controllo competente.',
			'<strong>Utenti residenti in California (CCPA):</strong> diritto di sapere quali dati personali sono raccolti, di richiederne cancellazione, di opporsi alla "vendita" dei dati (non praticata) e diritto alla non discriminazione per l\'esercizio di tali diritti.',
		],
	},
	{
		id: 'cookie',
		title: '9. Cookie e tecnologie di tracciamento',
		paragraphs: [
			'Il sito utilizza cookie tecnici necessari al funzionamento (sempre attivi) e, previo consenso esplicito, cookie analitici e di terze parti. Per l\'elenco dettagliato, le finalità, le durate e le modalità di gestione si rimanda alla nostra <a class="lp-link" href="/cookie-policy">Cookie Policy</a>.',
			'L\'utente può modificare le proprie preferenze in qualsiasi momento dal banner accessibile tramite il link "Gestisci cookie" presente nel footer di ogni pagina.',
		],
	},
	{
		id: 'contatti',
		title: '10. Contatti per la privacy',
		paragraphs: [
			`Per esercitare i diritti previsti dagli artt. 15-22 GDPR, per richieste di informazioni o per segnalare presunte violazioni, è possibile scrivere a <a class="lp-link" href="mailto:${legal.dpoEmail}">${legal.dpoEmail}</a>.`,
			// LEGAL-TODO: se il DPO viene formalmente nominato, indicare nome, recapito e atto di nomina
			`Eventuale Data Protection Officer: ${legal.dpoName}. PEC del Titolare: ${legal.pec}.`,
			'Tempo massimo di risposta: 30 giorni dal ricevimento (Art. 12.3 GDPR). In caso di mancata risposta o riscontro non soddisfacente è sempre possibile rivolgersi al Garante per la Protezione dei Dati Personali, Piazza Venezia 11, 00187 Roma.',
		],
	},
];

// Scroll to top utility (per il pulsante "Torna in alto")
function scrollToTop() {
	if (import.meta.client) {
		window.scrollTo({ top: 0, behavior: 'smooth' });
	}
}
</script>

<template>
	<section class="lp-page" aria-labelledby="lp-title">
		<!-- Skip link per accessibilità -->
		<a href="#lp-content" class="lp-skiplink">Salta al contenuto principale</a>

		<div class="lp-container">
			<!-- HERO -->
			<PublicPageHeader
				eyebrow="Privacy"
				title="Informativa sulla privacy"
				description="Come SpedizioneFacile raccoglie, utilizza e protegge i tuoi dati personali nell'ambito del servizio di intermediazione spedizioni con corriere BRT (GDPR 2016/679 e CCPA)."
				:crumbs="[{ label: 'Home', to: '/' }, { label: 'Privacy Policy' }]">
				<p class="lp-hero-meta"><span class="lp-hero-meta__label">Ultimo aggiornamento:</span> <time datetime="2026-04-18">{{ lastUpdate }}</time></p>
			</PublicPageHeader>

			<!-- LAYOUT 2 COLONNE: TOC + Contenuto -->
			<div class="lp-layout">
				<!-- Table of Contents (sticky desktop) -->
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

				<!-- Contenuto principale -->
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
							:key="i"
							class="lp-section__text"
							v-html="p"
						/>
						<ul v-if="s.list" class="lp-section__list">
							<li v-for="(item, i) in s.list" :key="i" v-html="item" />
						</ul>
					</article>

					<!-- Pulsante torna in alto -->
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
