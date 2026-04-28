<?php

/**
 * COMANDO ARTISAN: SINCRONIZZAZIONE TRACKING BRT (SyncBrtTracking)
 *
 * Questo comando si lancia dal terminale con:
 *   php artisan orders:sync-tracking
 *
 * Interroga le API BRT per aggiornare automaticamente lo stato degli ordini
 * che sono in transito (in_transit) o in lavorazione (processing).
 *
 * Viene schedulato ogni ora in routes/console.php.
 *
 * Opzioni:
 * --order=ID : Sincronizza solo un ordine specifico
 * --dry-run  : Mostra cosa cambierebbe senza aggiornare il DB
 *
 * Collegamento con altri file:
 * - app/Services/OrderBrtTrackingLifecycleService.php: owner canonico del tracking order-centric
 * - app/Models/Order.php: modello ordine con costanti di stato
 * - routes/console.php: scheduling automatico
 */

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderBrtTrackingLifecycleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncBrtTracking extends Command
{
    protected $signature = 'orders:sync-tracking
        {--order= : Sincronizza solo un ordine specifico}
        {--dry-run : Mostra le modifiche senza applicarle}';

    protected $description = 'Sincronizza lo stato degli ordini con il tracking BRT';

    public function handle(OrderBrtTrackingLifecycleService $trackingLifecycle): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $specificOrderId = $this->option('order');

        $query = Order::whereIn('status', [
            Order::IN_TRANSIT,
            Order::PROCESSING,
            Order::LABEL_GENERATED,
            Order::OUT_FOR_DELIVERY,
            Order::IN_GIACENZA,
        ])->where(function ($q) {
            $q->whereNotNull('brt_numeric_sender_reference')
                ->orWhereNotNull('brt_parcel_id');
        });

        if ($specificOrderId) {
            $query->where('id', $specificOrderId);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->info('Nessun ordine attivo da sincronizzare.');

            return self::SUCCESS;
        }

        $this->info("Trovati {$orders->count()} ordini da sincronizzare.");
        $updated = 0;
        $errors = 0;

        foreach ($orders as $order) {
            $result = $trackingLifecycle->syncOrderFromCarrier($order, $dryRun);
            $outcome = $result['outcome'] ?? 'unknown';
            $oldStatus = $result['old_status'] ?? $order->rawStatus();
            $newStatus = $result['new_status'] ?? null;
            $eventCode = $result['brt_event'] ?? null;
            $description = $result['description'] ?? null;

            if ($outcome === 'carrier_error') {
                $this->warn("Ordine #{$order->id}: errore - {$result['error']}");
                $errors++;

                continue;
            }

            if ($outcome === 'unmapped') {
                $this->line("Ordine #{$order->id}: nessun cambiamento di stato (evento: {$eventCode})");

                continue;
            }

            if ($outcome === 'unchanged') {
                $this->line("Ordine #{$order->id}: stato invariato ({$oldStatus})");

                continue;
            }

            if ($outcome === 'blocked_final_state') {
                $this->warn("Ordine #{$order->id}: transizione bloccata {$oldStatus} -> {$newStatus} (stato finale).");

                continue;
            }

            if ($outcome === 'updated') {
                if ($dryRun) {
                    $this->info("[DRY-RUN] Ordine #{$order->id}: {$oldStatus} -> {$newStatus} ({$description})");
                } else {
                    Log::info('SyncBrtTracking: ordine aggiornato', [
                        'order_id' => $order->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'brt_event' => $eventCode,
                    ]);

                    $this->info("Ordine #{$order->id}: {$oldStatus} -> {$newStatus} ({$description})");
                }

                $updated++;

                continue;
            }

            $this->line("Ordine #{$order->id}: nessuna azione ({$outcome}).");
        }

        $this->newLine();
        $this->info("Completato: {$updated} aggiornati, {$errors} errori su {$orders->count()} ordini.");

        return self::SUCCESS;
    }
}
