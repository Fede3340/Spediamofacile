<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/orders/{order}/packages (aggiunta collo a ordine in attesa pagamento).
 * Accetta dimensioni in cm, peso in kg, content_description opzionale.
 */
class AddOrderPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'package_type' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'weight' => ['required', 'numeric', 'min:0.1', 'max:9999'],
            'first_size' => ['required', 'numeric', 'min:1', 'max:9999'],
            'second_size' => ['required', 'numeric', 'min:1', 'max:9999'],
            'third_size' => ['required', 'numeric', 'min:1', 'max:9999'],
            'content_description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
