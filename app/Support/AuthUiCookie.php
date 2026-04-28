<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class AuthUiCookie
{
    public const NAME = 'sf_auth_ui';

    public static function issueForUser(User $user, bool $remember = false): SymfonyCookie
    {
        return Cookie::make(
            self::NAME,
            json_encode(self::payloadForUser($user), JSON_UNESCAPED_UNICODE),
            $remember ? 60 * 24 * 30 : 0,
            '/',
            null,
            false,
            false,
            false,
            'lax',
        );
    }

    public static function forget(): SymfonyCookie
    {
        return Cookie::forget(self::NAME, '/', null);
    }

    public static function payloadForUser(User $user): array
    {
        return [
            'authenticated' => true,
            'name' => (string) ($user->name ?? ''),
            'surname' => (string) ($user->surname ?? ''),
            'email' => (string) ($user->email ?? ''),
            'createdAt' => $user->created_at?->toIso8601String() ?? '',
            'userType' => (string) ($user->user_type ?? ''),
            'role' => $user->role ?: null,
        ];
    }
}
