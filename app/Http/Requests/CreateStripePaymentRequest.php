<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/payment/create (Stripe PaymentIntent off-session).
 * payment_method_id richiede precedente listPaymentMethods (carta salvata).
 */
class CreateStripePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'currency' => ['required', 'string'],
            'payment_method_id' => ['required', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'client_submission_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
