<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/referral/apply (genera commission per Pro su ordine cliente).
 * code 8 caratteri, order_id intero, order_amount opzionale (default da DB).
 */
class ApplyReferralRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:8'],
            'order_id' => ['required', 'integer'],
            'order_amount' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
