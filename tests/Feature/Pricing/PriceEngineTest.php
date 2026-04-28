<?php

namespace Tests\Feature\Pricing;

use App\Services\PriceEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-level tests for PriceEngineService.
 * Uses the service directly (not via HTTP).
 *
 * The service falls back to DEFAULT_WEIGHT_BANDS and DEFAULT_VOLUME_BANDS
 * when no Settings/PriceBand rows exist in DB, which is always the case
 * in these tests thanks to RefreshDatabase.
 *
 * Default weight bands (cents):
 *   0-2 kg   => 890   (8.90 EUR)
 *   2-5 kg   => 1190  (11.90 EUR)
 *   5-10 kg  => 1490  (14.90 EUR)
 *   10-25 kg => 1990  (19.90 EUR)
 *   25-50 kg => 2990  (29.90 EUR)
 *   50-75 kg => 3990  (39.90 EUR)
 *   75-100kg => 4990  (49.90 EUR)
 *
 * Default volume bands (cents):
 *   0-0.010 m3  => 890
 *   0.010-0.020 => 1190
 *   0.020-0.040 => 1490
 *   0.040-0.100 => 1990
 *   0.100-0.200 => 2990
 *   0.200-0.300 => 3990
 *   0.300-0.400 => 4990
 *
 * Extra rules (defaults):
 *   weight_start=101, weight_step=50, increment_cents=500
 *   base from last band effective price = 4990
 */
class PriceEngineTest extends TestCase
{
    use RefreshDatabase;

