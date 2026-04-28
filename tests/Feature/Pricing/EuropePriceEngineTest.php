<?php

namespace Tests\Feature\Pricing;

use App\Services\EuropePriceEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EuropePriceEngineTest extends TestCase
{
    use RefreshDatabase;

    private EuropePriceEngineService $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(EuropePriceEngineService::class);
    }

    public function test_supported_country_is_recognized(): void
    {
        $this->assertTrue($this->engine->isEuropeDestination('AT'));
        $this->assertFalse($this->engine->isEuropeDestination('IT'));
        $this->assertFalse($this->engine->isEuropeDestination(''));
    }

    public function test_austria_up_to_10kg_is_priced_at_30_eur(): void
    {
        $quote = $this->engine->calculateQuote('AT', 8, 0.03);

        $this->assertSame('priced', $quote['status']);
        $this->assertSame(3000, $quote['price_cents']);
        $this->assertSame('0-10 kg', $quote['band']['label']);
    }

    public function test_greece_up_to_30kg_is_priced_at_95_eur(): void
    {
        $quote = $this->engine->calculateQuote('GR', 28, 0.11);

        $this->assertSame('priced', $quote['status']);
        $this->assertSame(9500, $quote['price_cents']);
        $this->assertSame('10-30 kg', $quote['band']['label']);
    }

    public function test_danimarca_over_30kg_requires_manual_quote(): void
    {
        $quote = $this->engine->calculateQuote('DK', 45, 0.16);

        $this->assertSame('requires_quote', $quote['status']);
        $this->assertStringContainsString('preventivo manuale', $quote['message']);
        $this->assertSame('25-50 kg', $quote['band']['label']);
    }

    public function test_pricing_config_can_be_persisted(): void
    {
        $config = $this->engine->getPricingConfig();
        $config['bands'][0]['rates'][0]['price_cents'] = 3100;

        $saved = $this->engine->savePricingConfig($config);

        $this->assertSame(3100, $saved['bands'][0]['rates'][0]['price_cents']);
        $this->assertSame(3100, $this->engine->getPricingConfig()['bands'][0]['rates'][0]['price_cents']);
    }
}
