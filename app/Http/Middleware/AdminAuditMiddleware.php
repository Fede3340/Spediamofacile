<?php

/**
 * MIDDLEWARE: AdminAuditMiddleware
 *
 * P1.2 (compliance) — Logga automaticamente in audit_logs ogni mutation admin
 * (POST/PUT/PATCH/DELETE) che torna 2xx.
 *
 * Si aggancia al gruppo admin in routes/api/admin.php tramite alias 'admin.audit'.
 *
 * NOTE:
 *   - Non logga GET (read-only) ne' risposte non-2xx (validation/forbidden).
 *   - Esegue dopo $next, cosi' lo status code della response e' gia' deciso.
 *   - Coabita con chiamate esplicite AuditLogService::log()/$this->audit()
 *     gia' presenti nei controller (es. UserManagementController): quelle
 *     restano "rich context", il middleware fa da safety-net "catch-all".
 */

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuditMiddleware
{
    private const MUTATION_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldLog($request, $response)) {
            return $response;
        }

        $route = $request->route();
        $action = 'admin.'.strtolower($request->method()).'.'.($route?->getName() ?: $request->path());

        AuditLogService::log(
            action: $action,
            target: null,
            context: [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $route?->getName(),
                'parameters' => $route?->parameters() ?? [],
                'status' => $response->getStatusCode(),
            ],
        );

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        if (! in_array($request->method(), self::MUTATION_METHODS, true)) {
            return false;
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return false;
        }

        return $request->user() !== null;
    }
}
