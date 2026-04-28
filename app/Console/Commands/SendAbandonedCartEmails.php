<?php

/**
 * COMMAND: carts:send-abandoned-reminders (F15)
 *
 * Seleziona utenti con righe cart_user "abbandonate":
 *   - cart_user.created_at tra 7 giorni fa e 24 ore fa
 *   - NESSUN ordine creato dopo la riga del carrello
 *   - abandoned_cart_sent_at NULL (non gia' notificati)
 *
 * Per ogni utente candidato:
 *   1) calcola il numero di pacchi nel carrello
 *   2) genera un resume_token hmac (opaco, senza esporre id interni)
 *   3) dispatch del job SendAbandonedCartEmailJob
 *   4) aggiorna abandoned_cart_sent_at su TUTTE le sue righe cart_user
 *
 * Schedulato ogni 6h in routes/console.php.
 * Uso manuale: `php artisan carts:send-abandoned-reminders --dry-run`
 */

namespace App\Console\Commands;

use App\Jobs\SendAbandonedCartEmailJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SendAbandonedCartEmails extends Command
{
    protected $signature = 'carts:send-abandoned-reminders {--dry-run : Lista soltanto, non invia}';
    protected $description = 'Invia email di reminder agli utenti con carrello abbandonato (F15).';

    public function handle(): int
    {
        $now    = Carbon::now();
        $lower  = $now->copy()->subDays(7);     // limite inferiore: non piu' vecchio di 7g
        $upper  = $now->copy()->subHours(24);   // limite superiore: almeno 24h fa

        $dryRun = (bool) $this->option('dry-run');

        // Candidati: raggruppati per user_id con il MIN created_at del loro carrello
        // e un conteggio dei pacchi. Escludiamo carrelli guest (user_id NULL).
        $rows = DB::table('cart_user')
            ->selectRaw('user_id, MIN(created_at) as cart_started_at, COUNT(*) as item_count')
            ->whereNotNull('user_id')
            ->whereNull('abandoned_cart_sent_at')
            ->where('created_at', '>=', $lower)
            ->where('created_at', '<=', $upper)
            ->groupBy('user_id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Nessun carrello abbandonato da notificare.');
            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            // Se l'utente ha creato un ordine dopo quell'istante, consideriamo il
            // carrello "completato": non inviamo reminder.
            $hasOrderAfter = DB::table('orders')
                ->where('user_id', $row->user_id)
                ->where('created_at', '>=', $row->cart_started_at)
                ->exists();

            if ($hasOrderAfter) {
                $skipped++;
                continue;
            }

            $resumeToken = hash_hmac(
                'sha256',
                (string) $row->user_id . '|' . (string) $row->cart_started_at,
                (string) config('app.key')
            );

            if ($dryRun) {
                $this->line(sprintf(
                    '[DRY-RUN] user_id=%d items=%d token=%s',
                    $row->user_id,
                    $row->item_count,
                    substr($resumeToken, 0, 12) . '...'
                ));
                $sent++;
                continue;
            }

            SendAbandonedCartEmailJob::dispatch(
                (int) $row->user_id,
                $resumeToken,
                (int) $row->item_count
            );

            DB::table('cart_user')
                ->where('user_id', $row->user_id)
                ->whereNull('abandoned_cart_sent_at')
                ->update(['abandoned_cart_sent_at' => $now]);

            $sent++;
        }

        $this->info(sprintf(
            'Reminder dispatchati: %d. Utenti saltati (ordine dopo carrello): %d.',
            $sent,
            $skipped
        ));

        return self::SUCCESS;
    }
}