    private PriceEngineService $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(PriceEngineService::class);
    }

    /* ================================================================== */
    /*  T11.6.1: Weight band matching (5 kg => correct band)               */
    /* ================================================================== */
    public function test_weight_band_5kg(): void
    {
        // 5 kg falls in band 2-5 (index 1), boundary is inclusive for max
        // findMatchingBand: first band uses >=, others use > for min
        // 5 kg: > 2 (yes) and <= 5 (yes) => band 2-5 => 1190 cents = 11.90 EUR
        $price = $this->engine->calculateBandPrice('weight', 5);
        $this->assertEquals(11.90, $price);
    }

    public function test_weight_band_1kg(): void
    {
        // 1 kg falls in band 0-2 => 890 cents = 8.90 EUR
        $price = $this->engine->calculateBandPrice('weight', 1);
        $this->assertEquals(8.90, $price);
    }

    public function test_weight_band_10kg(): void
    {
        // 10 kg: > 5 (yes) and <= 10 (yes) => band 5-10 => 1490 cents = 14.90 EUR
        $price = $this->engine->calculateBandPrice('weight', 10);
        $this->assertEquals(14.90, $price);
    }

    public function test_weight_band_25kg(): void
    {
        // 25 kg: > 10 (yes) and <= 25 (yes) => band 10-25 => 1990 cents = 19.90 EUR
        $price = $this->engine->calculateBandPrice('weight', 25);
        $this->assertEquals(19.90, $price);
    }

    /* ================================================================== */
    /*  T11.6.2: Volume band matching                                      */
    /* ================================================================== */
    public function test_volume_band_small(): void
    {
        // 0.005 m3 falls in band 0-0.010 => 890 cents = 8.90 EUR
        $price = $this->engine->calculateBandPrice('volume', 0.005);
        $this->assertEquals(8.90, $price);
    }

    public function test_volume_band_medium(): void
    {
        // 0.050 m3 falls in band 0.040-0.100 => 1990 cents = 19.90 EUR
        $price = $this->engine->calculateBandPrice('volume', 0.050);
        $this->assertEquals(19.90, $price);
    }

    /* ================================================================== */
    /*  T11.6.3: MAX(weight, volume) formula                               */
    /*  The controller uses max(weightPrice, volumePrice)                  */
    /* ================================================================== */
    public function test_max_weight_volume_formula(): void
    {
        // Simulate what createDirectOrder does:
        // weight=5 kg => weightPrice = 11.90 EUR
        // dimensions: 30x20x15 cm => volume = 0.30*0.20*0.15 = 0.009 m3
        // volume 0.009 => band 0-0.010 => 8.90 EUR
        // max(11.90, 8.90) = 11.90 EUR (weight wins)

        $weightPrice = $this->engine->calculateBandPrice('weight', 5);
        $vol = (30 / 100) * (20 / 100) * (15 / 100); // 0.009 m3
        $volumePrice = $this->engine->calculateBandPrice('volume', $vol);

        $finalPriceCents = max(
            (int) round($weightPrice * 100),
            (int) round($volumePrice * 100)
        );

        $this->assertEquals(1190, $finalPriceCents);
    }

    public function test_max_formula_volume_wins(): void
    {
        // weight=1 kg => 890 cents (8.90 EUR)
        // dimensions: 100x100x100 cm => 1.0 m3 (beyond all bands => extra rules)
        // But let's use 80x60x50 cm => 0.24 m3 => band 0.200-0.300 => 3990 cents
        $weightPrice = $this->engine->calculateBandPrice('weight', 1);
        $vol = (80 / 100) * (60 / 100) * (50 / 100); // 0.24 m3
        $volumePrice = $this->engine->calculateBandPrice('volume', $vol);

        $finalPriceCents = max(
            (int) round($weightPrice * 100),
            (int) round($volumePrice * 100)
        );

        // Volume (3990) > Weight (890), so volume wins
        $this->assertEquals(3990, $finalPriceCents);
    }

    /* ================================================================== */
    /*  T11.6.4: Extra rule for > 100 kg                                   */
    /* ================================================================== */
    public function test_extra_rule_above_100kg(): void
    {
        // Above 100 kg the extra rules apply:
        // weight_start=101, weight_step=50, increment_cents=500
        // base_price = last band effective = 4990
        //
        // 110 kg: ceil to resolution 1 = 110
        // stepsFromStart = floor((110-101)/50) = floor(0.18) = 0 => bandNumber = 1
        // extraPrice = 4990 + (1 * 500) = 5490 cents = 54.90 EUR
        $price = $this->engine->calculateBandPrice('weight', 110);
        $this->assertEquals(54.90, $price);
    }

    public function test_extra_rule_150kg(): void
    {
        // 150 kg: stepsFromStart = floor((150-101)/50) = floor(0.98) = 0 => bandNumber = 1
        // extraPrice = 4990 + (1 * 500) = 5490 cents = 54.90 EUR
        $price = $this->engine->calculateBandPrice('weight', 150);
        $this->assertEquals(54.90, $price);
    }

    public function test_extra_rule_200kg(): void
    {
        // 200 kg: stepsFromStart = floor((200-101)/50) = floor(1.98) = 1 => bandNumber = 2
        // extraPrice = 4990 + (2 * 500) = 5990 cents = 59.90 EUR
        $price = $this->engine->calculateBandPrice('weight', 200);
        $this->assertEquals(59.90, $price);
    }

    /* ================================================================== */
    /*  T11.6.5: CAP supplement calculation                                */
    /* ================================================================== */
    public function test_cap_supplement_origin_90_prefix(): void
    {
        // Default supplement: prefix='90', amount_cents=250, apply_to='both'
        // Origin starts with '90' => +250 cents
        $cents = $this->engine->calculateCapSupplementCents('90100', '20121');
        $this->assertEquals(250, $cents);
    }

    public function test_cap_supplement_destination_90_prefix(): void
    {
        // Destination starts with '90' => +250 cents
        $cents = $this->engine->calculateCapSupplementCents('20121', '90100');
        $this->assertEquals(250, $cents);
    }

    public function test_cap_supplement_both_90_prefix(): void
    {
        // Both start with '90' => +250 + 250 = 500 cents
        $cents = $this->engine->calculateCapSupplementCents('90100', '90200');
        $this->assertEquals(500, $cents);
    }

    public function test_cap_supplement_neither_90_prefix(): void
    {
        // Neither starts with '90' => 0 cents
        $cents = $this->engine->calculateCapSupplementCents('20121', '00185');
        $this->assertEquals(0, $cents);
    }

    public function test_cap_supplement_null_caps(): void
    {
        $cents = $this->engine->calculateCapSupplementCents(null, null);
        $this->assertEquals(0, $cents);
    }

    public function test_cap_supplement_as_euros(): void
    {
        // Helper method returning EUR
        $eur = $this->engine->calculateCapSupplement('90100', '20121');
        $this->assertEquals(2.50, $eur);
    }
}
