<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per PATCH /api/admin/orders/{order}/pudo (admin only).
 * Aggiorna il PUDO associato all'ordine (es. cambio fermo deposito).
 */
class UpdateOrderPudoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'pudo_id' => ['nullable', 'string', 'max:100'],
            'pudo_name' => ['nullable', 'string', 'max:300'],
            'pudo_address' => ['nullable', 'string', 'max:300'],
            'pudo_city' => ['nullable', 'string', 'max:200'],
            'pudo_zip' => ['nullable', 'string', 'max:10'],
        ];
    }
}
