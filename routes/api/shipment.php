<?php

/**
 * ROTTE FLUSSO SPEDIZIONE
 *
 * Include: sessione preventivo, autocompletamento localita',
 * tracking pubblico, BRT PUDO (punti di ritiro/consegna).
 */

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Catalog\LocationDetailController;
use App\Http\Controllers\Catalog\LocationSearchController;
use App\Http\Controllers\Shipping\BrtController;
use App\Http\Controllers\Shipping\SessionDataController;
use Illuminate\Support\Facades\Route;

/* ===== SESSIONE PREVENTIVO ===== */

Route::get('/session', [LoginController::class, 'session']);
Route::post('/session/first-step', [SessionDataController::class, 'firstStep']);
Route::post('/session/second-step', [SessionDataController::class, 'secondStep']);
Route::middleware(['throttle:10,1'])->post('/session/reset', [SessionDataController::class, 'reset']);

/* ===== COMUNI, CAP, PROVINCE (autocompletamento indirizzi) ===== */

Route::middleware(['throttle:180,1'])->get('/locations/search', [LocationSearchController::class, 'search']);
Route::middleware(['throttle:180,1'])->get('/locations/by-cap', [LocationDetailController::class, 'byCap']);
Route::middleware(['throttle:180,1'])->get('/locations/by-city', [LocationDetailController::class, 'byCity']);

/* ===== TRACKING PUBBLICO ===== */

// P0.3 anti-scraping: chiave IP, 15/min — vedi RateLimiter "public-tracking" in AppServiceProvider
Route::middleware(['throttle:public-tracking'])->get('/tracking/search', [BrtController::class, 'publicTracking']);

/* ===== BRT PUDO PUBBLICO (Punti di ritiro/consegna) ===== */

Route::middleware(['throttle:30,1'])->get('brt/pudo/search', [BrtController::class, 'pudoSearch']);
Route::middleware(['throttle:30,1'])->get('brt/pudo/nearby', [BrtController::class, 'pudoNearby']);
Route::middleware(['throttle:30,1'])->get('brt/pudo/{pudoId}', [BrtController::class, 'pudoDetails']);
