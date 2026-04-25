<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/orders/pay-with-external (wallet o bonifico).
 * Idempotency: ext_id + idempotency_key per retry safe del retrieve&verify.
 */
class PayWithExternalProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'payment_type' => ['required', 'string', 'in:wallet,bonifico'],
            'ext_id' => ['nullable', 'string'],
            'is_existing_order' => ['nullable', 'boolean'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
