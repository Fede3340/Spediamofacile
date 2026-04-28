<?php

/**
 * RESOURCE: FORMATTAZIONE DATI TRANSAZIONE
 *
 * Trasforma i dati di una transazione di pagamento in formato JSON per il frontend.
 * Include: l'ID esterno (Stripe), il metodo di pagamento tradotto in italiano,
 * lo stato del pagamento e il totale formattato.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Transaction */
class TransactionResource extends JsonResource
{
    /**
     * Trasforma la transazione in un array di dati da inviare al frontend.
     */
    public function toArray(Request $request): array
    {
        return [
            'ext_id' => $this->ext_id,                          // ID del pagamento su Stripe
            'type' => $this->resource->getPaymentMethod($this->type), // Metodo di pagamento in italiano
            'status' => $this->status,                          // Stato del pagamento
            'total' => $this->total->formatted(),               // Importo formattato (es. "12,50 EUR")
        ];
    }
}
