<?php

namespace App\Http\Middleware;

use App\Support\AuthUiCookie;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * Keep the lightweight auth UI snapshot readable by Nuxt SSR/client.
     * It contains only non-sensitive presentation data and prevents guest flashes.
     *
     * @var array<int, string>
     */
    protected $except = [
        AuthUiCookie::NAME,
    ];
}
