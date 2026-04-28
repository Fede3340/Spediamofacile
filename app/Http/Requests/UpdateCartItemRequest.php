<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per PATCH /api/cart-items/{id} (modifica indirizzi/servizi/pacco in carrello).
 * Tutti i campi opzionali (nullable/array). Il controller riprezza dopo merge.
 */
class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'origin_address' => ['nullable', 'array'],
            'origin_address.type' => ['nullable', 'string', 'max:50'],
            'origin_address.name' => ['nullable', 'string', 'max:200'],
            'origin_address.additional_information' => ['nullable', 'string', 'max:500'],
            'origin_address.address' => ['nullable', 'string', 'max:300'],
            'origin_address.number_type' => ['nullable', 'string', 'max:50'],
            'origin_address.address_number' => ['nullable', 'string', 'max:20'],
            'origin_address.intercom_code' => ['nullable', 'string', 'max:50'],
            'origin_address.country' => ['nullable', 'string', 'max:100'],
            'origin_address.city' => ['nullable', 'string', 'max:200'],
            'origin_address.postal_code' => ['nullable', 'string', 'max:10'],
            'origin_address.province' => ['nullable', 'string', 'max:10'],
            'origin_address.telephone_number' => ['nullable', 'string', 'max:20'],
            'origin_address.email' => ['nullable', 'string', 'max:200'],

            'destination_address' => ['nullable', 'array'],
            'destination_address.type' => ['nullable', 'string', 'max:50'],
            'destination_address.name' => ['nullable', 'string', 'max:200'],
            'destination_address.additional_information' => ['nullable', 'string', 'max:500'],
            'destination_address.address' => ['nullable', 'string', 'max:300'],
            'destination_address.number_type' => ['nullable', 'string', 'max:50'],
            'destination_address.address_number' => ['nullable', 'string', 'max:20'],
            'destination_address.intercom_code' => ['nullable', 'string', 'max:50'],
            'destination_address.country' => ['nullable', 'string', 'max:100'],
            'destination_address.city' => ['nullable', 'string', 'max:200'],
            'destination_address.postal_code' => ['nullable', 'string', 'max:10'],
            'destination_address.province' => ['nullable', 'string', 'max:10'],
            'destination_address.telephone_number' => ['nullable', 'string', 'max:20'],
            'destination_address.email' => ['nullable', 'string', 'max:200'],

            'services' => ['nullable', 'array'],
            'services.service_type' => ['nullable', 'string', 'max:500'],
            'services.date' => ['nullable', 'string', 'max:20'],
            'services.time' => ['nullable', 'string', 'max:20'],
            'services.serviceData' => ['nullable', 'array'],
            'services.service_data' => ['nullable', 'array'],
            'services.sms_email_notification' => ['nullable', 'boolean'],

            'packages' => ['nullable', 'array', 'max:50'],
            'packages.*.package_type' => ['nullable', 'string', 'max:50'],
            'packages.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'packages.*.weight' => ['nullable', 'numeric', 'min:0.1', 'max:9999'],
            'packages.*.first_size' => ['nullable', 'numeric', 'min:1', 'max:9999'],
            'packages.*.second_size' => ['nullable', 'numeric', 'min:1', 'max:9999'],
            'packages.*.third_size' => ['nullable', 'numeric', 'min:1', 'max:9999'],

            'content_description' => ['nullable', 'string', 'max:255'],
            'delivery_mode' => ['nullable', 'string', 'in:home,pudo'],

            'pudo' => ['nullable', 'array'],
            'pudo.pudo_id' => ['nullable', 'string', 'max:100'],
            'pudo.name' => ['nullable', 'string', 'max:300'],
            'pudo.address' => ['nullable', 'string', 'max:300'],
            'pudo.city' => ['nullable', 'string', 'max:200'],
            'pudo.zip_code' => ['nullable', 'string', 'max:10'],
        ];
    }
}
