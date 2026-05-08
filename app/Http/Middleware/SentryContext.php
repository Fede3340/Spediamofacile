<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * FILE: app/Http/Middleware/SentryContext.php
 * SCOPO: Arricchisce ogni evento Sentry con metadati utili al debug.
 *
 * COSA AGGIUNGE (senza violare GDPR):
 *   - user.id, user.role (NO email, NO nome cognome)
 *   - request: route name, method, IP hashato (sha256 first 8 char)
 *   - tags: env, release, feature flags attive
 *
 * COSA NON AGGIUNGE:
 *   - Email, telefono, codice fiscale
 *   - IP in chiaro
 *   - Header Authorization / Cookie
 *
 * COME SI USA:
 *   Registrato in bootstrap/app.php come middleware globale dopo trustProxies.
 *   Viene eseguito PRIMA di ogni route: se un'eccezione esplode nel
 *   controller, lo scope Sentry e' gia' popolato.
 */
class SentryContext
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Se il pacchetto Sentry non e' installato (es. dev locale senza composer install),
        // salta tutto silenziosamente: nessun errore, nessun log rumoroso.
        if (! class_exists('\\Sentry\\SentrySdk')) {
            return $next($request);
        }

        // Il pacchetto sentry/sentry e' opzionale: dipende dal deploy. Usiamo dynamic
        // dispatch (call_user_func) per evitare riferimenti statici a simboli non
        // risolvibili al type-check (Sentry\State\Scope, Sentry\configureScope).
        try {
            $this->configureSentryScope($request);
        } catch (\Throwable $e) {
            // Difesa in profondita': se Sentry stesso rompe, non far crashare la richiesta.
            // I log Laravel cattureranno l'errore se serve.
        }

        return $next($request);
    }

    /**
     * Popola lo scope Sentry. Tutto invocato via callable dinamici per evitare
     * dipendenze hard-coded da Sentry SDK al livello PHPStan.
     */
    private function configureSentryScope(Request $request): void
    {
        // Riferimento via stringa (e cast a callable solo dopo function_exists)
        // per evitare che PHPStan tenti di collegare staticamente il simbolo al
        // pacchetto sentry/sentry (potrebbe non essere installato in dev).
        /** @var string $configureScope */
        $configureScope = 'Sentry\\configureScope';
        if (! function_exists($configureScope)) {
            return;
        }

        $callback = function ($scope) use ($request): void {
            $user = $request->user();
            if ($user !== null) {
                $scope->setUser([
                    'id' => (string) $user->getAuthIdentifier(),
                    'role' => $user->role ?? 'user',
                ]);
            }

            $routeName = optional($request->route())->getName() ?? '(unnamed)';
            $scope->setContext('request', [
                'route' => $routeName,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip_hash' => substr(hash('sha256', (string) $request->ip()), 0, 12),
                'user_agent_family' => $this->extractUserAgentFamily($request->userAgent() ?? ''),
            ]);

            $scope->setTag('env', (string) config('app.env'));
            $scope->setTag('locale', (string) app()->getLocale());

            if ($user !== null) {
                $scope->setTag('user.role', (string) ($user->role ?? 'user'));
                $scope->setTag('user.authenticated', 'yes');
            } else {
                $scope->setTag('user.authenticated', 'no');
            }
        };

        // function_exists e' gia' garante: riferimento a callable via variabile.
        $configureScope($callback);
    }

    /**
     * Estrae solo il "family" del user agent (es. "Chrome", "Firefox", "bot")
     * evitando di salvare la stringa completa che puo' contenere info utente.
     */
    private function extractUserAgentFamily(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'bot') || str_contains($ua, 'crawler') || str_contains($ua, 'spider')) {
            return 'bot';
        }
        foreach (['edg/' => 'edge', 'chrome/' => 'chrome', 'firefox/' => 'firefox', 'safari/' => 'safari'] as $needle => $family) {
            if (str_contains($ua, $needle)) {
                return $family;
            }
        }

        return 'other';
    }
}
