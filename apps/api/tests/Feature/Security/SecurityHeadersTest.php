<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_testing_environment_csp_omits_unsafe_eval(): void
    {
        $middleware = new SecurityHeaders();
        $request = Request::create('/security-check', 'GET');

        $response = $middleware->handle($request, fn () => new Response('ok'));
        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
        // CSP irrigidita: rimosso 'unsafe-inline' da script-src per security best practice.
        // Stripe e' whitelisted per integrazione checkout 3DS.
        $this->assertStringContainsString("script-src 'self' https://js.stripe.com", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
    }
}
