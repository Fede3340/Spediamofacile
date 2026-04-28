<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per PUT /api/account/saved-shipments/{id} (modifica indirizzi/servizi/pacco salvato).
 * Tutti i campi opzionali (sometimes/nullable) per consentire patch parziali.
 */
class UpdateSavedShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'origin_address' => 'sometimes|array',
            'origin_address.name' => 'nullable|string',
            'origin_address.address' => 'nullable|string',
            'origin_address.address_number' => 'nullable|string',
            'origin_address.city' => 'nullable|string',
            'origin_address.postal_code' => 'nullable|string',
            'origin_address.province' => 'nullable|string',
            'origin_address.telephone_number' => 'nullable|string',
            'origin_address.email' => 'nullable|string',
            'origin_address.country' => 'nullable|string',
            'origin_address.additional_information' => 'nullable|string',
            'origin_address.intercom_code' => 'nullable|string',

            'destination_address' => 'sometimes|array',
            'destination_address.name' => 'nullable|string',
            'destination_address.address' => 'nullable|string',
            'destination_address.address_number' => 'nullable|string',
            'destination_address.city' => 'nullable|string',
            'destination_address.postal_code' => 'nullable|string',
            'destination_address.province' => 'nullable|string',
            'destination_address.telephone_number' => 'nullable|string',
            'destination_address.email' => 'nullable|string',
            'destination_address.country' => 'nullable|string',
            'destination_address.additional_information' => 'nullable|string',
            'destination_address.intercom_code' => 'nullable|string',

            'package_type' => 'nullable|string',
            'quantity' => 'nullable|integer',
            'weight' => 'nullable|string',
            'first_size' => 'nullable|string',
            'second_size' => 'nullable|string',
            'third_size' => 'nullable|string',

            'services' => 'sometimes|array',
            'services.service_type' => 'nullable|string',
            'services.date' => 'nullable|string',
            'services.time' => 'nullable|string',
        ];
    }
}
