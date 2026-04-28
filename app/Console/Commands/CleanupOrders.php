<?php

/**
 * COMANDO ARTISAN: PULIZIA ORDINI VUOTI (CleanupOrders)
 *
 * Questo e' un comando che si puo' lanciare dal terminale con:
 *   php artisan orders:cleanup
 *
 * Serve per rimuovere dal database gli ordini "orfani", cioe' ordini
 * che non hanno pacchi validi collegati. Questo puo' succedere quando
 * un utente inizia a creare un ordine ma non lo completa.
 *
 * Opzioni disponibili:
 * --dry-run : Mostra cosa verrebbe eliminato SENZA eliminare davvero
 *             (utile per controllare prima di procedere)
 * --all : Controlla ordini con QUALSIASI stato (non solo "pending" e "payment_failed")
 *
 * Un pacco e' considerato "valido" se ha un tipo (es. "scatola") e almeno
 * il peso o le dimensioni compilati.
 *
 * Collegamento con altri file:
 * - app/Models/Order.php: il modello degli ordini che vengono controllati/eliminati
 * - app/Models/Package.php: i pacchi collegati agli ordini
 * - OrderController.php: contiene una logica simile nel metodo cleanupEmptyOrders()
 */

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Package;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOrders extends Command
{
    // Firma del comando: il nome "orders:cleanup" e le opzioni disponibili
    protected $signature = 'orders:cleanup
        {--dry-run : Show what would be deleted without actually deleting}
        {--all : Include all statuses, not just pending/payment_failed}';

    // Descrizione mostrata quando si lancia "php artisan list" (elenco comandi)
    protected $description = 'Remove orders that have no valid packages (orphaned orders)';

    // Funzione principale che viene eseguita quando si lancia il comando
    public function handle(): int
    {
        // Leggiamo le opzioni passate dal terminale
        $isDryRun = $this->option('dry-run');    // Se true, non elimina davvero
        $allStatuses = $this->option('all');       // Se true, controlla tutti gli stati

        // Prepariamo la query per recuperare gli ordini da controllare
        $query = Order::query();

        // Di default controlliamo solo gli ordini in attesa o con pagamento fallito
        // Con --all controlliamo tutti gli ordini
        if (!$allStatuses) {
            $query->whereIn('status', [Order::PENDING, Order::PAYMENT_FAILED]);
        }

        $orders = $query->get();
        $deletedCount = 0;

        // Per ogni ordine, controlliamo se ha pacchi validi collegati
        foreach ($orders as $order) {
            // Recuperiamo gli ID dei pacchi collegati a questo ordine
            $packageIds = DB::table('package_order')
                ->where('order_id', $order->id)
                ->pluck('package_id');

            $shouldDelete = false;

            if ($packageIds->isEmpty()) {
                // L'ordine non ha nessun pacco collegato -> da eliminare
                $shouldDelete = true;
            } else {
                // Contiamo quanti pacchi sono "validi" (hanno tipo e almeno peso o dimensioni)
                $validPackages = Package::whereIn('id', $packageIds)
                    ->where(function ($q) {
                        $q->whereNotNull('package_type')
                          ->where('package_type', '!=', '')
                          ->where(function ($q2) {
                              $q2->where('weight', '>', 0)
                                 ->orWhere('first_size', '>', 0);
                          });
                    })
                    ->count();

                if ($validPackages === 0) {
                    // L'ordine ha pacchi collegati ma nessuno e' valido -> da eliminare
                    $shouldDelete = true;
                }
            }

            if ($shouldDelete) {
                if ($isDryRun) {
                    // In modalita' dry-run, mostriamo solo cosa verrebbe eliminato
                    $this->line("Would delete order #{$order->id} (status: {$order->getRawOriginal('status')}, user: {$order->user_id})");
                } else {
                    // Eliminiamo i collegamenti con i pacchi e poi l'ordine stesso
                    DB::table('package_order')->where('order_id', $order->id)->delete();
                    $order->delete();
                }
                $deletedCount++;
            }
        }

        // Mostriamo il riepilogo dell'operazione
        if ($isDryRun) {
            $this->info("Dry run: {$deletedCount} orders would be deleted out of {$orders->count()} checked.");
        } else {
            $this->info("Deleted {$deletedCount} empty orders out of {$orders->count()} checked.");
        }

        return self::SUCCESS;
    }
}
