<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/brt/pudo/search.
 * Almeno uno tra zip_code e city richiesto (required_without).
 */
class BrtPudoSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address' => ['nullable', 'string'],
            'zip_code' => ['nullable', 'string', 'required_without:city'],
            'city' => ['nullable', 'string', 'required_without:zip_code'],
            'country' => ['nullable', 'string'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
