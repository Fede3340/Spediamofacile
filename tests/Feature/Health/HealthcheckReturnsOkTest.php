<?php

namespace Tests\Feature\Health;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test endpoint health check (Sprint 7.2 + P0.2 deep checks).
 *
 * Verifica:
 *  - GET /api/health → 200 OK con struttura check completa (db, cache, stripe, brt)
 *  - GET /api/health/live → 200 OK liveness probe minimo
 *
 * Rationale: Render polla /api/health/live ogni 30s e riavvia container dopo
 * 5 fail consecutivi. Test garantisce che la rotta resti disponibile e con
 * la struttura JSON attesa dai monitor esterni (UptimeRobot, Render dashboard).
 */
class HealthcheckReturnsOkTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_status_with_service_checks(): void
    {
        $response = $this->getJson('/api/health');

        // 200 in test (DB ok, cache ok in array driver, stripe/brt potrebbero
        // essere "degraded" ma non rompono il check critico → status code 200).
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database' => ['status'],
                'cache' => ['status'],
                'stripe' => ['status'],
                'brt' => ['status'],
            ],
        ]);
    }

    public function test_health_live_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health/live');

        $response->assertOk()
            ->assertJsonStructure(['status', 'timestamp'])
            ->assertJson(['status' => 'ok']);
    }
}
