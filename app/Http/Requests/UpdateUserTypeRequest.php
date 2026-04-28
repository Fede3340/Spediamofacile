<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per PATCH /api/admin/users/{user}/user-type (admin only).
 * privato = persona fisica; commerciante = ditta/PIVA.
 */
class UpdateUserTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'user_type' => ['required', 'in:privato,commerciante'],
        ];
    }
}
