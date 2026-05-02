<?php

namespace App\Services\Pricing;

use App\Models\Setting;
use App\Services\EuropePriceEngineService;
use App\Services\PriceEngineService;
use App\Services\Security\ImageSanitizer;
use App\Services\ShipmentServicePricingService;
use Database\Seeders\PriceBandSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

/**
 * Service applicativo per amministrare bande prezzo, supplementi
 * e impostazioni promozionali (lato admin). Centralizza l'orchestrazione
 * fra PriceEngine, EuropePriceEngine e ShipmentServicePricing.
 */
class PriceBandService
{
    public function __construct(
        private readonly PriceEngineService $priceEngine,
        private readonly EuropePriceEngineService $europePriceEngine,
        private readonly ShipmentServicePricingService $shipmentServicePricing,
    ) {}

    public function buildIndexPayload(): array
    {
        $config = $this->priceEngine->getPricingConfig();
        $shipment = $this->shipmentServicePricing->getPricingConfig();

        return $this->composePayload(
            $config,
            $this->europePriceEngine->getPricingConfig(),
            $shipment,
        );
    }

    public function runSeed(): void
    {
        (new PriceBandSeeder)->run();
        $this->priceEngine->clearPricingSettings();
        $this->forgetPublicCache();
    }

    public function applyBulkUpdate(array $payload, bool $hasEurope): array
    {
        $config = $this->priceEngine->savePricingConfig($payload);
        $europeConfig = $hasEurope
            ? $this->europePriceEngine->savePricingConfig($payload['europe'])
            : $this->europePriceEngine->getPricingConfig();
        $shipment = $this->shipmentServicePricing->savePricingConfig([
            'service_pricing' => $payload['service_pricing'] ?? [],
            'automatic_supplements' => $payload['automatic_supplements'] ?? [],
            'operational_fees' => $payload['operational_fees'] ?? [],
        ]);
        $this->forgetPublicCache();

        return $this->composePayload($config, $europeConfig, $shipment);
    }

    public function normalizeLegacyBands(array $legacy): array
    {
        $current = $this->priceEngine->getPricingConfig();
        $map = collect($legacy)->keyBy(fn (array $i) => (string) $i['id'])->all();

        return [
            'weight' => $this->mergeLegacyMap($current['weight'] ?? [], $map),
            'volume' => $this->mergeLegacyMap($current['volume'] ?? [], $map),
            'extra_rules' => $current['extra_rules'] ?? [],
            'supplements' => $current['supplements'] ?? [],
        ];
    }

    public function loadPromoSettings(): array
    {
        return [
            'promo_active' => Setting::get('promo_active', 'false'),
            'promo_label_text' => Setting::get('promo_label_text', ''),
            'promo_label_color' => Setting::get('promo_label_color', '#E44203'),
            'promo_label_image' => Setting::get('promo_label_image'),
            'promo_show_badges' => Setting::get('promo_show_badges', 'true'),
            'promo_description' => Setting::get('promo_description', ''),
        ];
    }

    public function savePromoSettings(array $data): void
    {
        Setting::set('promo_active', $data['promo_active']);
        Setting::set('promo_label_text', $data['promo_label_text'] ?? '');
        Setting::set('promo_label_color', $data['promo_label_color'] ?? '#E44203');
        Setting::set('promo_show_badges', $data['promo_show_badges']);
        Setting::set('promo_description', $data['promo_description'] ?? '');
        $this->forgetPublicCache();
    }

    public function storePromoImage(UploadedFile $file, ImageSanitizer $sanitizer): string
    {
        $path = $sanitizer->sanitizeAndStore($file, 'promo', 'public');
        $url = '/storage/'.$path;
        Setting::set('promo_label_image', $url);
        $this->forgetPublicCache();

        return $url;
    }

    private function composePayload(array $config, array $europe, array $shipment): array
    {
        return [
            'weight' => $config['weight'] ?? [],
            'volume' => $config['volume'] ?? [],
            'extra_rules' => $config['extra_rules'] ?? [],
            'supplements' => $config['supplements'] ?? [],
            'europe' => $europe,
            'service_pricing' => $shipment['service_pricing'] ?? [],
            'automatic_supplements' => $shipment['automatic_supplements'] ?? [],
            'operational_fees' => $shipment['operational_fees'] ?? [],
            'version' => $config['version'] ?? null,
        ];
    }

    private function mergeLegacyMap(array $bands, array $map): array
    {
        return collect($bands)->map(function (array $band) use ($map) {
            $id = (string) ($band['id'] ?? '');
            if (! isset($map[$id])) {
                return $band;
            }
            $band['base_price'] = (int) $map[$id]['base_price'];
            $band['discount_price'] = $map[$id]['discount_price'] ?? null;
            if (array_key_exists('show_discount', $map[$id])) {
                $band['show_discount'] = (bool) $map[$id]['show_discount'];
            }

            return $band;
        })->values()->all();
    }

    private function forgetPublicCache(): void
    {
        try {
            Cache::forget('public_price_bands');
        } catch (\Exception $e) { /* best-effort */
        }
    }
}
