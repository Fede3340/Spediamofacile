<?php

/**
 * FILE: web.php (Rotte Web)
 *
 * SCOPO:
 *   Contiene SOLO le rotte che devono usare il middleware "web" standard di Laravel
 *   (sessione classica, CSRF web, cookie). Queste rotte NON sono API.
 *
 * PERCHE' COSI' POCHE ROTTE?
 *   Prima tutte le rotte API erano qui dentro un Route::group(['prefix' => 'api']).
 *   Il problema era che il login (qui, middleware "web") creava la sessione in un modo,
 *   ma GET /api/user (in api.php, middleware "statefulApi") la leggeva in un altro modo.
 *   Risultato: dopo il login, l'utente risultava "Unauthenticated" perche' i due
 *   middleware stack gestivano la sessione in maniera diversa.
 *
 *   SOLUZIONE: tutte le rotte /api/* sono state spostate in api.php, cosi' login,
 *   /api/user, carrello, ordini ecc. usano TUTTI lo stesso middleware "statefulApi"
 *   e la sessione funziona correttamente.
 *
 * COSA CONTIENE:
 *   - GET /             → Pagina di benvenuto Laravel (non usata dal frontend Nuxt)
 *   - GET /login        → Redirect a /autenticazione (necessaria per Sanctum)
 *   - POST /stripe/webhook → Webhook Stripe (riceve notifiche pagamento, no sessione)
 *   - GET /auth/google/callback → Callback OAuth Google (redirect dal browser di Google)
 *
 * CHIAMATO DA:
 *   - Laravel (routing automatico)
 *   - Stripe (webhook POST)
 *   - Google OAuth (redirect callback)
 *   - Sanctum internamente (rotta "login" come fallback per utenti non autenticati)
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Shipping\BrtWebhookController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Checkout\StripeWebhookController;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

// Pagina principale del backend Laravel (non usata dal frontend Nuxt)
// Mostra la pagina di benvenuto di default di Laravel
Route::get('/', function () {
    return view('welcome');
});

// Rotta CSRF Sanctum ridefinita con rate limiting (throttle:30,1).
// Sanctum l'avrebbe registrata automaticamente, ma in config/sanctum.php
// abbiamo impostato 'routes' => false per avere controllo esplicito qui.
// Il throttle evita che un client possa brute-forzare il cookie CSRF o
// amplificare richieste verso servizi esterni.
Route::middleware(['web', 'throttle:30,1'])
    ->get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])
    ->name('sanctum.csrf-cookie');

// Rotta di login "fittizia" — Sanctum la usa come fallback
// Quando un utente non autenticato tenta di accedere a una rotta protetta con auth:sanctum,
// Laravel lo redirige alla rotta con nome 'login'. Noi lo mandiamo alla pagina di login
// del frontend Nuxt (/autenticazione) cosi' puo' inserire le sue credenziali.
// NOTA: questa rotta DEVE avere ->name('login') altrimenti Sanctum da' errore
Route::get('/login', function () {
    return redirect('/autenticazione');
})->name('login');

// Webhook di Stripe — riceve le notifiche di pagamento da Stripe
// Stripe invia qui un POST ogni volta che un pagamento viene completato, rimborsato, ecc.
// E' pubblico (senza login) perche' Stripe lo chiama direttamente dai suoi server.
// La verifica dell'autenticita' viene fatta dal StripeWebhookController tramite la firma.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// Webhook BRT — riceve aggiornamenti tracking push da BRT
// BRT invia qui un POST ogni volta che lo stato di una spedizione cambia.
// E' pubblico (senza login) perche' BRT lo chiama direttamente dai suoi server.
// La verifica dell'autenticita' avviene nel controller (HMAC o IP whitelist).
// Rate limit: max 60 richieste al minuto per IP.
Route::post('/webhooks/brt/tracking', [BrtWebhookController::class, 'handleTrackingUpdate'])
    ->middleware(['throttle:60,1']);

// Callback di Google OAuth — riceve il redirect dopo che l'utente si autentica con Google
// Quando l'utente clicca "Accedi con Google", viene mandato su Google, e dopo
// il login Google lo rimanda qui. Questa rotta e' in web.php (e NON in api.php)
// perche' il redirect di Google arriva direttamente nel browser dell'utente,
// quindi deve usare il middleware web per gestire la sessione e i cookie.
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

// SENTRY-OBS-04: rotta di test per verificare integrazione Sentry.
// Uso: dopo il deploy, visita /_test-sentry → verifica in Sentry dashboard
// che l'evento arrivi con release tag, env tag, user context, PII sanitizzati.
// Protetta da:
//   - abort(404) in produzione (in genere; abilitabile temporaneamente via env)
//   - throttle 1 richiesta al minuto per IP (anti-abuso)
Route::get('/_test-sentry', function () {
    // Blocco hard in produzione: questa rotta NON deve restare attiva.
    // Per test post-deploy, abilitare temporaneamente SENTRY_TEST_ROUTE_ENABLED=true
    // e rimuovere dopo la verifica.
    if (app()->environment('production') && !env('SENTRY_TEST_ROUTE_ENABLED', false)) {
        abort(404);
    }
    throw new \RuntimeException('Sentry test error — se lo vedi in dashboard, l\'integrazione funziona.');
})->middleware('throttle:1,1')->name('sentry.test');
