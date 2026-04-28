<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Throwable;

/**
 * HealthController — Sprint 7.2
 *
 * Endpoint di health check per monitoring esterno (UptimeRobot, Render probes, k8s).
 *
 * Convenzione HTTP:
 *   - 200 OK      → tutti i check "ok" (o "degraded" non critici)
 *   - 503 Service Unavailable → almeno un check critico "error"
 *
 * Distinzione liveness vs readiness:
 *   - liveness  (GET /api/health/live)      → il processo e' vivo (sempre 200 se risponde)
 *   - readiness (GET /api/health o /ready)  → dipendenze pronte (DB, cache, BRT, Stripe)
 *
 * Sicurezza: nessuna info sensibile (no stack trace, no credenziali) nei messaggi.
 */
class HealthController extends Controller
{
    /**
     * Readiness probe completo — verifica tutte le dipendenze.
     */
    public function index(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache'    => $this->checkCache(),
            'stripe'   => $this->checkStripe(),
            'brt'      => $this->checkBrt(),
        ];

        // Un check "error" su dipendenze critiche (database, cache) → 503
        // I check "degraded" su integrazioni esterne (stripe, brt) → 200 ma flag
        $criticalDown = in_array($checks['database']['status'], ['error'], true)
            || in_array($checks['cache']['status'], ['error'], true);

        $allOk = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        $httpStatus = $criticalDown ? 503 : 200;
        $overall    = $allOk ? 'ok' : ($criticalDown ? 'error' : 'degraded');

        return response()->json([
            'status'    => $overall,
            'timestamp' => now()->toIso8601String(),
            'version'   => config('app.version', 'unknown'),
            'checks'    => $checks,
        ], $httpStatus);
    }

    /**
     * Liveness probe — il processo e' vivo. Sempre 200 se il container risponde.
     * Usato da Kubernetes/Render per riavviare container morti.
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status'    => 'ok',
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            Log::error('Health check DB failed', ['exception' => $e->getMessage()]);

            return ['status' => 'error', 'message' => 'database unreachable'];
        }
    }

    private function checkCache(): array
    {
        try {
            $key   = 'health_check_' . random_int(1000, 9999);
            $value = 'ping_' . now()->timestamp;

            Cache::put($key, $value, 5);
            $read = Cache::get($key);
            Cache::forget($key);

            if ($read !== $value) {
                return ['status' => 'error', 'message' => 'cache read mismatch'];
            }

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            Log::error('Health check cache failed', ['exception' => $e->getMessage()]);

            return ['status' => 'error', 'message' => 'cache unreachable'];
        }
    }

    private function checkStripe(): array
    {
        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            return ['status' => 'degraded', 'message' => 'stripe not configured'];
        }

        try {
            $client = new StripeClient($secret);
            // Chiamata minima: retrieve balance (non modifica stato, non consuma quota)
            $client->balance->retrieve([], ['timeout' => 3]);

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            Log::warning('Health check Stripe failed', ['exception' => $e->getMessage()]);

            return ['status' => 'degraded', 'message' => 'stripe unreachable'];
        }
    }

    private function checkBrt(): array
    {
        $baseUrl = config('services.brt.api_url') ?? env('BRT_API_URL');
        if (empty($baseUrl)) {
            return ['status' => 'degraded', 'message' => 'brt not configured'];
        }

        try {
            // HEAD sull'host BRT con timeout corto — verifica raggiungibilita' rete
            $host = parse_url($baseUrl, PHP_URL_SCHEME) . '://' . parse_url($baseUrl, PHP_URL_HOST);

            $response = Http::timeout(3)->connectTimeout(2)->head($host);

            // Qualsiasi risposta HTTP (anche 401/404) significa che l'host risponde
            if ($response->status() > 0) {
                return ['status' => 'ok'];
            }

            return ['status' => 'degraded', 'message' => 'brt no response'];
        } catch (Throwable $e) {
            Log::warning('Health check BRT failed', ['exception' => $e->getMessage()]);

            return ['status' => 'degraded', 'message' => 'brt unreachable'];
        }
    }
}
