<?php

namespace App\Http\Requests;

/**
 * Upload immagine label promozionale: campo `image`, max 2MB.
 */
class PromoImageUploadRequest extends ImageUploadRequest
{
    protected function fieldName(): string
    {
        return 'image';
    }

    protected function maxKilobytes(): int
    {
        return 2048;
    }
}
