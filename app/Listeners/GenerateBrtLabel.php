<?php
namespace App\Listeners;

use App\Events\OrderPaid;
use App\Services\BrtService;
use App\Services\OrderBrtFulfillmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateBrtLabel implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * Numero massimo di tentativi che Laravel eseguira' in caso di eccezione.
     * PERF-02: sostituisce il retry loop interno con sleep().
     */
    public int $tries = 3;

    /**
     * Secondi di attesa tra un tentativo e l'altro (backoff esponenziale).
     *
     * @var array<int>
     */
    public array $backoff = [60, 120];

    public function handle(OrderPaid $event): void
    {
        $order = $event->order;
        $fulfillment = app(OrderBrtFulfillmentService::class);

        if (! config('services.brt.client_id')) {
            $fulfillment->markSkippedBecauseBrtNotConfigured($order);

            Log::info('BRT not configured, skipping label generation for order #'.$order->id);

            return;
        }

        if ($order->brt_parcel_id) {
            return;
        }

        $options = $fulfillment->buildAutomaticShipmentOptions($order);
        $result = app(BrtService::class)->createShipment($order, $options);

        if (! ($result['success'] ?? false)) {
            throw new \RuntimeException(
                'BRT label generation failed for order #'.$order->id.': '.($result['error'] ?? 'Errore sconosciuto')
            );
        }

        $order = $fulfillment->finalizeSuccessfulShipment($order, $result);

        Log::info('BRT label generated automatically for order #'.$order->id, [
            'parcel_id' => $result['parcel_id'] ?? null,
            'tracking_number' => $result['tracking_number'] ?? null,
            'tracking_url' => $result['tracking_url'] ?? null,
            'departure_depot' => $result['departure_depot'] ?? null,
            'arrival_depot' => $result['arrival_depot'] ?? null,
            'service_type' => $result['service_type'] ?? null,
        ]);
    }

    /**
     * Gestisce il fallimento definitivo dopo tutti i tentativi.
     * Salva l'errore nell'ordine cosi' il frontend puo' mostrarlo all'utente.
     */
    public function failed(OrderPaid $event, \Throwable $exception): void
    {
        $order = $event->order;
        $message = $exception->getMessage();

        app(OrderBrtFulfillmentService::class)->markGenerationFailed($order, $message);

        Log::error('BRT auto label generation failed after '.$this->tries.' attempts for order #'.$order->id, [
            'error' => $message,
        ]);
    }
}
