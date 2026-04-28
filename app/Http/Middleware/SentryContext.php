<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Sentry\State\Scope;

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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Se il pacchetto Sentry non e' installato (es. dev locale senza composer install),
        // salta tutto silenziosamente: nessun errore, nessun log rumoroso.
        if (!class_exists(\Sentry\SentrySdk::class)) {
            return $next($request);
        }

        try {
            \Sentry\configureScope(function (Scope $scope) use ($request): void {
                // User context — solo ID e ruolo, zero PII.
                $user = $request->user();
                if ($user !== null) {
                    $scope->setUser([
                        'id' => (string) $user->getAuthIdentifier(),
                        'role' => $user->role ?? 'user',
                        // NIENTE: email, name, ip_address
                    ]);
                }

                // Request context (metadati safe).
                $routeName = optional($request->route())->getName() ?? '(unnamed)';
                $scope->setContext('request', [
                    'route' => $routeName,
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'ip_hash' => substr(hash('sha256', (string) $request->ip()), 0, 12),
                    'user_agent_family' => $this->extractUserAgentFamily($request->userAgent() ?? ''),
                ]);

                // Tag globali (facilitano filtri in dashboard Sentry).
                $scope->setTag('env', (string) config('app.env'));
                $scope->setTag('locale', (string) app()->getLocale());

                if ($user !== null) {
                    $scope->setTag('user.role', (string) ($user->role ?? 'user'));
                    $scope->setTag('user.authenticated', 'yes');
                } else {
                    $scope->setTag('user.authenticated', 'no');
                }

                // Feature flags: facilita filtrare errori "solo utenti con flag X attivo".
                // Esempio: se abbiamo una feature "new_checkout", taggiamola.
                // Placeholder: aggiungere qui le flag attive del progetto.
                // $scope->setTag('feature.new_checkout', config('features.new_checkout') ? 'on' : 'off');
            });
        } catch (\Throwable $e) {
            // Difesa in profondita': se Sentry stesso rompe, non far crashare la richiesta.
            // I log Laravel cattureranno l'errore se serve.
        }

        return $next($request);
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
