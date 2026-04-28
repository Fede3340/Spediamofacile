<?php

/**
 * RESOURCE: FORMATTAZIONE DATI LOCALITA'
 *
 * Trasforma i dati di una localita' italiana in formato JSON per il frontend.
 * Viene usata per l'autocompletamento degli indirizzi:
 * quando l'utente digita un CAP o una citta', il sistema restituisce
 * le localita' corrispondenti in questo formato.
 */

namespace App\Http\Resources;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Location
 */
class LocationResource extends JsonResource
{
    /**
     * Trasforma la localita' in un array di dati da inviare al frontend.
     */
    public function toArray(Request $request): array
    {
        return [
            'postal_code' => $this->postal_code,  // CAP (es. "20121")
            'place_name' => $this->place_name,    // Nome localita' (es. "Milano")
            'province' => $this->province,        // Sigla provincia (es. "MI")
        ];
    }
}
