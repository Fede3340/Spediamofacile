<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione condivisa per azioni BRT che richiedono solo order_id.
 * Usato da confirmShipment, deleteShipment.
 */
class BrtOrderActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
        ];
    }
}
