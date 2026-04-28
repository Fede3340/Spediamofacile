<?php

/**
 * RESOURCE: FORMATTAZIONE DATI PACCO
 *
 * Trasforma i dati di un pacco in un formato JSON pulito per il frontend.
 * Include tutte le informazioni del pacco: tipo, dimensioni, peso, prezzi,
 * indirizzo di partenza, indirizzo di destinazione e servizio scelto.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\PackageAddressResource;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Package */
class PackageResource extends JsonResource
{
    /**
     * Trasforma il pacco in un array di dati da inviare al frontend.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'package_type' => $this->package_type,           // Tipo di pacco (busta, scatola...)
            'quantity' => $this->quantity,                    // Quantita'
            'weight' => $this->weight,                       // Peso in kg
            'first_size' => $this->first_size,               // Lunghezza in cm
            'second_size' => $this->second_size,             // Larghezza in cm
            'third_size' => $this->third_size,               // Altezza in cm
            'weight_price' => $this->weight_price,           // Prezzo calcolato sul peso
            'volume_price' => $this->volume_price,           // Prezzo calcolato sul volume
            'single_price' => $this->single_price,           // Prezzo finale per pacco
            'content_description' => $this->content_description, // Descrizione del contenuto
            'origin_address' => new PackageAddressResource($this->originAddress),       // Indirizzo partenza
            'destination_address' => new PackageAddressResource($this->destinationAddress), // Indirizzo arrivo
            'services' => new ServiceResource($this->service), // Servizio di spedizione
            'created_at' => $this->created_at?->toISOString(), // Data di creazione
        ];
    }
}
