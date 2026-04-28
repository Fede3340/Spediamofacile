<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per PUT/PATCH /api/admin/coupons/{coupon}.
 * Tutti i campi opzionali (sometimes); code unique escludendo l'id corrente.
 */
class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id ?? 'NULL';

        return [
            'code' => ['sometimes', 'string', 'max:50', 'unique:coupons,code,' . $couponId],
            'percentage' => ['sometimes', 'numeric', 'min:1', 'max:100'],
            'active' => ['sometimes', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'max_uses_per_user' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
