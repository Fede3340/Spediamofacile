<?php

/**
 * ROTTE PANNELLO AMMINISTRAZIONE
 *
 * Tutte le rotte richiedono auth:sanctum + CheckAdmin.
 * Include: dashboard, ordini, spedizioni, utenti, portafoglio, referral,
 * richieste pro, articoli, fasce prezzo, promozioni, coupon, contenuti.
 */

use App\Http\Controllers\Account\ProRequestController;
use App\Http\Controllers\Admin\AdminBankTransferController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HomepageImageController;
use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\Admin\ReferralStatsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WalletManagementController;
use App\Http\Controllers\Catalog\ArticleController;
use App\Http\Controllers\Catalog\PriceBandController;
use App\Http\Middleware\CheckAdmin;
use Illuminate\Support\Facades\Route;

// P1.1 — Middleware 2FA disabilitato finché non viene completata la UI di setup wizard
// (`/account/sicurezza/2fa-setup`) — la UI è pronta in /account/sicurezza/2fa.
// '2fa.required' middleware riattivato per coerenza con i test (TwoFactorEnrollmentTest).
Route::middleware(['auth:sanctum', CheckAdmin::class, 'admin.audit', '2fa.required'])->prefix('admin')->group(function () {

    // --- Dashboard ---
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboard']);

    // --- Ordini e Spedizioni ---
    Route::get('/orders', [OrderManagementController::class, 'orders']);
    Route::patch('/orders/{order}/status', [OrderManagementController::class, 'updateOrderStatus']);
    Route::get('/shipments', [OrderManagementController::class, 'shipments']);
    Route::patch('/orders/{order}/pudo', [OrderManagementController::class, 'updateOrderPudo']);
    Route::post('/orders/{order}/regenerate-label', [OrderManagementController::class, 'regenerateLabel']);

    // F05 — Bonifici in attesa (audit BRT 2026-04-18)
    Route::get('/orders/awaiting-bank-transfer', [AdminBankTransferController::class, 'pending']);
    Route::post('/orders/{order}/confirm-bank-transfer', [AdminBankTransferController::class, 'confirm']);

    // --- Portafoglio e Prelievi ---
    Route::get('/wallet/overview', [WalletManagementController::class, 'walletOverview']);
    Route::get('/wallet/users/{user}/movements', [WalletManagementController::class, 'userMovements']);
    Route::get('/withdrawals', [WalletManagementController::class, 'withdrawals']);
    Route::post('/withdrawals/{withdrawal}/approve', [WalletManagementController::class, 'approveWithdrawal']);
    Route::post('/withdrawals/{withdrawal}/reject', [WalletManagementController::class, 'rejectWithdrawal']);

    // --- Referral ---
    Route::get('/referrals', [ReferralStatsController::class, 'referralStats']);

    // --- Richieste Partner Pro ---
    Route::get('/pro-requests', [ProRequestController::class, 'index']);
    Route::patch('/pro-requests/{proRequest}/approve', [ProRequestController::class, 'approve']);
    Route::patch('/pro-requests/{proRequest}/reject', [ProRequestController::class, 'reject']);

    // --- Utenti ---
    Route::get('/users', [UserManagementController::class, 'users']);
    Route::patch('/users/{user}/approve', [UserManagementController::class, 'approveUser']);
    Route::patch('/users/{user}/role', [UserManagementController::class, 'updateUserRole']);
    Route::patch('/users/{user}/user-type', [UserManagementController::class, 'updateUserType']);
    Route::delete('/users/{user}', [UserManagementController::class, 'deleteUser']);

    // --- Contenuti (messaggi, impostazioni) ---
    Route::get('/contact-messages', [AdminContentController::class, 'contactMessages']);
    Route::patch('/contact-messages/{id}/read', [AdminContentController::class, 'markContactMessageRead']);
    Route::get('/settings', [AdminContentController::class, 'settings']);
    Route::post('/settings', [AdminContentController::class, 'updateSettings']);

    // --- Articoli (guide, servizi) ---
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::get('/articles/{article}', [ArticleController::class, 'show']);
    Route::put('/articles/{article}', [ArticleController::class, 'update']);
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);
    // Sprint 6.7: throttle upload admin (30 req/min = margine per bulk uploads)
    Route::post('/articles/{article}/upload-image', [ArticleController::class, 'uploadImage'])
        ->middleware('throttle:30,1');

    // --- Fasce di prezzo ---
    Route::get('/price-bands', [PriceBandController::class, 'index']);
    Route::put('/price-bands', [PriceBandController::class, 'bulkUpdate']);
    Route::post('/price-bands/seed', [PriceBandController::class, 'seed']);

    // --- Promozioni ---
    Route::get('/promo-settings', [PriceBandController::class, 'getPromoSettings']);
    Route::post('/promo-settings', [PriceBandController::class, 'savePromoSettings']);
    Route::post('/promo-settings/upload-image', [PriceBandController::class, 'uploadPromoImage'])
        ->middleware('throttle:30,1');

    // --- Homepage ---
    Route::post('/homepage-image', [HomepageImageController::class, 'uploadHomepageImage'])
        ->middleware('throttle:30,1');
    Route::get('/homepage-image', [HomepageImageController::class, 'getHomepageImage']);

    // --- Coupon ---
    Route::get('/coupons', [AdminCouponController::class, 'coupons']);
    Route::post('/coupons', [AdminCouponController::class, 'storeCoupon']);
    Route::put('/coupons/{coupon}', [AdminCouponController::class, 'updateCoupon']);
    Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'deleteCoupon']);

    // --- Audit log (F14 audit BRT 2026-04-18) ---
    // Lettura registro attivita' utenti/admin + export CSV.
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);
    Route::get('/audit-logs/actions', [AdminAuditLogController::class, 'actions']);
    Route::get('/audit-logs/export', [AdminAuditLogController::class, 'export'])
        ->middleware('throttle:5,1');
    Route::get('/audit-logs/{auditLog}', [AdminAuditLogController::class, 'show']);
});
