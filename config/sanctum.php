<?php
/**
 * FILE: config/sanctum.php
 * SCOPO: Configura Laravel Sanctum per l'autenticazione SPA (Single Page Application).
 *
 * SpediamoFacile usa Sanctum in modalita' "SPA cookie-based":
 * il frontend Nuxt comunica con il backend Laravel usando cookie di sessione
 * (NON token Bearer). Questo approccio e' piu' sicuro per le SPA perche':
 * - I cookie sono automaticamente gestiti dal browser
 * - Il cookie HttpOnly non e' accessibile da JavaScript (protezione XSS)
 * - La protezione CSRF e' integrata
 *
 * FLUSSO DI AUTENTICAZIONE:
 * 1. Frontend chiama GET /sanctum/csrf-cookie per ottenere il token CSRF
 * 2. Frontend chiama POST /api/login con email e password
 * 3. Il server crea una sessione e invia il cookie di sessione
 * 4. Tutte le richieste successive includono automaticamente il cookie
 *
 * IMPORTANTE:
 * - I domini in 'stateful' DEVONO corrispondere esattamente all'URL del frontend
 *   (inclusa la porta). Se non corrispondono, Sanctum non riconosce le richieste
 *   come "stateful" e l'autenticazione fallisce con errore 401.
 * - Configurabile da .env con SANCTUM_STATEFUL_DOMAINS
 *
 * DOCUMENTI CORRELATI:
 *   - config/cors.php — CORS con supports_credentials=true (obbligatorio per i cookie)
 *   - config/session.php — configurazione cookie sessione (domain, secure, same_site)
 *   - bootstrap/app.php — $middleware->statefulApi() attiva il middleware Sanctum
 */

use Laravel\Sanctum\Sanctum;

$defaultStateful = sprintf(
    '%s%s',
    'localhost,localhost:3000,localhost:3001,localhost:8787,127.0.0.1,127.0.0.1:3001,127.0.0.1:8000,127.0.0.1:8787,::1,*.trycloudflare.com,',
    Sanctum::currentApplicationUrlWithPort()
);

$stateful = collect(explode(',', env('SANCTUM_STATEFUL_DOMAINS', $defaultStateful)))
    ->map(fn ($domain) => trim((string) $domain))
    ->filter()
    ->values();

$currentHost = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
$currentHostname = strtolower((string) explode(':', $currentHost)[0]);
$isPrivateIpv4Host = (bool) preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)/', $currentHostname);

if (
    $currentHost !== ''
    && (
        $isPrivateIpv4Host
        || in_array($currentHostname, ['localhost', '127.0.0.1', '::1'], true)
        || str_ends_with($currentHostname, '.trycloudflare.com')
    )
) {
    $stateful->push($currentHost);
}

return [

    /*
    |--------------------------------------------------------------------------
    | Domini Stateful (Stateful Domains)
    |--------------------------------------------------------------------------
    |
    | Le richieste provenienti da questi domini riceveranno i cookie di sessione
    | per l'autenticazione. DEVONO includere tutti gli URL da cui il frontend
    | Nuxt puo' fare richieste (sviluppo locale + produzione).
    |
    | Se il frontend e' su localhost:3001, quel dominio DEVE essere nella lista,
    | altrimenti Sanctum tratta le richieste come "stateless" (senza sessione)
    | e restituisce sempre 401 Unauthorized.
    |
    */

    'stateful' => $stateful->unique()->values()->all(),

    /*
    |--------------------------------------------------------------------------
    | Guard di Autenticazione
    |--------------------------------------------------------------------------
    |
    | Il guard 'web' usa la sessione PHP per autenticare gli utenti.
    | Sanctum controlla prima questo guard; se fallisce, cerca un token Bearer.
    | Per la nostra SPA usiamo sempre il guard 'web' (sessione + cookie).
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Scadenza Token (non usato per SPA)
    |--------------------------------------------------------------------------
    |
    | Controlla la scadenza dei token API (non dei cookie di sessione).
    | Per la nostra SPA cookie-based, la scadenza e' gestita dalla sessione
    | (vedi config/session.php -> 'lifetime').
    | null = nessuna scadenza automatica dei token.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Prefisso Token (non usato per SPA)
    |--------------------------------------------------------------------------
    |
    | Prefisso per i token API generati da Sanctum. Non rilevante per la
    | nostra autenticazione SPA basata su cookie.
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Middleware Sanctum
    |--------------------------------------------------------------------------
    |
    | Middleware usati da Sanctum per processare le richieste autenticate.
    | - authenticate_session: verifica che la sessione sia valida
    | - encrypt_cookies: cripta i cookie prima di inviarli al browser
    | - validate_csrf_token: protegge da attacchi CSRF (Cross-Site Request Forgery)
    |
    */

    'middleware' => [
        // 'authenticate_session' DISABILITATO: la middleware richiede che in sessione
        // sia presente 'password_hash_web' sincronizzato con il DB. Nei flussi OAuth
        // (Google/Facebook/Apple) e dopo session()->regenerate() post-login, l'hash
        // puo' mancare: AuthenticateSession allora esegue logoutCurrentDevice() al
        // primo request successivo, disconnettendo l'utente — visibile in modo
        // evidente durante il redirect 3DS Stripe (pausa 30-90s sul payment).
        // In SPA stateful la protezione session hijack è già garantita dai cookie
        // HttpOnly + SameSite=lax + CSRF token. Riattivare solo dopo aver
        // allineato tutti i controller auth a salvare password_hash_web in sessione.
        // 'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Registrazione Rotte Sanctum (disabilitata — override locale)
    |--------------------------------------------------------------------------
    |
    | Con false Sanctum NON registra automaticamente GET /sanctum/csrf-cookie.
    | La rotta equivalente e' ridefinita in routes/web.php con rate limiting
    | (throttle:30,1) per evitare abusi / SSRF amplification. Mantiene
    | lo stesso controller e nome ('sanctum.csrf-cookie') di Sanctum.
    |
    */

    'routes' => false,

];
