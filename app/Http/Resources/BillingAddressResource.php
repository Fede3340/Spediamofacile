<?php

/**
 * RESOURCE: FORMATTAZIONE INDIRIZZO DI FATTURAZIONE
 *
 * Trasforma i dati di un indirizzo di fatturazione in formato JSON per il frontend.
 * Include: nome/ragione sociale, via, citta', provincia e CAP.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\BillingAddress */
class BillingAddressResource extends JsonResource
{
    /**
     * Trasforma l'indirizzo di fatturazione in un array di dati da inviare al frontend.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,                    // Nome o ragione sociale
            'address' => $this->address,              // Via/piazza
            'city' => $this->city,                    // Citta'
            'province_name' => $this->province_name,  // Nome provincia
            'postal_code' => $this->postal_code,      // CAP
            /* 'default' => $this->default, */
        ];
    }
}
