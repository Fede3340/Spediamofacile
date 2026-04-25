<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /webhooks/brt/tracking (callback BRT push tracking).
 * Endpoint pubblico — autenticato via shared secret in middleware.
 */
class BrtWebhookTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parcelId' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:100'],
            'timestamp' => ['required'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
