<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/orders/{order}/pickup/reschedule.
 * pickup_date in formato libero — controller lo normalizza a Carbon e verifica range.
 */
class ReschedulePickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'pickup_date' => ['required', 'string'],
            'pickup_time_slot' => ['nullable', 'string', 'max:50'],
            'pickup_notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
