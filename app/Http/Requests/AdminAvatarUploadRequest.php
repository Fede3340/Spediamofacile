<?php

namespace App\Http\Requests;

/**
 * Upload avatar admin: campo `admin_image`, max 2MB.
 */
class AdminAvatarUploadRequest extends ImageUploadRequest
{
    protected function fieldName(): string
    {
        return 'admin_image';
    }

    protected function maxKilobytes(): int
    {
        return 2048;
    }
}
