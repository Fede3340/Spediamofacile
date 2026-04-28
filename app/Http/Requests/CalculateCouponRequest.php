<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/calculate-coupon (preview sconto coupon/referral nel checkout).
 * Endpoint pubblico (no auth, rate-limited dalla route).
 */
class CalculateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon' => ['required', 'string', 'max:50'],
            'total' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'coupon.required' => 'Il codice coupon è obbligatorio.',
            'total.required' => 'Il totale carrello è obbligatorio.',
            'total.min' => 'Il totale non può essere negativo.',
        ];
    }
}
