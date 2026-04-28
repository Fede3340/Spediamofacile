<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/brt/pudo/nearby (ricerca PUDO per coordinate).
 */
class BrtPudoNearbyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
