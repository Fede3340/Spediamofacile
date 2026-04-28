<?php

namespace Tests\Unit\Services;

use App\Models\Coupon;
use App\Services\CheckoutSubmissionContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutSubmissionContextServiceTest extends TestCase
{
    use RefreshDatabase;

    private CheckoutSubmissionContextService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CheckoutSubmissionContextService();
    }

    public function test_from_request_array_estrae_client_submission_id_quando_presente(): void
    {
        $context = $this->service->fromRequestArray([
            'client_submission_id' => 'abc-123-def',
        ]);

        $this->assertSame('abc-123-def', $context['client_submission_id']);
    }

    public function test_from_request_array_ignora_valori_vuoti(): void
    {
        $context = $this->service->fromRequestArray([
            'client_submission_id' => '   ',
        ]);

        $this->assertArrayNotHasKey('client_submission_id', $context);
    }

    public function test_from_request_array_normalizza_discount_context_quando_presente(): void
    {
        $context = $this->service->fromRequestArray([
            'client_submission_id' => 'submission-discount-001',
            'discount_context' => [
                'type' => 'Referral',
                'code' => ' pro-roma ',
                'discount_percent' => '5',
                'discount_amount' => '2,50',
                'subtotal_raw' => '50,00',
                'final_total_raw' => '47,50',
                'pro_name' => ' Partner Roma ',
            ],
        ]);

        $this->assertSame([
            'code' => 'PRO-ROMA',
            'discount_amount' => 2.5,
            'discount_percent' => 5.0,
            'final_total_raw' => 47.5,
            'pro_name' => 'Partner Roma',
            'subtotal_raw' => 50.0,
            'type' => 'referral',
        ], $context['discount_context']);
    }

    public function test_enrich_genera_signature_deterministica_per_snapshot_identici(): void
    {
        $snapshotA = ['total_cents' => 1000, 'package_count' => 1];
        $snapshotB = ['package_count' => 1, 'total_cents' => 1000]; // ordine chiavi diverso

        $enrichedA = $this->service->enrich([], $snapshotA);
        $enrichedB = $this->service->enrich([], $snapshotB);

        // La signature deve essere identica: lo snapshot viene ordinato prima del fingerprint
        $this->assertSame($enrichedA['pricing_signature'], $enrichedB['pricing_signature']);
    }

    public function test_enrich_genera_signature_diversa_per_snapshot_diversi(): void
    {
        $snapshot1 = ['total_cents' => 1000];
        $snapshot2 = ['total_cents' => 2000];

        $enriched1 = $this->service->enrich([], $snapshot1);
        $enriched2 = $this->service->enrich([], $snapshot2);

        $this->assertNotSame($enriched1['pricing_signature'], $enriched2['pricing_signature']);
    }

    public function test_enrich_genera_client_submission_id_se_mancante(): void
    {
        $enriched = $this->service->enrich([], ['total_cents' => 500]);

        $this->assertNotEmpty($enriched['client_submission_id']);
        $this->assertStringStartsWith('submission-', $enriched['client_submission_id']);
    }

    public function test_enrich_preserva_client_submission_id_quando_fornito(): void
    {
        $enriched = $this->service->enrich(
            ['client_submission_id' => 'custom-id-42'],
            ['total_cents' => 500]
        );

        $this->assertSame('custom-id-42', $enriched['client_submission_id']);
    }

    public function test_enrich_include_versione_snapshot(): void
    {
        $enriched = $this->service->enrich([], ['total_cents' => 100]);

        $this->assertSame(1, $enriched['pricing_snapshot_version']);
        $this->assertArrayHasKey('pricing_snapshot', $enriched);
    }

    public function test_enrich_persist_discount_context_in_snapshot_without_changing_signature(): void
    {
        Coupon::factory()->create([
            'code' => 'SAVE5',
            'percentage' => 5,
            'active' => true,
        ]);

        $baseSnapshot = ['package_count' => 1, 'total_cents' => 1000];

        $withoutDiscount = $this->service->enrich(
            ['client_submission_id' => 'submission-discount-signature'],
            $baseSnapshot
        );
        $withDiscount = $this->service->enrich(
            [
                'client_submission_id' => 'submission-discount-signature',
                'discount_context' => [
                    'type' => 'coupon',
                    'code' => 'SAVE5',
                    'discount_percent' => 5,
                    'discount_amount' => 0.5,
                    'subtotal_raw' => 10,
                    'final_total_raw' => 9.5,
                ],
            ],
            $baseSnapshot
        );

        $this->assertSame($withoutDiscount['pricing_signature'], $withDiscount['pricing_signature']);
        $this->assertSame('SAVE5', data_get($withDiscount, 'pricing_snapshot.discount_context.code'));
        $this->assertSame('coupon', data_get($withDiscount, 'pricing_snapshot.discount_context.type'));
    }
}
