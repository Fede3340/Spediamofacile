<?php
namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;

use App\Models\Setting;
use App\Services\EuropePriceEngineService;
use App\Services\PriceEngineService;
use App\Services\ShipmentServicePricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicPriceBandController extends Controller
{
    public function __construct(
        private readonly PriceEngineService $priceEngine,
        private readonly EuropePriceEngineService $europePriceEngine,
        private readonly ShipmentServicePricingService $shipmentServicePricing,
    )
    {
    }

    // Ritorna tutte le fasce di prezzo + promo, con cache di 60 minuti
    public function index(): JsonResponse
    {
        try {
            $result = Cache::remember('public_price_bands', 3600, function () {
                $config = $this->priceEngine->getPricingConfig();

                return [
                    'data' => [
                        'weight' => $config['weight'] ?? [],
                        'volume' => $config['volume'] ?? [],
                        'extra_rules' => $config['extra_rules'] ?? [],
                        'supplements' => $config['supplements'] ?? [],
                        'europe' => $this->europePriceEngine->getPricingConfig(),
                        'service_pricing' => $this->shipmentServicePricing->getPricingConfig()['service_pricing'] ?? [],
                        'automatic_supplements' => $this->shipmentServicePricing->getPricingConfig()['automatic_supplements'] ?? [],
                        'operational_fees' => $this->shipmentServicePricing->getPricingConfig()['operational_fees'] ?? [],
                        'version' => $config['version'] ?? null,
                    ],
                    'promo' => $this->getPromoSettings(),
                ];
            });
        } catch (\Exception $e) {
            // Cache non disponibile: query diretta al DB
            try {
                $config = $this->priceEngine->getPricingConfig();
                $result = [
                    'data' => [
                        'weight' => $config['weight'] ?? [],
                        'volume' => $config['volume'] ?? [],
                        'extra_rules' => $config['extra_rules'] ?? [],
                        'supplements' => $config['supplements'] ?? [],
                        'europe' => $this->europePriceEngine->getPricingConfig(),
                        'service_pricing' => $this->shipmentServicePricing->getPricingConfig()['service_pricing'] ?? [],
                        'automatic_supplements' => $this->shipmentServicePricing->getPricingConfig()['automatic_supplements'] ?? [],
                        'operational_fees' => $this->shipmentServicePricing->getPricingConfig()['operational_fees'] ?? [],
                        'version' => $config['version'] ?? null,
                    ],
                    'promo' => $this->getPromoSettings(),
                ];
            } catch (\Exception $e2) {
                $result = [
                    'data' => [
                        'weight' => [],
                        'volume' => [],
                        'extra_rules' => [],
                        'supplements' => [],
                        'europe' => $this->europePriceEngine->getPricingConfig(),
                        'service_pricing' => $this->shipmentServicePricing->getPricingConfig()['service_pricing'] ?? [],
                        'automatic_supplements' => $this->shipmentServicePricing->getPricingConfig()['automatic_supplements'] ?? [],
                        'operational_fees' => $this->shipmentServicePricing->getPricingConfig()['operational_fees'] ?? [],
                        'version' => null,
                    ],
                    'promo' => ['active' => false, 'label_text' => '', 'label_color' => '#E44203', 'label_image' => null, 'show_badges' => false, 'description' => ''],
                ];
            }
        }

        return response()->json($result);
    }

    /**
     * Recupera le impostazioni promo dal DB.
     */
    private function getPromoSettings(): array
    {
        return [
            'active' => Setting::get('promo_active', 'false') === 'true',
            'label_text' => Setting::get('promo_label_text', ''),
            'label_color' => Setting::get('promo_label_color', '#E44203'),
            'label_image' => Setting::get('promo_label_image'),
            'show_badges' => Setting::get('promo_show_badges', 'true') === 'true',
            'description' => Setting::get('promo_description', ''),
        ];
    }
}
