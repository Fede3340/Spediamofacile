<?php

/**
 * ROTTE CARRELLO
 *
 * Include: carrello ospite (sessione), carrello utente (database), pacchi.
 *
 * CartTotalController — operazioni a livello di carrello (index, svuota, merge, totali)
 * CartItemController  — CRUD su singoli pacchi (store, show, update, destroy, quantity)
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cart\CartTotalController;
use App\Http\Controllers\Cart\CartItemController;
use App\Http\Controllers\Cart\GuestCartController;
use App\Http\Controllers\Catalog\PackageController;

/* ===== CARRELLO OSPITE (senza login) ===== */

Route::apiResource('guest-cart', GuestCartController::class);
Route::delete('empty-guest-cart', [GuestCartController::class, 'emptyCart']);

/* ===== CARRELLO UTENTE (login richiesto) ===== */

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::delete('empty-cart', [CartTotalController::class, 'emptyCart']);
    Route::post('cart/merge', [CartTotalController::class, 'mergeIdentical']);

    // Index (lista carrello + meta/totali) → CartTotalController
    Route::get('cart', [CartTotalController::class, 'index'])->name('cart.index');

    // Operazioni su singoli pacchi → CartItemController
    Route::post('cart', [CartItemController::class, 'store'])->name('cart.store');
    Route::get('cart/{cart}', [CartItemController::class, 'show'])->name('cart.show');
    Route::put('cart/{cart}', [CartItemController::class, 'update'])->name('cart.update');
    Route::patch('cart/{cart}', [CartItemController::class, 'update']);
    Route::delete('cart/{cart}', [CartItemController::class, 'destroy'])->name('cart.destroy');

    Route::patch('cart/{id}/quantity', [CartItemController::class, 'updateQuantity']);
    Route::apiResource('packages', PackageController::class);
});
