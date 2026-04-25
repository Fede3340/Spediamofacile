<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/admin/brt/test-create (admin only).
 * Crea spedizione BRT in modalita' test (no ordine reale).
 */
class BrtTestCreateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'consignee_name' => ['required', 'string', 'max:255'],
            'consignee_address' => ['required', 'string', 'max:255'],
            'consignee_city' => ['required', 'string', 'max:255'],
            'consignee_zip' => ['required', 'string', 'max:10'],
            'consignee_province' => ['required', 'string', 'max:2'],
            'consignee_country' => ['required', 'string', 'max:2'],
            'consignee_email' => ['nullable', 'email'],
            'consignee_phone' => ['nullable', 'string', 'max:20'],
            'weight_kg' => ['required', 'integer', 'min:1'],
            'parcels' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
