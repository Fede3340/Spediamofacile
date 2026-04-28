<?php

/**
 * RESOURCE: FORMATTAZIONE INDIRIZZO DEL PACCO
 *
 * Trasforma i dati di un indirizzo (partenza o destinazione di un pacco)
 * in formato JSON per il frontend. Include tutti i campi dell'indirizzo:
 * nome, via, numero, citta', CAP, provincia, nazione, telefono, email.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PackageAddress */
class PackageAddressResource extends JsonResource
{
    /**
     * Trasforma l'indirizzo in un array di dati da inviare al frontend.
     */
    public function toArray(Request $request): array
    {
         return [
            'name' => $this->name,                              // Nome mittente/destinatario
            'additional_information' => $this->additional_information, // Info aggiuntive
            'address' => $this->address,                        // Via/piazza
            'number_type' => $this->number_type,                // Tipo numero civico
            'address_number' => $this->address_number,          // Numero civico
            'intercom_code' => $this->intercom_code,            // Codice citofono
            'country' => $this->country,                        // Nazione
            'city' => $this->city,                              // Citta'
            'postal_code' => $this->postal_code,                // CAP
            'province' => $this->province,                      // Provincia
            'telephone_number' => $this->telephone_number,      // Telefono
            'email' => $this->email,                            // Email
        ];
    }
}
