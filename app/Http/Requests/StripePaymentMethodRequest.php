<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per endpoint che accettano un payment_method o payment_method_id Stripe.
 * Usato da setDefault, changeDefault e deleteCard di StripeCustomerController.
 *
 * Accetta sia "payment_method" sia "payment_method_id" per retrocompatibilita'.
 */
class StripePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required_without:payment_method_id', 'string'],
            'payment_method_id' => ['required_without:payment_method', 'string'],
        ];
    }
}
