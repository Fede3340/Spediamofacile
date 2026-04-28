<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/shipment/session (step 1 funnel preventivo).
 * Origine + destinazione + lista pacchi (almeno 1).
 *
 * Regole condizionali (CAP obbligatorio per IT, monocollo per EU) sono nel controller.
 */
class StoreSessionFirstStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipment_details.origin_city' => ['required', 'string'],
            'shipment_details.origin_postal_code' => ['nullable', 'string'],
            'shipment_details.origin_country_code' => ['nullable', 'string', 'size:2'],
            'shipment_details.origin_country' => ['nullable', 'string'],
            'shipment_details.destination_city' => ['required', 'string'],
            'shipment_details.destination_postal_code' => ['nullable', 'string'],
            'shipment_details.destination_country_code' => ['nullable', 'string', 'size:2'],
            'shipment_details.destination_country' => ['nullable', 'string'],
            'shipment_details.date' => ['nullable', 'string'],
            'packages' => ['required', 'array', 'min:1'],
            'packages.*.package_type' => ['required', 'string'],
            'packages.*.quantity' => ['required', 'integer', 'min:1'],
            'packages.*.weight' => ['required'],
            'packages.*.first_size' => ['required'],
            'packages.*.second_size' => ['required'],
            'packages.*.third_size' => ['required'],
        ];
    }
}
