<?php

/**
 * web.php — rotte Inertia + webhook esterni.
 *
 * Tutto il sito è servito via Inertia (root view: resources/views/app.blade.php).
 * I webhook BRT/Stripe restano POST web (no sessione, autenticati per firma).
 * Le rotte API legacy stanno in routes/api.php (mantenute per compatibilità API esterne).
 */

use App\Http\Controllers\InertiaAccountController;
use App\Http\Controllers\InertiaAdminController;
use App\Http\Controllers\InertiaAuthController;
use App\Http\Controllers\InertiaCheckoutController;
use App\Http\Controllers\InertiaShipmentController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ServiziController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Checkout\StripeWebhookController;
use App\Http\Controllers\Shipping\BrtWebhookController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

/* ────── Public pages (Inertia) ────── */
Route::get('/', [PagesController::class, 'home'])->name('home');
Route::get('/chi-siamo', [PagesController::class, 'chiSiamo']);
Route::get('/contatti', [PagesController::class, 'contatti']);
Route::post('/contatti', [PagesController::class, 'contatti']); // form submit (stub: rimanda allo show)
Route::get('/faq', [PagesController::class, 'faq']);
Route::get('/privacy-policy', [PagesController::class, 'privacy']);
Route::get('/cookie-policy', [PagesController::class, 'cookie']);
Route::get('/termini-e-condizioni', [PagesController::class, 'termini']);
Route::get('/traccia', [PagesController::class, 'tracciaForm']);
Route::get('/guide', [PagesController::class, 'guide']);

/* ────── Servizi ────── */
Route::get('/servizi', [ServiziController::class, 'index']);
Route::get('/servizi/{slug}', [ServiziController::class, 'show']);

/* ────── Preventivo + Funnel spedizione ────── */
Route::get('/preventivo', [InertiaShipmentController::class, 'preventivo']);
Route::post('/preventivo/calcola', [InertiaShipmentController::class, 'calcola']);
Route::post('/la-tua-spedizione/inizia', [InertiaShipmentController::class, 'inizia']);
Route::get('/la-tua-spedizione/{step}', [InertiaShipmentController::class, 'step']);
Route::post('/la-tua-spedizione/{step}', [InertiaShipmentController::class, 'saveStep']);

/* ────── Carrello + Checkout (Stripe hosted) ────── */
Route::get('/carrello', [InertiaCheckoutController::class, 'carrello']);
Route::middleware('auth')->group(function () {
    Route::post('/checkout/stripe', [InertiaCheckoutController::class, 'startStripeCheckout'])->middleware('throttle:10,1');
});
Route::get('/checkout/return', [InertiaCheckoutController::class, 'return']);
Route::get('/checkout/cancel', [InertiaCheckoutController::class, 'cancel']);
Route::get('/checkout/success', [InertiaCheckoutController::class, 'success']);

/* ────── Auth (Inertia) ────── */
Route::middleware('guest')->group(function () {
    Route::get('/login', [InertiaAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [InertiaAuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/registrazione', [InertiaAuthController::class, 'showRegister']);
    Route::post('/registrazione', [InertiaAuthController::class, 'register']);
    Route::get('/recupera-password', [InertiaAuthController::class, 'showForgotPassword']);
    Route::post('/recupera-password', [InertiaAuthController::class, 'forgotPassword']);
    Route::get('/aggiorna-password/{token}', [InertiaAuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/aggiorna-password', [InertiaAuthController::class, 'resetPassword']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [InertiaAuthController::class, 'logout'])->name('logout');
    Route::get('/email/verify', [InertiaAuthController::class, 'showVerifyEmail'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [InertiaAuthController::class, 'verifyEmail'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [InertiaAuthController::class, 'resendVerification'])->middleware('throttle:6,1');

    /* ────── Account cliente ────── */
    Route::get('/account', [InertiaAccountController::class, 'dashboard']);
    Route::get('/account/spedizioni', [InertiaAccountController::class, 'spedizioni']);
    Route::get('/account/profilo', [InertiaAccountController::class, 'profilo']);
    Route::put('/account/profilo', [InertiaAccountController::class, 'updateProfilo']);
    Route::get('/account/indirizzi', [InertiaAccountController::class, 'indirizzi']);
    Route::get('/account/fatture', [InertiaAccountController::class, 'fatture']);
    Route::get('/account/portafoglio', [InertiaAccountController::class, 'portafoglio']);
    Route::get('/account/assistenza', [InertiaAccountController::class, 'assistenza']);

    /* ────── Admin (auth + role check) ────── */
    Route::middleware([\App\Http\Middleware\CheckAdmin::class])->prefix('account/amministrazione')->group(function () {
        Route::get('/', [InertiaAdminController::class, 'dashboard']);
        Route::get('/ordini', [InertiaAdminController::class, 'ordini']);
        Route::get('/utenti', [InertiaAdminController::class, 'utenti']);
        Route::get('/spedizioni', [InertiaAdminController::class, 'spedizioni']);
        Route::get('/bonifici', [InertiaAdminController::class, 'bonifici']);
        Route::get('/prezzi', [InertiaAdminController::class, 'prezzi']);
        Route::get('/impostazioni', [InertiaAdminController::class, 'impostazioni']);
        Route::put('/impostazioni', [InertiaAdminController::class, 'updateImpostazioni']);
    });
});

/* ────── Webhook esterni (no sessione, no Inertia) ────── */
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
Route::post('/webhooks/brt/tracking', [BrtWebhookController::class, 'handleTrackingUpdate'])->middleware('throttle:60,1');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

/* ────── Sanctum CSRF (per richieste AJAX da pages Inertia) ────── */
Route::middleware(['web', 'throttle:30,1'])
    ->get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])
    ->name('sanctum.csrf-cookie');
