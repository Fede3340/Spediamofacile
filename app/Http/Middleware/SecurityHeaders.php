<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        // Prima lascia che la richiesta venga processata normalmente
        $response = $next($request);
        $isDevelopment = app()->environment(['local', 'development']);

        // Sprint 6.2 — BLOCKER GO-LIVE: RIMOSSO 'unsafe-inline' da script-src
        // in production (XSS bypass CVSS 6.1). Laravel serve SOLO API/webhook JSON
        // per un SPA Nuxt: nessun inline <script> server-side, CSP strict possibile.
        // In dev: manteniamo 'unsafe-inline' 'unsafe-eval' per compatibilita tool
        // locali (Telescope, Horizon, debugbar).
        $scriptSrc = $isDevelopment
            ? "'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com https://m.stripe.network"
            : "'self' https://js.stripe.com https://m.stripe.network";

        // --- INTESTAZIONI DI SICUREZZA ---

        // X-Content-Type-Options: nosniff
        // Impedisce al browser di "indovinare" il tipo di file (MIME sniffing).
        // Senza questo, un file .txt potrebbe essere interpretato come JavaScript
        // malevolo se il suo contenuto sembra codice.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-Frame-Options: SAMEORIGIN
        // Impedisce che il sito venga caricato in un <iframe> di un altro sito.
        // Protegge dal "clickjacking": un attaccante potrebbe sovrapporre il nostro
        // sito (invisibile) sopra un altro sito, facendo cliccare l'utente su
        // pulsanti che non vede (es. "Conferma pagamento").
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // X-XSS-Protection: attiva la protezione XSS integrata nel browser.
        // mode=block: se rileva un attacco XSS, blocca la pagina intera
        // invece di provare a "pulirla" (piu' sicuro).
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy: controlla quali informazioni vengono inviate nell'header
        // "Referer" quando l'utente clicca un link esterno.
        // strict-origin-when-cross-origin: invia l'URL completo solo per link interni,
        // per link esterni invia solo il dominio (es. "spediamofacile.it" senza il percorso).
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy: blocca l'accesso a funzionalita' sensibili del dispositivo.
        // camera=()         → nessuno puo' usare la fotocamera
        // microphone=()     → nessuno puo' usare il microfono
        // geolocation=(self)→ solo il nostro sito puo' accedere alla posizione GPS
        //                     (usato da ricerca punti PUDO BRT)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');

        // HSTS (HTTP Strict Transport Security) - SOLO su connessioni HTTPS
        // Dice al browser: "per il prossimo anno, usa SEMPRE HTTPS per questo sito,
        // anche se l'utente digita http://".
        //
        // PERCHE' solo su HTTPS: se inviamo HSTS su una connessione HTTP locale
        // (es. localhost:8787), il browser tenterebbe di forzare HTTPS anche in
        // sviluppo locale, causando errori di connessione.
        //
        // $request->secure() restituisce true quando:
        //   - La richiesta arriva direttamente via HTTPS, oppure
        //   - L'header X-Forwarded-Proto e' "https" (Cloudflare tunnel)
        //     (funziona grazie a trustProxies in bootstrap/app.php)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy (CSP): definisce ESATTAMENTE quali risorse esterne
        // il browser puo' caricare. Se un attaccante inietta uno script malevolo,
        // il browser lo blocca perche' non e' nella lista dei permessi.
        //
        // Dettaglio delle regole:
        //   default-src 'self'           → per default, carica solo risorse dal nostro dominio
        //   script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com
        //                                → script: nostri + inline + eval + Stripe (pagamenti)
        //   style-src 'self' 'unsafe-inline'
        //                                → stili: nostri + inline (necessario per Vue/Nuxt)
        //   img-src 'self' data: https:  → immagini: nostre + data-URI + qualsiasi HTTPS
        //   font-src 'self' data:        → font: nostri + data-URI (font embedddati in CSS)
        //   connect-src 'self' https://api.stripe.com https://nominatim.openstreetmap.org
        //                                → AJAX/fetch: nostro server + API Stripe + geocoding per punti PUDO
        //   frame-src https://js.stripe.com https://hooks.stripe.com
        //                                → iframe: solo Stripe (per il form di pagamento 3D Secure)
        //   object-src 'none'            → blocca tutti i plugin (Flash, Java, ecc.)
        //   base-uri 'self'              → impedisce di cambiare il base URL della pagina
        // Report-Only pre-cutover: setta CSP_REPORT_ONLY=true in .env per 48h
        // monitoring senza bloccare richieste. Poi passa a enforce.
        $cspHeader = env('CSP_REPORT_ONLY', false)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        // form-action + frame-ancestors aggiunti per OWASP best practice.
        // Laravel serve JSON (API) → form-action 'self' blocca exfil via <form>.
        $response->headers->set($cspHeader, "default-src 'self'; script-src {$scriptSrc}; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' https://api.stripe.com https://m.stripe.network https://nominatim.openstreetmap.org; frame-src https://js.stripe.com https://hooks.stripe.com; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");

        return $response;
    }
}
