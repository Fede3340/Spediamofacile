<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/admin/settings/stripe-keys (admin).
 * publishable_key deve iniziare con pk_, secret_key con sk_.
 */
class UpdateStripeKeysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'publishable_key' => ['required', 'string', 'starts_with:pk_'],
            'secret_key' => ['required', 'string', 'starts_with:sk_'],
        ];
    }

    public function messages(): array
    {
        return [
            'publishable_key.starts_with' => 'La Publishable Key deve iniziare con pk_',
            'secret_key.starts_with' => 'La Secret Key deve iniziare con sk_',
        ];
    }
}
