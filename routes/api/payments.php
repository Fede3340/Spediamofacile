<?php

/**
 * ROTTE PAGAMENTI (post-rewrite v2.0)
 *
 * Stripe Elements custom RIMOSSO. Pagamenti ordine ora via Inertia hosted
 * checkout (POST /checkout/stripe → redirect Stripe). Qui restano:
 * - Stripe Connect (partner Pro): onboarding account
 * - Stripe Customer: carte salvate (setup intent + payment methods)
 * - Wallet: ricarica, pagamento, saldo, movimenti
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Checkout\StripeCustomerController;
use App\Http\Controllers\Checkout\StripeConnectController;
use App\Http\Controllers\Catalog\SettingsController;
use App\Http\Controllers\Wallet\WalletController;
use App\Http\Middleware\CheckAdmin;

Route::group(['middleware' => ['auth:sanctum']], function () {

    /* ===== STRIPE CONNECT (Partner Pro onboarding) ===== */
    Route::get('/stripe/connect', [StripeConnectController::class, 'connect']);
    Route::get('/stripe/callback', [StripeConnectController::class, 'callback']);
    Route::get('/stripe/create-account', [StripeConnectController::class, 'createAccount']);

    /* ===== IMPOSTAZIONI STRIPE ===== */
    Route::get('settings/stripe', [SettingsController::class, 'getStripeConfig']);
    Route::middleware([CheckAdmin::class])->post('settings/stripe', [SettingsController::class, 'saveStripeConfig']);

    /* ===== CARTE SALVATE (Stripe Customer) ===== */
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
