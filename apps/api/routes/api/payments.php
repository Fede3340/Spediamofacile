<?php

/**
 * ROTTE PAGAMENTI
 *
 * Include: Stripe (pagamento ordini, carte salvate, impostazioni),
 * portafoglio virtuale (ricarica, pagamento, saldo, movimenti).
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Checkout\StripeCheckoutController;
use App\Http\Controllers\Checkout\StripeCustomerController;
use App\Http\Controllers\Checkout\StripeConnectController;
use App\Http\Controllers\Catalog\SettingsController;
use App\Http\Controllers\Wallet\WalletController;
use App\Http\Middleware\CheckCart;
use App\Http\Middleware\CheckAdmin;

Route::group(['middleware' => ['auth:sanctum']], function () {

    /* ===== STRIPE CONNECT (Partner Pro) ===== */

    Route::get('/stripe/connect', [StripeConnectController::class, 'connect']);
    Route::get('/stripe/callback', [StripeConnectController::class, 'callback']);
    Route::get('/stripe/create-account', [StripeConnectController::class, 'createAccount']);

    /* ===== PAGAMENTO ORDINI ESISTENTI ===== */

    // Il completamento finale di wallet/bonifico puo' essere ritentato piu' volte
    // dalla stessa UI durante i probe locali o dopo retry di rete. Qui manteniamo
    // il rate limit, ma con margine piu' alto per non bloccare un checkout valido.
    Route::middleware(['throttle:60,1,stripe-mark-order-completed:'])->post('stripe/mark-order-completed', [StripeCheckoutController::class, 'markOrderCompleted']);

    Route::middleware(['throttle:60,1'])->group(function () {
        Route::post('stripe/existing-order-payment', [StripeCheckoutController::class, 'createPayment']);
        Route::post('stripe/existing-order-payment-intent', [StripeCheckoutController::class, 'createPaymentIntent']);
        Route::post('stripe/existing-order-paid', [StripeCheckoutController::class, 'orderPaid']);
    });

    /* ===== PAGAMENTO DA CARRELLO ===== */

    Route::group(['middleware' => [CheckCart::class, 'throttle:10,1']], function () {
        Route::post('stripe/create-payment', [StripeCheckoutController::class, 'createPayment']);
        Route::post('stripe/create-order', [StripeCheckoutController::class, 'createOrder']);
        Route::post('stripe/create-payment-intent', [StripeCheckoutController::class, 'createPaymentIntent']);
        Route::post('stripe/order-paid', [StripeCheckoutController::class, 'orderPaid']);
    });

    /* ===== IMPOSTAZIONI STRIPE ===== */

    Route::get('settings/stripe', [SettingsController::class, 'getStripeConfig']);
    Route::middleware([CheckAdmin::class])->group(function () {
        Route::post('settings/stripe', [SettingsController::class, 'saveStripeConfig']);
    });

    /* ===== CARTE DI CREDITO SALVATE ===== */

    Route::middleware(['throttle:10,1'])->post('stripe/create-setup-intent', [StripeCustomerController::class, 'createSetupIntent']);
    Route::get('stripe/payment-methods', [StripeCustomerController::class, 'listPaymentMethods']);
    Route::middleware(['throttle:10,1'])->post('stripe/set-default-payment-method', [StripeCustomerController::class, 'setDefaultPaymentMethod']);
    Route::middleware(['throttle:10,1'])->post('stripe/change-default-payment-method', [StripeCustomerController::class, 'changeDefaultPaymentMethod']);
    Route::get('stripe/default-payment-method', [StripeCustomerController::class, 'getDefaultPaymentMethod']);
    Route::middleware(['throttle:10,1'])->delete('stripe/delete-card', [StripeCustomerController::class, 'deleteCard']);
});

/* ===== PORTAFOGLIO VIRTUALE ===== */

Route::middleware('auth:sanctum')->prefix('wallet')->group(function () {
    Route::get('/balance', [WalletController::class, 'balance']);
    Route::get('/movements', [WalletController::class, 'movements']);
    Route::middleware(['throttle:5,1'])->post('/top-up', [WalletController::class, 'topUp']);
    Route::middleware(['throttle:10,1'])->post('/pay', [WalletController::class, 'payWithWallet']);
});
