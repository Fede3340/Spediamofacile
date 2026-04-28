<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/orders/paid (callback frontend post-pagamento OK).
 * ext_id = Stripe PaymentIntent id; usato per retrieve&verify lato server.
 */
class OrderPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'ext_id' => ['required', 'string'],
            'is_existing_order' => ['nullable', 'boolean'],
            'client_submission_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
