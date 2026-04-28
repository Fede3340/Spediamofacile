<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/account/saved-shipments/add-to-cart.
 * Accetta lista di package_id da copiare dal salvato verso il carrello.
 */
class AddSavedShipmentToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'package_ids' => ['required', 'array', 'min:1'],
            'package_ids.*' => ['integer'],
        ];
    }
}
