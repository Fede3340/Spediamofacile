<?php

namespace Tests\Unit\Services;

use App\Services\PriceEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    private PriceEngineService $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PriceEngineService();
    }

    public function test_calcola_prezzo_band_corretto_per_peso_nella_prima_fascia(): void
    {
        // peso 1 kg -> prima fascia 0-2 kg -> 890 cent
        $this->assertSame(890, $this->engine->calculateBandPriceCents('weight', 1.0));
    }

    public function test_calcola_prezzo_band_ascende_con_peso_crescente(): void
    {
        $light = $this->engine->calculateBandPriceCents('weight', 2.0);   // 890
        $medium = $this->engine->calculateBandPriceCents('weight', 10.0); // 1490
        $heavy = $this->engine->calculateBandPriceCents('weight', 50.0);  // 2990

        $this->assertLessThan($medium, $light);
        $this->assertLessThan($heavy, $medium);
    }

    public function test_calcola_prezzo_band_per_volume(): void
    {
        // 0.005 m3 -> prima fascia 0-0.010 -> 890 cent
        $this->assertSame(890, $this->engine->calculateBandPriceCents('volume', 0.005));
    }

    public function test_peso_oltre_ultima_fascia_usa_extra_rules(): void
    {
        // 150 kg supera l'ultima fascia (75-100) -> calcolo via extra_rules
        $result = $this->engine->calculateBandPriceCents('weight', 150.0);

        // Deve essere almeno il prezzo dell'ultima fascia (4990) + 1 step (500)
        $this->assertGreaterThan(4990, $result);
    }

    public function test_edge_case_peso_zero_lancia_eccezione(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine->calculateBandPriceCents('weight', 0.0);
    }

    public function test_edge_case_peso_negativo_lancia_eccezione(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine->calculateBandPriceCents('weight', -5.0);
    }

    public function test_peso_oltre_limite_massimo_lancia_eccezione(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine->calculateBandPriceCents('weight', 2000.0); // max 1000 kg
    }

    public function test_cap_supplement_applica_sovrapprezzo_per_prefisso_configurato(): void
    {
        // Default supplement: prefix "90" -> 250 cent su origin e destination
        $supplement = $this->engine->calculateCapSupplementCents('90100', '00100');
        // Origin inizia con "90" -> +250 cent
        $this->assertSame(250, $supplement);
    }

    public function test_cap_supplement_zero_senza_prefissi_match(): void
    {
        $supplement = $this->engine->calculateCapSupplementCents('00100', '20100');
        $this->assertSame(0, $supplement);
    }

    public function test_effective_price_cents_preferisce_sconto_solo_se_positivo(): void
    {
        $band = ['base_price' => 1000, 'discount_price' => 800];
        $this->assertSame(800, PriceEngineService::effectivePriceCents($band));

        // discount_price = 0 deve fallback a base_price (evita spedizioni gratis)
        $bandZeroDiscount = ['base_price' => 1000, 'discount_price' => 0];
        $this->assertSame(1000, PriceEngineService::effectivePriceCents($bandZeroDiscount));

        // discount_price null -> base_price
        $bandNullDiscount = ['base_price' => 1000, 'discount_price' => null];
        $this->assertSame(1000, PriceEngineService::effectivePriceCents($bandNullDiscount));
    }
}
