<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/guest-cart (carrello session-based per utenti non loggati).
 * Endpoint pubblico — pacchi vengono persistiti in session (no DB).
 */
class StoreGuestCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'packages' => ['required', 'array', 'min:1'],
            'packages.*.package_type' => ['required', 'string'],
            'packages.*.weight' => ['required'],
            'packages.*.first_size' => ['required'],
            'packages.*.second_size' => ['required'],
            'packages.*.third_size' => ['required'],
            'origin_address' => ['required', 'array'],
            'destination_address' => ['required', 'array'],
        ];
    }
}
