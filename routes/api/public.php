<?php

/**
 * ROTTE PUBBLICHE — Contenuti e GDPR
 *
 * Include: guide, servizi, fasce prezzo, immagine homepage,
 * GDPR (cancellazione account, export dati, consenso cookie).
 */

use App\Http\Controllers\Account\UserDataExportController;
use App\Http\Controllers\Admin\HomepageImageController;
use App\Http\Controllers\Catalog\PublicArticleController;
use App\Http\Controllers\Catalog\PublicPriceBandController;
use App\Http\Controllers\Gdpr\GdprController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/* ===== HEALTH CHECK (Sprint 7.2) ===== */
// Endpoint usati da Render, UptimeRobot, load balancer per verificare stato app.
// Throttle 30 req/min per evitare abuso (probe esterni poll ogni 30-60s).
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/health', [HealthController::class, 'index']);        // readiness
    Route::get('/health/live', [HealthController::class, 'live']);    // liveness
});

/* ===== CONTENUTI PUBBLICI ===== */

Route::prefix('public')->group(function () {
    Route::get('/guides', [PublicArticleController::class, 'guides']);
    Route::get('/guides/{slug}', [PublicArticleController::class, 'guide']);
    Route::get('/services', [PublicArticleController::class, 'services']);
    Route::get('/services/{slug}', [PublicArticleController::class, 'service']);
    Route::get('/price-bands', [PublicPriceBandController::class, 'index']);
    Route::get('/homepage-image', [HomepageImageController::class, 'getHomepageImage']);
});

/* ===== GDPR ===== */

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::delete('/account', [GdprController::class, 'deleteAccount']);
    Route::get('/data-export', [GdprController::class, 'dataExport']);
});

// Alias canonico (P1.3): /api/me/export-data — download streaming + audit log.
// Il legacy /api/user/data-export resta attivo per back-compat (JSON inline, no audit).
Route::middleware('auth:sanctum')
    ->get('/me/export-data', UserDataExportController::class)
    ->name('me.export-data');

Route::middleware(['throttle:10,1'])->post('/cookie-consent', [GdprController::class, 'cookieConsent']);
