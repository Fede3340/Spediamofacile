<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EuropePriceEngineService
{
    private const SETTINGS_KEY_CONFIG = 'pricing_europe_monocollo';

    private const SETTINGS_KEY_VERSION = 'pricing_europe_version';

    private const DEFAULT_CONFIG = [
        'enabled' => true,
        'scope' => 'europe_monocollo',
        'origin_country_code' => 'IT',
        'max_packages' => 1,
        'max_quantity_per_package' => 1,
        'bands' => [
            [
                'id' => 'eu-10',
                'label' => '0-10 kg',
                'max_weight_kg' => 10,
                'max_volume_m3' => 0.040,
                'volumetric_factor' => 250,
                'rates' => [
                    ['country_code' => 'AT', 'country_name' => 'Austria', 'price_cents' => 3000, 'quote_required' => false],
                    ['country_code' => 'DE', 'country_name' => 'Germania', 'price_cents' => 3000, 'quote_required' => false],
                    ['country_code' => 'ES', 'country_name' => 'Spagna', 'price_cents' => 3000, 'quote_required' => false],
                    ['country_code' => 'FR', 'country_name' => 'Francia', 'price_cents' => 3000, 'quote_required' => false],
                    ['country_code' => 'NL', 'country_name' => 'Olanda', 'price_cents' => 3000, 'quote_required' => false],
                    ['country_code' => 'PT', 'country_name' => 'Portogallo', 'price_cents' => 3000, 'quote_required' => false],
                    ['country_code' => 'BE', 'country_name' => 'Belgio', 'price_cents' => 3000, 'quote_required' => false],
                    ['country_code' => 'LU', 'country_name' => 'Lussemburgo', 'price_cents' => 3000, 'quote_required' => false],
                    ['country_code' => 'DK', 'country_name' => 'Danimarca', 'price_cents' => 4700, 'quote_required' => false],
                    ['country_code' => 'FI', 'country_name' => 'Finlandia', 'price_cents' => 4700, 'quote_required' => false],
                    ['country_code' => 'SE', 'country_name' => 'Svezia', 'price_cents' => 4700, 'quote_required' => false],
                    ['country_code' => 'EE', 'country_name' => 'Estonia', 'price_cents' => 4700, 'quote_required' => false],
                    ['country_code' => 'LV', 'country_name' => 'Lettonia', 'price_cents' => 4700, 'quote_required' => false],
                    ['country_code' => 'LT', 'country_name' => 'Lituania', 'price_cents' => 4700, 'quote_required' => false],
                    ['country_code' => 'PL', 'country_name' => 'Polonia', 'price_cents' => 4700, 'quote_required' => false],
                    ['country_code' => 'CZ', 'country_name' => 'Repubblica Ceca', 'price_cents' => 4700, 'quote_required' => false],
                    ['country_code' => 'SK', 'country_name' => 'Slovacchia', 'price_cents' => 4700, 'quote_required' => false],
                    ['country_code' => 'HU', 'country_name' => 'Ungheria', 'price_cents' => 4700, 'quote_required' => false],
                    ['country_code' => 'SI', 'country_name' => 'Slovenia', 'price_cents' => 4700, 'quote_required' => false],
                    ['country_code' => 'RO', 'country_name' => 'Romania', 'price_cents' => 4700, 'quote_required' => false],
                    ['country_code' => 'BG', 'country_name' => 'Bulgaria', 'price_cents' => 5800, 'quote_required' => false],
                    ['country_code' => 'HR', 'country_name' => 'Croazia', 'price_cents' => 7800, 'quote_required' => false],
                    ['country_code' => 'GR', 'country_name' => 'Grecia', 'price_cents' => 7800, 'quote_required' => false],
                ],
            ],
            [
                'id' => 'eu-30',
                'label' => '10-30 kg',
                'max_weight_kg' => 30,
                'max_volume_m3' => 0.120,
                'volumetric_factor' => 250,
                'rates' => [
                    ['country_code' => 'AT', 'country_name' => 'Austria', 'price_cents' => 5200, 'quote_required' => false],
                    ['country_code' => 'DE', 'country_name' => 'Germania', 'price_cents' => 5200, 'quote_required' => false],
                    ['country_code' => 'ES', 'country_name' => 'Spagna', 'price_cents' => 5200, 'quote_required' => false],
                    ['country_code' => 'FR', 'country_name' => 'Francia', 'price_cents' => 5200, 'quote_required' => false],
                    ['country_code' => 'NL', 'country_name' => 'Olanda', 'price_cents' => 5200, 'quote_required' => false],
                    ['country_code' => 'PT', 'country_name' => 'Portogallo', 'price_cents' => 5200, 'quote_required' => false],
                    ['country_code' => 'BE', 'country_name' => 'Belgio', 'price_cents' => 5200, 'quote_required' => false],
                    ['country_code' => 'LU', 'country_name' => 'Lussemburgo', 'price_cents' => 5200, 'quote_required' => false],
                    ['country_code' => 'DK', 'country_name' => 'Danimarca', 'price_cents' => 7200, 'quote_required' => false],
                    ['country_code' => 'FI', 'country_name' => 'Finlandia', 'price_cents' => 7200, 'quote_required' => false],
                    ['country_code' => 'SE', 'country_name' => 'Svezia', 'price_cents' => 7200, 'quote_required' => false],
                    ['country_code' => 'EE', 'country_name' => 'Estonia', 'price_cents' => 7200, 'quote_required' => false],
                    ['country_code' => 'LV', 'country_name' => 'Lettonia', 'price_cents' => 7200, 'quote_required' => false],
                    ['country_code' => 'LT', 'country_name' => 'Lituania', 'price_cents' => 7200, 'quote_required' => false],
                    ['country_code' => 'PL', 'country_name' => 'Polonia', 'price_cents' => 7200, 'quote_required' => false],
                    ['country_code' => 'CZ', 'country_name' => 'Repubblica Ceca', 'price_cents' => 7200, 'quote_required' => false],
                    ['country_code' => 'SK', 'country_name' => 'Slovacchia', 'price_cents' => 7200, 'quote_required' => false],
                    ['country_code' => 'HU', 'country_name' => 'Ungheria', 'price_cents' => 7200, 'quote_required' => false],
                    ['country_code' => 'SI', 'country_name' => 'Slovenia', 'price_cents' => 7200, 'quote_required' => false],
                    ['country_code' => 'RO', 'country_name' => 'Romania', 'price_cents' => 7200, 'quote_required' => false],
                    ['country_code' => 'BG', 'country_name' => 'Bulgaria', 'price_cents' => 7900, 'quote_required' => false],
                    ['country_code' => 'HR', 'country_name' => 'Croazia', 'price_cents' => 9500, 'quote_required' => false],
                    ['country_code' => 'GR', 'country_name' => 'Grecia', 'price_cents' => 9500, 'quote_required' => false],
                ],
            ],
            [
                'id' => 'eu-50',
                'label' => '25-50 kg',
                'max_weight_kg' => 50,
                'max_volume_m3' => 0.165,
                'volumetric_factor' => 300,
                'rates' => [
                    ['country_code' => 'AT', 'country_name' => 'Austria', 'price_cents' => 7900, 'quote_required' => false],
                    ['country_code' => 'DE', 'country_name' => 'Germania', 'price_cents' => 8900, 'quote_required' => false],
                    ['country_code' => 'ES', 'country_name' => 'Spagna', 'price_cents' => 8500, 'quote_required' => false],
                    ['country_code' => 'FR', 'country_name' => 'Francia', 'price_cents' => 8900, 'quote_required' => false],
                    ['country_code' => 'NL', 'country_name' => 'Olanda', 'price_cents' => 9900, 'quote_required' => false],
                    ['country_code' => 'PT', 'country_name' => 'Portogallo', 'price_cents' => 9900, 'quote_required' => false],
                    ['country_code' => 'BE', 'country_name' => 'Belgio', 'price_cents' => 9900, 'quote_required' => false],
                    ['country_code' => 'LU', 'country_name' => 'Lussemburgo', 'price_cents' => 9900, 'quote_required' => false],
                    ['country_code' => 'DK', 'country_name' => 'Danimarca', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'FI', 'country_name' => 'Finlandia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'SE', 'country_name' => 'Svezia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'EE', 'country_name' => 'Estonia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'LV', 'country_name' => 'Lettonia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'LT', 'country_name' => 'Lituania', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'PL', 'country_name' => 'Polonia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'CZ', 'country_name' => 'Repubblica Ceca', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'SK', 'country_name' => 'Slovacchia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'HU', 'country_name' => 'Ungheria', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'SI', 'country_name' => 'Slovenia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'RO', 'country_name' => 'Romania', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'BG', 'country_name' => 'Bulgaria', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'HR', 'country_name' => 'Croazia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'GR', 'country_name' => 'Grecia', 'price_cents' => null, 'quote_required' => true],
                ],
            ],
            [
                'id' => 'eu-75',
                'label' => '50-75 kg',
                'max_weight_kg' => 75,
                'max_volume_m3' => 0.250,
                'volumetric_factor' => 300,
                'rates' => [
                    ['country_code' => 'AT', 'country_name' => 'Austria', 'price_cents' => 10000, 'quote_required' => false],
                    ['country_code' => 'DE', 'country_name' => 'Germania', 'price_cents' => 12000, 'quote_required' => false],
                    ['country_code' => 'ES', 'country_name' => 'Spagna', 'price_cents' => 11500, 'quote_required' => false],
                    ['country_code' => 'FR', 'country_name' => 'Francia', 'price_cents' => 12500, 'quote_required' => false],
                    ['country_code' => 'NL', 'country_name' => 'Olanda', 'price_cents' => 14000, 'quote_required' => false],
                    ['country_code' => 'PT', 'country_name' => 'Portogallo', 'price_cents' => 14000, 'quote_required' => false],
                    ['country_code' => 'BE', 'country_name' => 'Belgio', 'price_cents' => 14000, 'quote_required' => false],
                    ['country_code' => 'LU', 'country_name' => 'Lussemburgo', 'price_cents' => 14000, 'quote_required' => false],
                    ['country_code' => 'DK', 'country_name' => 'Danimarca', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'FI', 'country_name' => 'Finlandia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'SE', 'country_name' => 'Svezia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'EE', 'country_name' => 'Estonia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'LV', 'country_name' => 'Lettonia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'LT', 'country_name' => 'Lituania', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'PL', 'country_name' => 'Polonia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'CZ', 'country_name' => 'Repubblica Ceca', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'SK', 'country_name' => 'Slovacchia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'HU', 'country_name' => 'Ungheria', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'SI', 'country_name' => 'Slovenia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'RO', 'country_name' => 'Romania', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'BG', 'country_name' => 'Bulgaria', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'HR', 'country_name' => 'Croazia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'GR', 'country_name' => 'Grecia', 'price_cents' => null, 'quote_required' => true],
                ],
            ],
            [
                'id' => 'eu-100',
                'label' => '75-100 kg',
                'max_weight_kg' => 100,
                'max_volume_m3' => 0.333,
                'volumetric_factor' => 300,
                'rates' => [
                    ['country_code' => 'AT', 'country_name' => 'Austria', 'price_cents' => 13500, 'quote_required' => false],
                    ['country_code' => 'DE', 'country_name' => 'Germania', 'price_cents' => 15500, 'quote_required' => false],
                    ['country_code' => 'ES', 'country_name' => 'Spagna', 'price_cents' => 14500, 'quote_required' => false],
                    ['country_code' => 'FR', 'country_name' => 'Francia', 'price_cents' => 15500, 'quote_required' => false],
                    ['country_code' => 'NL', 'country_name' => 'Olanda', 'price_cents' => 17500, 'quote_required' => false],
                    ['country_code' => 'PT', 'country_name' => 'Portogallo', 'price_cents' => 17500, 'quote_required' => false],
                    ['country_code' => 'BE', 'country_name' => 'Belgio', 'price_cents' => 17500, 'quote_required' => false],
                    ['country_code' => 'LU', 'country_name' => 'Lussemburgo', 'price_cents' => 17500, 'quote_required' => false],
                    ['country_code' => 'DK', 'country_name' => 'Danimarca', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'FI', 'country_name' => 'Finlandia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'SE', 'country_name' => 'Svezia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'EE', 'country_name' => 'Estonia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'LV', 'country_name' => 'Lettonia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'LT', 'country_name' => 'Lituania', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'PL', 'country_name' => 'Polonia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'CZ', 'country_name' => 'Repubblica Ceca', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'SK', 'country_name' => 'Slovacchia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'HU', 'country_name' => 'Ungheria', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'SI', 'country_name' => 'Slovenia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'RO', 'country_name' => 'Romania', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'BG', 'country_name' => 'Bulgaria', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'HR', 'country_name' => 'Croazia', 'price_cents' => null, 'quote_required' => true],
                    ['country_code' => 'GR', 'country_name' => 'Grecia', 'price_cents' => null, 'quote_required' => true],
                ],
            ],
        ],
    ];

    private ?array $cachedConfig = null;

    public function getPricingConfig(): array
    {
        if ($this->cachedConfig !== null) {
            return $this->cachedConfig;
        }

        $raw = Setting::get(self::SETTINGS_KEY_CONFIG);
        $decoded = $raw ? json_decode($raw, true) : null;
        $config = is_array($decoded) ? $decoded : self::DEFAULT_CONFIG;
        $normalized = $this->normalizeConfig($config);
        $version = Setting::get(self::SETTINGS_KEY_VERSION) ?: (string) time();
        $normalized['version'] = $version;

        $this->cachedConfig = $normalized;

        return $this->cachedConfig;
    }

    public function invalidateLocalCache(): void
    {
        $this->cachedConfig = null;
    }

    public function savePricingConfig(array $config): array
    {
        $normalized = $this->normalizeConfig($config);
        $version = (string) time();

        DB::transaction(function () use ($normalized, $version): void {
            Setting::set(self::SETTINGS_KEY_CONFIG, json_encode($normalized, JSON_UNESCAPED_UNICODE));
            Setting::set(self::SETTINGS_KEY_VERSION, $version);
        });

        $this->cachedConfig = null;

        return $this->getPricingConfig();
    }

    public function getSupportedCountryCodes(): array
    {
        return $this->getPricingConfig()['supported_country_codes'] ?? [];
    }

    public function isEuropeDestination(?string $countryCode): bool
    {
        $code = strtoupper(trim((string) ($countryCode ?? '')));
        if ($code === '' || $code === 'IT') {
            return false;
        }

        return in_array($code, $this->getSupportedCountryCodes(), true);
    }

    public function calculateQuote(?string $destinationCountryCode, float $weightKg, float $volumeM3): array
    {
        $countryCode = strtoupper(trim((string) ($destinationCountryCode ?? '')));
        if ($countryCode === '' || $countryCode === 'IT') {
            return [
                'status' => 'not_europe',
                'message' => 'Destinazione non europea o nazionale.',
            ];
        }

        // Validate weight and volume — zero or negative is never a valid shipment
        if ($weightKg <= 0) {
            return [
                'status' => 'error',
                'message' => 'Il peso deve essere maggiore di zero.',
            ];
        }
        if ($volumeM3 <= 0) {
            return [
                'status' => 'error',
                'message' => 'Il volume deve essere maggiore di zero.',
            ];
        }

        $config = $this->getPricingConfig();
        $band = $this->findMatchingBand($config['bands'], $weightKg, $volumeM3);
        if ($band === null) {
            return [
                'status' => 'requires_quote',
                'message' => 'Per questo peso o volume verso l\'Europa serve un preventivo manuale.',
            ];
        }

        $rate = $this->findRateForCountry($band['rates'], $countryCode);
        if ($rate === null) {
            return [
                'status' => 'not_supported',
                'message' => 'Destinazione europea non configurata nel listino attuale.',
                'band' => $band,
            ];
        }

        if ($rate['quote_required']) {
            return [
                'status' => 'requires_quote',
                'message' => sprintf('Per %s in questa fascia va richiesto un preventivo manuale.', $rate['country_name']),
                'band' => $band,
                'rate' => $rate,
            ];
        }

        return [
            'status' => 'priced',
            'price_cents' => (int) $rate['price_cents'],
            'price' => round(((int) $rate['price_cents']) / 100, 2),
            'band' => $band,
            'rate' => $rate,
        ];
    }

    private function normalizeConfig(array $config): array
    {
        $bands = array_map(fn (array $band) => $this->normalizeBand($band), $config['bands'] ?? self::DEFAULT_CONFIG['bands']);
        usort($bands, fn (array $a, array $b) => $a['max_weight_kg'] <=> $b['max_weight_kg']);

        return [
            'enabled' => ($config['enabled'] ?? true) !== false,
            'scope' => 'europe_monocollo',
            'origin_country_code' => strtoupper(trim((string) ($config['origin_country_code'] ?? 'IT'))),
            'max_packages' => max(1, (int) ($config['max_packages'] ?? 1)),
            'max_quantity_per_package' => max(1, (int) ($config['max_quantity_per_package'] ?? 1)),
            'supported_country_codes' => $this->extractCountryCodes($bands),
            'bands' => $bands,
        ];
    }

    private function extractCountryCodes(array $bands): array
    {
        $codes = [];
        foreach ($bands as $band) {
            foreach (($band['rates'] ?? []) as $rate) {
                $countryCode = strtoupper(trim((string) ($rate['country_code'] ?? '')));
                if ($countryCode !== '') {
                    $codes[$countryCode] = true;
                }
            }
        }

        $result = array_values(array_keys($codes));
        sort($result);

        return $result;
    }

    private function normalizeBand(array $band): array
    {
        $rates = array_map(function (array $rate) {
            return [
                'country_code' => strtoupper(trim((string) ($rate['country_code'] ?? ''))),
                'country_name' => trim((string) ($rate['country_name'] ?? '')),
                'price_cents' => $rate['price_cents'] === null || $rate['price_cents'] === '' ? null : max(0, (int) $rate['price_cents']),
                'quote_required' => ($rate['quote_required'] ?? false) === true,
            ];
        }, $band['rates'] ?? []);

        usort($rates, fn (array $a, array $b) => strcmp($a['country_code'], $b['country_code']));

        return [
            'id' => trim((string) ($band['id'] ?? uniqid('eu-band-', true))),
            'label' => trim((string) ($band['label'] ?? '')), 
            'max_weight_kg' => round((float) ($band['max_weight_kg'] ?? 0), 3),
            'max_volume_m3' => round((float) ($band['max_volume_m3'] ?? 0), 6),
            'volumetric_factor' => max(1, (int) ($band['volumetric_factor'] ?? 250)),
            'rates' => array_values(array_filter($rates, fn (array $rate) => $rate['country_code'] !== '')),
        ];
    }

    /**
     * Find the matching band for a given weight and volume.
     *
     * Bands are sorted by max_weight_kg ascending (see normalizeConfig). The
     * weight is matched to the first band whose max_weight_kg covers it, then
     * volume is checked against that band's max_volume_m3. This derives
     * thresholds directly from the loaded configuration instead of hardcoding
     * specific band IDs or weight values.
     */
    private function findMatchingBand(array $bands, float $weightKg, float $volumeM3): ?array
    {
        // Bands are already sorted ascending by max_weight_kg (see normalizeConfig).
        // Walk through them in order and pick the first band that covers the weight.
        foreach ($bands as $band) {
            $maxWeight = (float) $band['max_weight_kg'];
            if ($weightKg <= $maxWeight && $volumeM3 <= (float) $band['max_volume_m3']) {
                return $band;
            }
        }

        return null;
    }

    private function findRateForCountry(array $rates, string $countryCode): ?array
    {
        foreach ($rates as $rate) {
            if (($rate['country_code'] ?? '') === $countryCode) {
                return $rate;
            }
        }

        return null;
    }
}
