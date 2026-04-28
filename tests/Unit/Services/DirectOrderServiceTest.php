<?php

namespace Tests\Unit\Services;

use App\Services\CheckoutSubmissionContextService;
use App\Services\DirectOrderService;
use App\Services\PriceEngineService;
use App\Services\ShipmentServicePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private DirectOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DirectOrderService(
            new PriceEngineService(),
            app(ShipmentServicePricingService::class),
            new CheckoutSubmissionContextService(),
        );
    }

    public function test_price_packages_calcola_single_price_cents_correttamente(): void
    {
        $result = $this->service->pricePackages([
            [
                'package_type' => 'Pacco',
                'quantity' => 2,
                'weight' => '5',
                'first_size' => '30',
                'second_size' => '20',
                'third_size' => '10',
            ],
        ], '00100', '20100');

        $this->assertCount(1, $result['priced_packages']);
        $this->assertGreaterThan(0, $result['subtotal_cents']);
        // subtotal deve essere = single_price (che include quantity)
        $this->assertSame($result['priced_packages'][0]['single_price'], $result['subtotal_cents']);
    }

    public function test_price_packages_applica_cap_supplement(): void
    {
        // CAP origin "90100" ha supplemento default 250 cent
        $withSupplement = $this->service->pricePackages([[
            'package_type' => 'Pacco', 'quantity' => 1,
            'weight' => '1', 'first_size' => '10', 'second_size' => '10', 'third_size' => '10',
        ]], '90100', '00100');

        $withoutSupplement = $this->service->pricePackages([[
            'package_type' => 'Pacco', 'quantity' => 1,
            'weight' => '1', 'first_size' => '10', 'second_size' => '10', 'third_size' => '10',
        ]], '00100', '00100');

        $this->assertSame(250, $withSupplement['cap_supplement_cents']);
        $this->assertSame(0, $withoutSupplement['cap_supplement_cents']);
        // Il prezzo base con supplemento deve essere maggiore
        $this->assertGreaterThan(
            $withoutSupplement['priced_packages'][0]['single_price'],
            $withSupplement['priced_packages'][0]['single_price'],
        );
    }

    public function test_price_packages_moltiplica_per_quantity(): void
    {
        $single = $this->service->pricePackages([[
            'package_type' => 'Pacco', 'quantity' => 1,
            'weight' => '1', 'first_size' => '10', 'second_size' => '10', 'third_size' => '10',
        ]], '00100', '00100');

        $triple = $this->service->pricePackages([[
            'package_type' => 'Pacco', 'quantity' => 3,
            'weight' => '1', 'first_size' => '10', 'second_size' => '10', 'third_size' => '10',
        ]], '00100', '00100');

        $this->assertSame(3 * $single['priced_packages'][0]['single_price'], $triple['priced_packages'][0]['single_price']);
    }

    public function test_price_single_package_ritorna_struttura_attesa(): void
    {
        $result = $this->service->priceSinglePackage(5.0, 30, 20, 10, 2, '00100', '00100');

        $this->assertArrayHasKey('weight_price', $result);
        $this->assertArrayHasKey('volume_price', $result);
        $this->assertArrayHasKey('single_price_cents', $result);
        $this->assertIsFloat($result['weight_price']);
        $this->assertIsInt($result['single_price_cents']);
        $this->assertGreaterThan(0, $result['single_price_cents']);
    }
}
