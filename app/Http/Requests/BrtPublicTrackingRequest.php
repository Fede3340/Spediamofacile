<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per GET /api/brt/tracking?code= (pubblico, no auth).
 * code = numero spedizione BRT o reference cliente (max 100 char).
 */
class BrtPublicTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100'],
        ];
    }
}
