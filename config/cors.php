<?php
/**
 * FILE: config/cors.php
 * SCOPO: Configura le regole CORS (Cross-Origin Resource Sharing) per permettere
 *        al frontend Nuxt di comunicare con il backend Laravel.
 *
 * CORS e' necessario perche' frontend e backend sono su porte diverse:
 *   - Frontend Nuxt: http://localhost:3001 (o porta 8787 via Caddy)
 *   - Backend Laravel: http://localhost:8000 (o porta 8787 via Caddy)
 *
 * Senza CORS, il browser bloccherebbe le richieste dal frontend al backend
 * perche' considera "sospetto" che un sito comunichi con un altro sito diverso.
 *
 * IMPORTANTE:
 *   - supports_credentials=true e' OBBLIGATORIO per Sanctum SPA auth (invia i cookie di sessione)
 *   - Le origini permesse devono corrispondere esattamente all'URL del frontend
 *   - Il pattern trycloudflare permette l'accesso da tunnel Cloudflare (sviluppo remoto)
 *
 * CHIAMATO DA:
 *   - Laravel automaticamente — applicato a tutte le richieste che matchano 'paths'
 *
 * DOCUMENTI CORRELATI:
 *   - config/sanctum.php — domini stateful per autenticazione SPA
 *   - config/session.php — cookie di sessione (domain, secure, same_site)
 *   - .env — CORS_ALLOWED_ORIGINS per configurare le origini permesse
 */

return [

    // Percorsi a cui si applicano le regole CORS
    // Solo le richieste API e il cookie CSRF di Sanctum necessitano di CORS
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Metodi HTTP permessi (tutti quelli usati dall'applicazione)
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Origini (URL) da cui il frontend puo' fare richieste
    // Configurabile da .env con CORS_ALLOWED_ORIGINS (lista separata da virgole)
    // Default: localhost dev + domini di produzione
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://127.0.0.1:8787,http://localhost:8787,http://127.0.0.1:3001,http://localhost:3001,http://localhost:3000,http://127.0.0.1:3000,https://spediamofacile.it,https://www.spediamofacile.it')),

    // Pattern regex per origini dinamiche (es. tunnel Cloudflare che cambiano URL ogni volta).
    // I pattern di sviluppo (trycloudflare.com e IP privati LAN) sono inclusi SOLO quando
    // CORS_ALLOW_DEV_TUNNELS=true. In produzione va lasciato off (default) per non accettare
    // origini sconosciute (*.trycloudflare.com puo' essere registrato da chiunque).
    'allowed_origins_patterns' => env('CORS_ALLOW_DEV_TUNNELS', false) ? [
        '/https?:\/\/.*\.trycloudflare\.com/',
        '/https?:\/\/(?:10\.\d{1,3}\.\d{1,3}\.\d{1,3}|192\.168\.\d{1,3}\.\d{1,3}|172\.(?:1[6-9]|2\d|3[0-1])\.\d{1,3}\.\d{1,3})(?::\d+)?$/',
    ] : [],

    // Intestazioni HTTP permesse nelle richieste (* = tutte)
    'allowed_headers' => ['*'],

    // Intestazioni che il browser puo' leggere dalla risposta
    'exposed_headers' => [],

    // Tempo in secondi per cui il browser puo' memorizzare le regole CORS (2 ore)
    'max_age' => 7200,

    // FONDAMENTALE per Sanctum: permette l'invio di cookie (sessione + CSRF)
    // nelle richieste cross-origin. Senza questo, l'autenticazione SPA non funziona.
    'supports_credentials' => true,

];
