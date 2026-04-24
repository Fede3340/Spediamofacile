# Glossario - SpediamoFacile

Definizioni dei termini di dominio utilizzati nel progetto. Scritto per chi legge il codice per la prima volta.

---

## Termini di spedizione

**Spedizione**
L'intero processo di invio di uno o piu pacchi da un mittente a un destinatario. Una spedizione corrisponde a un ordine (`Order`) nel sistema.

**Pacco / Collo**
Un singolo oggetto fisico da spedire. Nel codice si chiama `Package`. Ha tipo (scatola, busta, pallet), peso, dimensioni (lunghezza, larghezza, altezza) e quantita. Piu pacchi identici possono essere raggruppati con la quantita.

**Preventivo**
Il calcolo del prezzo stimato per una spedizione. Viene fatto prima di creare l'ordine, in base a peso e volume dei colli. Non e vincolante, ma il prezzo viene ricalcolato lato server al momento del pagamento.

**Servizio**
Il tipo di spedizione scelto. Nel codice si chiama `Service`. Include: tipo di servizio (standard, express, economy), data di ritiro e orario di ritiro. Viene mappato ai codici servizio BRT.

**Mittente**
Chi spedisce il pacco. I suoi dati sono nell'indirizzo di partenza (`origin_address`).

**Destinatario**
Chi riceve il pacco. I suoi dati sono nell'indirizzo di destinazione (`destination_address`).

**Ritiro**
Il momento in cui il corriere BRT passa a prendere il pacco dal mittente. La data e l'orario di ritiro sono specificati nel `Service`.

**Destinazione**
Il luogo dove il pacco deve essere consegnato. Specificato nell'indirizzo di destinazione.

---

## Termini geografici

**CAP (Codice di Avviamento Postale)**
Codice numerico a 5 cifre che identifica una zona di consegna. Nel codice si chiama `postal_code`. Esempio: "20121" per Milano centro.

**Provincia**
Suddivisione territoriale italiana. Nel codice si usa la sigla a 2 lettere (`province`). Esempio: "MI" per Milano, "RM" per Roma.

**Localita**
Una citta o frazione italiana con il suo CAP e provincia. Memorizzata nel modello `Location`. Usata per l'autocompletamento degli indirizzi.

---

## Termini BRT (corriere)

**BRT / Bartolini**
Il corriere che si occupa del trasporto fisico dei pacchi. SpediamoFacile comunica con le API REST di BRT per creare spedizioni e generare etichette.

**Etichetta**
Il foglio (PDF) con codice a barre da stampare e attaccare al pacco. Generata dalle API BRT. Nel codice salvata come `brt_label_base64` (codificata in base64).

**Tracking / Tracciamento**
Il sistema per seguire dove si trova il pacco durante il trasporto. BRT fornisce un URL di tracking e un numero di tracking (`brt_tracking_number`).

**Parcel ID**
L'identificativo univoco del pacco nel sistema BRT. Nel codice si chiama `brt_parcel_id`.

**PUDO (Pick Up Drop Off)**
Punti di ritiro/consegna convenzionati con BRT (tabaccai, edicole, negozi). L'utente puo scegliere di ritirare o consegnare il pacco presso un PUDO invece che a domicilio. Identificato da `brt_pudo_id`.

**Deposito BRT**
Sede fisica del corriere BRT da cui partono o arrivano le spedizioni. Identificato da un codice numerico (`brt_departure_depot`, `brt_arrival_depot`).

**Contrassegno / COD (Cash On Delivery)**
Modalita di pagamento in cui il destinatario paga al momento della consegna. Il corriere incassa l'importo per conto del mittente. Nel codice: `is_cod` (booleano) e `cod_amount` (importo in centesimi).

---

## Termini di pagamento

**Ordine**
Un insieme di pacchi da spedire con un totale da pagare. Nel codice si chiama `Order`. Ha uno stato che evolve: pending -> processing -> in_transit -> delivered.

**Transazione**
Un singolo tentativo di pagamento. Nel codice si chiama `Transaction`. Un ordine puo avere piu transazioni (es. primo tentativo fallito, secondo riuscito).

**PaymentIntent**
Concetto Stripe: rappresenta l'intenzione di effettuare un pagamento. Creato dal backend, completato dal frontend con la carta dell'utente. Contiene `client_secret` che il frontend usa per completare il pagamento.

