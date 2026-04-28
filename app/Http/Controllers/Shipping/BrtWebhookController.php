<?php
namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;

use App\Models\BrtWebhookEvent;
use App\Services\OrderBrtTrackingLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BrtWebhookController extends Controller
{
    public function __construct(
        private readonly OrderBrtTrackingLifecycleService $trackingLifecycle,
    ) {}

    /**
     * Riceve un aggiornamento tracking push da BRT.
     */
    public function handleTrackingUpdate(Request $request): JsonResponse
    {
        $authError = $this->verifyRequestAuthenticity($request);
        if ($authError) {
            Log::warning('BRT webhook auth failed', [
                'ip' => $request->ip(),
                'reason' => $authError,
            ]);

            return response()->json(['error' => $authError], 403);
        }

        $validated = $request->validate([
            'parcelId' => 'required|string|max:100',
            'status' => 'required|string|max:100',
            'timestamp' => 'required',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        Log::info('BRT webhook received', [
            'parcelId' => $validated['parcelId'],
            'status' => $validated['status'],
            'timestamp' => $validated['timestamp'],
            'location' => $validated['location'] ?? null,
            'description' => $validated['description'] ?? null,
            'ip' => $request->ip(),
        ]);

        $eventTimestamp = (string) $validated['timestamp'];
        $isNewEvent = BrtWebhookEvent::markAsProcessed(
            $validated['parcelId'],
            $validated['status'],
            $eventTimestamp,
        );

        if (! $isNewEvent) {
            Log::info('BRT webhook: evento duplicato (dedup via fingerprint)', [
                'parcelId' => $validated['parcelId'],
                'status' => $validated['status'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Evento gia\' processato.',
                'skipped' => 'already_processed',
            ]);
        }

        $result = $this->trackingLifecycle->applyWebhookStatusUpdate(
            $validated['parcelId'],
            $validated['status'],
            $validated['description'] ?? null,
        );

        $outcome = $result['outcome'] ?? 'unknown';
        $order = $result['order'] ?? null;
        $orderId = $order?->id;

        if ($outcome === 'order_not_found') {
            Log::warning('BRT webhook: ordine non trovato', [
                'parcelId' => $validated['parcelId'],
            ]);

            return response()->json([
                'error' => 'Ordine non trovato per il parcelId fornito.',
            ], 404);
        }

        if ($outcome === 'unmapped') {
            Log::info('BRT webhook: status non mappato, ignorato', [
                'order_id' => $orderId,
                'brt_status' => $validated['status'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status ricevuto ma non mappato a uno stato interno.',
            ]);
        }

        if ($outcome === 'unchanged') {
            Log::info('BRT webhook: stato invariato', [
                'order_id' => $orderId,
                'status' => $result['new_status'] ?? $result['old_status'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stato invariato.',
            ]);
        }

        if ($outcome === 'blocked_final_state') {
            Log::warning('BRT webhook: tentativo di retrocedere da stato finale', [
                'order_id' => $orderId,
                'old_status' => $result['old_status'] ?? null,
                'new_status' => $result['new_status'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stato finale non sovrascrivibile.',
            ]);
        }

        if ($outcome === 'updated') {
            Log::info('BRT webhook: ordine aggiornato', [
                'order_id' => $orderId,
                'old_status' => $result['old_status'] ?? null,
                'new_status' => $result['new_status'] ?? null,
                'brt_status' => $validated['status'],
            ]);

            return response()->json([
                'success' => true,
                'order_id' => $orderId,
                'old_status' => $result['old_status'] ?? null,
                'new_status' => $result['new_status'] ?? null,
            ]);
        }

        Log::warning('BRT webhook: outcome inatteso', [
            'outcome' => $outcome,
            'parcelId' => $validated['parcelId'],
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Aggiornamento tracking non applicato.',
        ], 422);
    }

    /**
     * Verifica che la richiesta provenga realmente dai server BRT.
     *
     * Strategia a due livelli:
     * 1. Se BRT_WEBHOOK_SECRET e' configurato → verifica firma HMAC-SHA256
     * 2. Se BRT_WEBHOOK_ALLOWED_IPS e' configurato → verifica IP sorgente
     * 3. Se nessuno dei due → accetta (solo per sviluppo locale)
     *
     * @return string|null Messaggio di errore, o null se la verifica e' ok
     */
    private function verifyRequestAuthenticity(Request $request): ?string
    {
        $secret = config('services.brt.webhook_secret');
        $allowedIps = config('services.brt.webhook_allowed_ips');

        if (! empty($secret)) {
            $signature = $request->header('X-Brt-Signature');

            if (empty($signature)) {
                return 'Header X-Brt-Signature mancante.';
            }

            $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

            if (! hash_equals($expectedSignature, $signature)) {
                return 'Firma HMAC non valida.';
            }

            return null;
        }

        if (! empty($allowedIps)) {
            $ips = array_map('trim', explode(',', $allowedIps));
            $clientIp = $request->ip();

            if (! in_array($clientIp, $ips, true)) {
                return 'IP ' . $clientIp . ' non autorizzato.';
            }

            return null;
        }

        if (app()->isProduction()) {
            Log::warning('BRT webhook: nessuna protezione configurata in produzione. Configurare BRT_WEBHOOK_SECRET o BRT_WEBHOOK_ALLOWED_IPS.');
        }

        return null;
    }
}
