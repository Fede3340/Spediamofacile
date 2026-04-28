<?php

/**
 * RESOURCE: FORMATTAZIONE DATI ORDINE
 *
 * Una "Resource" in Laravel serve per decidere QUALI dati inviare al frontend
 * e IN CHE FORMATO. Invece di mandare tutti i campi del database cosi' come sono,
 * la Resource li filtra e li formatta per renderli leggibili.
 *
 * Questa Resource trasforma un ordine in un formato JSON pulito con:
 * - Lo stato tradotto in italiano
 * - Il prezzo formattato (es. "12,50 EUR")
 * - La data formattata nel fuso orario italiano
 * - I pacchi e le transazioni gia' formattati con le loro Resource
 * - I dati BRT (tracking, etichetta, contrassegno)
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\PackageResource;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    /**
     * Trasforma l'ordine in un array di dati da inviare al frontend.
     */
    public function toArray(Request $request): array
    {
        $rawStatus = $this->getAttributes()['status'] ?? $this->status;

        // Calcola se l'ordine e' annullabile (per mostrare/nascondere il bottone nel frontend)
        $cancellable = in_array($rawStatus, ['pending', 'payment_failed', 'completed', 'processing', 'in_transit', 'awaiting_bank_transfer']);

        return [
            'id' => $this->id,
            'status' => $this->getStatus($rawStatus),                    // Stato tradotto in italiano
            'raw_status' => $rawStatus,                                  // Stato originale in inglese (per logica frontend)
            'subtotal' => $this->subtotal->formatted(),                  // Prezzo formattato
            'subtotal_cents' => (int) $this->subtotal->amount(),         // Prezzo in centesimi (per calcoli frontend)
            'gross_subtotal_cents' => $this->grossSubtotalCents(),       // Totale lordo in centesimi, prima degli sconti
            'discount_amount_cents' => $this->discountAmountCents(),     // Sconto applicato in centesimi
            'payable_total' => $this->payableTotal()->formatted(),       // Totale scontato da pagare
            'payable_total_cents' => $this->payableTotalCents(),         // Totale scontato in centesimi
            'discount_context' => $this->discountContext(),              // Dettaglio coupon/referral persistito nello snapshot
            'client_submission_id' => $this->client_submission_id,
            'pricing_signature' => $this->pricing_signature,
            'pricing_snapshot_version' => $this->pricing_snapshot_version,
            'pricing_snapshot' => $this->pricing_snapshot,
            'user' => $this->whenLoaded('user'),                             // Dati dell'utente (solo se eager-loaded)
            'created_at' => $this->created_at->setTimezone('Europe/Rome')->format('d/m/Y H:i'), // Data italiana
            'packages' => PackageResource::collection($this->packages),  // Lista pacchi formattati
            'transactions' => TransactionResource::collection($this->transactions), // Lista pagamenti
            'brt_parcel_id' => $this->brt_parcel_id,                     // ID pacco BRT
            'brt_tracking_number' => $this->brt_tracking_number,         // Numero tracking BRT (parcelNumberFrom)
            'brt_numeric_sender_reference' => $this->brt_numeric_sender_reference, // Riferimento numerico mittente BRT
            'brt_tracking_url' => $this->brt_tracking_url,               // Link tracking BRT
            'brt_pudo_id' => $this->brt_pudo_id,                         // ID punto ritiro BRT
            'is_cod' => (bool) $this->is_cod,                            // Se e' in contrassegno
            'cod_amount' => $this->cod_amount,                           // Importo contrassegno
            'has_label' => !empty($this->brt_label_base64),              // Se ha l'etichetta BRT
            'brt_error' => $this->brt_error,                             // Errore generazione etichetta BRT (null se tutto ok)
            'brt_service_type' => $this->brt_service_type,               // Tipo servizio BRT
            // Campi rimborso
            'cancellable' => $cancellable,                               // Se l'ordine puo' essere annullato
            'refund_status' => $this->refund_status,                     // Stato rimborso (pending, completed, failed, none)
            'refund_amount' => $this->refund_amount ? number_format($this->refund_amount / 100, 2, ',', '.') . ' EUR' : null,
            'refund_amount_cents' => $this->refund_amount,               // Importo rimborso in centesimi
            'refund_method' => $this->refund_method,                     // Metodo rimborso (stripe, wallet)
            'refund_reason' => $this->refund_reason,                     // Motivo del rimborso
            'refunded_at' => $this->refunded_at ? $this->refunded_at->setTimezone('Europe/Rome')->format('d/m/Y H:i') : null,
            'cancellation_fee' => $this->cancellation_fee ? number_format($this->cancellation_fee / 100, 2, ',', '.') . ' EUR' : null,
            'payment_method' => $this->payment_method,                   // Metodo di pagamento originale
            // F04 — data ritiro programmata (rieditabile finché non ritirato)
            'pickup_date' => $this->pickup_date?->format('Y-m-d'),
            'pickup_status' => $this->pickup_status,
            'pickup_time_slot' => $this->pickup_time_slot,
            // F05 — conferma bonifico (null se non ancora confermato)
            'bank_transfer_confirmed_at' => $this->bank_transfer_confirmed_at?->setTimezone('Europe/Rome')->format('d/m/Y H:i'),
            'bank_transfer_reference' => $this->bank_transfer_reference,
        ];
    }
}
