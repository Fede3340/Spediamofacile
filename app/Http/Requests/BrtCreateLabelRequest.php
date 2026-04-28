<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/brt/create-label (genera etichetta BRT per un ordine).
 * order_id obbligatorio; gli altri campi sono override controllati (admin).
 */
class BrtCreateLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'is_cod' => ['nullable', 'boolean'],
            'cod_amount' => ['nullable', 'integer', 'min:0'],
            'pudo_id' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
