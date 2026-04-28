<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/admin/orders/{order}/confirm-bank-transfer.
 * bank_transfer_reference opzionale (CRO o riferimento del bonifico).
 */
class ConfirmBankTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'bank_transfer_reference' => ['nullable', 'string', 'max:128'],
        ];
    }
}
