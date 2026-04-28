<?php

/**
 * LISTENER: LogAuthenticationEvents
 *
 * F14 (audit BRT 2026-04-18) — Aggancia gli eventi standard di Laravel Auth
 * e produce righe in audit_logs.
 *
 * EVENTI INTERCETTATI:
 *   - Login successo / fallito
 *   - Logout
 *   - Lockout (rate limit)
 *   - Password reset
 *
 * REGISTRAZIONE: in EventServiceProvider $listen, oppure auto-discover.
 * Qui usiamo subscribe() per centralizzare i bind in un solo posto.
 */

namespace App\Listeners;

use App\Services\AuditLogService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;

class LogAuthenticationEvents
{
    public function handleLogin(Login $event): void
    {
        AuditLogService::log('auth.login', null, [
            'guard' => $event->guard,
            'remember' => (bool) ($event->remember ?? false),
        ], ['user' => $event->user]);
    }

    public function handleFailed(Failed $event): void
    {
        AuditLogService::log('auth.login_failed', null, [
            'guard' => $event->guard,
            'email' => $event->credentials['email'] ?? null,
        ], [
            // attribuiamo l'evento all'utente esistente se l'email matcha,
            // altrimenti resta come guest (user_id null).
            'user' => $event->user,
            'actor_type' => $event->user ? 'user' : 'guest',
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        AuditLogService::log('auth.logout', null, [
            'guard' => $event->guard,
        ], ['user' => $event->user]);
    }

    public function handleLockout(Lockout $event): void
    {
        AuditLogService::log('auth.lockout', null, [
            'route' => optional($event->request->route())->getName(),
            'path' => $event->request->path(),
        ], ['actor_type' => 'guest']);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        AuditLogService::log('auth.password_reset', null, [], [
            'user' => $event->user,
        ]);
    }

    /**
     * @return array<class-string,string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Logout::class => 'handleLogout',
            Lockout::class => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
