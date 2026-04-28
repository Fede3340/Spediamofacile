<?php

/**
 * RESOURCE: FORMATTAZIONE DATI SERVIZIO
 *
 * Trasforma i dati di un servizio di spedizione in formato JSON per il frontend.
 * Include: il tipo di servizio (standard, express...), la data e l'orario di ritiro.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Service */
class ServiceResource extends JsonResource
{
    /**
     * Trasforma il servizio in un array di dati da inviare al frontend.
     */
    public function toArray(Request $request): array
    {
        return [
            'service_type' => $this->service_type,  // Tipo di servizio (es. "standard", "express")
            'date' => $this->date,                  // Data di ritiro
            'time' => $this->time,                  // Orario di ritiro
            'serviceData' => $this->service_data,   // Dati aggiuntivi (es. importo contrassegno)
        ];
    }
}
