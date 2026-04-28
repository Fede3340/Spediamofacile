<?php

/**
 * ROTTE FLUSSO SPEDIZIONE
 *
 * Include: sessione preventivo, autocompletamento localita',
 * tracking pubblico, BRT PUDO (punti di ritiro/consegna).
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Catalog\LocationController;
use App\Http\Controllers\Shipping\SessionDataController;
use App\Http\Controllers\Shipping\BrtController;

/* ===== SESSIONE PREVENTIVO ===== */

Route::get('/session', [SessionDataController::class, 'show']);
Route::post('/session/first-step', [SessionDataController::class, 'firstStep']);
Route::post('/session/second-step', [SessionDataController::class, 'secondStep']);
Route::middleware(['throttle:10,1'])->post('/session/reset', [SessionDataController::class, 'reset']);

/* ===== COMUNI, CAP, PROVINCE (autocompletamento indirizzi) ===== */

Route::middleware(['throttle:180,1'])->get('/locations/search', [LocationController::class, 'search']);
Route::middleware(['throttle:180,1'])->get('/locations/by-cap', [LocationController::class, 'byCap']);
Route::middleware(['throttle:180,1'])->get('/locations/by-city', [LocationController::class, 'byCity']);

/* ===== TRACKING PUBBLICO ===== */

Route::middleware(['throttle:15,1'])->get('/tracking/search', [BrtController::class, 'publicTracking']);

/* ===== BRT PUDO PUBBLICO (Punti di ritiro/consegna) ===== */

Route::middleware(['throttle:30,1'])->get('brt/pudo/search', [BrtController::class, 'pudoSearch']);
Route::middleware(['throttle:30,1'])->get('brt/pudo/nearby', [BrtController::class, 'pudoNearby']);
Route::middleware(['throttle:30,1'])->get('brt/pudo/{pudoId}', [BrtController::class, 'pudoDetails']);
