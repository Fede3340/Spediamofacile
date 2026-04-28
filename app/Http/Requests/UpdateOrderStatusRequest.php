<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per PATCH /api/admin/orders/{order}/status (admin only).
 * Stati canonici allineati con App\Models\Order constants.
 */
class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:pending,processing,completed,payment_failed,cancelled,payed,in_transit,delivered,in_giacenza,awaiting_bank_transfer'],
        ];
    }
}
