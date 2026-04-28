<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/wallet/top-up (ricarica portafoglio via Stripe).
 * Importo in euro (es. 50.00); idempotency_key opzionale per retry safe.
 */
class WalletTopUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method_id' => ['required', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
