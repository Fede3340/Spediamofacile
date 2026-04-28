<?php

/**
 * CONFIGURAZIONE FATTURAZIONE (config/billing.php)
 *
 * Dati cedente, regime fiscale, numerazione, regole IVA e bolli per la
 * generazione delle fatture PDF (M10 — InvoicePdfGenerator).
 *
 * Tutti i campi sensibili (P.IVA, IBAN, ecc.) vanno popolati nel file .env.
 *
 * VARIABILI .env DA POPOLARE:
 *
 *   # Dati cedente (azienda emittente)
 *   BILLING_COMPANY_NAME="SpedizioneFacile S.r.l."
 *   BILLING_COMPANY_VAT="01234567890"
 *   BILLING_COMPANY_FISCAL_CODE="01234567890"
 *   BILLING_COMPANY_REA="MI-1234567"
 *   BILLING_COMPANY_CAPITAL="10000.00"
 *   BILLING_COMPANY_ADDRESS="Via Roma 1"
 *   BILLING_COMPANY_POSTAL_CODE="20100"
 *   BILLING_COMPANY_CITY="Milano"
 *   BILLING_COMPANY_PROVINCE="MI"
 *   BILLING_COMPANY_COUNTRY="IT"
 *   BILLING_COMPANY_EMAIL="amministrazione@spedizionefacile.it"
 *   BILLING_COMPANY_PEC="spedizionefacile@pec.it"
 *   BILLING_COMPANY_PHONE="+39 02 0000000"
 *   BILLING_COMPANY_WEBSITE="https://spedizionefacile.it"
 *
 *   # Regime fiscale e numerazione
 *   BILLING_REGIME_FISCALE="RF01"
 *   BILLING_INVOICE_PREFIX="INV"
 *   BILLING_INVOICE_RESET_YEARLY=true
 *
 *   # IVA e bolli
 *   BILLING_VAT_RATE=22
 *   BILLING_STAMP_DUTY_THRESHOLD=77.47
 *   BILLING_STAMP_DUTY_AMOUNT=2.00
 *
 *   # Pagamento
 *   BILLING_PAYMENT_TERMS_DAYS=0
 *   BILLING_BANK_NAME="Banca Esempio"
 *   BILLING_BANK_IBAN="IT00X0000000000000000000000"
 *   BILLING_BANK_SWIFT="EXMPITM1"
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Dati cedente / prestatore
    |--------------------------------------------------------------------------
    | Anagrafica della societa' emittente la fattura. Vengono stampati nell'header
    | del PDF e usati come <CedentePrestatore> nell'XML SDI.
    */
    'cedente' => [
        'ragione_sociale' => env('BILLING_COMPANY_NAME', 'SpedizioneFacile S.r.l.'),
        'partita_iva' => env('BILLING_COMPANY_VAT', '00000000000'),
        'codice_fiscale' => env('BILLING_COMPANY_FISCAL_CODE', '00000000000'),
        'numero_rea' => env('BILLING_COMPANY_REA', null),
        'capitale_sociale' => env('BILLING_COMPANY_CAPITAL', null),

        // Sede legale
        'indirizzo' => env('BILLING_COMPANY_ADDRESS', 'Via Esempio 1'),
        'cap' => env('BILLING_COMPANY_POSTAL_CODE', '00100'),
        'citta' => env('BILLING_COMPANY_CITY', 'Roma'),
        'provincia' => env('BILLING_COMPANY_PROVINCE', 'RM'),
        'paese' => env('BILLING_COMPANY_COUNTRY', 'IT'),

        // Contatti
        'email' => env('BILLING_COMPANY_EMAIL', 'amministrazione@spedizionefacile.it'),
        'pec' => env('BILLING_COMPANY_PEC', null),
        'telefono' => env('BILLING_COMPANY_PHONE', null),
        'sito_web' => env('BILLING_COMPANY_WEBSITE', 'https://spedizionefacile.it'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Regime fiscale
    |--------------------------------------------------------------------------
    | Codice regime fiscale ufficiale Agenzia delle Entrate (tabella TR0001).
    |   RF01 = Ordinario (default per S.r.l. e ditte > soglia minimi)
    |   RF02 = Contribuenti minimi (art. 1, c.96-117, L. 244/2007)
    |   RF19 = Regime forfettario (art. 1, c.54-89, L. 190/2014)
    | NB: questo progetto usa RF01 ordinario (NON forfettario).
    */
    'regime_fiscale' => env('BILLING_REGIME_FISCALE', 'RF01'),

    /*
    |--------------------------------------------------------------------------
    | Numerazione fatture
    |--------------------------------------------------------------------------
    | Schema: {prefix}-{YYYY}-{NNNNN}  → es. "INV-2026-00042"
    |
    | - prefix: stringa prefisso (default "INV")
    | - reset_yearly: se true, il counter riparte da 1 ogni 1 gennaio (consigliato)
    | - padding: numero cifre del progressivo (default 5 → "00042")
    */
    'numerazione' => [
        'prefix' => env('BILLING_INVOICE_PREFIX', 'INV'),
        'reset_yearly' => (bool) env('BILLING_INVOICE_RESET_YEARLY', true),
        'padding' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | IVA
    |--------------------------------------------------------------------------
    | Aliquota IVA standard applicata alle righe fattura (servizi di spedizione
    | nazionale: 22% — D.P.R. 633/1972).
    */
    'iva' => [
        'aliquota' => (float) env('BILLING_VAT_RATE', 22),
        // I prezzi nel sistema (Order::subtotal) sono IVA INCLUSA: scorporiamo per imponibile.
        'prezzi_iva_inclusa' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bollo virtuale (D.P.R. 642/1972)
    |--------------------------------------------------------------------------
    | Per fatture di importo > 77,47 EUR esenti IVA o non imponibili e' obbligatorio
    | applicare un bollo virtuale di 2,00 EUR. Per fatture IVA22% standard NON serve,
    | ma il template gestisce comunque la nota.
    */
    'bollo_virtuale' => [
        'soglia_eur' => (float) env('BILLING_STAMP_DUTY_THRESHOLD', 77.47),
        'importo_eur' => (float) env('BILLING_STAMP_DUTY_AMOUNT', 2.00),
        // Se true, il template stampa la nota informativa "imposta di bollo assolta in modo virtuale".
        // L'applicazione effettiva dell'addebito dipende dal regime IVA della riga.
        'mostra_nota' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Modalita' di pagamento
    |--------------------------------------------------------------------------
    | Termini di pagamento (giorni dalla data fattura). 0 = pagamento immediato
    | (default per ordini gia' saldati con Stripe/wallet).
    */
    'pagamento' => [
        'termini_giorni' => (int) env('BILLING_PAYMENT_TERMS_DAYS', 0),
        'banca' => env('BILLING_BANK_NAME', null),
        'iban' => env('BILLING_BANK_IBAN', null),
        'swift' => env('BILLING_BANK_SWIFT', null),

        // Etichette per modalita' pagamento mostrate nel PDF (chiave = Order::payment_method).
        'etichette' => [
            'stripe' => 'Carta di credito (Stripe)',
            'wallet' => 'Portafoglio virtuale',
            'bonifico' => 'Bonifico bancario',
            'cod' => 'Contrassegno',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage dei PDF
    |--------------------------------------------------------------------------
    | I PDF generati vengono salvati nel disk "local" sotto invoices/{year}/{month}/
    | per facilita' di backup e per soddisfare il requisito di conservazione.
    */
    'storage' => [
        'disk' => env('BILLING_INVOICE_DISK', 'local'),
        'base_path' => 'invoices',
    ],

];
