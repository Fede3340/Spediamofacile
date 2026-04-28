<?php

namespace App\Http\Requests;

/**
 * Upload immagine hero homepage: file opzionale (puo' arrivare solo la config),
 * campo `image`, max 5MB.
 */
class HomepageImageUploadRequest extends ImageUploadRequest
{
    protected function fieldName(): string
    {
        return 'image';
    }

    protected function fileIsRequired(): bool
    {
        return false; // l'endpoint accetta anche solo update config
    }

    public function rules(): array
    {
        // Ereditiamo validazione immagine, aggiungiamo i campi non-file della
        // stessa request (config/desktop/mobile) che il controller legge dopo.
        return array_merge(parent::rules(), [
            'config' => 'nullable',
            'desktop' => 'nullable|array',
            'mobile' => 'nullable|array',
        ]);
    }
}
