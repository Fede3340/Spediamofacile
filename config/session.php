<?php
/**
 * FILE: config/session.php
 * SCOPO: Configura la sessione PHP usata per l'autenticazione Sanctum SPA e il preventivo rapido.
 *
 * La sessione e' fondamentale per SpediamoFacile:
 * 1. AUTENTICAZIONE: Sanctum usa il cookie di sessione per riconoscere l'utente loggato
 * 2. PREVENTIVO RAPIDO: i dati del preventivo (pacchi, indirizzi, prezzi) sono salvati in sessione
 *    (SessionController.php) per utenti non registrati
 *
 * PARAMETRI CRITICI PER SANCTUM SPA:
 * - driver: 'database' per sessioni persistenti (sopravvivono al riavvio del server)
 * - domain: DEVE corrispondere al dominio del frontend (o null per auto-detect)
 * - secure: null per auto-detect (HTTPS in produzione, HTTP in sviluppo)
 * - same_site: 'lax' per permettere i cookie cross-site con sicurezza
 * - http_only: true per impedire accesso JavaScript al cookie (protezione XSS)
 *
 * ATTENZIONE:
 * - Se domain e' impostato male, i cookie non vengono inviati e l'auth fallisce con 401
 * - expire_on_close=true: la sessione scade quando si chiude il browser
 * - In sviluppo locale (HTTP), secure DEVE essere false o null
 *
 * DOCUMENTI CORRELATI:
 *   - config/sanctum.php — domini stateful per autenticazione SPA
 *   - config/cors.php — supports_credentials=true per inviare i cookie cross-origin
 *   - .env — SESSION_DOMAIN, SESSION_SECURE_COOKIE, SESSION_SAME_SITE
 */

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Driver di Sessione
    |--------------------------------------------------------------------------
    |
    | Determina dove vengono salvati i dati della sessione.
    | 'database': i dati sono salvati nella tabella 'sessions' del database.
    | Questa e' la scelta migliore per SpediamoFacile perche':
    | - Le sessioni sopravvivono al riavvio del server
    | - Funzionano con piu' server (se necessario in futuro)
    | - Permettono di vedere le sessioni attive nel database
    |
    | Alternative: "file", "cookie", "redis", "memcached", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Durata della Sessione
    |--------------------------------------------------------------------------
    |
    | Quanti minuti la sessione puo' restare inattiva prima di scadere.
    | 120 minuti (2 ore) = l'utente resta loggato per 2 ore senza attivita'.
    |
    | expire_on_close: se true, la sessione scade quando l'utente chiude il browser.
    | Con true, anche se il lifetime e' 120 min, la sessione muore alla chiusura del browser.
    |
    */

    'lifetime' => env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', true),

    /*
    |--------------------------------------------------------------------------
    | Crittografia Sessione
    |--------------------------------------------------------------------------
    |
    | Se abilitato, tutti i dati della sessione vengono criptati prima di
    | essere salvati. Di default e' disabilitato perche' il database e' gia'
    | protetto e la crittografia aggiunge overhead.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Posizione File Sessione
    |--------------------------------------------------------------------------
    |
    | Se si usa il driver "file", i file di sessione vengono salvati qui.
    | Ogni sessione e' un file separato con l'ID sessione come nome.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Connessione Database per Sessioni
    |--------------------------------------------------------------------------
    |
    | Se si usa il driver "database" o "redis", specifica quale connessione usare.
    | Se null, usa la connessione di default dal file config/database.php.
    |
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Tabella Database per Sessioni
    |--------------------------------------------------------------------------
    |
    | Se si usa il driver "database", questa e' la tabella dove vengono salvate.
    | Colonne necessarie: id, user_id, ip_address, user_agent, payload, last_activity.
    |
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Cache Store per Sessioni
    |--------------------------------------------------------------------------
    |
    | Se si usa un driver basato su cache (apc, memcached, redis, dynamodb),
    | specifica quale "store" di cache usare (definiti in config/cache.php).
    |
    */

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Pulizia Sessioni Scadute (Lottery)
    |--------------------------------------------------------------------------
    |
    | Alcuni driver (come "file") devono pulire periodicamente le sessioni scadute.
    | [2, 100] = ad ogni richiesta c'e' il 2% di probabilita' che venga fatta
    | la pulizia. Non si fa ad ogni richiesta per non rallentare il server.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Nome del Cookie di Sessione
    |--------------------------------------------------------------------------
    |
    | Nome del cookie di sessione creato da Laravel. Di default e' basato
    | sul nome dell'applicazione (es. "spedizionefacile_session").
    | Non c'e' motivo di cambiarlo.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Percorso del Cookie (Path)
    |--------------------------------------------------------------------------
    |
    | Il percorso per cui il cookie e' valido. "/" significa "tutto il sito".
    | Il browser invia il cookie solo per le richieste a questo percorso e sotto-percorsi.
    |
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Dominio del Cookie
    |--------------------------------------------------------------------------
    |
    | Il dominio per cui il cookie e' valido. Se vuoto/null, il browser lo imposta
    | automaticamente al dominio corrente. Per supportare i sottodomini,
    | si puo' impostare ".spediamofacile.it" (con il punto davanti).
    |
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Cookie Solo HTTPS (Secure)
    |--------------------------------------------------------------------------
    |
    | Se true, il cookie di sessione viene inviato SOLO su connessioni HTTPS.
    | null = auto-detect: secure in HTTPS, non-secure in HTTP.
    |
    | ATTENZIONE: in sviluppo locale (HTTP), questo DEVE essere false o null,
    | altrimenti il browser non invia il cookie e l'autenticazione fallisce.
    | In produzione (HTTPS), dovrebbe essere true per sicurezza.
    |
    */

    // null = auto-detect (secure quando la richiesta e' HTTPS, non-secure quando HTTP)
    'secure' => env('SESSION_SECURE_COOKIE') === 'null' ? null : env('SESSION_SECURE_COOKIE'),

    /*
    |--------------------------------------------------------------------------
    | Cookie Solo HTTP (HttpOnly)
    |--------------------------------------------------------------------------
    |
    | Se true, il cookie di sessione NON e' accessibile da JavaScript.
    | Questo e' FONDAMENTALE per la sicurezza: impedisce che un attacco XSS
    | possa rubare il cookie di sessione e impersonare l'utente.
    | NON disabilitare mai questa opzione.
    |
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookie
    |--------------------------------------------------------------------------
    |
    | Controlla se il cookie viene inviato nelle richieste cross-site.
    | - 'lax': il cookie viene inviato per navigazione top-level (link cliccati)
    |   ma NON per richieste AJAX cross-site (protegge da CSRF)
    | - 'none': il cookie viene sempre inviato (richiede secure=true)
    | - 'strict': il cookie NON viene mai inviato in richieste cross-site
    |
    | Per Sanctum SPA con frontend su porta diversa: 'lax' e' la scelta corretta.
    | Se frontend e backend sono su domini diversi, potrebbe servire 'none' + secure=true.
    |
    */

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Cookie Partizionati (CHIPS)
    |--------------------------------------------------------------------------
    |
    | Se true, il cookie viene partizionato per sito di primo livello.
    | Richiede secure=true e same_site='none'. Non necessario per la nostra configurazione.
    |
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
