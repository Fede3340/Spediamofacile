<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Auth via Inertia: login, register, logout, password reset, email verification.
 * Sostituisce 6 controller Auth API con 3 metodi atomici e shared via HandleInertiaRequests.
 */
class InertiaAuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);

        if (! Auth::attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            (bool) ($credentials['remember'] ?? false)
        )) {
            return back()->withErrors(['email' => 'Le credenziali non sono corrette.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        return redirect()->intended('/account');
    }

    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'surname' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'telephone_number' => 'nullable|string|max:30',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'privacy_accepted' => 'accepted',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'surname' => $data['surname'],
            'email' => $data['email'],
            'telephone_number' => $data['telephone_number'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'User',
            'privacy_accepted_at' => now(),
        ]);

        Auth::login($user);
        $user->sendEmailVerificationNotification();

        return redirect('/email/verify')->with('success', 'Account creato. Controlla la posta per verificare l\'email.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function showForgotPassword(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));
        return back()->with('success', 'Se l\'email esiste, ti abbiamo inviato un link.');
    }

    public function showResetPassword(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $data,
            fn (User $user, string $password) => $user->forceFill(['password' => Hash::make($password)])->save(),
        );

        return $status === Password::PASSWORD_RESET
            ? redirect('/login')->with('success', 'Password aggiornata. Accedi.')
            : back()->withErrors(['email' => __($status)]);
    }

    public function showVerifyEmail(): Response
    {
        return Inertia::render('Auth/VerifyEmail', [
            'status' => session('status'),
        ]);
    }

    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->markEmailAsVerified();
            event(new Verified($request->user()));
        }
        return redirect('/account')->with('success', 'Email verificata.');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    }
}
