<?php

namespace App\Services\Pricing;

/**
 * Normalizza e valida le configurazioni di pricing dei servizi spedizione.
 *
 * Responsabilita':
 * - Normalizzazione service_pricing, automatic_supplements, operational_fees
 * - Normalizzazione liste stringhe, tier di peso, valori di default
 */
class PricingConfigNormalizer
{
    private static ?array $defaultConfigCache = null;

    public static function getDefaultConfig(): array
    {
        return self::$defaultConfigCache ??= config('pricing_service_rules');
    }

    /**
     * Normalizza l'intero blocco di configurazione pricing.
     */
    public function normalize(array $config): array
    {
        return [
            'service_pricing' => $this->normalizeServicePricing($config['service_pricing'] ?? []),
            'automatic_supplements' => $this->normalizeAutomaticSupplements($config['automatic_supplements'] ?? []),
            'operational_fees' => $this->normalizeOperationalFees($config['operational_fees'] ?? []),
        ];
    }

    private function normalizeServicePricing(array $config): array
    {
        $defaults = self::getDefaultConfig()['service_pricing'];
        $normalized = [];

        foreach ($defaults as $key => $default) {
            $source = is_array($config[$key] ?? null) ? $config[$key] : [];
            $normalized[$key] = [
                'label' => trim((string) ($source['label'] ?? $default['label'])),
                'description' => trim((string) ($source['description'] ?? $default['description'])),
                'pricing_type' => in_array(($source['pricing_type'] ?? $default['pricing_type']), ['fixed', 'threshold_percentage'], true)
                    ? $source['pricing_type'] ?? $default['pricing_type']
                    : $default['pricing_type'],
                'price_cents' => max(0, (int) ($source['price_cents'] ?? $default['price_cents'] ?? 0)),
                'threshold_amount_eur' => round((float) ($source['threshold_amount_eur'] ?? $default['threshold_amount_eur'] ?? 0), 2),
                'min_fee_cents' => max(0, (int) ($source['min_fee_cents'] ?? $default['min_fee_cents'] ?? 0)),
                'percentage_rate' => round((float) ($source['percentage_rate'] ?? $default['percentage_rate'] ?? 0), 4),
                'enabled' => ($source['enabled'] ?? $default['enabled']) !== false,
                'application' => trim((string) ($source['application'] ?? $default['application'])),
                'note' => trim((string) ($source['note'] ?? $default['note'])),
            ];
        }

        return $normalized;
    }

    private function normalizeAutomaticSupplements(array $config): array
    {
        $defaults = self::getDefaultConfig()['automatic_supplements'];
        $normalized = [];

        foreach ($defaults as $key => $default) {
            $source = is_array($config[$key] ?? null) ? $config[$key] : [];
            $normalized[$key] = [
                'label' => trim((string) ($source['label'] ?? $default['label'])),
                'description' => trim((string) ($source['description'] ?? $default['description'])),
                'enabled' => ($source['enabled'] ?? $default['enabled']) !== false,
                'pricing_type' => trim((string) ($source['pricing_type'] ?? $default['pricing_type'])),
                'application' => trim((string) ($source['application'] ?? $default['application'])),
                'price_cents' => $this->normalizeOptionalInt($source, $default, 'price_cents'),
                'province_codes' => $this->normalizeStringList($source['province_codes'] ?? $default['province_codes'] ?? [], true),
                'country_codes' => $this->normalizeStringList($source['country_codes'] ?? $default['country_codes'] ?? [], true),
                'keyword_list' => $this->normalizeStringList($source['keyword_list'] ?? $default['keyword_list'] ?? []),
                'flag_keys' => $this->normalizeStringList($source['flag_keys'] ?? $default['flag_keys'] ?? []),
                'delivery_modes' => $this->normalizeStringList($source['delivery_modes'] ?? $default['delivery_modes'] ?? []),
                'tiers' => $this->normalizeTiers($source['tiers'] ?? $default['tiers'] ?? []),
                'max_weight_kg' => $this->normalizeOptionalFloat($source, $default, 'max_weight_kg'),
                'threshold_cm' => $this->normalizeOptionalFloat($source, $default, 'threshold_cm'),
                'longest_side_threshold_cm' => $this->normalizeOptionalFloat($source, $default, 'longest_side_threshold_cm'),
                'girth_threshold_cm' => $this->normalizeOptionalFloat($source, $default, 'girth_threshold_cm'),
                'min_longest_side_cm' => $this->normalizeOptionalFloat($source, $default, 'min_longest_side_cm'),
                'max_secondary_side_cm' => $this->normalizeOptionalFloat($source, $default, 'max_secondary_side_cm'),
                'note' => trim((string) ($source['note'] ?? $default['note'] ?? '')),
            ];
        }

        return $normalized;
    }

    private function normalizeOperationalFees(array $config): array
    {
        $defaults = self::getDefaultConfig()['operational_fees'];
        $normalized = [];

        foreach ($defaults as $key => $default) {
            $source = is_array($config[$key] ?? null) ? $config[$key] : [];
            $normalized[$key] = [
                'label' => trim((string) ($source['label'] ?? $default['label'])),
                'description' => trim((string) ($source['description'] ?? $default['description'])),
                'pricing_type' => 'fixed',
                'price_cents' => max(0, (int) ($source['price_cents'] ?? $default['price_cents'] ?? 0)),
                'enabled' => ($source['enabled'] ?? $default['enabled']) !== false,
                'application' => trim((string) ($source['application'] ?? $default['application'])),
                'note' => trim((string) ($source['note'] ?? $default['note'])),
            ];
        }

        return $normalized;
    }

    /**
     * Normalizza un campo int opzionale (presente solo se esiste nel default o source).
     */
    private function normalizeOptionalInt(array $source, array $default, string $key): ?int
    {
        if (! array_key_exists($key, $default) && ! array_key_exists($key, $source)) {
            return null;
        }

        return max(0, (int) ($source[$key] ?? $default[$key] ?? 0));
    }

    /**
     * Normalizza un campo float opzionale (presente solo se esiste nel default o source).
     */
    private function normalizeOptionalFloat(array $source, array $default, string $key): ?float
    {
        if (! array_key_exists($key, $default) && ! array_key_exists($key, $source)) {
            return null;
        }

        return round((float) ($source[$key] ?? $default[$key] ?? 0), 2);
    }

    public function normalizeStringList(array $values, bool $uppercase = false): array
    {
        $normalized = [];
        foreach ($values as $value) {
            $item = trim((string) $value);
            if ($item === '') {
                continue;
            }
            $item = $uppercase ? strtoupper($item) : mb_strtolower($item, 'UTF-8');
            $normalized[$item] = true;
        }

        return array_values(array_keys($normalized));
    }

    public function normalizeTiers(array $tiers): array
    {
        $normalized = array_map(static function ($tier) {
            return [
                'up_to_kg' => ($tier['up_to_kg'] ?? null) === null || $tier['up_to_kg'] === ''
                    ? null
                    : round((float) $tier['up_to_kg'], 2),
                'price_cents' => max(0, (int) ($tier['price_cents'] ?? 0)),
            ];
        }, array_values(array_filter($tiers, static fn ($tier) => is_array($tier))));

        usort($normalized, static function (array $a, array $b) {
            $aWeight = $a['up_to_kg'] ?? INF;
            $bWeight = $b['up_to_kg'] ?? INF;

            return $aWeight <=> $bWeight;
        });

        return $normalized;
    }
}
