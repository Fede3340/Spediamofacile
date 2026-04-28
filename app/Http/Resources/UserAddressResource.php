<?php

/**
 * RESOURCE: FORMATTAZIONE INDIRIZZO UTENTE (RUBRICA)
 *
 * Trasforma i dati di un indirizzo salvato nella rubrica dell'utente
 * in formato JSON per il frontend. Include anche il campo "default"
 * che indica se e' l'indirizzo predefinito dell'utente.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\UserAddress */
class UserAddressResource extends JsonResource
{
    /**
     * Trasforma l'indirizzo in un array di dati da inviare al frontend.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,                                  // ID univoco indirizzo
            'type' => $this->type,                              // Tipo: 'origin' | 'destination' (per filtro tabs frontend)
            'name' => $this->name,                              // Nome associato all'indirizzo
            'address' => $this->address,                        // Via/piazza
            'additional_information' => $this->additional_information, // Info aggiuntive
            'number_type' => $this->number_type,                // Tipo numero civico
            'address_number' => $this->address_number,          // Numero civico
            'intercom_code' => $this->intercom_code,            // Codice citofono
            'country' => $this->country,                        // Nazione
            'city' => $this->city,                              // Citta'
            'postal_code' => $this->postal_code,                // CAP
            'province' => $this->province,                      // Provincia
            'telephone_number' => $this->telephone_number,      // Telefono
            'email' => $this->email,                            // Email
            'default' => $this->default,                        // Se e' l'indirizzo predefinito
        ];
    }
}
