<?php

/**
 * FILE: bootstrap/app.php
 * SCOPO: Configurazione principale dell'applicazione Laravel 11.
 *
 * COSA FA:
 *   - Definisce le rotte (web, api, console) dell'applicazione
 *   - Configura i middleware globali (sicurezza, CSRF, proxy, sessione)
 *   - Gestisce le eccezioni personalizzate (firma scaduta, troppi tentativi)
 *
 * COME FUNZIONA:
 *   In Laravel 11, questo file sostituisce il vecchio Kernel.php.
 *   Tutto il setup dei middleware e delle eccezioni avviene qui dentro,
 *   usando il pattern "fluent" con ->withMiddleware() e ->withExceptions().
 *
 * CHIAMATO DA:
 *   - Laravel stesso all'avvio dell'applicazione (bootstrap automatico)
 *   - Ogni singola richiesta HTTP passa attraverso questo file
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
// -- ARCHIVIATO 2026-04-20 -- use App\Http\Middleware\AuthenticateProApiKey;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\HydrateSanctumFrontendHeaders;
use App\Http\Middleware\SentryContext;
use App\Support\AuthUiCookie;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Rotte delle pagine web (login, registrazione, ecc.)
        web: __DIR__.'/../routes/web.php',
        // Rotte dell'API REST (carrello, ordini, preventivi, ecc.)
        api: __DIR__.'/../routes/api.php',
        // Comandi artisan personalizzati
        commands: __DIR__.'/../routes/console.php',
        // Endpoint di health check: GET /up (usato da monitoraggio e deploy)
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        // Se il browser o il proxy non inviano Origin/Referer sulle chiamate API
        // stateful, Sanctum puo' trattarle come stateless e perdere la sessione.
        // Questo middleware ricostruisce gli header trusted prima che entri in gioco
        // la pipeline statefulApi.
        $middleware->prepend(HydrateSanctumFrontendHeaders::class);

        // Abilita l'autenticazione "stateful" per le API (Sanctum SPA).
        // Senza questo, Sanctum non userebbe i cookie di sessione per le API,
        // e l'autenticazione SPA (Single Page Application) non funzionerebbe.
        $middleware->statefulApi();

        // FIDUCIA NEI PROXY (Caddy + Cloudflare Tunnel)
        // -----------------------------------------------
        // Il traffico arriva cosi': Browser → Cloudflare (HTTPS) → Caddy (:8787 HTTP) → Laravel (:8000)
        //
        // Cloudflare e Caddy inviano degli header speciali:
        //   - X-Forwarded-Proto: "https" (dice che il client originale usava HTTPS)
        //   - X-Forwarded-For: IP reale del client
        //   - X-Forwarded-Host: hostname originale (es. abc123.trycloudflare.com)
        //
        // Senza trustProxies, Laravel vede la richiesta come HTTP (perche' Caddy gliela
        // passa in HTTP locale), e quindi:
        //   - I cookie di sessione NON hanno il flag "Secure" → il browser avvisa "sessione insicura"
        //   - Gli URL generati da Laravel iniziano con http:// invece di https://
        //   - $request->secure() restituisce sempre false
        //
        // Con at: '*' diciamo a Laravel: "fidati di TUTTI i proxy" (sicuro perche'
        // il server non e' esposto direttamente a Internet, ma solo attraverso Caddy).
        $middleware->trustProxies(at: '*');

        // Esclude dal controllo CSRF solo gli endpoint che non possono inviare
        // il token XSRF-TOKEN del browser.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'webhooks/brt/tracking',
            'auth/apple/callback',
        ]);

        // Aggiunge le intestazioni di sicurezza a TUTTE le risposte
        // (vedi app/Http/Middleware/SecurityHeaders.php per i dettagli)
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // SENTRY-OBS-01: contesto Sentry (user id + ruolo, ip hash, route name).
        // Eseguito DOPO trustProxies cosi' $request->ip() e' quello vero.
        // NON contiene PII: conforme GDPR. Vedi app/Http/Middleware/SentryContext.php.
        $middleware->append(SentryContext::class);

        // -- ARCHIVIATO 2026-04-20 --
        // PRO-API: alias middleware per autenticazione via X-Pro-Api-Key.
        // Uso: Route::middleware('pro.api')->... oppure 'pro.api:shipments:read'
        // $middleware->alias([
        //     'pro.api' => AuthenticateProApiKey::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401)->cookie(AuthUiCookie::forget());
            }
        });

        // Se un link di verifica email ha la firma scaduta o non valida,
        // reindirizza al frontend con un messaggio chiaro invece di mostrare
        // la pagina di errore generica di Laravel (errore 403 brutto)
        $exceptions->render(function (InvalidSignatureException $e, $request) {
            return redirect(config('app.frontend_url') . '/verifica-email?status=invalid_signature');
        });

        // Se l'utente fa troppe richieste (rate limiting), restituisce
        // un messaggio in italiano con il tempo di attesa
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            return response()->json([
                'message' => 'Hai superato il numero massimo di tentativi. Riprova tra ' . ($e->getHeaders()['Retry-After'] ?? 60) . ' secondi.'
            ], 429);
        });

        // SENTRY-OBS-02: invio a Sentry di TUTTE le eccezioni non gestite.
        // Laravel 11 pattern: $exceptions->report() hooka il reporter SENZA
        // sostituirlo — l'exception continua a fluire per altri handler
        // (es. log stack) e la gestione di rendering resta invariata.
        // Il filtro ignore_exceptions e' applicato da sentry.php#before_send
        // implicitamente via Integration helper.
        $exceptions->report(function (\Throwable $e) {
            if (class_exists(\Sentry\Laravel\Integration::class) && app()->bound('sentry')) {
                \Sentry\Laravel\Integration::captureUnhandledException($e);
            }
        });
    })->create();
