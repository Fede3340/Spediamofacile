<?php

namespace App\Http\Requests;

/**
 * Upload featured image articoli (guide/servizi): campo `image`, max 5MB.
 */
class ArticleImageUploadRequest extends ImageUploadRequest
{
    protected function fieldName(): string
    {
        return 'image';
    }

    protected function maxKilobytes(): int
    {
        return 5120;
    }
}
