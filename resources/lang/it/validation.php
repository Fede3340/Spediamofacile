<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Messaggi di errore di validazione — usati automaticamente da Laravel
    | quando il locale è impostato su "it". I segnaposto :attribute, :min,
    | :max, :value, :other, :values, :date, :format, :size, :digits,
    | :decimal vengono sostituiti a runtime.
    |
    */

    'accepted' => 'Il campo :attribute deve essere accettato.',
    'accepted_if' => 'Il campo :attribute deve essere accettato quando :other è :value.',
    'active_url' => 'Il campo :attribute non contiene un URL valido.',
    'after' => 'Il campo :attribute deve essere una data successiva a :date.',
    'after_or_equal' => 'Il campo :attribute deve essere una data successiva o uguale a :date.',
    'alpha' => 'Il campo :attribute può contenere solo lettere.',
    'alpha_dash' => 'Il campo :attribute può contenere solo lettere, numeri, trattini e underscore.',
    'alpha_num' => 'Il campo :attribute può contenere solo lettere e numeri.',
    'array' => 'Il campo :attribute deve essere una lista.',
    'ascii' => 'Il campo :attribute deve contenere solo caratteri alfanumerici e simboli a byte singolo.',
    'before' => 'Il campo :attribute deve essere una data precedente a :date.',
    'before_or_equal' => 'Il campo :attribute deve essere una data precedente o uguale a :date.',
    'between' => [
        'array' => 'Il campo :attribute deve avere tra :min e :max elementi.',
        'file' => 'Il campo :attribute deve essere compreso tra :min e :max kilobyte.',
        'numeric' => 'Il campo :attribute deve essere compreso tra :min e :max.',
        'string' => 'Il campo :attribute deve contenere tra :min e :max caratteri.',
    ],
    'boolean' => 'Il campo :attribute deve essere vero o falso.',
    'can' => 'Il campo :attribute contiene un valore non autorizzato.',
    'confirmed' => 'La conferma del campo :attribute non corrisponde.',
    'contains' => 'Nel campo :attribute manca un valore obbligatorio.',
    'current_password' => 'La password inserita non è corretta.',
    'date' => 'Il campo :attribute deve essere una data valida.',
    'date_equals' => 'Il campo :attribute deve essere una data uguale a :date.',
    'date_format' => 'Il campo :attribute non corrisponde al formato :format.',
    'decimal' => 'Il campo :attribute deve avere :decimal decimali.',
    'declined' => 'Il campo :attribute deve essere rifiutato.',
    'declined_if' => 'Il campo :attribute deve essere rifiutato quando :other è :value.',
    'different' => 'I campi :attribute e :other devono essere diversi.',
    'digits' => 'Il campo :attribute deve essere di :digits cifre.',
    'digits_between' => 'Il campo :attribute deve avere tra :min e :max cifre.',
    'dimensions' => 'Le dimensioni dell\'immagine del campo :attribute non sono valide.',
    'distinct' => 'Il campo :attribute contiene valori duplicati.',
    'doesnt_end_with' => 'Il campo :attribute non deve terminare con uno dei seguenti valori: :values.',
    'doesnt_start_with' => 'Il campo :attribute non deve iniziare con uno dei seguenti valori: :values.',
    'email' => 'Il campo :attribute deve essere un indirizzo email valido.',
    'ends_with' => 'Il campo :attribute deve terminare con uno dei seguenti valori: :values.',
    'enum' => 'Il valore selezionato per :attribute non è valido.',
    'exists' => 'Il valore selezionato per :attribute non esiste.',
    'extensions' => 'Il campo :attribute deve avere una delle seguenti estensioni: :values.',
    'file' => 'Il campo :attribute deve essere un file.',
    'filled' => 'Il campo :attribute deve avere un valore.',
    'gt' => [
        'array' => 'Il campo :attribute deve contenere più di :value elementi.',
        'file' => 'Il campo :attribute deve essere più grande di :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere maggiore di :value.',
        'string' => 'Il campo :attribute deve contenere più di :value caratteri.',
    ],
    'gte' => [
        'array' => 'Il campo :attribute deve contenere almeno :value elementi.',
        'file' => 'Il campo :attribute deve essere maggiore o uguale a :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere maggiore o uguale a :value.',
        'string' => 'Il campo :attribute deve contenere almeno :value caratteri.',
    ],
    'hex_color' => 'Il campo :attribute deve essere un colore esadecimale valido.',
    'image' => 'Il campo :attribute deve essere un\'immagine.',
    'in' => 'Il valore selezionato per :attribute non è valido.',
    'in_array' => 'Il campo :attribute non esiste in :other.',
    'integer' => 'Il campo :attribute deve essere un numero intero.',
    'ip' => 'Il campo :attribute deve essere un indirizzo IP valido.',
    'ipv4' => 'Il campo :attribute deve essere un indirizzo IPv4 valido.',
    'ipv6' => 'Il campo :attribute deve essere un indirizzo IPv6 valido.',
    'json' => 'Il campo :attribute deve essere una stringa JSON valida.',
    'list' => 'Il campo :attribute deve essere una lista.',
    'lowercase' => 'Il campo :attribute deve essere in minuscolo.',
    'lt' => [
        'array' => 'Il campo :attribute deve contenere meno di :value elementi.',
        'file' => 'Il campo :attribute deve essere più piccolo di :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere minore di :value.',
        'string' => 'Il campo :attribute deve contenere meno di :value caratteri.',
    ],
    'lte' => [
        'array' => 'Il campo :attribute deve contenere al massimo :value elementi.',
        'file' => 'Il campo :attribute deve essere minore o uguale a :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere minore o uguale a :value.',
        'string' => 'Il campo :attribute deve contenere al massimo :value caratteri.',
    ],
    'mac_address' => 'Il campo :attribute deve essere un indirizzo MAC valido.',
    'max' => [
        'array' => 'Il campo :attribute non può avere più di :max elementi.',
        'file' => 'Il campo :attribute non può essere più grande di :max kilobyte.',
        'numeric' => 'Il campo :attribute non può essere superiore a :max.',
        'string' => 'Il campo :attribute non può superare :max caratteri.',
    ],
    'max_digits' => 'Il campo :attribute non può avere più di :max cifre.',
    'mimes' => 'Il campo :attribute deve essere un file di tipo: :values.',
    'mimetypes' => 'Il campo :attribute deve essere un file di tipo: :values.',
    'min' => [
        'array' => 'Il campo :attribute deve avere almeno :min elementi.',
        'file' => 'Il campo :attribute deve essere almeno :min kilobyte.',
        'numeric' => 'Il campo :attribute deve essere almeno :min.',
        'string' => 'Il campo :attribute deve contenere almeno :min caratteri.',
    ],
    'min_digits' => 'Il campo :attribute deve avere almeno :min cifre.',
    'missing' => 'Il campo :attribute non deve essere presente.',
    'missing_if' => 'Il campo :attribute non deve essere presente quando :other è :value.',
    'missing_unless' => 'Il campo :attribute non deve essere presente a meno che :other non sia :value.',
    'missing_with' => 'Il campo :attribute non deve essere presente quando è presente :values.',
    'missing_with_all' => 'Il campo :attribute non deve essere presente quando sono presenti :values.',
    'multiple_of' => 'Il campo :attribute deve essere un multiplo di :value.',
    'not_in' => 'Il valore selezionato per :attribute non è valido.',
    'not_regex' => 'Il formato del campo :attribute non è valido.',
    'numeric' => 'Il campo :attribute deve essere un numero.',
    'password' => [
        'letters' => 'Il campo :attribute deve contenere almeno una lettera.',
        'mixed' => 'Il campo :attribute deve contenere almeno una lettera maiuscola e una minuscola.',
        'numbers' => 'Il campo :attribute deve contenere almeno un numero.',
        'symbols' => 'Il campo :attribute deve contenere almeno un simbolo.',
        'uncompromised' => 'Il valore del campo :attribute compare in una fuga di dati. Scegli un altro valore.',
    ],
    'present' => 'Il campo :attribute deve essere presente.',
    'present_if' => 'Il campo :attribute deve essere presente quando :other è :value.',
    'present_unless' => 'Il campo :attribute deve essere presente a meno che :other non sia :value.',
    'present_with' => 'Il campo :attribute deve essere presente quando è presente :values.',
    'present_with_all' => 'Il campo :attribute deve essere presente quando sono presenti :values.',
    'prohibited' => 'Il campo :attribute non è consentito.',
    'prohibited_if' => 'Il campo :attribute non è consentito quando :other è :value.',
    'prohibited_unless' => 'Il campo :attribute non è consentito a meno che :other non sia in :values.',
    'prohibits' => 'Il campo :attribute impedisce la presenza di :other.',
    'regex' => 'Il formato del campo :attribute non è valido.',
    'required' => 'Il campo :attribute è obbligatorio.',
    'required_array_keys' => 'Il campo :attribute deve contenere le chiavi: :values.',
    'required_if' => 'Il campo :attribute è obbligatorio quando :other è :value.',
    'required_if_accepted' => 'Il campo :attribute è obbligatorio quando :other è accettato.',
    'required_if_declined' => 'Il campo :attribute è obbligatorio quando :other viene rifiutato.',
    'required_unless' => 'Il campo :attribute è obbligatorio a meno che :other non sia in :values.',
    'required_with' => 'Il campo :attribute è obbligatorio quando è presente :values.',
    'required_with_all' => 'Il campo :attribute è obbligatorio quando sono presenti :values.',
    'required_without' => 'Il campo :attribute è obbligatorio quando non è presente :values.',
    'required_without_all' => 'Il campo :attribute è obbligatorio quando non è presente nessuno tra :values.',
    'same' => 'I campi :attribute e :other devono coincidere.',
    'size' => [
        'array' => 'Il campo :attribute deve contenere :size elementi.',
        'file' => 'Il campo :attribute deve essere di :size kilobyte.',
        'numeric' => 'Il campo :attribute deve essere :size.',
        'string' => 'Il campo :attribute deve essere di :size caratteri.',
    ],
    'starts_with' => 'Il campo :attribute deve iniziare con uno dei seguenti valori: :values.',
    'string' => 'Il campo :attribute deve essere una stringa.',
    'timezone' => 'Il campo :attribute deve essere un fuso orario valido.',
    'unique' => 'Il valore del campo :attribute è già in uso.',
    'uploaded' => 'Il caricamento di :attribute non è riuscito.',
    'uppercase' => 'Il campo :attribute deve essere in maiuscolo.',
    'url' => 'Il campo :attribute deve essere un URL valido.',
    'ulid' => 'Il campo :attribute deve essere un ULID valido.',
    'uuid' => 'Il campo :attribute deve essere un UUID valido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Messaggi personalizzati per combinazioni attributo+regola specifiche
    | della nostra app. Formato: "attributo.regola" => "messaggio".
    |
    */

    'custom' => [
        'packages' => [
            'required' => 'Devi aggiungere almeno un collo alla spedizione.',
            'array' => 'L\'elenco dei colli non è valido.',
            'min' => 'Devi aggiungere almeno :min collo alla spedizione.',
            'max' => 'Non puoi aggiungere più di :max colli per spedizione.',
        ],
        'packages.*.package_type' => [
            'required' => 'Seleziona il tipo di collo (busta, scatola, pacco…).',
        ],
        'packages.*.weight' => [
            'required' => 'Inserisci il peso del collo.',
            'numeric' => 'Il peso del collo deve essere un numero.',
            'min' => 'Il peso del collo deve essere almeno :min kg.',
            'max' => 'Il peso del collo non può superare :max kg.',
        ],
        'packages.*.first_size' => [
            'required' => 'Inserisci la lunghezza del collo.',
            'numeric' => 'La lunghezza deve essere un numero.',
        ],
        'packages.*.second_size' => [
            'required' => 'Inserisci la larghezza del collo.',
            'numeric' => 'La larghezza deve essere un numero.',
        ],
        'packages.*.third_size' => [
            'required' => 'Inserisci l\'altezza del collo.',
            'numeric' => 'L\'altezza deve essere un numero.',
        ],
        'packages.*.quantity' => [
            'integer' => 'La quantità deve essere un numero intero.',
            'min' => 'La quantità minima per collo è :min.',
            'max' => 'La quantità massima per collo è :max.',
        ],
        'email' => [
            'unique' => 'Esiste già un account con questa email.',
        ],
        'password' => [
            'confirmed' => 'La conferma della password non corrisponde.',
            'min' => 'La password deve contenere almeno :min caratteri.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Nomi human-friendly dei campi app-specific. Sostituiscono :attribute
    | nei messaggi di errore al posto del nome tecnico (snake_case).
    |
    */

    'attributes' => [
        // Anagrafica
        'first_name' => 'nome',
        'last_name' => 'cognome',
        'name' => 'nome',
        'company' => 'azienda',
        'company_name' => 'ragione sociale',
        'vat_number' => 'partita IVA',
        'tax_code' => 'codice fiscale',
        'fiscal_code' => 'codice fiscale',
        'sdi_code' => 'codice SDI',
        'pec' => 'PEC',
        'pec_email' => 'PEC',
        'profile_type' => 'tipo profilo',
        'phone' => 'telefono',
        'phone_number' => 'numero di telefono',

        // Credenziali
        'email' => 'email',
        'password' => 'password',
        'password_confirmation' => 'conferma password',
        'current_password' => 'password attuale',
        'new_password' => 'nuova password',
        'token' => 'token',

        // Indirizzo
        'address' => 'indirizzo',
        'street' => 'via',
        'street_number' => 'numero civico',
        'civic' => 'numero civico',
        'postal_code' => 'CAP',
        'cap' => 'CAP',
        'zip' => 'CAP',
        'zip_code' => 'CAP',
        'city' => 'città',
        'province' => 'provincia',
        'state' => 'provincia',
        'region' => 'regione',
        'country' => 'paese',

        // Spedizione / colli
        'packages' => 'colli',
        'packages.*.package_type' => 'tipo collo',
        'packages.*.weight' => 'peso',
        'packages.*.first_size' => 'lunghezza',
        'packages.*.second_size' => 'larghezza',
        'packages.*.third_size' => 'altezza',
        'packages.*.quantity' => 'quantità',
        'package_type' => 'tipo collo',
        'weight' => 'peso',
        'first_size' => 'lunghezza',
        'second_size' => 'larghezza',
        'third_size' => 'altezza',
        'quantity' => 'quantità',
        'insurance' => 'assicurazione',
        'insurance_value' => 'valore assicurato',
        'service' => 'servizio',
        'services' => 'servizi',
        'notes' => 'note',
        'description' => 'descrizione',
        'content_description' => 'descrizione contenuto',

        // Mittente / destinatario
        'sender' => 'mittente',
        'recipient' => 'destinatario',
        'sender_name' => 'nome mittente',
        'recipient_name' => 'nome destinatario',
        'origin' => 'origine',
        'destination' => 'destinazione',
        'origin_city' => 'città di partenza',
        'destination_city' => 'città di arrivo',
        'origin_postal_code' => 'CAP di partenza',
        'destination_postal_code' => 'CAP di arrivo',
        'pickup_date' => 'data di ritiro',
        'pickup_time' => 'orario di ritiro',
        'delivery_date' => 'data di consegna',

        // Pagamento / ordine
        'total' => 'totale',
        'subtotal' => 'subtotale',
        'amount' => 'importo',
        'price' => 'prezzo',
        'single_price' => 'prezzo unitario',
        'currency' => 'valuta',
        'payment_method' => 'metodo di pagamento',
        'coupon' => 'coupon',
        'coupon_code' => 'codice coupon',
        'order_id' => 'ID ordine',
        'tracking_number' => 'numero di tracciamento',

        // Consenso / legale
        'terms' => 'termini e condizioni',
        'privacy' => 'informativa privacy',
        'marketing' => 'consenso marketing',
        'accepted' => 'accettazione',
    ],

];
