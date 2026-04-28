<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $guides = $this->getGuides();
        $services = $this->getServices();

        foreach ($guides as $index => $guide) {
            Article::updateOrCreate(
                ['slug' => $guide['slug']],
                array_merge($guide, [
                    'type' => 'guide',
                    'is_published' => true,
                    'sort_order' => $index + 1,
                ])
            );
        }

        foreach ($services as $index => $service) {
            Article::updateOrCreate(
                ['slug' => $service['slug']],
                array_merge($service, [
                    'type' => 'service',
                    'is_published' => true,
                    'sort_order' => $index + 1,
                ])
            );
        }
    }

    private function getGuides(): array
    {
        return [
            [
                'slug' => 'come-preparare-un-pacco',
                'title' => 'Come preparare un pacco per la spedizione',
                'meta_description' => 'Guida completa su come preparare un pacco per la spedizione: scelta della scatola, materiali di riempimento, chiusura e etichettatura.',
                'intro' => 'Scopri come preparare correttamente un pacco per garantire che arrivi a destinazione in perfette condizioni.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 14l12-7 12 7v14l-12 7-12-7V14z"/><path d="M8 14l12 7"/><path d="M20 35V21"/><path d="M32 14l-12 7"/><path d="M26 10.5l-12 7"/><path d="M14 10v7l6 3.5"/></svg>',
                'sections' => [
                    ['heading' => 'Scegliere la scatola giusta', 'text' => 'Il primo passo per una spedizione sicura è la scelta della scatola. Utilizza sempre un contenitore in cartone ondulato rigido, proporzionato al contenuto: una scatola troppo grande costringe a usare più materiale di riempimento e aumenta il rischio che l\'oggetto si muova durante il trasporto. Se possibile, scegli una scatola nuova o in ottime condizioni, senza piegature o segni di usura che possano comprometterne la resistenza strutturale.'],
                    ['heading' => 'Proteggere il contenuto', 'text' => 'Avvolgi ogni oggetto singolarmente con pluriball, carta kraft o schiuma protettiva. Riempi tutti gli spazi vuoti con materiale da imbottitura come patatine di polistirolo, carta accartocciata o cuscini d\'aria. L\'obiettivo è evitare qualsiasi movimento interno: il contenuto deve essere ben fermo e non deve toccare le pareti della scatola. Per oggetti pesanti, usa un doppio strato di cartone sul fondo.'],
                    ['heading' => 'Chiudere e sigillare correttamente', 'text' => 'Chiudi la scatola con nastro adesivo da imballaggio largo almeno 5 cm, applicandolo lungo tutte le giunture, sia sopra che sotto. Evita nastro trasparente da ufficio o nastro di carta, che non garantiscono tenuta sufficiente. Applica il nastro a forma di H sulle aperture per massimizzare la resistenza. Se il pacco è pesante, rinforza gli angoli con ulteriore nastro.'],
                    ['heading' => 'Etichettatura e documenti', 'text' => 'Applica l\'etichetta di spedizione su una superficie piana della scatola, in modo che sia completamente leggibile e non coperta da nastro o giunture. Inserisci anche un foglio con indirizzo del destinatario e del mittente all\'interno del pacco, come precauzione in caso l\'etichetta esterna si danneggi. Rimuovi o copri eventuali vecchie etichette o codici a barre per evitare confusione nella smistamento.'],
                    ['heading' => 'Ultimo controllo prima della spedizione', 'text' => 'Prima di affidare il pacco al corriere, agitalo leggermente: se senti rumori o movimenti interni, apri e aggiungi materiale di riempimento. Verifica che il peso e le dimensioni rientrino nei limiti del servizio scelto. Controlla che l\'indirizzo sia corretto e completo di CAP, numero civico e eventuale scala o interno. Un pacco ben preparato riduce drasticamente il rischio di danni e contestazioni.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'imballare-oggetti-fragili',
                'title' => 'Come imballare oggetti fragili',
                'meta_description' => 'Tecniche professionali per imballare oggetti fragili: vetro, ceramica, elettronica e altri materiali delicati.',
                'intro' => 'Tecniche e materiali per proteggere al meglio oggetti delicati e fragili durante il trasporto.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 5l14 7v16l-14 7L6 28V12l14-7z"/><path d="M20 18v-5"/><path d="M20 26l.01 0"/><path d="M15 15l5 3 5-3"/></svg>',
                'sections' => [
                    ['heading' => 'Materiali indispensabili', 'text' => 'Per imballare oggetti fragili servono materiali specifici: pluriball a bolle piccole per avvolgere ogni singolo pezzo, carta velina o carta di seta per il primo strato di protezione, schiuma espansa per riempire gli spazi e cartone ondulato per creare divisori interni. Non usare mai giornali a diretto contatto con l\'oggetto: l\'inchiostro potrebbe macchiare superfici delicate. Tieni a portata di mano anche nastro adesivo resistente e un pennarello per le indicazioni "Fragile".'],
                    ['heading' => 'Tecnica di avvolgimento', 'text' => 'Avvolgi ogni oggetto fragile individualmente, partendo da almeno due strati di pluriball. Per piatti e bicchieri, avvolgi ciascun pezzo separatamente e inseriscili in verticale nella scatola con divisori in cartone tra un pezzo e l\'altro. Per oggetti dalla forma irregolare, crea una sorta di nido con il pluriball e fissa con nastro adesivo. Assicurati che nessun oggetto tocchi direttamente le pareti della scatola: deve esserci almeno 5 cm di materiale protettivo su ogni lato.'],
                    ['heading' => 'La scatola dentro la scatola', 'text' => 'Per oggetti particolarmente delicati, usa la tecnica della doppia scatola: posiziona l\'oggetto imballato in una scatola interna, poi inserisci questa in una scatola esterna più grande riempiendo lo spazio tra le due con materiale ammortizzante. Questa tecnica assorbe gli urti e le vibrazioni del trasporto in modo molto efficace. È la soluzione raccomandata per vetro, porcellana, specchi e opere d\'arte.'],
                    ['heading' => 'Indicazioni sulla scatola', 'text' => 'Scrivi chiaramente "FRAGILE" su tutti i lati della scatola e sulla parte superiore. Aggiungi le frecce che indicano il verso corretto del pacco ("Alto" con freccia verso l\'alto). Queste indicazioni non garantiscono un trattamento speciale da parte del corriere, ma aumentano la probabilità che il pacco venga maneggiato con più attenzione. Considera anche l\'uso di adesivi "Fragile" prestampati, più visibili rispetto alle scritte a mano.'],
                    ['heading' => 'Assicurazione consigliata', 'text' => 'Per oggetti di valore elevato, l\'assicurazione sulla spedizione è fortemente raccomandata. Anche con il miglior imballaggio, incidenti di trasporto possono verificarsi. L\'assicurazione ti tutela economicamente in caso di danni. Scatta foto dell\'oggetto prima dell\'imballaggio e dell\'imballaggio completato: questa documentazione è essenziale per qualsiasi richiesta di rimborso.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'dimensioni-pesi-massimi',
                'title' => 'Guida alle dimensioni e pesi massimi',
                'meta_description' => 'Limiti di peso e dimensioni per le spedizioni con i principali corrieri italiani: BRT, GLS, DHL, SDA e altri.',
                'intro' => 'Tutto quello che devi sapere sui limiti di peso e dimensioni accettati dai principali corrieri.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 33h26"/><path d="M7 33V7"/><path d="M7 7l4 4"/><path d="M7 7l-4 4"/><path d="M33 33l-4-4"/><path d="M33 33l4-4"/><path d="M15 13h12v12H15z"/><path d="M19 13v12"/><path d="M23 13v12"/><path d="M15 19h12"/><path d="M15 23h12"/></svg>',
                'sections' => [
                    ['heading' => 'Perché conoscere i limiti è importante', 'text' => 'Ogni corriere stabilisce limiti di peso e dimensioni per i colli che trasporta. Superare questi limiti può comportare sovraprezzi, rifiuto del ritiro o problemi nella consegna. Conoscere in anticipo le specifiche del corriere scelto ti permette di preparare il pacco in modo corretto, evitare costi imprevisti e scegliere il servizio più adatto alle tue esigenze. SpediamoFacile ti mostra automaticamente i corrieri compatibili con le dimensioni del tuo pacco.'],
                    ['heading' => 'Limiti di peso standard', 'text' => 'La maggior parte dei corrieri nazionali accetta colli fino a 30-50 kg per le spedizioni standard. Per pesi superiori, esistono servizi dedicati che possono gestire fino a 70-100 kg per singolo collo. Oltre certi limiti, il trasporto richiede mezzi con sponda idraulica o servizi di logistica pesante. È importante ricordare che il peso fatturato può essere quello reale o quello volumetrico, a seconda di quale sia maggiore.'],
                    ['heading' => 'Come si calcola il peso volumetrico', 'text' => 'Il peso volumetrico si calcola moltiplicando le tre dimensioni del pacco (lunghezza x larghezza x altezza in centimetri) e dividendo il risultato per un coefficiente che varia per corriere, generalmente 5000 per spedizioni nazionali. Ad esempio, un pacco di 60x40x30 cm ha un peso volumetrico di 14,4 kg. Se il peso reale è inferiore, il corriere fatturerà il peso volumetrico. Questo sistema incentiva a usare scatole proporzionate al contenuto.'],
                    ['heading' => 'Limiti di dimensioni', 'text' => 'Le dimensioni massime accettate variano per corriere. In genere, il lato più lungo non deve superare i 175-200 cm e la somma di lunghezza più il doppio di altezza e larghezza non deve superare i 300-360 cm. Per pacchi fuori misura, alcuni corrieri offrono tariffe speciali o servizi dedicati. Verifica sempre le specifiche prima di preparare il pacco, soprattutto per oggetti lunghi o ingombranti come tubi, sci o mobili.'],
                    ['heading' => 'Consigli pratici', 'text' => 'Misura il pacco dopo averlo chiuso e imballato, non prima. L\'imbottitura e il nastro adesivo aggiungono centimetri. Usa una bilancia precisa per il peso e un metro rigido per le dimensioni. Se sei al limite, considera di dividere il contenuto in due colli più piccoli: spesso due pacchi standard costano meno di uno fuori misura. Su SpediamoFacile puoi inserire peso e dimensioni nel preventivo per vedere subito le opzioni disponibili e i relativi costi.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'tracciare-spedizione',
                'title' => 'Come tracciare la tua spedizione',
                'meta_description' => 'Come monitorare lo stato della tua spedizione in tempo reale: codice tracking, notifiche e area personale.',
                'intro' => 'Impara a seguire il tuo pacco in tempo reale dalla partenza fino alla consegna.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="14" cy="14" r="4"/><path d="M14 18c-5 0-8 3-8 6"/><circle cx="28" cy="28" r="3"/><path d="M14 14l14 14"/><path d="M10 28h2"/><path d="M30 10v2"/><circle cx="30" cy="10" r="2"/></svg>',
                'sections' => [
                    ['heading' => 'Il codice di tracciamento', 'text' => 'Dopo aver confermato la spedizione, ricevi un codice di tracciamento (tracking number) univoco che identifica il tuo pacco all\'interno della rete del corriere. Questo codice ti viene inviato via email e lo trovi anche nella tua area personale su SpediamoFacile. Conservalo fino alla conferma di avvenuta consegna: è lo strumento principale per monitorare lo stato della spedizione e per qualsiasi comunicazione con il servizio assistenza.'],
                    ['heading' => 'Dove inserire il codice tracking', 'text' => 'Puoi tracciare la tua spedizione direttamente dalla tua area personale su SpediamoFacile, dove trovi lo stato aggiornato di tutti i tuoi ordini in un unico pannello. In alternativa, puoi usare il codice tracking sul sito web del corriere che gestisce la consegna. SpediamoFacile ti indica sempre quale corriere è stato assegnato alla tua spedizione, così sai esattamente dove cercare informazioni più dettagliate se necessario.'],
                    ['heading' => 'Gli stati della spedizione', 'text' => 'Durante il viaggio, il pacco attraversa diversi stati: "Ritirato" indica che il corriere ha preso in carico il pacco; "In transito" significa che si sta spostando verso la destinazione attraverso i centri di smistamento; "In consegna" segnala che il pacco è sul furgone per la consegna finale; "Consegnato" conferma che il destinatario ha ricevuto il pacco. Eventuali stati come "Giacenza" o "Tentativo di consegna fallito" richiedono attenzione e possono essere gestiti dalla tua area personale.'],
                    ['heading' => 'Tempi di aggiornamento', 'text' => 'Il tracciamento non si aggiorna in tempo reale istantaneo: i dati vengono trasmessi quando il pacco passa per un punto di scansione, come un centro di smistamento o il furgone di consegna. Nelle prime ore dopo il ritiro potrebbe non esserci alcun aggiornamento: è normale. Se dopo 24-48 ore lavorative il tracking non mostra progressi, contatta l\'assistenza di SpediamoFacile per verificare la situazione.'],
                    ['heading' => 'Notifiche automatiche', 'text' => 'SpediamoFacile ti invia notifiche via email nei momenti chiave della spedizione: conferma di ritiro, pacco in transito e avvenuta consegna. Queste notifiche ti risparmiano la necessità di controllare manualmente il tracking. Se hai attivato il servizio di notifica al destinatario, anche il ricevente verrà avvisato dello stato della spedizione e dell\'orario stimato di consegna, quando disponibile.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'pacco-danneggiato',
                'title' => 'Cosa fare se il pacco è danneggiato',
                'meta_description' => 'Procedura completa per gestire un pacco danneggiato: reclamo, documentazione fotografica e rimborso.',
                'intro' => 'La procedura da seguire in caso di pacco danneggiato: documentazione, reclamo e rimborso.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 33L20 7l15 26H5z"/><path d="M20 15v8"/><circle cx="20" cy="27" r="0.5" fill="#ffffff"/></svg>',
                'sections' => [
                    ['heading' => 'Verifica immediata alla consegna', 'text' => 'Quando ricevi un pacco, controllalo attentamente prima di firmare la ricevuta di consegna. Se noti danni evidenti all\'esterno (ammaccature, buchi, nastro rotto, scatola bagnata), accetta il pacco "con riserva" specificando il tipo di danno sulla ricevuta del corriere. Questa annotazione è fondamentale per qualsiasi successiva richiesta di rimborso. Se il corriere ti chiede di firmare senza riserva, hai il diritto di insistere per annotare i danni visibili.'],
                    ['heading' => 'Documentare il danno', 'text' => 'Scatta foto dettagliate del pacco dall\'esterno prima di aprirlo, poi fotografa l\'imballo interno e infine il contenuto danneggiato. Le foto devono mostrare chiaramente l\'entità del danno e, se possibile, includere un riferimento dimensionale. Conserva tutto il materiale di imballaggio, compresa la scatola e i riempitivi: il corriere potrebbe richiederli per l\'ispezione. Questa documentazione è la base per la richiesta di risarcimento.'],
                    ['heading' => 'Aprire un reclamo su SpediamoFacile', 'text' => 'Dalla tua area personale su SpediamoFacile, vai nella sezione della spedizione interessata e avvia la procedura di reclamo. Ti verrà chiesto di caricare le foto del danno, descrivere il contenuto danneggiato e indicare il valore della merce. Il nostro team di assistenza gestirà la comunicazione con il corriere per tuo conto, seguendo i tempi e le procedure previste dalle condizioni del vettore.'],
                    ['heading' => 'Tempi e modalità di rimborso', 'text' => 'I tempi di gestione del reclamo dipendono dal corriere e dalla complessità del caso: in genere servono da 15 a 45 giorni lavorativi. Il rimborso base copre il valore dichiarato della merce fino ai limiti previsti dalle condizioni generali del corriere, che spesso sono calcolati per chilogrammo. Se avevi attivato l\'assicurazione aggiuntiva, la copertura può essere più ampia e i tempi di rimborso più rapidi.'],
                    ['heading' => 'Come prevenire i danni', 'text' => 'La migliore difesa contro i danni da trasporto è un imballaggio accurato. Usa scatole rigide, materiale protettivo abbondante e la tecnica della doppia scatola per oggetti fragili. Per merci di valore, attiva sempre l\'assicurazione sulla spedizione: il costo è minimo rispetto alla tranquillità che offre. Consulta la nostra guida dedicata all\'imballaggio di oggetti fragili per tecniche dettagliate.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'spedire-elettronica',
                'title' => 'Spedire elettronica in sicurezza',
                'meta_description' => 'Come spedire dispositivi elettronici in sicurezza: imballaggio, protezione antistatica e assicurazione.',
                'intro' => 'Come imballare e spedire dispositivi elettronici senza rischi di danni.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="8" width="28" height="19" rx="2"/><path d="M6 23h28"/><path d="M16 31h8"/><path d="M20 27v4"/><circle cx="20" cy="25" r="0.5" fill="#ffffff"/></svg>',
                'sections' => [
                    ['heading' => 'Preparazione del dispositivo', 'text' => 'Prima di spedire un dispositivo elettronico, rimuovi batterie esterne rimovibili e conservale separatamente con i contatti protetti da nastro isolante. Spegni completamente il dispositivo (non in standby). Rimuovi accessori sporgenti come cavi, adattatori, schede di memoria o pennette USB e imballali a parte. Se spedisci un computer, considera di rimuovere l\'hard disk e spedirlo separatamente per proteggere i dati. Effettua un backup dei dati prima della spedizione.'],
                    ['heading' => 'Protezione antistatica', 'text' => 'I componenti elettronici sono sensibili alle scariche elettrostatiche. Avvolgi il dispositivo in una busta antistatica prima di procedere con l\'imballaggio tradizionale. Le buste antistatiche si trovano facilmente online o nei negozi di elettronica. Se non hai una busta antistatica, usa almeno un sacchetto di plastica per proteggere dall\'umidità, ma evita materiali che generano elettricità statica come il polistirolo sfuso a diretto contatto con il dispositivo.'],
                    ['heading' => 'Imballaggio ideale', 'text' => 'Se hai la confezione originale del dispositivo, usala: è progettata per proteggere quel prodotto specifico. In mancanza dell\'originale, avvolgi il dispositivo con almeno 5 cm di pluriball su tutti i lati, poi inseriscilo in una scatola rigida. Usa la tecnica della doppia scatola per dispositivi di valore: scatola interna con il dispositivo protetto, scatola esterna con materiale ammortizzante tra le due. Assicurati che non ci sia nessun movimento interno scuotendo delicatamente il pacco.'],
                    ['heading' => 'Batterie al litio e normative', 'text' => 'I dispositivi con batterie al litio integrate (smartphone, tablet, laptop) possono essere spediti con i corrieri standard via terra, ma ci sono restrizioni per le spedizioni aeree e internazionali. Le batterie al litio sfuse richiedono imballaggio e documentazione specifici. Verifica sempre le normative del corriere scelto prima della spedizione. SpediamoFacile ti segnala eventuali restrizioni durante la creazione dell\'ordine.'],
                    ['heading' => 'Assicurazione e valore dichiarato', 'text' => 'Per dispositivi elettronici, l\'assicurazione aggiuntiva è quasi sempre consigliabile dato il loro valore elevato. Dichiara il valore reale del dispositivo e conserva la prova d\'acquisto o una stima del valore di mercato. In caso di danno o smarrimento, la documentazione del valore è essenziale per ottenere il rimborso. Fotografa il dispositivo funzionante prima della spedizione come ulteriore prova delle sue condizioni.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'contrassegno',
                'title' => 'Guida al contrassegno (pagamento alla consegna)',
                'meta_description' => 'Come funziona il contrassegno: pagamento alla consegna con i corrieri, limiti, costi e procedura su SpediamoFacile.',
                'intro' => 'Come funziona il pagamento alla consegna: vantaggi, limiti e procedura completa.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="10" width="28" height="20" rx="2"/><path d="M6 16h28"/><circle cx="20" cy="24" r="4"/><path d="M18.5 24h3"/><path d="M20 22.5v3"/></svg>',
                'sections' => [
                    ['heading' => 'Cos\'è il contrassegno', 'text' => 'Il contrassegno è un servizio accessorio alla spedizione che prevede l\'incasso di una somma di denaro da parte del corriere al momento della consegna. Il destinatario paga al corriere l\'importo stabilito dal mittente e solo dopo aver pagato riceve il pacco. L\'importo incassato viene poi riversato al mittente secondo le modalità e i tempi concordati. È una soluzione molto diffusa in Italia, specialmente per le vendite online dove il cliente preferisce pagare alla ricezione della merce.'],
                    ['heading' => 'Quando conviene usarlo', 'text' => 'Il contrassegno è particolarmente utile quando vendi a clienti che non dispongono di metodi di pagamento elettronici, quando operi in mercati dove il pagamento anticipato incontra resistenze, o quando vuoi offrire un\'opzione di pagamento aggiuntiva per aumentare le conversioni del tuo negozio. È anche una soluzione pratica per vendite occasionali tra privati, dove il pagamento anticipato potrebbe generare diffidenza. Va considerato che il contrassegno comporta un sovrapprezzo sulla spedizione.'],
                    ['heading' => 'Come attivarlo su SpediamoFacile', 'text' => 'Durante la creazione della spedizione su SpediamoFacile, trovi l\'opzione "Pagamento alla consegna" tra i servizi aggiuntivi. Attivandola, ti viene chiesto di indicare l\'importo da incassare e la modalità di riversamento preferita (accredito su conto corrente o portafoglio SpediamoFacile). L\'importo del contrassegno viene stampato sull\'etichetta e comunicato al corriere. Il sovrapprezzo per il servizio viene mostrato prima della conferma dell\'ordine.'],
                    ['heading' => 'Limiti e condizioni', 'text' => 'Ogni corriere stabilisce un importo massimo per il contrassegno, che generalmente varia tra 1.000 e 3.000 euro per i servizi nazionali. Il pagamento al corriere avviene tipicamente in contanti; alcuni vettori accettano anche POS dove disponibile. È fondamentale che il destinatario sia informato dell\'importo esatto da pagare e del metodo di pagamento accettato. In caso di rifiuto del pagamento, il pacco non viene consegnato e può rientrare al mittente, con i costi di giacenza e reso a carico del mittente.'],
                    ['heading' => 'Riversamento dell\'incasso', 'text' => 'Dopo l\'avvenuto incasso, l\'importo viene riversato al mittente entro i tempi previsti dal vettore, che possono variare da pochi giorni lavorativi a un paio di settimane. Su SpediamoFacile, puoi monitorare lo stato dell\'incasso dalla tua area personale, con indicazione chiara di "incasso in corso", "incasso eseguito" e "riversato". Puoi scaricare le quietanze e le distinte di riversamento per la tua contabilità. Il portafoglio SpediamoFacile come metodo di riversamento offre generalmente tempi più rapidi.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'scegliere-corriere',
                'title' => 'Come scegliere il corriere giusto',
                'meta_description' => 'Criteri per scegliere il corriere migliore: tempi di consegna, prezzi, copertura territoriale e servizi aggiuntivi.',
                'intro' => 'Criteri e consigli per selezionare il corriere più adatto alle tue esigenze di spedizione.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 10h18v16H6z"/><path d="M24 16h6l4 5v5h-10V16z"/><circle cx="12" cy="28" r="3"/><circle cx="28" cy="28" r="3"/><path d="M15 26h10"/></svg>',
                'sections' => [
                    ['heading' => 'I criteri di scelta principali', 'text' => 'La scelta del corriere dipende da diversi fattori: il tipo di merce da spedire, la destinazione, i tempi di consegna richiesti, il budget disponibile e i servizi aggiuntivi necessari. Non esiste un corriere migliore in assoluto: ogni vettore ha punti di forza specifici. SpediamoFacile confronta automaticamente le opzioni disponibili e ti mostra i corrieri compatibili con le tue esigenze, ordinati per prezzo e tempi di consegna.'],
                    ['heading' => 'Tempi di consegna', 'text' => 'Se la velocità è la tua priorità, confronta i tempi di consegna stimati. Le spedizioni nazionali standard impiegano generalmente 1-3 giorni lavorativi sulle tratte principali, mentre le zone remote o le isole possono richiedere 1-2 giorni in più. I servizi espressi garantiscono la consegna entro il giorno lavorativo successivo su gran parte del territorio nazionale. Per le spedizioni internazionali, i tempi variano significativamente in base alla destinazione e al tipo di servizio.'],
                    ['heading' => 'Copertura territoriale', 'text' => 'Non tutti i corrieri coprono allo stesso modo l\'intero territorio. Alcuni sono particolarmente forti sulle tratte nord-sud, altri hanno una rete capillare nelle zone rurali, altri ancora eccellono nelle consegne urbane. Per le isole e le zone disagiate, verifica che il corriere offra un servizio regolare e a costi ragionevoli. SpediamoFacile ti mostra solo i corrieri che effettivamente servono la tratta che ti interessa, evitando sorprese.'],
                    ['heading' => 'Servizi aggiuntivi disponibili', 'text' => 'Valuta quali servizi accessori ti servono: contrassegno, assicurazione, consegna su appuntamento, consegna al piano, sponda idraulica per colli pesanti, ritiro a domicilio. Non tutti i corrieri offrono tutti i servizi, e i costi possono variare sensibilmente. Se hai bisogno di servizi specifici, filtra le opzioni in base a queste necessità prima di confrontare i prezzi.'],
                    ['heading' => 'Rapporto qualità-prezzo', 'text' => 'Il prezzo più basso non è sempre la scelta migliore. Considera il rapporto tra costo, tempi di consegna, affidabilità e servizi inclusi. Un corriere leggermente più costoso che offre tracciamento dettagliato, consegna puntuale e assistenza reattiva può farti risparmiare tempo e problemi. Leggi le recensioni e, se spedisci regolarmente, prova diversi corrieri per trovare quello che meglio si adatta alle tue esigenze specifiche.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'nazionali-vs-internazionali',
                'title' => 'Spedizioni nazionali vs internazionali',
                'meta_description' => 'Differenze tra spedizioni nazionali e internazionali: documentazione, tempi, costi e normative doganali.',
                'intro' => 'Le differenze principali tra spedizioni in Italia e all\'estero: tempi, costi e documentazione.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="20" cy="20" r="14"/><ellipse cx="20" cy="20" rx="6" ry="14"/><path d="M6 20h28"/><path d="M8 12h24"/><path d="M8 28h24"/></svg>',
                'sections' => [
                    ['heading' => 'Le differenze fondamentali', 'text' => 'Le spedizioni nazionali si muovono all\'interno dei confini italiani e sono soggette solo alla normativa nazionale. Le spedizioni internazionali attraversano i confini e possono essere soggette a controlli doganali, dazi, tasse di importazione e normative diverse a seconda del paese di destinazione. All\'interno dell\'Unione Europea, le merci circolano liberamente senza dazi, ma per spedizioni extra-UE la documentazione doganale è obbligatoria.'],
                    ['heading' => 'Documentazione necessaria', 'text' => 'Per le spedizioni nazionali, l\'etichetta di spedizione con i dati del mittente e del destinatario è sufficiente. Per le spedizioni internazionali, servono documenti aggiuntivi: la fattura commerciale o proforma con la descrizione dettagliata del contenuto, il valore e l\'origine della merce. Per le spedizioni extra-UE serve anche la dichiarazione doganale. Alcuni prodotti richiedono certificati specifici come fitosanitari, CITES per prodotti di origine animale o vegetale, o licenze di esportazione.'],
                    ['heading' => 'Tempi e costi', 'text' => 'Le spedizioni nazionali standard richiedono 1-3 giorni lavorativi e hanno costi contenuti. Le spedizioni internazionali in Europa impiegano 3-7 giorni lavorativi, mentre quelle intercontinentali possono richiedere 5-15 giorni via aerea o 20-40 giorni via mare. I costi internazionali sono significativamente più alti a causa delle distanze, della gestione doganale e delle tariffe dei vettori. Dazi e tasse di importazione nel paese di destinazione sono generalmente a carico del destinatario.'],
                    ['heading' => 'Restrizioni e merci vietate', 'text' => 'Le restrizioni per le spedizioni internazionali sono più severe. Oltre ai divieti comuni a tutte le spedizioni (esplosivi, materiali pericolosi), le spedizioni internazionali possono essere limitate da embarghi commerciali, restrizioni su prodotti alimentari, farmaci, cosmetici e prodotti tecnologici soggetti a controllo delle esportazioni. Ogni paese ha le proprie regole: verifica sempre le restrizioni specifiche della destinazione prima di spedire.'],
                    ['heading' => 'Come gestire le spedizioni internazionali su SpediamoFacile', 'text' => 'Su SpediamoFacile, seleziona il paese di destinazione nel preventivo e il sistema ti mostrerà automaticamente i corrieri disponibili, i tempi stimati e i costi. Per le spedizioni extra-UE, ti guidiamo nella compilazione della documentazione doganale richiesta. Ti segnaliamo anche eventuali restrizioni note per la destinazione scelta. Per volumi regolari di spedizioni internazionali, contattaci per valutare condizioni personalizzate.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'risparmiare-spedizioni',
                'title' => 'Come risparmiare sulle spedizioni',
                'meta_description' => 'Consigli pratici per risparmiare sulle spedizioni: confronto prezzi, peso volumetrico, imballaggio e servizi di gruppo.',
                'intro' => 'Strategie pratiche per ridurre i costi di spedizione senza rinunciare alla qualità del servizio.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 34c-4 0-7-3-7-7V17a10 10 0 0 1 20 0v1"/><ellipse cx="12" cy="17" rx="10" ry="3"/><path d="M28 20v-2"/><circle cx="28" cy="24" r="6"/><path d="M26.5 24h3"/><path d="M28 22.5v3"/></svg>',
                'sections' => [
                    ['heading' => 'Confronta sempre i prezzi', 'text' => 'Il primo modo per risparmiare è confrontare le tariffe di diversi corrieri per la stessa tratta. I prezzi possono variare anche del 30-50% tra un vettore e l\'altro per lo stesso servizio. SpediamoFacile ti mostra automaticamente tutte le opzioni disponibili ordinate per prezzo, permettendoti di scegliere la più conveniente senza dover visitare i siti di decine di corrieri. Ricorda che il prezzo più basso non include sempre gli stessi servizi: verifica cosa è incluso nella tariffa.'],
                    ['heading' => 'Ottimizza peso e dimensioni', 'text' => 'Il costo della spedizione dipende dal peso effettivo o dal peso volumetrico, il maggiore dei due. Usa scatole il più possibile aderenti al contenuto per ridurre il volume inutile. Evita scatole troppo grandi riempite di materiale protettivo: il peso volumetrico risultante potrebbe far lievitare il costo. Se possibile, scegli materiali di riempimento leggeri come cuscini d\'aria al posto della carta accartocciata. Ogni centimetro risparmiato in dimensioni si traduce in risparmio economico.'],
                    ['heading' => 'Spedisci con frequenza e volumi', 'text' => 'Se spedisci regolarmente, anche piccoli volumi, puoi ottenere tariffe migliori. SpediamoFacile offre condizioni vantaggiose per chi ha volumi ricorrenti. Raggruppa le spedizioni quando possibile: inviare più colli con un unico ritiro è più economico che prenotare ritiri separati. Se gestisci un\'attività, pianifica le spedizioni per massimizzare l\'efficienza e ridurre i costi di ritiro.'],
                    ['heading' => 'Scegli il servizio giusto', 'text' => 'Non tutte le spedizioni richiedono la consegna espressa. Se il pacco non è urgente, il servizio standard costa significativamente meno e la differenza nei tempi è spesso di uno o due giorni lavorativi. Valuta se servizi aggiuntivi come l\'assicurazione, il contrassegno o la consegna su appuntamento siano davvero necessari: ognuno ha un costo che si somma alla tariffa base.'],
                    ['heading' => 'Approfitta delle promozioni', 'text' => 'SpediamoFacile propone periodicamente offerte e codici sconto. Iscriviti alla newsletter per essere aggiornato. Il portafoglio ricaricabile può offrire vantaggi in termini di velocità di pagamento. Per i venditori online con volumi importanti, contattataci per un preventivo personalizzato: le tariffe dedicate possono fare una differenza significativa sui margini del tuo business.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'documenti-necessari',
                'title' => 'Documenti necessari per la spedizione',
                'meta_description' => 'Tutti i documenti necessari per spedire in Italia e all\'estero: etichetta, fattura, dichiarazione doganale e certificati.',
                'intro' => 'Quali documenti servono per spedire in Italia e all\'estero: guida completa alla documentazione.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 6h12l8 8v20a2 2 0 0 1-2 2H11a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/><path d="M23 6v8h8"/><path d="M14 18h12"/><path d="M14 23h12"/><path d="M14 28h8"/></svg>',
                'sections' => [
                    ['heading' => 'Spedizioni nazionali', 'text' => 'Per le spedizioni all\'interno dell\'Italia, la documentazione richiesta è minima: serve l\'etichetta di spedizione con i dati completi del mittente e del destinatario (nome, indirizzo completo con CAP, numero di telefono). L\'etichetta viene generata automaticamente da SpediamoFacile dopo la conferma dell\'ordine e può essere stampata o, dove il corriere lo consente, gestita in formato digitale. Per vendite commerciali, è buona pratica allegare il documento di trasporto (DDT) con la descrizione del contenuto.'],
                    ['heading' => 'Spedizioni nell\'Unione Europea', 'text' => 'All\'interno dell\'UE, le merci circolano liberamente senza formalità doganali per i beni commercializzati legalmente. Tuttavia, per spedizioni commerciali è necessaria la fattura o il documento di trasporto che attesti la natura della merce e il suo valore. Per alcune categorie merceologiche (alimentari, farmaci, prodotti fitosanitari) possono essere richiesti certificati specifici anche all\'interno dell\'UE. SpediamoFacile ti guida nella preparazione della documentazione corretta.'],
                    ['heading' => 'Spedizioni extra-UE', 'text' => 'Per le spedizioni fuori dall\'Unione Europea, la documentazione doganale è obbligatoria. Servono: fattura commerciale o proforma in lingua inglese con descrizione dettagliata della merce, quantità, valore, peso e paese di origine; dichiarazione doganale del corriere; e, a seconda della merce e della destinazione, certificati di origine, licenze di esportazione o documentazione sanitaria. I codici doganali (codici HS) devono essere indicati correttamente per evitare ritardi o blocchi alla dogana.'],
                    ['heading' => 'Documenti per merci particolari', 'text' => 'Alcune tipologie di merce richiedono documentazione aggiuntiva indipendentemente dalla destinazione: le merci pericolose necessitano della dichiarazione ADR o IATA a seconda del mezzo di trasporto; alimenti e bevande possono richiedere certificati sanitari; prodotti di origine animale o vegetale destinati fuori UE necessitano di certificati fitosanitari o veterinari; opere d\'arte e beni culturali possono richiedere l\'autorizzazione alla circolazione rilasciata dalla Soprintendenza.'],
                    ['heading' => 'Come SpediamoFacile ti aiuta', 'text' => 'SpediamoFacile genera automaticamente l\'etichetta di spedizione e, per le spedizioni internazionali, ti guida nella compilazione della documentazione doganale. Durante la creazione dell\'ordine, ti segnaliamo i documenti necessari in base alla destinazione e alla tipologia di merce dichiarata. Per esigenze particolari o dubbi sulla documentazione, il nostro servizio assistenza è disponibile per aiutarti a preparare tutto il necessario prima della spedizione.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'cosa-non-spedire',
                'title' => 'Cosa non si può spedire',
                'meta_description' => 'Elenco completo degli oggetti vietati e soggetti a restrizioni nelle spedizioni: merci pericolose, alimenti, valori e altro.',
                'intro' => 'L\'elenco degli oggetti vietati o soggetti a restrizioni nelle spedizioni nazionali e internazionali.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="20" cy="20" r="14"/><path d="M10 10l20 20"/></svg>',
                'sections' => [
                    ['heading' => 'Oggetti sempre vietati', 'text' => 'Esistono categorie di oggetti la cui spedizione è sempre vietata, indipendentemente dal corriere o dalla destinazione. Tra questi: esplosivi e materiali infiammabili, armi da fuoco e munizioni, sostanze stupefacenti e psicotrope, materiale radioattivo, organismi vivi pericolosi. Tentare di spedire questi oggetti è illegale e comporta responsabilità penali oltre alla confisca della merce. Nessun corriere accetta questi articoli e SpediamoFacile non li gestisce in alcun caso.'],
                    ['heading' => 'Merci pericolose regolamentate', 'text' => 'Alcune merci sono classificate come pericolose ma possono essere spedite con specifiche autorizzazioni e imballaggi conformi alle normative ADR (trasporto su strada) o IATA (trasporto aereo). Rientrano in questa categoria: batterie al litio sfuse, liquidi infiammabili, gas compressi, sostanze corrosive, magneti potenti. La spedizione di queste merci richiede imballaggi omologati, etichettatura specifica e documentazione di accompagnamento. Non tutti i corrieri sono autorizzati a trasportarle.'],
                    ['heading' => 'Alimenti e deperibili', 'text' => 'La spedizione di alimenti è possibile con alcune limitazioni. I prodotti secchi, confezionati e a lunga conservazione possono generalmente essere spediti con corriere standard. Alimenti freschi o deperibili richiedono imballaggi isotermici e spedizioni rapide, non tutti i corrieri offrono questo servizio. Per le spedizioni internazionali, le restrizioni sugli alimenti sono molto più severe: molti paesi vietano l\'importazione di carne, latticini, frutta e verdura fresca. Verifica sempre le normative del paese di destinazione.'],
                    ['heading' => 'Denaro e oggetti di valore', 'text' => 'La spedizione di denaro contante, carte di credito, titoli al portatore e documenti di identità è generalmente vietata o fortemente sconsigliata dai corrieri standard. Per gioielli, orologi di valore, pietre preziose e altri oggetti ad alto valore, i corrieri standard prevedono limiti di copertura molto bassi. Per questi articoli, esistono servizi di trasporto valori specializzati con assicurazione adeguata, veicoli blindati e procedure di sicurezza dedicate.'],
                    ['heading' => 'Come verificare se puoi spedire un oggetto', 'text' => 'In caso di dubbio, consulta le condizioni generali del corriere scelto o contatta il servizio assistenza di SpediamoFacile prima di preparare la spedizione. Durante la creazione dell\'ordine, ti chiediamo una descrizione del contenuto: se rileveremo possibili incompatibilità, ti avviseremo prima della conferma. Meglio investire qualche minuto in una verifica preventiva che rischiare il sequestro della merce, sanzioni o la perdita della copertura assicurativa per dichiarazione non conforme.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'ritiro-domicilio',
                'title' => 'Come funziona il ritiro a domicilio',
                'meta_description' => 'Guida completa al ritiro a domicilio: come prenotare, preparare il pacco e gestire il passaggio del corriere.',
                'intro' => 'Tutto sul servizio di ritiro a domicilio: come prenotarlo, preparare il pacco e cosa aspettarsi.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 20l14-13 14 13"/><path d="M10 18v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V18"/><path d="M16 34v-10h8v10"/></svg>',
                'sections' => [
                    ['heading' => 'Cos\'è il ritiro a domicilio', 'text' => 'Il ritiro a domicilio è il servizio con cui il corriere viene direttamente al tuo indirizzo per prelevare il pacco. Non devi portare nulla in ufficio postale o punto di raccolta: prepari il pacco, prenoti il ritiro e attendi il corriere. È il servizio più comodo per chi spedisce da casa o dall\'ufficio, incluso nel prezzo della maggior parte delle spedizioni su SpediamoFacile. Il corriere passa nella fascia oraria concordata e ritira il pacco già pronto con l\'etichetta applicata.'],
                    ['heading' => 'Come prenotare il ritiro', 'text' => 'Su SpediamoFacile, il ritiro a domicilio viene prenotato automaticamente durante la creazione della spedizione. Indichi l\'indirizzo di ritiro, la data preferita e la fascia oraria (mattina o pomeriggio, dove disponibile). Ricevi una conferma via email con i dettagli del ritiro. Se hai bisogno di modificare data o indirizzo, puoi farlo dalla tua area personale prima della scadenza per le modifiche, che varia a seconda del corriere.'],
                    ['heading' => 'Preparare il pacco per il ritiro', 'text' => 'Il pacco deve essere pronto e chiuso quando arriva il corriere. L\'etichetta di spedizione deve essere stampata e applicata in modo visibile su una superficie piana della scatola. Controlla che l\'indirizzo del destinatario sia corretto e che il pacco rispetti i limiti di peso e dimensioni del servizio scelto. Se hai più colli, raggruppali in un punto facilmente accessibile. Il corriere non è tenuto ad attendere la preparazione del pacco.'],
                    ['heading' => 'Cosa aspettarsi dal corriere', 'text' => 'Il corriere si presenta nell\'orario concordato e ritira il pacco. In genere, la finestra di ritiro è ampia (esempio: 9-13 o 14-18), quindi è importante essere disponibili per tutta la fascia indicata. Il corriere verifica che il pacco sia chiuso e che l\'etichetta sia applicata, ma non verifica il contenuto. Ritira il pacco e ti lascia una ricevuta di presa in carico. Da quel momento, il tracking viene attivato e puoi seguire il viaggio del pacco online.'],
                    ['heading' => 'Cosa fare se il corriere non passa', 'text' => 'Se il corriere non si presenta nella fascia oraria indicata, verifica prima lo stato del ritiro nella tua area personale. Potrebbe esserci stato un ritardo operativo. Se il ritiro risulta "non effettuato", contatta l\'assistenza SpediamoFacile: provvederemo a riprogrammare il ritiro per il giorno lavorativo successivo senza costi aggiuntivi. Per evitare problemi, assicurati che l\'indirizzo sia corretto, che il citofono funzioni e che qualcuno sia presente per consegnare il pacco.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'assicurazione-spedizione',
                'title' => 'Assicurazione sulla spedizione: quando conviene',
                'meta_description' => 'Guida all\'assicurazione sulle spedizioni: quando conviene, quanto costa, cosa copre e come richiedere il rimborso.',
                'intro' => 'Quando conviene assicurare un pacco, quanto costa e come funziona la copertura.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 5L7 11v10c0 9 6 15 13 18 7-3 13-9 13-18V11L20 5z"/><path d="M15 20l4 4 7-8"/></svg>',
                'sections' => [
                    ['heading' => 'Cos\'è l\'assicurazione sulla spedizione', 'text' => 'L\'assicurazione sulla spedizione è un servizio accessorio che copre il valore della merce in caso di danneggiamento, smarrimento o furto durante il trasporto. Senza assicurazione, il rimborso del corriere è limitato ai massimali previsti dalle condizioni generali del vettore, calcolati in genere per chilogrammo di peso e spesso insufficienti a coprire il valore reale della merce. Con l\'assicurazione aggiuntiva, puoi dichiarare il valore effettivo del contenuto e ottenere un rimborso adeguato.'],
                    ['heading' => 'Quando conviene assicurare', 'text' => 'L\'assicurazione conviene quando il valore della merce supera il rimborso base garantito dal corriere, quando spedisci oggetti fragili o facilmente danneggiabili, quando il contenuto è difficile da sostituire (pezzi unici, edizioni limitate, documenti originali), e quando spedisci verso destinazioni con elevato rischio di smarrimento. In generale, è consigliata per qualsiasi spedizione il cui valore superi i 50-100 euro.'],
                    ['heading' => 'Quanto costa', 'text' => 'Il costo dell\'assicurazione è proporzionale al valore dichiarato della merce, tipicamente tra l\'1% e il 3% del valore. Per un pacco del valore di 200 euro, l\'assicurazione costa indicativamente 2-6 euro. Su SpediamoFacile, il costo esatto viene calcolato e mostrato durante la creazione dell\'ordine, prima della conferma del pagamento. Rispetto al valore della merce, si tratta di un investimento minimo che offre una protezione significativa.'],
                    ['heading' => 'Cosa copre e cosa no', 'text' => 'L\'assicurazione copre danni fisici alla merce causati durante il trasporto, smarrimento del collo e furto documentato. Non copre generalmente: vizi propri della merce, imballaggio inadeguato (motivo per cui è fondamentale imballare correttamente), deperibilità naturale, ritardi nella consegna, danni indiretti o mancato guadagno. Le condizioni esatte variano in base alla polizza del corriere. Leggi sempre le condizioni prima di attivare il servizio.'],
                    ['heading' => 'Come richiedere il rimborso', 'text' => 'In caso di danno o smarrimento, apri un reclamo dalla tua area personale SpediamoFacile entro i tempi previsti (generalmente 5-7 giorni dalla consegna per danni visibili). Dovrai fornire: foto del pacco e del contenuto danneggiato, prova del valore della merce (fattura, ricevuta, listino), descrizione dettagliata del danno. Il nostro team gestisce la pratica con il corriere e la compagnia assicurativa, tenendoti aggiornato sullo stato del rimborso.'],
                ],
                'faqs' => null,
            ],
            [
                'slug' => 'faq-ecommerce',
                'title' => 'FAQ sulle spedizioni e-commerce',
                'meta_description' => 'Risposte alle domande più frequenti sulla gestione delle spedizioni per negozi online: integrazioni, resi, etichette e costi.',
                'intro' => 'Risposte alle domande più frequenti per chi gestisce un negozio online e deve spedire regolarmente.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="15" cy="15" r="6"/><path d="M15 21v7"/><path d="M11 30h8"/><path d="M25 8h6a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-2l-4 4v-4"/></svg>',
                'sections' => [
                    ['heading' => 'Come gestire le spedizioni del mio negozio online', 'text' => 'Per un e-commerce, la gestione efficiente delle spedizioni è fondamentale per la soddisfazione del cliente e la reputazione del negozio. SpediamoFacile ti permette di gestire tutte le spedizioni da un unico pannello, confrontando automaticamente le tariffe dei corrieri e scegliendo l\'opzione migliore per ogni ordine. Puoi creare spedizioni singolarmente o in blocco, generare etichette, prenotare ritiri e monitorare lo stato di tutte le consegne dalla tua area personale.'],
                    ['heading' => 'Posso personalizzare le tariffe di spedizione per i miei clienti?', 'text' => 'Su SpediamoFacile puoi impostare le tariffe che preferisci per i tuoi clienti, indipendentemente dal costo effettivo della spedizione. Puoi offrire spedizione gratuita sopra una certa soglia di acquisto, tariffe flat per zona geografica, o far pagare il costo reale della spedizione. La differenza tra il prezzo pagato dal cliente e il costo del corriere rappresenta il tuo margine o il tuo investimento in servizio al cliente.'],
                    ['heading' => 'Come gestire i resi', 'text' => 'La gestione dei resi è un aspetto critico dell\'e-commerce. Su SpediamoFacile puoi creare spedizioni di reso con etichetta prepagata da inviare al cliente, oppure richiedere al cliente di effettuare la spedizione di reso a proprie spese. Per una gestione efficiente, stabilisci una policy di reso chiara e comunicala al cliente prima dell\'acquisto. Molti venditori includono l\'etichetta di reso nel pacco originale per semplificare la procedura.'],
                    ['heading' => 'Quanto costa spedire per un e-commerce', 'text' => 'I costi di spedizione per un e-commerce dipendono dai volumi, dalle destinazioni e dal peso medio dei pacchi. Con SpediamoFacile, anche piccoli e-commerce possono accedere a tariffe competitive grazie agli accordi con i corrieri. Per volumi superiori alle 50 spedizioni al mese, contattaci per un preventivo personalizzato con tariffe dedicate. Considera sempre i costi di spedizione nel calcolo dei margini: sottovalutarli è uno degli errori più comuni dei nuovi venditori online.'],
                    ['heading' => 'Come ridurre i tempi di evasione ordini', 'text' => 'Per evadere gli ordini rapidamente, prepara in anticipo i materiali di imballaggio standardizzati, usa un flusso di lavoro efficiente (stampa etichette in blocco, prenota ritiri a orari fissi), e mantieni aggiornato lo stock per evitare annullamenti. SpediamoFacile ti permette di salvare indirizzi frequenti, creare template di spedizione per prodotti ricorrenti e automatizzare le notifiche al cliente. Un processo di evasione ben organizzato migliora la soddisfazione del cliente e riduce i costi operativi.'],
                ],
                'faqs' => null,
            ],
        ];
    }

    private function getServices(): array
    {
        return [
            [
                'slug' => 'pagamento-alla-consegna',
                'title' => 'Pagamento alla consegna',
                'meta_description' => 'Spedisci con pagamento alla consegna: il corriere incassa per tuo conto al momento della consegna. Scopri come funziona il contrassegno con SpediamoFacile.',
                'intro' => 'Il pagamento alla consegna è utile quando vuoi far pagare il destinatario al momento del ritiro del pacco. Il corriere incassa per tuo conto e poi riversa l\'importo al mittente secondo le tempistiche previste dal servizio. È una soluzione comoda per vendite occasionali o quando il cliente finale preferisce pagare solo alla consegna.',
                'icon' => 'mdi:cash-register',
                'sections' => [
                    ['heading' => 'Cosa è?', 'text' => 'Il pagamento alla consegna è un servizio accessorio con cui il corriere ritira dal destinatario l\'importo indicato dal mittente al momento della consegna e lo riversa al mittente con la modalità scelta. Per i vettori nazionali il pagamento avviene di norma in contanti e l\'importo da incassare deve essere indicato chiaramente sulla spedizione; il riversamento può avvenire con accredito su conto o altre modalità rese disponibili dal vettore.'],
                    ['heading' => 'Quando usarlo', 'text' => 'Quando vuoi spedire merce senza esporre il cliente a pagamenti anticipati. Quando vendi fuori canale e-commerce tradizionale o in aree dove i pagamenti elettronici sono poco usati. Quando vuoi combinare consegna e incasso in un\'unica operazione, con evidenza dell\'importo in etichetta e nei documenti di trasporto. Molti corrieri italiani offrono servizi di incasso alla consegna in contanti.'],
                    ['heading' => 'Come funziona con SpediamoFacile', 'text' => 'Nel modulo di spedizione attivi "Pagamento alla consegna" e inserisci: importo da incassare, descrizione breve per ricevuta del destinatario, modalità di riversamento preferita (accredito su conto, rimessa su portafoglio interno, altra opzione disponibile). Il sistema stampa l\'indicazione del contrassegno sull\'etichetta e nei documenti che accompagnano il collo.'],
                    ['heading' => 'Requisiti, limiti e buone pratiche', 'text' => 'Importo in cifre ben visibile in etichetta e documenti. Pagamento tipicamente in contanti alla consegna. Verifica sempre eventuali limiti o soglie del vettore e la normativa corrente sui pagamenti in contanti. Dati del destinatario completi e reperibilità telefonica. Imballo robusto e prova di valore per merce costosa. In caso di mancato pagamento il collo non viene consegnato e può tornare al mittente.'],
                    ['heading' => 'Costi', 'text' => 'Il pagamento alla consegna comporta un sovrapprezzo rispetto alla spedizione standard, in quanto il corriere svolge un servizio di incasso e riversamento. L\'importo del sovrapprezzo dipende dal vettore e dalle condizioni applicate. La tariffa esatta viene mostrata nel preventivo prima dell\'acquisto.'],
                    ['heading' => 'Riversamento e tracciamento', 'text' => 'Dopo l\'incasso, l\'importo viene riversato al mittente con la modalità selezionata al momento dell\'ordine. Nel tuo profilo vedi lo stato "incasso in corso", "incasso eseguito", "riversato". Puoi scaricare la quietanza di incasso e la distinta di riversamento.'],
                ],
                'faqs' => [
                    ['title' => 'Il pagamento alla consegna è solo in contanti?', 'text' => 'Per i servizi nazionali è normalmente in contanti; alcune reti offrono varianti o servizi correlati. Verifica sempre le condizioni del vettore scelto.'],
                    ['title' => 'Come scelgo come ricevere i soldi incassati?', 'text' => 'Al momento dell\'ordine selezioni la modalità di riversamento resa disponibile dal vettore; per esempio accredito su conto corrente.'],
                    ['title' => 'Posso far pagare solo le spese di spedizione e non la merce?', 'text' => 'Sì: alcuni corrieri hanno servizi in cui il destinatario paga solo il trasporto; GLS lo chiama "Destination Pay". È diverso dal contrassegno merce.'],
                    ['title' => 'Cosa devo scrivere sui documenti?', 'text' => 'Possono esserci limiti legati al vettore e alla normativa sui contanti, soglie soggette ad aggiornamenti. Controlla sempre le condizioni ufficiali prima della spedizione.'],
                ],
            ],
            [
                'slug' => 'spedizione-senza-etichetta',
                'title' => 'Spedizione senza etichetta',
                'meta_description' => 'Spedisci senza stampare l\'etichetta: il corriere la gestisce per te. Scopri come funziona il servizio senza etichetta di SpediamoFacile.',
                'intro' => 'Non hai una stampante? Nessun problema. Con il servizio di spedizione senza etichetta, puoi preparare il pacco e affidare la gestione dell\'etichetta al corriere o al punto di raccolta.',
                'icon' => 'mdi:qrcode',
                'sections' => [
                    ['heading' => 'Come funziona', 'text' => 'Crei la tua spedizione su SpediamoFacile come di consueto, inserendo tutti i dati del mittente e del destinatario. Al momento della conferma, scegli l\'opzione "senza etichetta". Il sistema genera un codice QR o un codice alfanumerico che puoi mostrare al corriere al momento del ritiro o portare al punto di raccolta convenzionato. L\'etichetta viene stampata dal personale del punto di ritiro o dal corriere stesso.'],
                    ['heading' => 'Quando usarlo', 'text' => 'Il servizio è ideale per chi non ha una stampante a casa, per spedizioni occasionali dove non vale la pena acquistare una stampante, o per chi preferisce la comodità di presentarsi al punto di raccolta con il solo codice sul telefono. È disponibile per le spedizioni nazionali con i corrieri che supportano questa modalità. Verifica la disponibilità durante la creazione dell\'ordine.'],
                    ['heading' => 'Vantaggi', 'text' => 'Niente stampante, niente nastro adesivo per applicare l\'etichetta, niente rischi di etichette illeggibili per problemi di stampa. Il codice digitale è sempre leggibile e non si deteriora. Puoi conservarlo sullo smartphone e mostrarlo quando serve. Il servizio è incluso nella tariffa di spedizione senza costi aggiuntivi dove disponibile.'],
                ],
                'faqs' => [
                    ['title' => 'Devo comunque preparare il pacco?', 'text' => 'Si, il pacco deve essere chiuso e pronto per la spedizione. L\'unica differenza è che non devi stampare e applicare l\'etichetta.'],
                    ['title' => 'Tutti i corrieri supportano questa opzione?', 'text' => 'No, il servizio è disponibile con i corrieri che offrono la gestione digitale dell\'etichetta. SpediamoFacile ti mostra le opzioni disponibili durante la creazione dell\'ordine.'],
                ],
            ],
            [
                'slug' => 'ritiro-a-domicilio',
                'title' => 'Ritiro a domicilio',
                'meta_description' => 'Il corriere viene a casa tua a ritirare il pacco. Scopri come funziona il ritiro a domicilio con SpediamoFacile.',
                'intro' => 'Con il ritiro a domicilio, il corriere viene direttamente al tuo indirizzo per prelevare il pacco. Non devi portare nulla in posta o al punto di raccolta: prepari il pacco, prenoti il ritiro e aspetti comodamente.',
                'icon' => 'mdi:home-clock-outline',
                'sections' => [
                    ['heading' => 'Come prenotare', 'text' => 'Il ritiro a domicilio viene prenotato automaticamente quando crei una spedizione su SpediamoFacile. Inserisci l\'indirizzo di ritiro, la data preferita e, dove disponibile, la fascia oraria. Ricevi una conferma via email con tutti i dettagli. Il servizio è incluso nel prezzo della spedizione per la maggior parte dei corrieri e delle tratte.'],
                    ['heading' => 'Come prepararsi al ritiro', 'text' => 'Il pacco deve essere pronto, chiuso e con l\'etichetta applicata quando il corriere arriva. Assicurati che qualcuno sia presente all\'indirizzo indicato per tutta la fascia oraria del ritiro. Prepara il pacco in un punto facilmente accessibile, vicino all\'ingresso. Se hai più colli, raggruppali insieme. Il corriere verifica solo che il pacco sia chiuso e l\'etichetta leggibile.'],
                    ['heading' => 'Fascia oraria e puntualità', 'text' => 'La fascia oraria di ritiro è generalmente ampia (mattina 9-13 o pomeriggio 14-18) e dipende dalla zona e dal corriere. Non è possibile fissare un orario esatto, ma il corriere passa sempre nella finestra indicata. In caso di mancato ritiro, contatta l\'assistenza SpediamoFacile per riprogrammare senza costi aggiuntivi.'],
                ],
                'faqs' => [
                    ['title' => 'Il ritiro a domicilio è sempre gratuito?', 'text' => 'Per la maggior parte delle spedizioni, il ritiro a domicilio è incluso nel prezzo. In rari casi potrebbe applicarsi un sovrapprezzo per zone particolarmente remote.'],
                    ['title' => 'Posso modificare la data del ritiro?', 'text' => 'Si, puoi modificare la data dalla tua area personale entro i termini previsti dal corriere, generalmente entro la sera prima del giorno di ritiro.'],
                ],
            ],
            [
                'slug' => 'assicurazione-spedizione',
                'title' => 'Assicurazione sulla spedizione',
                'meta_description' => 'Proteggi i tuoi pacchi con l\'assicurazione SpediamoFacile: copertura per danni, smarrimento e furto durante il trasporto.',
                'intro' => 'L\'assicurazione sulla spedizione ti protegge dal rischio di danni, smarrimento o furto durante il trasporto. Per un costo minimo rispetto al valore della merce, hai la certezza di un rimborso adeguato in caso di problemi.',
                'icon' => 'mdi:shield-check-outline',
                'sections' => [
                    ['heading' => 'Perché assicurare la spedizione', 'text' => 'Senza assicurazione aggiuntiva, il rimborso del corriere è limitato ai massimali previsti dalle condizioni generali, spesso calcolati per chilogrammo di peso e insufficienti a coprire il valore reale della merce. Con l\'assicurazione, dichiari il valore effettivo del contenuto e, in caso di sinistro, ottieni un rimborso proporzionato al danno subito, fino all\'importo assicurato.'],
                    ['heading' => 'Come attivarla', 'text' => 'Durante la creazione della spedizione su SpediamoFacile, trovi l\'opzione "Assicurazione" tra i servizi aggiuntivi. Inserisci il valore della merce e il sistema calcola automaticamente il premio assicurativo. Il costo viene aggiunto al totale della spedizione e mostrato prima della conferma del pagamento. Conserva la prova del valore della merce (fattura, scontrino, listino) per eventuali richieste di rimborso.'],
                    ['heading' => 'Cosa copre', 'text' => 'L\'assicurazione copre: danni fisici alla merce causati durante il trasporto (urti, cadute, schiacciamento), smarrimento totale del collo, e furto documentato. Il rimborso è calcolato sul valore effettivo del danno, fino al massimale dichiarato. Non sono coperti: danni da imballaggio inadeguato, deperibilità naturale, ritardi nella consegna e danni indiretti.'],
                    ['heading' => 'Quanto costa', 'text' => 'Il premio assicurativo è generalmente compreso tra l\'1% e il 3% del valore dichiarato della merce. Per un pacco del valore di 500 euro, l\'assicurazione costa indicativamente 5-15 euro. Un investimento minimo per una protezione significativa. Il costo esatto dipende dal corriere, dalla tratta e dal tipo di merce.'],
                ],
                'faqs' => [
                    ['title' => 'L\'assicurazione copre anche le spedizioni internazionali?', 'text' => 'Si, l\'assicurazione è disponibile anche per le spedizioni internazionali, con copertura valida per tutta la durata del trasporto.'],
                    ['title' => 'Entro quanto tempo devo segnalare un danno?', 'text' => 'Il danno va segnalato entro 5-7 giorni dalla consegna, con foto documentali del pacco e del contenuto. Per smarrimenti, i tempi si calcolano dalla data di mancata consegna.'],
                ],
            ],
            [
                'slug' => 'sponda-idraulica',
                'title' => 'Sponda idraulica',
                'meta_description' => 'Servizio di consegna e ritiro con sponda idraulica per colli pesanti e voluminosi. Scopri come funziona con SpediamoFacile.',
                'intro' => 'La sponda idraulica è il servizio dedicato a colli pesanti o voluminosi che non possono essere caricati o scaricati manualmente dal furgone. Una piattaforma mobile montata sul veicolo solleva il pacco dal livello strada al pianale del mezzo e viceversa.',
                'icon' => 'mdi:forklift',
                'sections' => [
                    ['heading' => 'Quando serve', 'text' => 'Il servizio di sponda idraulica è necessario quando il collo supera i 50-70 kg di peso o quando le dimensioni rendono impossibile il carico manuale. È la soluzione standard per: elettrodomestici pesanti, macchinari industriali, pallet, mobili voluminosi, attrezzature sportive di grandi dimensioni. Alcuni corrieri richiedono la sponda idraulica obbligatoriamente al di sopra di certi limiti di peso.'],
                    ['heading' => 'Come richiederlo', 'text' => 'Durante la creazione della spedizione su SpediamoFacile, seleziona "Sponda idraulica" tra i servizi aggiuntivi. Il sistema mostra automaticamente questa opzione quando il peso o le dimensioni del pacco la rendono consigliabile. Il servizio è disponibile sia per il ritiro che per la consegna, e puoi richiederlo per una o entrambe le operazioni in base alle esigenze.'],
                    ['heading' => 'Costi e condizioni', 'text' => 'Il servizio di sponda idraulica prevede un sovrapprezzo rispetto alla spedizione standard. Il costo dipende dal corriere e dalla zona di ritiro o consegna. Il prezzo viene mostrato durante la creazione dell\'ordine, prima della conferma del pagamento. Il servizio include lo scarico al piano strada, non la consegna al piano. Per la consegna al piano, dove disponibile, è previsto un servizio aggiuntivo dedicato.'],
                ],
                'faqs' => [
                    ['title' => 'La sponda idraulica include la consegna al piano?', 'text' => 'No, il servizio di sponda idraulica porta il collo dal furgone al piano strada. La consegna al piano è un servizio separato, disponibile con alcuni corrieri.'],
                    ['title' => 'Posso richiederla solo per il ritiro?', 'text' => 'Si, puoi richiedere la sponda idraulica solo per il ritiro, solo per la consegna, o per entrambe le operazioni.'],
                ],
            ],
            [
                'slug' => 'spedizione-programmata',
                'title' => 'Spedizione programmata',
                'meta_description' => 'Programma le tue spedizioni in anticipo con SpediamoFacile: scegli data e orario di ritiro e consegna.',
                'intro' => 'Con la spedizione programmata puoi pianificare in anticipo il ritiro e la consegna dei tuoi pacchi. Scegli la data che preferisci e il corriere si organizza di conseguenza: ideale per chi vuole gestire le spedizioni con precisione.',
                'icon' => 'mdi:calendar-clock-outline',
                'sections' => [
                    ['heading' => 'Come funziona', 'text' => 'Durante la creazione della spedizione, seleziona l\'opzione "Spedizione programmata" e indica la data preferita per il ritiro. Il sistema verifica la disponibilità del corriere e conferma la prenotazione. Puoi programmare il ritiro con diversi giorni di anticipo, utile per organizzare il lavoro e preparare i pacchi con calma. Le spedizioni programmate seguono poi i normali tempi di consegna del servizio scelto.'],
                    ['heading' => 'Vantaggi per le aziende', 'text' => 'Per le aziende che spediscono regolarmente, la programmazione delle spedizioni consente di organizzare il magazzino, pianificare la produzione e gestire le risorse in modo efficiente. Puoi programmare ritiri ricorrenti a giorni e orari fissi, creando un flusso logistico prevedibile e ottimizzato. Il corriere si presenta puntualmente nella finestra concordata, riducendo i tempi di attesa.'],
                    ['heading' => 'Modifiche e cancellazioni', 'text' => 'Le spedizioni programmate possono essere modificate o cancellate dalla tua area personale entro i termini previsti dal corriere, generalmente entro la sera prima del giorno di ritiro programmato. Le modifiche possono riguardare la data, l\'indirizzo di ritiro o il numero di colli. La cancellazione è gratuita se effettuata nei tempi previsti.'],
                ],
                'faqs' => [
                    ['title' => 'Con quanto anticipo posso programmare una spedizione?', 'text' => 'Puoi programmare il ritiro fino a 30 giorni in anticipo, in base alla disponibilità del corriere sulla tratta scelta.'],
                    ['title' => 'Posso programmare spedizioni ricorrenti?', 'text' => 'Si, per le aziende con esigenze regolari è possibile impostare ritiri ricorrenti. Contattaci per configurare il servizio.'],
                ],
            ],
            [
                'slug' => 'chiamata-pre-consegna',
                'title' => 'Chiamata pre-consegna',
                'meta_description' => 'Il corriere chiama prima di consegnare: scopri il servizio di chiamata pre-consegna di SpediamoFacile.',
                'intro' => 'Con il servizio di chiamata pre-consegna, il corriere contatta telefonicamente il destinatario prima di effettuare la consegna. Questo riduce i tentativi di consegna a vuoto e migliora l\'esperienza del destinatario.',
                'icon' => 'mdi:phone-ring-outline',
                'sections' => [
                    ['heading' => 'Come funziona', 'text' => 'Quando attivi il servizio di chiamata pre-consegna, il corriere telefona al destinatario prima di presentarsi all\'indirizzo di consegna. Questo permette al destinatario di confermare la propria presenza, concordare un orario indicativo o segnalare eventuali variazioni. Il servizio è particolarmente utile per consegne in zone dove la reperibilità del destinatario non è garantita.'],
                    ['heading' => 'Quando è utile', 'text' => 'La chiamata pre-consegna è consigliata quando: il destinatario ha orari variabili e potrebbe non essere presente, la consegna avviene in una zona con accesso complesso (cancello con codice, condominio senza portiere), il pacco è di valore e si vuole garantire la consegna al primo tentativo, o semplicemente si vuole offrire un servizio di qualità superiore al destinatario.'],
                    ['heading' => 'Costi e disponibilità', 'text' => 'Il servizio di chiamata pre-consegna è disponibile come servizio accessorio con un piccolo sovrapprezzo. Non tutti i corrieri lo offrono su tutte le tratte. La disponibilità viene mostrata durante la creazione della spedizione su SpediamoFacile. È fondamentale che il numero di telefono del destinatario sia corretto e raggiungibile durante l\'orario di consegna.'],
                ],
                'faqs' => [
                    ['title' => 'Il destinatario può spostare la consegna?', 'text' => 'La chiamata serve a verificare la presenza. Spostare la consegna a un altro giorno dipende dalla flessibilità del corriere e non è garantito.'],
                    ['title' => 'Cosa succede se il destinatario non risponde?', 'text' => 'Il corriere tenta comunque la consegna. Se il destinatario non è presente, si applicano le normali procedure di mancata consegna.'],
                ],
            ],
            [
                'slug' => 'assistenza-rapida',
                'title' => 'Assistenza rapida',
                'meta_description' => 'Assistenza dedicata e tempi di risposta rapidi per le tue spedizioni. Scopri il servizio di assistenza rapida SpediamoFacile.',
                'intro' => 'Il servizio di assistenza rapida ti garantisce supporto prioritario per tutte le tue esigenze di spedizione. Un team dedicato gestisce le tue richieste con tempi di risposta ridotti, per risolvere velocemente qualsiasi problema.',
                'icon' => 'mdi:headset',
                'sections' => [
                    ['heading' => 'Cosa include', 'text' => 'L\'assistenza rapida prevede: tempi di risposta prioritari rispetto al canale standard, un interlocutore dedicato che conosce il tuo profilo e le tue esigenze, gestione accelerata di reclami, giacenze e variazioni di consegna, supporto nella preparazione di spedizioni complesse e consulenza personalizzata sulla scelta dei servizi. Il team di assistenza rapida è raggiungibile tramite email, telefono e chat dalla tua area personale.'],
                    ['heading' => 'Per chi è pensato', 'text' => 'Il servizio è ideale per: aziende con volumi di spedizione regolari che necessitano di supporto operativo costante, e-commerce che devono gestire rapidamente problematiche dei clienti legate alle spedizioni, professionisti che spediscono documenti o materiali urgenti e non possono permettersi ritardi. Anche i privati possono attivare l\'assistenza rapida per spedizioni particolarmente importanti.'],
                    ['heading' => 'Come attivarlo', 'text' => 'L\'assistenza rapida può essere attivata come servizio aggiuntivo su singole spedizioni o come abbonamento per tutte le tue spedizioni. Dalla tua area personale su SpediamoFacile, vai nella sezione Assistenza e scegli la modalità che preferisci. Per le aziende con volumi elevati, il servizio può essere incluso nelle condizioni personalizzate.'],
                ],
                'faqs' => [
                    ['title' => 'Quali sono i tempi di risposta?', 'text' => 'L\'assistenza rapida garantisce una prima risposta entro poche ore lavorative, rispetto ai tempi standard che possono raggiungere le 24-48 ore.'],
                    ['title' => 'Posso attivarlo solo per alcune spedizioni?', 'text' => 'Si, puoi scegliere di attivare l\'assistenza rapida anche su una singola spedizione, quando ne hai particolare bisogno.'],
                ],
            ],
            [
                'slug' => 'punti-fedelta',
                'title' => 'Programma punti fedeltà',
                'meta_description' => 'Accumula punti con ogni spedizione e ottieni sconti e vantaggi esclusivi con il programma fedeltà SpediamoFacile.',
                'intro' => 'Con il programma punti fedeltà di SpediamoFacile, ogni spedizione ti fa guadagnare punti che puoi convertire in sconti sulle spedizioni successive. Più spedisci, più risparmi.',
                'icon' => 'mdi:star-circle-outline',
                'sections' => [
                    ['heading' => 'Come funziona', 'text' => 'Per ogni spedizione confermata e pagata, accumuli punti fedeltà in proporzione all\'importo speso. I punti vengono accreditati automaticamente sul tuo profilo dopo la conferma di consegna. Puoi monitorare il saldo punti dalla tua area personale e utilizzarli come sconto parziale o totale sulle spedizioni successive. Il programma è gratuito e si attiva automaticamente con la registrazione.'],
                    ['heading' => 'Come utilizzare i punti', 'text' => 'In fase di pagamento, trovi l\'opzione per utilizzare i tuoi punti fedeltà. Puoi scegliere quanti punti usare: tutti, una parte o nessuno. I punti vengono convertiti in sconto con un rapporto fisso e il risparmio viene mostrato nel riepilogo dell\'ordine prima della conferma. Puoi combinare i punti fedeltà con altri sconti o coupon dove previsto.'],
                    ['heading' => 'Vantaggi aggiuntivi', 'text' => 'Oltre allo sconto diretto, il programma fedeltà offre vantaggi crescenti in base al livello raggiunto: promozioni esclusive riservate ai membri, accesso anticipato a nuovi servizi e tariffe speciali per i clienti più fedeli. Il programma premia la costanza: più spedisci con SpediamoFacile, più vantaggi ottieni nel tempo.'],
                ],
                'faqs' => [
                    ['title' => 'I punti hanno una scadenza?', 'text' => 'I punti fedeltà restano validi per 12 mesi dalla data di accredito. I punti in scadenza vengono segnalati nella tua area personale con anticipo.'],
                    ['title' => 'Come posso controllare il mio saldo punti?', 'text' => 'Il saldo punti è sempre visibile nella tua area personale, nella sezione dedicata al programma fedeltà. Trovi anche lo storico di accumulo e utilizzo.'],
                ],
            ],
        ];
    }
}
