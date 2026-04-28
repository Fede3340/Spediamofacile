<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HydrateSanctumFrontendHeaders
{
    public function handle(Request $request, Closure $next)
    {
        if (
            $request->headers->has('referer')
            || $request->headers->has('origin')
            || (! $request->is('api/*') && ! $request->is('sanctum/csrf-cookie'))
        ) {
            return $next($request);
        }

        $hasSessionCookie = $request->cookies->has((string) config('session.cookie'));
        $hasXsrfCookie = $request->cookies->has('XSRF-TOKEN');

        if (! $hasSessionCookie && ! $hasXsrfCookie) {
            return $next($request);
        }

        // Usiamo il frontend_url dalla config invece di derivarlo dal request.
        // Motivo: il request puo' essere manipolato dall'utente (header Host spoofing),
        // mentre la config e' un valore trusted impostato dal server.
        $origin = rtrim(config('app.frontend_url', config('app.url')), '/');

        if ($origin !== '') {
            $request->headers->set('origin', $origin);
            $request->headers->set('referer', $origin . '/');
        }

        return $next($request);
    }
}
