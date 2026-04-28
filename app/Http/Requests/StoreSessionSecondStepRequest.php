<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/shipment/session/step-2 (servizi + indirizzi).
 * client_submission_id usato per idempotenza (retry safe).
 */
class StoreSessionSecondStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_submission_id' => ['nullable', 'string', 'max:255'],
            'services' => ['nullable', 'array'],
            'services.service_type' => ['nullable', 'string'],
            'services.date' => ['nullable', 'string'],
            'services.time' => ['nullable', 'string'],
            'services.serviceData' => ['nullable', 'array'],
            'services.sms_email_notification' => ['nullable', 'boolean'],
            'content_description' => ['required', 'string'],
            'pickup_date' => ['required', 'string'],
            'sms_email_notification' => ['nullable', 'boolean'],

            'packages' => ['nullable', 'array', 'min:1'],
            'packages.*.package_type' => ['required_with:packages', 'string'],
            'packages.*.quantity' => ['required_with:packages', 'integer', 'min:1'],
            'packages.*.weight' => ['required_with:packages'],
            'packages.*.first_size' => ['required_with:packages'],
            'packages.*.second_size' => ['required_with:packages'],
            'packages.*.third_size' => ['required_with:packages'],

            'origin_address' => ['nullable', 'array'],
            'origin_address.type' => ['nullable', 'string'],
            'origin_address.name' => ['nullable', 'string'],
            'origin_address.additional_information' => ['nullable', 'string'],
            'origin_address.address' => ['nullable', 'string'],
            'origin_address.number_type' => ['nullable', 'string'],
            'origin_address.address_number' => ['nullable', 'string'],
            'origin_address.intercom_code' => ['nullable', 'string'],
            'origin_address.country' => ['nullable', 'string'],
            'origin_address.city' => ['nullable', 'string'],
            'origin_address.postal_code' => ['nullable', 'string'],
            'origin_address.province' => ['nullable', 'string'],
            'origin_address.telephone_number' => ['nullable', 'string'],
            'origin_address.email' => ['nullable', 'string'],

            'destination_address' => ['nullable', 'array'],
            'destination_address.type' => ['nullable', 'string'],
            'destination_address.name' => ['nullable', 'string'],
            'destination_address.additional_information' => ['nullable', 'string'],
            'destination_address.address' => ['nullable', 'string'],
            'destination_address.number_type' => ['nullable', 'string'],
            'destination_address.address_number' => ['nullable', 'string'],
            'destination_address.intercom_code' => ['nullable', 'string'],
            'destination_address.country' => ['nullable', 'string'],
            'destination_address.city' => ['nullable', 'string'],
            'destination_address.postal_code' => ['nullable', 'string'],
            'destination_address.province' => ['nullable', 'string'],
            'destination_address.telephone_number' => ['nullable', 'string'],
            'destination_address.email' => ['nullable', 'string'],

            'delivery_mode' => ['nullable', 'string', 'in:home,pudo'],
            'selected_pudo' => ['nullable', 'array'],
        ];
    }
}
