<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/referral/validate e /api/referral/apply.
 * Codice referral = 8 caratteri (alfanumerici uppercase generati lato server).
 */
class ReferralCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:8'],
        ];
    }
}
