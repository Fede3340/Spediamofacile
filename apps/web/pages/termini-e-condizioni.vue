<!-- Termini e Condizioni — Cod. Consumo (D.Lgs. 206/2005), Cod. Civile (artt. 1678 ss.),
     CMR internazionale, Dir. UE 2011/83. Hero + TOC sticky + 12 sezioni. -->
<script setup>
useSeoMeta({
	title: 'Termini e Condizioni',
	ogTitle: 'Termini e Condizioni | SpedizioneFacile',
	description:
		'Condizioni generali di servizio di SpedizioneFacile: intermediazione spedizioni con corriere BRT, prezzi, recesso, assicurazione, foro competente.',
});

const lastUpdate = '18/04/2026';

// LEGAL-TODO: dati identificativi del fornitore in app.config.ts -> legal
const appConfig = useAppConfig();
const legal = appConfig.legal;

const sections = [
	{
		id: 'fornitore',
		title: '1. Identificazione del fornitore',
		paragraphs: [
			// LEGAL-TODO: aggiornare dati legali in app.config.ts prima del go-live
			`Il servizio è offerto da ${legal.companyName}, P.IVA ${legal.vatNumber}, codice fiscale ${legal.fiscalCode}, con sede legale in ${legal.registeredOffice} (REA ${legal.rea}, capitale sociale ${legal.shareCapital} interamente versato), iscritta al Registro delle Imprese.`,
			`Rappresentante legale: ${legal.legalRepresentative}. Posta elettronica certificata: <a class="lp-link" href="mailto:${legal.pec}">${legal.pec}</a>. Email di contatto generale: <a class="lp-link" href="mailto:${legal.supportEmail}">${legal.supportEmail}</a>.`,
		],
	},
	{
		id: 'oggetto',
		title: '2. Oggetto del contratto',
		paragraphs: [
			'SpedizioneFacile (di seguito anche "il Fornitore") è una piattaforma online di <strong>intermediazione</strong> che consente all\'utente di confrontare, prenotare, pagare e tracciare spedizioni nazionali ed internazionali tramite il vettore <strong>BRT S.p.A.</strong> (Bartolini) e altri corrieri convenzionati.',
			'Il Fornitore <strong>non è un vettore</strong> ai sensi degli artt. 1678 e seguenti del Codice Civile e non esegue direttamente l\'attività di trasporto. Agisce esclusivamente come intermediario tra l\'utente e il vettore prescelto, in nome proprio e per conto dell\'utente, secondo lo schema del contratto di spedizione (artt. 1737-1741 c.c.).',
			'Il Fornitore mette a disposizione strumenti di preventivazione automatica, prenotazione del ritiro, generazione della lettera di vettura, gestione del pagamento, monitoraggio in tempo reale della spedizione e assistenza post-vendita.',
		],
	},
	{
		id: 'registrazione',
		title: '3. Registrazione e account utente',
		paragraphs: [
			'L\'accesso ai servizi richiede la creazione di un account gratuito. L\'utente garantisce la veridicità, la correttezza e l\'aggiornamento dei dati forniti in fase di registrazione e si obbliga a comunicare tempestivamente ogni variazione.',
			'L\'utente è esclusivo responsabile della custodia delle credenziali di accesso e di ogni attività compiuta tramite il proprio account. È vietato cedere a terzi le credenziali o consentire l\'utilizzo dell\'account a soggetti non autorizzati.',
			'Il Fornitore si riserva il diritto di sospendere o disattivare gli account in caso di violazione dei presenti termini, utilizzo fraudolento, dichiarazioni mendaci o richieste delle autorità competenti, previa motivata comunicazione all\'utente salvo ipotesi di pericolo immediato.',
		],
	},
	{
		id: 'preventivo',
		title: '4. Offerta, preventivo e accettazione',
		paragraphs: [
			'I preventivi visualizzati a video hanno natura di <strong>invito a proporre</strong> e si considerano vincolanti solo dopo la conferma dell\'ordine e l\'avvenuto pagamento. I prezzi sono calcolati in tempo reale in base a peso, dimensioni, tipologia di servizio, indirizzi di ritiro e consegna, eventuali servizi accessori (assicurazione, contrassegno).',
			'La conclusione del contratto avviene quando l\'utente, dopo aver visionato il riepilogo dell\'ordine, conferma l\'acquisto e completa con esito positivo il pagamento. A seguito della conferma, l\'utente riceve un\'email di riepilogo all\'indirizzo indicato.',
			'Il Fornitore si riserva la facoltà di non accettare ordini in caso di indisponibilità del vettore, errori manifesti di prezzo dovuti a malfunzionamenti tecnici, sospetto di frode, mancato superamento delle verifiche antifrode di Stripe o spedizioni incompatibili con le condizioni di trasporto del vettore.',
		],
	},
	{
		id: 'prezzi-pagamento',
		title: '5. Prezzi, fatturazione e pagamento',
		paragraphs: [
			'Tutti i prezzi sono espressi in <strong>Euro (EUR)</strong>, IVA inclusa ove dovuta, e calcolati internamente in <em>centesimi</em> tramite la libreria moneyphp/money per garantire la massima precisione contabile. Nessun arrotondamento penalizzante per l\'utente è applicato.',
			'I metodi di pagamento accettati sono:',
		],
		list: [
			'<strong>Carta di credito/debito</strong> (circuiti Visa, Mastercard, American Express) tramite Stripe Payments Europe Ltd., con autenticazione 3D Secure ove applicabile.',
			'<strong>Wallet digitali</strong> (Apple Pay, Google Pay) tramite Stripe.',
			'<strong>Bonifico bancario</strong> per ordini di importo elevato e clienti business, previa accettazione del Fornitore. La spedizione viene attivata solo dopo la verifica dell\'accredito.',
			'<strong>Saldo del Wallet SpedizioneFacile</strong>, alimentato da ricariche o crediti promozionali.',
		],
		extra: [
			'La fattura elettronica viene emessa entro 12 giorni dalla conferma dell\'ordine (D.Lgs. 127/2015) e trasmessa tramite Sistema di Interscambio (SDI) all\'utente registrato come professionista o impresa.',
			'In caso di rifiuto del pagamento da parte dell\'istituto bancario o di chargeback non motivato, il Fornitore si riserva di sospendere il servizio e di addebitare le spese di gestione previste dalle convenzioni con i propri provider di pagamento.',
		],
	},
	{
		id: 'consegna',
		title: '6. Tempi di consegna e clausole BRT',
		paragraphs: [
			'I tempi di consegna indicati a sito sono <strong>indicativi</strong> e si riferiscono ai tempi medi dichiarati dal vettore BRT in giorni lavorativi (dal lunedì al venerdì, esclusi festivi). I tempi decorrono dalla data di ritiro del collo da parte del vettore, non dalla data di conferma dell\'ordine.',
			'Sono escluse dal calcolo dei tempi di consegna: le spedizioni verso località disagiate, isole minori, zone montane e ZTL urbane, per le quali si applicano le tariffe e i tempi specifici del vettore; ritardi causati da forza maggiore, eventi naturali eccezionali, scioperi, emergenze sanitarie, blocchi doganali per spedizioni internazionali, indirizzi errati o incompleti forniti dall\'utente.',
			'L\'utente è tenuto a rendere il pacco disponibile per il ritiro nella data e fascia oraria concordate, debitamente imballato secondo le linee guida BRT (carta da pacchi, scotch resistente, etichetta visibile sul lato superiore). Il vettore può rifiutare il ritiro in caso di pacchi non conformi.',
		],
	},
	{
		id: 'recesso',
		title: '7. Diritto di recesso del consumatore',
		paragraphs: [
			`Il <strong>consumatore</strong> ai sensi dell'art. 3 del D.Lgs. 206/2005 (Codice del Consumo) ha diritto di recedere dal contratto entro <strong>14 giorni</strong> dalla conclusione, senza alcuna motivazione e senza penalità, mediante comunicazione esplicita inviata via email a <a class="lp-link" href="mailto:${legal.supportEmail}">${legal.supportEmail}</a> oppure tramite il modulo di recesso disponibile in piattaforma.`,
			'<strong>Eccezione importante:</strong> ai sensi dell\'art. 59, lettera a) del Codice del Consumo, il diritto di recesso <strong>non si applica</strong> qualora la prestazione del servizio sia già iniziata con l\'accordo espresso del consumatore e con l\'accettazione della perdita del diritto di recesso. Nel caso specifico, l\'attivazione della prenotazione presso BRT (presa in carico del ritiro) costituisce inizio dell\'esecuzione e comporta la rinuncia al recesso, salvo rimborso delle somme non ancora maturate dal vettore.',
			'In caso di recesso esercitato prima dell\'attivazione della prenotazione, il rimborso integrale viene effettuato entro 14 giorni con lo stesso metodo di pagamento utilizzato per l\'acquisto, salvo diversa indicazione dell\'utente.',
		],
	},
	{
		id: 'assicurazione',
		title: '8. Assicurazione delle spedizioni',
		paragraphs: [
			'In assenza di copertura assicurativa aggiuntiva, la responsabilità del vettore BRT in caso di smarrimento o danneggiamento è limitata ai massimali previsti dalla Convenzione CMR (per le spedizioni internazionali) e dalle Condizioni Generali di Trasporto BRT pubblicate su <a class="lp-link" href="https://www.brt.it" target="_blank" rel="noopener noreferrer">brt.it</a>.',
			'L\'utente può sottoscrivere in fase di prenotazione una <strong>copertura assicurativa aggiuntiva</strong> per il valore reale della merce, fino al massimale previsto dalla polizza convenzionata (tipicamente Euro 5.000 per collo, salvo diverse condizioni). I premi assicurativi sono indicati esplicitamente nel riepilogo prima della conferma.',
		],
		list: [
			'Sono <strong>escluse dalla copertura</strong>: opere d\'arte, gioielli, denaro, documenti di valore, oggetti deperibili, materiali pericolosi e tutto quanto vietato dalle norme di trasporto BRT.',
			'In caso di danno o smarrimento, l\'utente deve aprire reclamo entro <strong>8 giorni</strong> dalla consegna (per danni manifesti, immediatamente al momento della consegna, con riserva sulla bolla) inoltrando documentazione fotografica e prova del valore.',
			'L\'indennizzo viene erogato direttamente dalla compagnia assicurativa, previa istruttoria, entro i tempi indicati nelle condizioni di polizza (tipicamente 30-60 giorni).',
		],
	},
	{
		id: 'reclami',
		title: '9. Reclami, contestazioni e assistenza',
		paragraphs: [
			`In caso di anomalie (mancata consegna, ritardo eccessivo, danni, smarrimento, errori di fatturazione) l'utente può presentare reclamo scrivendo a <a class="lp-link" href="mailto:${legal.supportEmail}">${legal.supportEmail}</a> o tramite il <a class="lp-link" href="/account/assistenza">centro assistenza</a>.`,
			'Il Fornitore si impegna a fornire un primo riscontro entro <strong>5 giorni lavorativi</strong> dalla ricezione del reclamo e a chiudere la pratica entro un termine ragionevole, compatibilmente con i tempi di istruttoria del vettore (30-60 giorni). Per i reclami relativi a danni materiali la pratica viene attivata presso BRT secondo le procedure previste dalle Condizioni Generali di Trasporto.',
		],
	},
	{
		id: 'responsabilita',
		title: '10. Limiti di responsabilità',
		paragraphs: [
			'In quanto intermediario, il Fornitore risponde esclusivamente della corretta trasmissione dell\'ordine al vettore e della messa a disposizione degli strumenti previsti in piattaforma. Non risponde per ritardi, danni, smarrimenti o mancata consegna imputabili al vettore, salvo dolo o colpa grave del Fornitore stesso.',
			'Per le spedizioni internazionali su strada, la responsabilità del vettore è regolata dalla <strong>Convenzione CMR</strong> del 19/05/1956, che limita l\'indennizzo a <strong>8,33 DSP per chilogrammo</strong> di merce mancante o avariata (art. 23.3 CMR), salvo dichiarazione di valore o sottoscrizione di assicurazione integrativa.',
			'In ogni caso, la responsabilità complessiva del Fornitore verso l\'utente per qualsiasi titolo non potrà superare l\'importo effettivamente pagato dall\'utente per la specifica spedizione oggetto di contestazione, fatti salvi i limiti inderogabili previsti dalla legge a tutela del consumatore.',
			'Il Fornitore non è responsabile per danni indiretti, perdita di profitto, perdita di chance, danni reputazionali o consequenziali derivanti dall\'utilizzo o dall\'impossibilità di utilizzo del servizio.',
		],
	},
	{
		id: 'foro-odr',
		title: '11. Legge applicabile, foro competente e ODR',
		paragraphs: [
			'Il presente contratto è regolato dalla <strong>legge italiana</strong>. Per le controversie con utenti consumatori il foro competente in via esclusiva è quello del luogo di residenza o domicilio del consumatore, ai sensi dell\'art. 66-bis del Codice del Consumo.',
			'Per gli utenti professionali (P.IVA), foro esclusivamente competente è quello di <strong>Milano</strong>.',
			'Ai sensi del Regolamento UE 524/2013, gli utenti consumatori possono accedere alla piattaforma di Risoluzione delle Controversie Online (ODR) della Commissione Europea, disponibile all\'indirizzo <a class="lp-link" href="https://ec.europa.eu/consumers/odr" target="_blank" rel="noopener noreferrer">ec.europa.eu/consumers/odr</a>, per tentare una risoluzione extragiudiziale della controversia.',
		],
	},
	{
		id: 'modifiche',
		title: '12. Modifiche dei termini',
		paragraphs: [
			'Il Fornitore si riserva di modificare i presenti termini in qualsiasi momento per adeguarli a sopravvenute esigenze normative, tecniche, organizzative o commerciali. Le modifiche saranno comunicate agli utenti registrati con preavviso di almeno 30 giorni tramite email all\'indirizzo associato all\'account, salvo modifiche imposte dalla legge con efficacia immediata.',
			'L\'utente che non intenda accettare le modifiche ha facoltà di recedere dall\'account senza oneri entro la data di entrata in vigore. L\'utilizzo continuato del servizio oltre tale data costituisce accettazione tacita dei nuovi termini.',
			'La versione vigente è sempre consultabile a questa pagina, con indicazione della data di ultimo aggiornamento.',
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
				eyebrow="Termini"
				title="Termini e Condizioni di Servizio"
				description="Condizioni generali del servizio di intermediazione spedizioni offerto da SpedizioneFacile tramite BRT S.p.A. e corrieri convenzionati. L'accettazione è condizione necessaria per l'utilizzo della piattaforma."
				:crumbs="[{ label: 'Home', to: '/' }, { label: 'Termini e Condizioni' }]">
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
						<ul v-if="s.list" class="lp-section__list">
							<li v-for="(item, i) in s.list" :key="`l-${i}`" v-html="item" />
						</ul>
						<p
							v-for="(p, i) in s.extra || []"
							:key="`e-${i}`"
							class="lp-section__text"
							v-html="p"
						/>
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