**Stripe**
Il servizio esterno di pagamento online utilizzato dal sito. Gestisce carte di credito, pagamenti e rimborsi. Le chiavi API sono configurate nel database (`Setting`) o nel file `.env`.

**Customer ID**
L'identificativo dell'utente nel sistema Stripe. Salvato come `customer_id` nell'utente. Necessario per associare carte di pagamento.

**Portafoglio / Wallet**
Conto virtuale interno al sito. L'utente puo ricaricare il portafoglio (via carta Stripe) e usare il saldo per pagare le spedizioni. I movimenti sono tracciati nel modello `WalletMovement`.

**Credit / Debit**
Tipi di movimento nel portafoglio. Credit = soldi in entrata (ricarica, commissione). Debit = soldi in uscita (pagamento spedizione).

**Bonifico**
Metodo di pagamento alternativo. L'utente paga tramite bonifico bancario. L'ordine resta in stato `pending` finche l'admin non conferma la ricezione del pagamento.

---

## Termini referral

**Referral / Codice Amico**
Sistema per incentivare nuovi utenti. Un Partner Pro ha un codice univoco di 8 caratteri che puo condividere. Chi usa il codice riceve uno sconto, il Partner Pro guadagna una commissione.

**Partner Pro**
Utente con ruolo speciale (`role = "Partner Pro"`). Ha un codice referral e guadagna commissioni del 5% sugli ordini di chi usa il suo codice. Per diventare Pro serve approvazione dell'admin.

**Commissione**
Il guadagno del Partner Pro quando qualcuno usa il suo codice referral. Pari al 5% dell'importo dell'ordine. Viene accreditata nel portafoglio del Pro come `WalletMovement` di tipo credit con source "commission".

**Sconto Referral**
Lo sconto applicato all'acquirente che usa un codice referral. Pari al 5% dell'importo dell'ordine.

**Prelievo**
La richiesta del Partner Pro di incassare le commissioni guadagnate. Nel codice si chiama `WithdrawalRequest`. Deve essere approvata dall'admin.

---

## Termini tecnici

**Sessione**
Memoria temporanea lato server associata al browser dell'utente. Usata per salvare il preventivo in corso e il carrello degli ospiti. Si perde quando il browser viene chiuso.

**Store (Pinia)**
Memoria globale lato frontend (Nuxt/Vue). Usata per condividere dati tra le pagine senza doverli ricaricare dal server. Si perde quando si ricarica la pagina.

**Composable**
Funzione riutilizzabile in Nuxt/Vue che incapsula logica condivisa. Esempio: `useCart()` gestisce il carrello sia per utenti loggati che per ospiti.

**Middleware**
Codice che viene eseguito prima di una richiesta. Lato backend: `auth:sanctum` (verifica login), `CheckAdmin` (verifica ruolo admin), `CheckCart` (verifica carrello non vuoto). Lato frontend: `admin.js`, `email-verification.js`, `shipment-validation.js`.

**Evento / Event**
Meccanismo Laravel per disaccoppiare le azioni. Quando succede qualcosa di importante (es. pagamento), viene "lanciato" un evento. I listener registrati reagiscono automaticamente. Esempio: `OrderPaid` -> `MarkOrderProcessing` + `GenerateBrtLabel`.

**Listener / Ascoltatore**
Codice che "ascolta" un evento e reagisce. Esempio: `GenerateBrtLabel` ascolta `OrderPaid` e genera automaticamente l'etichetta BRT.

**Webhook**
Notifica automatica inviata da un servizio esterno (Stripe) al nostro server. Quando un pagamento viene completato su Stripe, Stripe manda una richiesta al nostro endpoint `/stripe/webhook`.

**Sanctum**
Sistema di autenticazione di Laravel. Gestisce sessioni e token per le API. Il middleware `auth:sanctum` protegge le rotte che richiedono il login.

**Centesimi**
I prezzi nel backend sono spesso in centesimi per evitare errori di arrotondamento. 900 centesimi = 9,00 euro. La classe `MyMoney` gestisce la conversione e la formattazione.

**Idempotency Key**
Chiave univoca usata per evitare operazioni duplicate. Se la stessa richiesta viene inviata due volte con la stessa chiave, la seconda viene ignorata. Usata nei movimenti portafoglio.

**Base64**
Metodo di codifica per rappresentare dati binari (come un PDF) come stringa di testo. L'etichetta BRT e salvata in base64 nel database e decodificata quando viene scaricata.
