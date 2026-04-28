<?php

namespace App\Services;

use App\Models\PriceBand;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PriceEngineService
{
    private const SETTINGS_KEY_WEIGHT_BANDS = 'pricing_national_bands_weight';
    private const SETTINGS_KEY_VOLUME_BANDS = 'pricing_national_bands_volume';
    private const SETTINGS_KEY_EXTRA_RULES = 'pricing_national_extra_rules';
    private const SETTINGS_KEY_SUPPLEMENTS = 'pricing_national_supplements';
    private const SETTINGS_KEY_VERSION = 'pricing_national_version';
    private const EPSILON = 0.0000001;

    public const DEFAULT_WEIGHT_BANDS = [
        ['min_value' => 0, 'max_value' => 2, 'base_price' => 890, 'discount_price' => null, 'show_discount' => true],
        ['min_value' => 2, 'max_value' => 5, 'base_price' => 1190, 'discount_price' => null, 'show_discount' => true],
        ['min_value' => 5, 'max_value' => 10, 'base_price' => 1490, 'discount_price' => null, 'show_discount' => true],
        ['min_value' => 10, 'max_value' => 25, 'base_price' => 1990, 'discount_price' => null, 'show_discount' => true],
        ['min_value' => 25, 'max_value' => 50, 'base_price' => 2990, 'discount_price' => null, 'show_discount' => true],
        ['min_value' => 50, 'max_value' => 75, 'base_price' => 3990, 'discount_price' => null, 'show_discount' => true],
        ['min_value' => 75, 'max_value' => 100, 'base_price' => 4990, 'discount_price' => null, 'show_discount' => true],
    ];

    public const DEFAULT_VOLUME_BANDS = [
        ['min_value' => 0, 'max_value' => 0.010, 'base_price' => 890, 'discount_price' => null, 'show_discount' => true],
        ['min_value' => 0.010, 'max_value' => 0.020, 'base_price' => 1190, 'discount_price' => null, 'show_discount' => true],
        ['min_value' => 0.020, 'max_value' => 0.040, 'base_price' => 1490, 'discount_price' => null, 'show_discount' => true],
        ['min_value' => 0.040, 'max_value' => 0.100, 'base_price' => 1990, 'discount_price' => null, 'show_discount' => true],
        ['min_value' => 0.100, 'max_value' => 0.200, 'base_price' => 2990, 'discount_price' => null, 'show_discount' => true],
        ['min_value' => 0.200, 'max_value' => 0.300, 'base_price' => 3990, 'discount_price' => null, 'show_discount' => true],
        ['min_value' => 0.300, 'max_value' => 0.400, 'base_price' => 4990, 'discount_price' => null, 'show_discount' => true],
    ];

    public const DEFAULT_EXTRA_RULES = [
        'enabled' => true,
        'weight_start' => 101, 'weight_step' => 50,
        'volume_start' => 0.401, 'volume_step' => 0.200,
        'increment_cents' => 500, 'increment_mode' => 'flat',
        'weight_increment_ladder' => [['from_step' => 1, 'to_step' => null, 'increment_cents' => 500]],
        'volume_increment_ladder' => [['from_step' => 1, 'to_step' => null, 'increment_cents' => 500]],
        'base_price_cents_mode' => 'last_band_effective', 'base_price_cents_manual' => null,
        'weight_resolution' => 1, 'volume_resolution' => 0.001,
    ];

    public const DEFAULT_SUPPLEMENTS = [
        ['prefix' => '90', 'amount_cents' => 250, 'apply_to' => 'both', 'enabled' => true],
    ];

    private ?array $cachedConfig = null;
    private PriceBandValidator $validator;

    public function __construct(?PriceBandValidator $validator = null)
    {
        $this->validator = $validator ?? new PriceBandValidator();
    }

    public function getPricingConfig(): array
    {
        if ($this->cachedConfig !== null) {
            return $this->cachedConfig;
        }

        $weightBands = $this->decodeJsonSetting(self::SETTINGS_KEY_WEIGHT_BANDS);
        $volumeBands = $this->decodeJsonSetting(self::SETTINGS_KEY_VOLUME_BANDS);

        if (empty($weightBands)) $weightBands = $this->loadBandsFromDatabase('weight');
        if (empty($volumeBands)) $volumeBands = $this->loadBandsFromDatabase('volume');
        if (empty($weightBands)) $weightBands = self::DEFAULT_WEIGHT_BANDS;
        if (empty($volumeBands)) $volumeBands = self::DEFAULT_VOLUME_BANDS;

        $extraRules = $this->validator->normalizeExtraRules(
            $this->decodeJsonSetting(self::SETTINGS_KEY_EXTRA_RULES, self::DEFAULT_EXTRA_RULES),
            self::DEFAULT_EXTRA_RULES
        );

        try {
            $normalizedWeight = $this->validator->normalizeBands($weightBands, 'weight', (float) $extraRules['weight_resolution']);
        } catch (ValidationException) {
            $normalizedWeight = $this->validator->normalizeBands(self::DEFAULT_WEIGHT_BANDS, 'weight', (float) self::DEFAULT_EXTRA_RULES['weight_resolution']);
        }

        try {
            $normalizedVolume = $this->validator->normalizeBands($volumeBands, 'volume', (float) $extraRules['volume_resolution']);
        } catch (ValidationException) {
            $normalizedVolume = $this->validator->normalizeBands(self::DEFAULT_VOLUME_BANDS, 'volume', (float) self::DEFAULT_EXTRA_RULES['volume_resolution']);
        }

        try {
            $this->validator->validateExtraRulesAgainstBands($normalizedWeight, $normalizedVolume, $extraRules);
        } catch (ValidationException) {
            $extraRules = $this->validator->normalizeExtraRules(self::DEFAULT_EXTRA_RULES, self::DEFAULT_EXTRA_RULES);
        }

        $supplements = $this->validator->normalizeSupplements(
            $this->decodeJsonSetting(self::SETTINGS_KEY_SUPPLEMENTS, self::DEFAULT_SUPPLEMENTS)
        );

        $version = Setting::get(self::SETTINGS_KEY_VERSION) ?: (string) time();

        $this->cachedConfig = [
            'weight' => $normalizedWeight,
            'volume' => $normalizedVolume,
            'extra_rules' => $extraRules,
            'supplements' => $supplements,
            'version' => $version,
        ];

        return $this->cachedConfig;
    }

    public function invalidateLocalCache(): void
    {
        $this->cachedConfig = null;
    }

    /**
     * Calcola il prezzo in euro per un dato peso o volume.
     *
     * ATTENZIONE: usare calculateBandPriceCents() per calcoli interni per evitare
     * errori di arrotondamento float. Questo metodo esiste solo per compatibilita'
     * con il frontend che mostra il prezzo in euro.
     *
     * @throws \InvalidArgumentException se il valore e' <= 0 o supera il massimo
     */
    public function calculateBandPrice(string $type, float $value): float
    {
        return round($this->calculateBandPriceCents($type, $value) / 100, 2);
    }

    /** Maximum accepted weight in kg. Anything above is rejected. */
    private const MAX_WEIGHT_KG = 1000.0;

    /** Maximum accepted volume in m3. Anything above is rejected. */
    private const MAX_VOLUME_M3 = 100.0;

    /**
     * Calcola il prezzo in centesimi per un dato peso o volume.
     *
     * @throws \InvalidArgumentException se il valore e' <= 0 o supera il massimo
     */
    public function calculateBandPriceCents(string $type, float $value): int
    {
        $type = $type === 'volume' ? 'volume' : 'weight';

        // Validate value range — zero or negative means invalid input, never a valid shipment
        if ($value <= 0) {
            throw new \InvalidArgumentException(
                sprintf('Il %s deve essere maggiore di zero (ricevuto: %s).', $type === 'weight' ? 'peso' : 'volume', $value)
            );
        }

        $maxAllowed = $type === 'weight' ? self::MAX_WEIGHT_KG : self::MAX_VOLUME_M3;
        if ($value > $maxAllowed) {
            throw new \InvalidArgumentException(
                sprintf('Il %s %s supera il massimo consentito di %s.', $type === 'weight' ? 'peso' : 'volume', $value, $maxAllowed)
            );
        }

        $config = $this->getPricingConfig();
        $bands = $config[$type] ?? [];

        $band = $this->findMatchingBand($bands, $value);
        if ($band !== null) {
            return self::effectivePriceCents($band);
        }

        $extraPrice = $this->calculateExtraPriceCents($type, $value, $bands, $config['extra_rules'] ?? self::DEFAULT_EXTRA_RULES);
        if ($extraPrice !== null) {
            return $extraPrice;
        }

        if (! empty($bands)) {
            return self::effectivePriceCents(end($bands));
        }

        $fallbackBands = $type === 'weight' ? self::DEFAULT_WEIGHT_BANDS : self::DEFAULT_VOLUME_BANDS;
        return self::effectivePriceCents(end($fallbackBands));
    }

    public function calculateCapSupplementCents(?string $originCap, ?string $destinationCap): int
    {
        $config = $this->getPricingConfig();
        $supplements = $config['supplements'] ?? [];
        $origin = preg_replace('/\D+/', '', (string) ($originCap ?? ''));
        $destination = preg_replace('/\D+/', '', (string) ($destinationCap ?? ''));
        $total = 0;

        foreach ($supplements as $rule) {
            if (! ($rule['enabled'] ?? true)) continue;
            $prefix = preg_replace('/\D+/', '', (string) ($rule['prefix'] ?? ''));
            if ($prefix === '') continue;
            $amount = (int) ($rule['amount_cents'] ?? 0);
            if ($amount <= 0) continue;
            $applyTo = (string) ($rule['apply_to'] ?? 'both');

            if (($applyTo === 'origin' || $applyTo === 'both') && $origin !== '' && str_starts_with($origin, $prefix)) {
                $total += $amount;
            }
            if (($applyTo === 'destination' || $applyTo === 'both') && $destination !== '' && str_starts_with($destination, $prefix)) {
                $total += $amount;
            }
        }

        return $total;
    }

    public function calculateCapSupplement(?string $originCap, ?string $destinationCap): float
    {
        return round($this->calculateCapSupplementCents($originCap, $destinationCap) / 100, 2);
    }

    public function savePricingConfig(array $input): array
    {
        $normalizedExtraRules = $this->validator->normalizeExtraRules($input['extra_rules'] ?? [], self::DEFAULT_EXTRA_RULES);
        $normalizedWeight = $this->validator->normalizeBands($input['weight'] ?? [], 'weight', (float) $normalizedExtraRules['weight_resolution']);
        $normalizedVolume = $this->validator->normalizeBands($input['volume'] ?? [], 'volume', (float) $normalizedExtraRules['volume_resolution']);
        $this->validator->validateExtraRulesAgainstBands($normalizedWeight, $normalizedVolume, $normalizedExtraRules);
        $normalizedSupplements = $this->validator->normalizeSupplements($input['supplements'] ?? []);

        DB::transaction(function () use ($normalizedWeight, $normalizedVolume, $normalizedExtraRules, $normalizedSupplements): void {
            Setting::set(self::SETTINGS_KEY_WEIGHT_BANDS, json_encode($normalizedWeight, JSON_UNESCAPED_UNICODE));
            Setting::set(self::SETTINGS_KEY_VOLUME_BANDS, json_encode($normalizedVolume, JSON_UNESCAPED_UNICODE));
            Setting::set(self::SETTINGS_KEY_EXTRA_RULES, json_encode($normalizedExtraRules, JSON_UNESCAPED_UNICODE));
            Setting::set(self::SETTINGS_KEY_SUPPLEMENTS, json_encode($normalizedSupplements, JSON_UNESCAPED_UNICODE));
            Setting::set(self::SETTINGS_KEY_VERSION, (string) (int) round(microtime(true) * 1000));
        });

        $this->invalidateLocalCache();
        return $this->getPricingConfig();
    }

    public function clearPricingSettings(): void
    {
        DB::transaction(function (): void {
            Setting::set(self::SETTINGS_KEY_WEIGHT_BANDS, null);
            Setting::set(self::SETTINGS_KEY_VOLUME_BANDS, null);
            Setting::set(self::SETTINGS_KEY_EXTRA_RULES, null);
            Setting::set(self::SETTINGS_KEY_SUPPLEMENTS, null);
            Setting::set(self::SETTINGS_KEY_VERSION, (string) (int) round(microtime(true) * 1000));
        });
        $this->invalidateLocalCache();
    }

    private function findMatchingBand(array $bands, float $value): ?array
    {
        foreach ($bands as $idx => $band) {
            $min = (float) $band['min_value'];
            $max = (float) $band['max_value'];
            $lowerOk = $idx === 0 ? $value >= ($min - self::EPSILON) : $value > ($min + self::EPSILON);
            if ($lowerOk && $value <= ($max + self::EPSILON)) {
                return $band;
            }
        }
        return null;
    }

    private function calculateExtraPriceCents(string $type, float $rawValue, array $bands, array $extraRules): ?int
    {
        if (! ($extraRules['enabled'] ?? true)) return null;

        $start = (float) ($type === 'weight' ? ($extraRules['weight_start'] ?? 101) : ($extraRules['volume_start'] ?? 0.401));
        $step = (float) ($type === 'weight' ? ($extraRules['weight_step'] ?? 50) : ($extraRules['volume_step'] ?? 0.200));
        $resolution = (float) ($type === 'weight' ? ($extraRules['weight_resolution'] ?? 1) : ($extraRules['volume_resolution'] ?? 0.001));
        $increment = (int) ($extraRules['increment_cents'] ?? 500);

        if ($step <= 0 || $increment < 0 || $resolution <= 0) return null;

        $value = self::ceilByResolution($rawValue, $resolution);
        if ($value + self::EPSILON < $start) return null;

        $baseMode = (string) ($extraRules['base_price_cents_mode'] ?? 'last_band_effective');
        if ($baseMode === 'manual') {
            $extraBaseCents = (int) ($extraRules['base_price_cents_manual'] ?? 0);
        } else {
            $lastBand = end($bands);
            if ($lastBand === false) {
                $fallbackBands = $type === 'weight' ? self::DEFAULT_WEIGHT_BANDS : self::DEFAULT_VOLUME_BANDS;
                $lastBand = end($fallbackBands);
            }
            $extraBaseCents = self::effectivePriceCents($lastBand);
        }

        $stepsFromStart = (int) floor((($value - $start) + self::EPSILON) / $step);
        $bandNumber = max(0, $stepsFromStart) + 1;

        return $extraBaseCents + ($bandNumber * $increment);
    }

    private function decodeJsonSetting(string $key, ?array $default = []): array
    {
        $raw = Setting::get($key);
        if ($raw === null || $raw === '') return $default ?? [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ($default ?? []);
    }

    private function loadBandsFromDatabase(string $type): array
    {
        $rows = PriceBand::where('type', $type)->orderBy('sort_order')->orderBy('min_value')->get();
        if ($rows->isEmpty()) return [];

        return $rows->map(fn (PriceBand $band) => [
            'id' => (string) $band->id,
            'type' => $band->type,
            'min_value' => (float) $band->min_value,
            'max_value' => (float) $band->max_value,
            'base_price' => (int) $band->base_price,
            'discount_price' => $band->discount_price !== null ? (int) $band->discount_price : null,
            'show_discount' => (bool) ($band->show_discount ?? true),
            'sort_order' => (int) ($band->sort_order ?? 0),
        ])->all();
    }

    public static function effectivePriceCents(array $band): int
    {
        // discount_price must be strictly > 0 to be treated as a real discount.
        // A value of 0 means "no discount configured" — fall back to base_price.
        // This prevents a misconfigured discount_price=0 from making shipments free.
        if (isset($band['discount_price']) && $band['discount_price'] !== null && (int) $band['discount_price'] > 0) {
            return (int) $band['discount_price'];
        }
        return (int) ($band['base_price'] ?? 0);
    }

    private static function ceilByResolution(float $value, float $resolution): float
    {
        if ($resolution <= 0) return $value;
        $multiplier = 1 / $resolution;
        $rounded = ceil(($value * $multiplier) - self::EPSILON) / $multiplier;
        return PriceBandValidator::normalizeDecimal($rounded);
    }
}
