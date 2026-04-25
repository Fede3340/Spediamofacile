<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/payment/intent (Stripe PaymentIntent on-session per 3DS).
 * Usato dal frontend Stripe Elements; ritorna client_secret.
 */
class CreatePaymentIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'client_submission_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
