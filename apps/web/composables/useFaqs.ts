/**
 * useFaqs — dataset statico FAQ + helpers highlight (no Fuse.js). Puro/serializzabile.
 *
 * @typedef {'Spedizione'|'Preventivi'|'Pagamenti'|'Tracking'|'Reclami'|'Account'|'Pro'} FaqCategory
 *
 * @typedef {Object} FaqItem
 * @property {string} id
 * @property {FaqCategory} category
 * @property {string} question
 * @property {string} answer
 */

export const FAQ_CATEGORIES = [
	'Spedizione',
	'Preventivi',
	'Pagamenti',
	'Tracking',
	'Reclami',
	'Account',
	'Pro',
];

const FAQS = [
	// ── Spedizione ──────────────────────────────────────────────
	{
		id: 'sped-italia-brt',
		category: 'Spedizione',
		question: 'Quanto costa spedire un pacco in Italia con BRT?',
		answer:
			'Il prezzo dipende da peso reale e volumetrico, città di partenza e destinazione, servizio scelto (standard, express, isole) ed eventuali servizi accessori come contrassegno o assicurazione. Su SpedizioneFacile vedi il prezzo finale tutto incluso prima di confermare: nessun sovrapprezzo nascosto, nessuna sorpresa in fattura.',
	},
	{
		id: 'sped-ritiro-domicilio',
		category: 'Spedizione',
		question: 'Come funziona il ritiro a domicilio?',
		answer:
			'Indichi indirizzo, fascia oraria e una persona di riferimento. Il corriere BRT passa nella fascia concordata (di solito il giorno lavorativo successivo) con la lettera di vettura prestampata. Tu devi solo consegnare il pacco già imballato ed etichettato, oppure stamparlo dopo aver ricevuto la conferma via email.',
	},
	{
		id: 'sped-modifica-indirizzo',
		category: 'Spedizione',
		question: "Posso modificare l'indirizzo dopo aver prenotato?",
		answer:
			'Finché il pacco non è ancora stato preso in carico dal corriere puoi modificare i dati direttamente dal pannello, alla voce Spedizioni. Una volta partito il pacco, le modifiche di indirizzo vanno gestite tramite il nostro supporto: in molti casi BRT permette comunque la rotta alternativa (indirizzo alternativo o filiale) con un piccolo sovrapprezzo.',
	},
	{
		id: 'sped-corriere-non-trova',
		category: 'Spedizione',
		question: 'Cosa succede se il corriere non trova il destinatario?',
		answer:
			'BRT lascia un avviso di mancata consegna e ritenta automaticamente il giorno lavorativo successivo. Dopo il secondo tentativo non riuscito il pacco viene inviato in giacenza presso la filiale di competenza, dove rimane disponibile per il ritiro. Dal pannello puoi richiedere una nuova consegna, il dirottamento a un fermopoint o la consegna in fascia serale.',
	},
	{
		id: 'sped-batterie-litio',
		category: 'Spedizione',
		question: 'Posso spedire batterie al litio?',
		answer:
			'Le batterie al litio rientrano nelle merci pericolose (ADR classe 9) e sono soggette a regole molto strette. SpedizioneFacile accetta esclusivamente batterie installate dentro un dispositivo (es. notebook, smartphone) e nei limiti previsti dalla normativa IATA/ADR. Le batterie sciolte, danneggiate o di ricambio non sono ammesse: contattaci prima di prenotare se hai dubbi.',
	},
	{
		id: 'sped-imballaggio',
		category: 'Spedizione',
		question: 'Come devo imballare correttamente il pacco?',
		answer:
			'Usa scatole in cartone doppia onda nuove o in ottime condizioni, riempi i vuoti con materiale ammortizzante (pluriball, chips, schiuma) e sigilla con nastro adesivo da imballaggio su tutte le giunture. Per oggetti fragili applica triplice protezione interna e l\'etichetta "Fragile". Non usare buste, scatole rotte o nastro da pacchi insufficiente: sono la prima causa di danni e contestazioni.',
	},
	{
		id: 'sped-tempi-europa',
		category: 'Spedizione',
		question: 'Entro quanto tempo arriva la spedizione in Europa?',
		answer:
			'Verso i principali Paesi UE i tempi standard BRT vanno da 2 a 5 giorni lavorativi: 2-3 giorni per Francia, Germania, Austria e Slovenia, 3-4 per Spagna, Belgio, Olanda, 4-5 per Portogallo, Polonia ed Est Europa. Per Regno Unito serve dichiarazione doganale e i tempi salgono a 4-7 giorni lavorativi.',
	},
	{
		id: 'sped-cosa-non-spedire',
		category: 'Spedizione',
		question: 'Quali oggetti non posso spedire?',
		answer:
			'Sono vietati: contanti, gioielli e oggetti di valore non assicurabili, armi e munizioni, droghe e sostanze illegali, animali vivi, alimenti deperibili, liquidi infiammabili, esplosivi, materiale pornografico e merci contraffatte. La lista completa è nelle Condizioni di trasporto BRT. In caso di dubbio chiedi prima al supporto: spedire merce vietata comporta il blocco del pacco e l\'addebito delle spese.',
	},

	// ── Preventivi ─────────────────────────────────────────────
	{
		id: 'prev-come-calcolato',
		category: 'Preventivi',
		question: 'Come viene calcolato il preventivo?',
		answer:
			'Il sistema confronta peso reale e peso volumetrico (lunghezza × larghezza × altezza ÷ 5000) e usa il maggiore dei due. A questo aggiunge la tariffa di tratta (CAP origine → CAP destinazione), il servizio scelto (standard, express, mare, aereo) e i servizi accessori opzionali. Il prezzo è finale, IVA inclusa, senza sorprese.',
	},
	{
		id: 'prev-validita',
		category: 'Preventivi',
		question: 'Per quanto tempo è valido un preventivo?',
		answer:
			'I preventivi rimangono validi 7 giorni dalla data di calcolo. Dopo questo termine i prezzi possono variare in base a tariffe del corriere, supplementi carburante o variazioni stagionali. Se hai salvato un preventivo nel pannello e i prezzi cambiano, vedrai un avviso prima della conferma.',
	},
	{
		id: 'prev-multipli',
		category: 'Preventivi',
		question: 'Posso preventivare più pacchi nella stessa spedizione?',
		answer:
			'Sì. In fase di preventivo puoi aggiungere fino a 99 colli con peso e dimensioni diversi: il prezzo viene calcolato sul totale e ricevi un\'unica lettera di vettura con etichette numerate. Per spedizioni superiori contattaci: gestiamo anche pallet e multi-pallet a tariffe dedicate.',
	},
	{
		id: 'prev-confronto-corrieri',
		category: 'Preventivi',
		question: 'Posso confrontare più corrieri nello stesso preventivo?',
		answer:
			'Al momento SpedizioneFacile lavora in esclusiva con BRT (Bartolini), il che ci permette di garantire tariffe più basse del listino pubblico, supporto in italiano e una rete capillare in tutta Italia. Stiamo valutando l\'integrazione di altri corrieri per le tratte internazionali: chi è registrato riceverà un\'email appena saranno disponibili.',
	},

	// ── Pagamenti ──────────────────────────────────────────────
	{
		id: 'pag-metodi',
		category: 'Pagamenti',
		question: 'Quali metodi di pagamento accettate?',
		answer:
			'Accettiamo carte di credito e debito (Visa, Mastercard, American Express) tramite Stripe, e il portafoglio prepagato interno SpedizioneFacile. Tutti i pagamenti sono protetti con crittografia SSL e 3D Secure. Non gestiamo PayPal e bonifico immediato per ora, ma puoi ricaricare il portafoglio una volta e usarlo per più spedizioni.',
	},
	{
		id: 'pag-cod-contrassegno',
		category: 'Pagamenti',
		question: 'Come attivare il contrassegno (COD)?',
		answer:
			'In fase di prenotazione, alla sezione "Servizi accessori", attiva il contrassegno e indica l\'importo da incassare e la modalità (contanti o assegno). Il corriere consegnerà il pacco solo dopo aver ricevuto il pagamento dal destinatario. L\'importo incassato viene accreditato sul tuo IBAN entro 7-10 giorni lavorativi dalla consegna.',
	},
	{
		id: 'pag-fattura-elettronica',
		category: 'Pagamenti',
		question: 'Quali documenti servono per la fattura elettronica?',
		answer:
			'Per ricevere fattura elettronica servono: ragione sociale o nome completo, partita IVA o codice fiscale, indirizzo della sede legale, codice destinatario SDI a 7 caratteri (oppure indirizzo PEC). Inserisci i dati una sola volta nel tuo profilo, sezione "Dati di fatturazione": ogni nuova spedizione genererà automaticamente la fattura inviata al SDI.',
	},
	{
		id: 'pag-rimborso',
		category: 'Pagamenti',
		question: 'Come ottengo un rimborso se annullo la spedizione?',
		answer:
			'Se annulli prima del ritiro del corriere il rimborso è completo e automatico, accreditato sullo stesso metodo di pagamento entro 5-7 giorni lavorativi. Se il pacco è già stato preso in carico ma non ancora consegnato, possiamo richiedere il blocco a BRT: il rimborso è parziale (50-70%) in base alla fase logistica raggiunta.',
	},
	{
		id: 'pag-ricarica-portafoglio',
		category: 'Pagamenti',
		question: 'Come funziona il portafoglio ricaricabile?',
		answer:
			'Il portafoglio è un credito prepagato in euro che usi per pagare le spedizioni con un click, senza reinserire la carta ogni volta. Lo ricarichi da 10€ in su con carta o bonifico, vedi saldo e movimenti in tempo reale e per ricariche superiori a 200€ ricevi un bonus del 2-5%. Il saldo non scade e puoi richiedere il prelievo residuo in qualsiasi momento.',
	},

	// ── Tracking ───────────────────────────────────────────────
	{
		id: 'trk-come-tracciare',
		category: 'Tracking',
		question: 'Come traccio una spedizione?',
		answer:
			'Hai tre opzioni: 1) accedi al pannello e clicca sulla spedizione per vedere lo storico completo aggiornato in tempo reale; 2) usa la pagina pubblica /traccia-spedizione inserendo numero LDV e CAP destinazione; 3) ricevi notifiche email automatiche a ogni cambio di stato (ritirato, in transito, in consegna, consegnato).',
	},
	{
		id: 'trk-stato-non-aggiornato',
		category: 'Tracking',
		question: 'Lo stato del tracking non si aggiorna da giorni: cosa fare?',
		answer:
			'I dati BRT vengono aggiornati a ogni passaggio in filiale, di solito ogni 12-24 ore. Se non vedi aggiornamenti da più di 48 ore lavorative apri una richiesta di "controllo spedizione" dal pannello: il nostro team contatta direttamente la filiale BRT competente e ti aggiorna entro 24 ore con la posizione effettiva del pacco.',
	},
	{
		id: 'trk-consegnato-non-ricevuto',
		category: 'Tracking',
		question: 'Il tracking dice "consegnato" ma non ho ricevuto il pacco: cosa fare?',
		answer:
			'Verifica prima con vicini, portineria, familiari e custode: nel 70% dei casi il pacco è stato consegnato a una persona presente all\'indirizzo. Se non lo trovi, apri subito (entro 24 ore) una segnalazione di "consegna non avvenuta" dal pannello. Forniremo a BRT la prova di consegna firmata e, se necessario, attiveremo la procedura di ricerca.',
	},

	// ── Reclami ────────────────────────────────────────────────
	{
		id: 'rec-danno',
		category: 'Reclami',
		question: 'Come richiedere un reclamo per danno?',
		answer:
			'Apri il reclamo entro 8 giorni dalla consegna dal tuo pannello, sezione "Reclami". Allega: foto del pacco prima dell\'apertura (con etichetta visibile), foto dell\'imballo aperto, foto del danno, ricevuta o fattura del contenuto. Più la documentazione è completa, più rapida è la chiusura: in media il rimborso arriva in 20-30 giorni lavorativi.',
	},
	{
		id: 'rec-smarrimento',
		category: 'Reclami',
		question: 'Cosa succede se il pacco viene smarrito?',
		answer:
			'Se dopo 10 giorni lavorativi il pacco non risulta consegnato né rintracciabile in filiale, apriamo automaticamente la pratica di smarrimento con BRT. Il rimborso base copre fino a 1€/kg (limite del corriere); se hai attivato l\'assicurazione integrativa il rimborso copre il valore reale dichiarato fino al massimale scelto, di solito entro 30-45 giorni.',
	},
	{
		id: 'rec-assicurazione',
		category: 'Reclami',
		question: "Conviene attivare l'assicurazione integrativa?",
		answer:
			'Se il valore della merce supera 50€ o se il contenuto è fragile/elettronico, sì: il rimborso base BRT (1€/kg) raramente copre il valore reale. Per 1-2€ in più ottieni copertura "all risk" fino al valore dichiarato (max 5.000€). L\'attivazione si fa al checkout con un click.',
	},
	{
		id: 'rec-tempi-risposta',
		category: 'Reclami',
		question: 'Quanto tempo serve per la risposta a un reclamo?',
		answer:
			'Per i reclami semplici (danni evidenti, documentazione completa) la risposta arriva in 7-15 giorni lavorativi. Per smarrimenti o casi complessi (consegne contestate, indirizzi errati) i tempi salgono a 30-45 giorni perché serve l\'istruttoria BRT. Riceverai email a ogni avanzamento dello stato e dal pannello vedi sempre la fase corrente.',
	},

	// ── Account ────────────────────────────────────────────────
	{
		id: 'acc-registrazione',
		category: 'Account',
		question: 'La registrazione è gratuita?',
		answer:
			'Sì. La registrazione su SpedizioneFacile è completamente gratuita, senza canoni mensili né vincoli. Crei il tuo account in 2 minuti, salvi indirizzi ricorrenti, vedi lo storico spedizioni e gestisci pagamenti e fatture. Paghi solo le spedizioni che effettui, niente più niente meno.',
	},
	{
		id: 'acc-recupero-password',
		category: 'Account',
		question: 'Ho dimenticato la password: come la recupero?',
		answer:
			'Vai su /recupera-password, inserisci l\'email del tuo account e clicca "Invia link". Riceverai entro 1-2 minuti un\'email con un link sicuro valido 60 minuti per impostare una nuova password. Se non vedi l\'email controlla la cartella spam, oppure scrivi al supporto e verificheremo manualmente la tua identità.',
	},
	{
		id: 'acc-modifica-dati',
		category: 'Account',
		question: 'Come modifico email, telefono o dati di fatturazione?',
		answer:
			'Accedi al pannello, sezione "Profilo": puoi modificare in autonomia nome, telefono, password e dati di fatturazione (ragione sociale, partita IVA, codice SDI, indirizzo). Per cambiare l\'email principale serve verificare il nuovo indirizzo via link di conferma, per motivi di sicurezza.',
	},
	{
		id: 'acc-elimina-account',
		category: 'Account',
		question: 'Posso eliminare il mio account?',
		answer:
			'Sì. Dalla sezione "Profilo > Privacy" trovi il pulsante "Elimina account": dopo conferma, eliminiamo definitivamente i tuoi dati personali entro 30 giorni, nel rispetto del GDPR. Restano archiviati solo i dati fiscali obbligatori per legge (fatture emesse) per 10 anni come prescritto dalla normativa italiana.',
	},

	// ── Pro ────────────────────────────────────────────────────
	{
		id: 'pro-diventare',
		category: 'Pro',
		question: 'Come diventare utente Pro?',
		answer:
			'Per attivare il profilo Pro vai nel pannello, sezione "Account > Diventa Pro" e compila la richiesta indicando partita IVA, settore e volume medio mensile di spedizioni stimato. Il nostro team valuta la richiesta entro 2 giorni lavorativi e ti contatta per finalizzare l\'attivazione: nessun costo, nessun vincolo, solo vantaggi.',
	},
	{
		id: 'pro-vantaggi',
		category: 'Pro',
		question: 'Quali sono i vantaggi del profilo Pro?',
		answer:
			'Il profilo Pro sblocca: tariffe scalari personalizzate sul volume mensile (sconti dal 5% al 25%), fatturazione differita a 30 giorni invece del prepagato, account manager dedicato per richieste complesse, API per integrazione con il tuo gestionale o e-commerce, dashboard analitica con report mensili scaricabili, supporto prioritario via canale dedicato.',
	},
	{
		id: 'pro-volumi-minimi',
		category: 'Pro',
		question: 'Servono volumi minimi per accedere al profilo Pro?',
		answer:
			'Non c\'è una soglia rigida ma in genere dai 30 spedizioni/mese in su il profilo Pro inizia a essere conveniente. Sotto questa soglia il profilo Standard offre già le migliori tariffe del mercato senza canone. Sopra le 200 spedizioni/mese si attivano condizioni dedicate ulteriormente più vantaggiose: scrivici per una proposta su misura.',
	},
	{
		id: 'pro-api-ecommerce',
		category: 'Pro',
		question: 'Posso integrare SpedizioneFacile con il mio e-commerce?',
		answer:
			'Sì. Il profilo Pro include l\'accesso alle nostre API REST documentate per creare spedizioni, calcolare preventivi, scaricare etichette e ricevere webhook di tracking direttamente nel tuo sistema. Forniamo plugin pronti per WooCommerce, PrestaShop e Shopify; per Magento e gestionali custom il nostro team tecnico ti supporta nell\'integrazione.',
	},
];

// escapeHtml centralizzato in utils/html.ts (re-export per retro-compat dei caller).
import { escapeHtml as escapeHtmlUtil } from '~/utils/html';
export const escapeHtml = (value) => escapeHtmlUtil(value);

/** Evidenzia con tag <mark> le occorrenze case-insensitive della query. */
export function highlightMatch(text, query) {
	const safeText = escapeHtml(text);
	const trimmed = query.trim();
	if (!trimmed) return safeText;
	const escapedQuery = trimmed.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	const re = new RegExp(`(${escapedQuery})`, 'gi');
	return safeText.replace(re, '<mark class="faq-mark">$1</mark>');
}

/** Composable dataset FAQ statico. */
export function useFaqs() {
	// Array statici, esposti come riferimenti diretti.
	// Sono di fatto immutabili (definiti come const a livello modulo)
	// e vengono solo letti dalla pagina: niente mutazioni runtime.
	return {
		faqs: FAQS,
		categories: FAQ_CATEGORIES,
		escapeHtml,
		highlightMatch,
	};
}
