<?php

namespace App\Services;

use App\Mail\ShipmentDocumentsMail;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ShipmentDocumentDispatcher
{
    public function dispatchForOrder(Order $order): array
    {
        $order->loadMissing('user');

        $missingPreconditions = [];
        if (empty($order->brt_label_base64)) {
            $missingPreconditions[] = 'etichetta BRT assente';
        }
        if (empty($order->bordero_document_base64) && empty($order->bordero_reference)) {
            $missingPreconditions[] = 'borderò assente';
        }

        if (! empty($missingPreconditions)) {
            $reason = 'Invio documenti saltato: '.implode(', ', $missingPreconditions).'.';
            $order->documents_status = 'skipped';
            $order->execution_error = trim(($order->execution_error ? $order->execution_error.' | ' : '').$reason);
            $order->save();

            Log::warning('Shipment documents dispatch skipped', [
                'order_id' => $order->id,
                'reason' => $reason,
            ]);

            return [
                'success' => false,
                'status' => 'skipped',
                'customer_sent_at' => $order->documents_sent_customer_at?->toIso8601String(),
                'admin_sent_at' => $order->documents_sent_admin_at?->toIso8601String(),
                'errors' => [$reason],
            ];
        }

        $customerSentAt = null;
        $adminSentAt = null;
        $errors = [];

        if (! empty($order->user->email)) {
            try {
                // Async via queue: il webhook BRT non aspetta più l'invio mail
                // (decine di secondi se SMTP lento). In dev con QUEUE_CONNECTION=sync
                // si comporta come prima.
                Mail::to($order->user->email)->queue(new ShipmentDocumentsMail($order));
                $customerSentAt = now();
            } catch (\Throwable $e) {
                $errors[] = 'Invio cliente fallito: '.$e->getMessage();
            }
        } else {
            $errors[] = 'Email cliente non disponibile.';
        }

        $adminEmail = trim((string) Setting::get('support_email', ''));
        if ($adminEmail === '') {
            $adminEmail = trim((string) Setting::get('admin_notification_email', ''));
        }
        if ($adminEmail === '') {
            $adminEmail = trim((string) config('mail.from.address', ''));
        }
        if (! empty($adminEmail)) {
            try {
                // Async via queue (vedi sopra).
                Mail::to($adminEmail)->queue(new ShipmentDocumentsMail($order));
                $adminSentAt = now();
            } catch (\Throwable $e) {
                $errors[] = 'Invio admin fallito: '.$e->getMessage();
            }
        } else {
            $errors[] = 'Email admin non configurata.';
        }

        $status = empty($errors) ? 'sent' : 'failed';

        $order->documents_status = $status;
        if ($customerSentAt) {
            $order->documents_sent_customer_at = $customerSentAt;
        }
        if ($adminSentAt) {
            $order->documents_sent_admin_at = $adminSentAt;
        }
        if (! empty($errors)) {
            $order->execution_error = implode(' | ', $errors);
        }
        $order->save();

        if (! empty($errors)) {
            Log::warning('Shipment documents dispatch completed with errors', [
                'order_id' => $order->id,
                'errors' => $errors,
            ]);
        }

        return [
            'success' => empty($errors),
            'status' => $status,
            'customer_sent_at' => $order->documents_sent_customer_at?->toIso8601String(),
            'admin_sent_at' => $order->documents_sent_admin_at?->toIso8601String(),
            'errors' => $errors,
        ];
    }
}
