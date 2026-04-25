<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per PATCH /api/admin/users/{user}/role (admin only).
 * Cambia il ruolo dell'utente; il controller gestisce side-effect (gen referral code).
 */
class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:User,Partner Pro,Admin'],
        ];
    }
}
