<?php

/**
 * ROTTE AUTENTICAZIONE E GESTIONE UTENTE
 *
 * Include: utente corrente, logout, login, registrazione, verifica email,
 * recupero password, OAuth social, upload file admin, confirm password.
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Account\UserController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\CustomRegisterController;
use App\Http\Controllers\Auth\PasswordResetRequestController;
use App\Http\Middleware\CheckAdmin;
use App\Support\AuthUiCookie;

/* ===== UTENTE CORRENTE E LOGOUT ===== */

Route::get('/user', function (Request $request) {
    return response()->json($request->user())
        ->cookie(AuthUiCookie::issueForUser($request->user(), Auth::guard('web')->viaRemember()));
})->middleware('auth:sanctum');

Route::post('/logout', function (Request $request) {
    $user = $request->user();
    // GDPR-07: Revoca tutti i token Sanctum prima del logout
    $user->tokens()->delete();

    // F14 audit
    \App\Services\AuditLogService::log('auth.logout', null, [], ['user' => $user]);

    Auth::guard('web')->logout();
    if ($request->hasSession()) {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
    return response()->json(['message' => 'Logged out'])
        ->cookie(AuthUiCookie::forget());
})->middleware('auth:sanctum');

/* ===== REGISTRAZIONE ===== */

Route::middleware(['throttle:10,1'])->post('/custom-register', [RegisterController::class, 'register']);

/* ===== PROVIDER OAUTH ===== */

Route::get('/auth/providers', function () {
    $isConfigured = static fn (string $provider) => filled(config("services.{$provider}.client_id"))
        && filled(config("services.{$provider}.client_secret"))
        && filled(config("services.{$provider}.redirect"));

    // Solo Google OAuth attivo (Facebook + Apple rimossi).
    return response()->json([
        'google' => $isConfigured('google'),
        'facebook' => false,
        'apple' => false,
    ]);
});

// Sprint 6.3 (BLOCKER GO-LIVE): le rotte di redirect OAuth devono avere una
// SESSIONE attiva per salvare lo state CSRF + (Google) il code_verifier PKCE.
// Con Sanctum statefulApi() la sessione viene caricata solo se l'Origin e'
// tra gli host trusted, condizione non garantita quando il browser segue un
// redirect top-level. Aggiungiamo esplicitamente StartSession in modo che
// l'OAuth handshake sia indipendente dai controlli stateful.
Route::middleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
])->group(function () {
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirectToGoogle']);
});

/* ===== LOGIN ===== */

// Login rate-limit più stretto (anti-bruteforce): 10 tentativi/min per IP
Route::middleware(['throttle:10,1'])->post('/custom-login', [LoginController::class, 'login']);
Route::middleware(['throttle:5,1'])->post('/resend-verification-email', [RegisterController::class, 'resendVerificationEmail']);
Route::middleware(['throttle:5,1'])->post('/verify-code', [RegisterController::class, 'verifyCode']);

/* ===== CONFERMA EMAIL ===== */

Route::get('/verify-email/{id}', [VerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

/* ===== RECUPERO PASSWORD ===== */

Route::middleware(['throttle:5,1'])->post('/forgot-password', [PasswordResetRequestController::class, 'sendEmail']);
Route::middleware(['throttle:5,1'])->post('/update-password', [ChangePasswordController::class, 'passwordResetProcess']);

/* ===== UPLOAD FILE (admin) E IMMAGINE ADMIN ===== */

// Sprint 6.7: throttle avatar upload admin 30/min
Route::post('/upload-file', [UserController::class, 'uploadFile'])
    ->middleware(['auth:sanctum', CheckAdmin::class, 'throttle:30,1']);
Route::get('/get-admin-image', [UserController::class, 'getAdminImage']);

/* ===== ROTTE PROTETTE UTENTE ===== */

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/auth/confirm-password', [LoginController::class, 'confirmPassword']);
    // Solo le rotte necessarie — NO apiResource completo che espone GET /api/users
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
});
