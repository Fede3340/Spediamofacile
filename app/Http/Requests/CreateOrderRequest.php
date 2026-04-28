<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/orders/create (crea ordine da carrello).
 * client_submission_id usato per idempotenza (retry safe pre-pagamento).
 */
class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'subtotal' => ['nullable', 'numeric'],
            'package_ids' => ['nullable', 'array'],
            'package_ids.*' => ['integer'],
            'billing_data' => ['nullable', 'array'],
            'client_submission_id' => ['nullable', 'string', 'max:255'],
            'single_order_only' => ['nullable', 'boolean'],
        ];
    }
}
