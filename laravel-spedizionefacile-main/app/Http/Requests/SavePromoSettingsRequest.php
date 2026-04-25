<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/admin/settings/promo (admin only).
 * Configura badge promozionale e descrizione sconto in homepage.
 *
 * NOTA: promo_active arriva come stringa ("true"/"false") da legacy frontend.
 */
class SavePromoSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'promo_active' => ['required', 'in:true,false'],
            'promo_label_text' => ['nullable', 'string', 'max:100'],
            'promo_label_color' => ['nullable', 'string', 'max:20'],
            'promo_show_badges' => ['required', 'in:true,false'],
            'promo_description' => ['nullable', 'string', 'max:300'],
        ];
    }
}
