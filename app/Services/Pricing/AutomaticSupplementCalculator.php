<?php

namespace App\Services\Pricing;

/**
 * Calcola i supplementi automatici in base a regole configurabili.
 *
 * Responsabilita':
 * - Calcolo supplementi per zona (CSI, isole minori, ecc.)
 * - Calcolo supplementi per dimensione (fuori sagoma, lato >130cm, aste/tubi)
 * - Matching province, isole minori, flag dimensionali
 * - Normalizzazione pacchi e indirizzi per il calcolo
 */
class AutomaticSupplementCalculator
{
    /**
     * Calcola tutti i supplementi automatici applicabili.
     *
     * @return array<int, array{key: string, label: string, amount_cents: int, type: string, automatic: bool, application: string}>
     */
    public function calculate(array $config, array $serviceData, array $context): array
    {
        $items = [];
        $packages = $this->normalizePackages($context['packages'] ?? []);
        $destination = $this->normalizeAddress($context['destination_address'] ?? []);
        $deliveryMode = mb_strtolower(trim((string) ($context['delivery_mode'] ?? 'home')), 'UTF-8');
        $requiresManualQuote = (bool) ($context['requires_manual_quote'] ?? false);

        $items = array_merge($items, $this->calculateZoneSupplements($config, $packages, $destination, $deliveryMode));
        $items = array_merge($items, $this->calculateDimensionSupplements($config, $packages, $serviceData));

        if (($config['eu_manual_extra']['enabled'] ?? false) && $requiresManualQuote) {
            $items[] = $this->buildItem('eu_manual_extra', $config['eu_manual_extra'], (int) ($config['eu_manual_extra']['price_cents'] ?? 0));
        }

        return array_values(array_filter($items, static fn (array $item) => (int) ($item['amount_cents'] ?? 0) > 0));
    }

    /**
     * Supplementi legati alla zona geografica di destinazione.
     */
    private function calculateZoneSupplements(array $config, array $packages, array $destination, string $deliveryMode): array
    {
        $items = [];

        if (($config['calabria_sardegna_sicilia']['enabled'] ?? false)
            && $this->matchesProvince($destination, $config['calabria_sardegna_sicilia']['province_codes'] ?? [])) {
            foreach ($packages as $package) {
                $fee = $this->findTierPriceCents($package['weight_kg'], $config['calabria_sardegna_sicilia']['tiers'] ?? []);
                if ($fee > 0) {
                    $items[] = $this->buildItem('calabria_sardegna_sicilia', $config['calabria_sardegna_sicilia'], $fee * $package['quantity']);
                }
            }
        }

        if (($config['brt_point_csi']['enabled'] ?? false)
            && $deliveryMode === 'pudo'
            && $this->matchesProvince($destination, $config['brt_point_csi']['province_codes'] ?? [])) {
            $maxWeight = (float) ($config['brt_point_csi']['max_weight_kg'] ?? 0);
            $fee = (int) ($config['brt_point_csi']['price_cents'] ?? 0);
            foreach ($packages as $package) {
                if ($package['weight_kg'] > 0 && ($maxWeight <= 0 || $package['weight_kg'] <= $maxWeight) && $fee > 0) {
                    $items[] = $this->buildItem('brt_point_csi', $config['brt_point_csi'], $fee * $package['quantity']);
                }
            }
        }

        if (($config['isole_minori_italia']['enabled'] ?? false) && $this->matchesMinorIsland($destination, $config['isole_minori_italia'])) {
            $items[] = $this->buildItem('isole_minori_italia', $config['isole_minori_italia'], (int) ($config['isole_minori_italia']['price_cents'] ?? 0));
        }

        if (($config['isole_minori_europa']['enabled'] ?? false) && $this->matchesMinorIsland($destination, $config['isole_minori_europa'])) {
            $items[] = $this->buildItem('isole_minori_europa', $config['isole_minori_europa'], (int) ($config['isole_minori_europa']['price_cents'] ?? 0));
        }

        return $items;
    }

    /**
     * Supplementi legati alle dimensioni/forma dei pacchi.
     */
    private function calculateDimensionSupplements(array $config, array $packages, array $serviceData): array
    {
        $items = [];

        if ($config['fuori_sagoma']['enabled'] ?? false) {
            foreach ($packages as $package) {
                if (! $this->matchesOutOfGauge($package, $serviceData, $config['fuori_sagoma'])) {
                    continue;
                }
                $fee = $this->findTierPriceCents($package['weight_kg'], $config['fuori_sagoma']['tiers'] ?? []);
                if ($fee > 0) {
                    $items[] = $this->buildItem('fuori_sagoma', $config['fuori_sagoma'], $fee * $package['quantity']);
                }
            }
        }

        if ($config['lato_superiore_130cm']['enabled'] ?? false) {
            $threshold = (float) ($config['lato_superiore_130cm']['threshold_cm'] ?? 130);
            $fee = (int) ($config['lato_superiore_130cm']['price_cents'] ?? 0);
            foreach ($packages as $package) {
                if ($fee > 0 && $package['max_side_cm'] > $threshold) {
                    $items[] = $this->buildItem('lato_superiore_130cm', $config['lato_superiore_130cm'], $fee * $package['quantity']);
                }
            }
        }

        if ($config['aste_tubi']['enabled'] ?? false) {
            $fee = (int) ($config['aste_tubi']['price_cents'] ?? 0);
            foreach ($packages as $package) {
                if ($fee > 0 && $this->matchesRodsAndTubes($package, $serviceData, $config['aste_tubi'])) {
                    $items[] = $this->buildItem('aste_tubi', $config['aste_tubi'], $fee * $package['quantity']);
                }
            }
        }

        return $items;
    }

    // --- Matching helpers ---

    public function matchesProvince(array $address, array $provinceCodes): bool
    {
        $province = strtoupper(trim((string) ($address['province'] ?? '')));
        if ($province === '') {
            return false;
        }

        return in_array($province, array_map('strtoupper', $provinceCodes), true);
    }

    public function matchesMinorIsland(array $address, array $rule): bool
    {
        $countryCodes = array_map('strtoupper', $rule['country_codes'] ?? []);
        if (! empty($countryCodes) && ! in_array(strtoupper($address['country'] ?? ''), $countryCodes, true)) {
            return false;
        }

        $haystack = trim(implode(' | ', array_filter([
            $address['city'] ?? '',
            $address['address'] ?? '',
            $address['additional_information'] ?? '',
        ])));

        if ($haystack === '') {
            return false;
        }

        foreach (($rule['keyword_list'] ?? []) as $keyword) {
            if ($keyword !== '' && str_contains($haystack, mb_strtolower((string) $keyword, 'UTF-8'))) {
                return true;
            }
        }

        return false;
    }

    private function matchesOutOfGauge(array $package, array $serviceData, array $rule): bool
    {
        if ($this->matchesAnyFlag($package['raw'], $serviceData, $rule['flag_keys'] ?? [])) {
            return true;
        }

        $longestThreshold = (float) ($rule['longest_side_threshold_cm'] ?? 0);
        $girthThreshold = (float) ($rule['girth_threshold_cm'] ?? 0);

        return ($longestThreshold > 0 && $package['max_side_cm'] > $longestThreshold)
            || ($girthThreshold > 0 && $package['secondary_side_sum_cm'] > $girthThreshold);
    }

    private function matchesRodsAndTubes(array $package, array $serviceData, array $rule): bool
    {
        if ($this->matchesAnyFlag($package['raw'], $serviceData, $rule['flag_keys'] ?? [])) {
            return true;
        }

        $minLongest = (float) ($rule['min_longest_side_cm'] ?? 0);
        $maxSecondary = (float) ($rule['max_secondary_side_cm'] ?? 0);

        return $package['max_side_cm'] >= $minLongest
            && $package['secondary_side_sum_cm'] > 0
            && $package['secondary_side_sum_cm'] <= ($maxSecondary * 2);
    }

    private function matchesAnyFlag(array $package, array $serviceData, array $flagKeys): bool
    {
        foreach ($flagKeys as $flagKey) {
            $snakeKey = trim((string) $flagKey);
            if ($snakeKey === '') {
                continue;
            }
            if (! empty($package[$snakeKey]) || ! empty($serviceData[$snakeKey])) {
                return true;
            }
        }

        return false;
    }

    // --- Data normalization helpers ---

    public function normalizePackages(array $packages): array
    {
        return array_values(array_filter(array_map(function ($package) {
            if ($package instanceof \App\Models\Package) {
                $package = [
                    'package_type' => $package->package_type,
                    'quantity' => $package->quantity,
                    'weight' => $package->weight,
                    'first_size' => $package->first_size,
                    'second_size' => $package->second_size,
                    'third_size' => $package->third_size,
                ];
            }

            if (! is_array($package)) {
                return null;
            }

            $first = (float) ($package['first_size'] ?? 0);
            $second = (float) ($package['second_size'] ?? 0);
            $third = (float) ($package['third_size'] ?? 0);

            return [
                'package_type' => trim((string) ($package['package_type'] ?? '')),
                'weight_kg' => max(0, (float) ($package['weight'] ?? 0)),
                'quantity' => max(1, (int) ($package['quantity'] ?? 1)),
                'first_size_cm' => max(0, $first),
                'second_size_cm' => max(0, $second),
                'third_size_cm' => max(0, $third),
                'max_side_cm' => max($first, $second, $third),
                'secondary_side_sum_cm' => $this->secondarySideSum([$first, $second, $third]),
                'raw' => $package,
            ];
        }, $packages), static fn ($p) => is_array($p)));
    }

    public function normalizeAddress(array $address): array
    {
        return [
            'country' => strtoupper(trim((string) ($address['country'] ?? $address['country_code'] ?? 'IT'))),
            'province' => strtoupper(trim((string) ($address['province'] ?? ''))),
            'city' => mb_strtolower(trim((string) ($address['city'] ?? '')), 'UTF-8'),
            'address' => mb_strtolower(trim((string) ($address['address'] ?? '')), 'UTF-8'),
            'additional_information' => mb_strtolower(trim((string) ($address['additional_information'] ?? '')), 'UTF-8'),
        ];
    }

    public function findTierPriceCents(float $weightKg, array $tiers): int
    {
        foreach ($tiers as $tier) {
            $limit = $tier['up_to_kg'] ?? null;
            if ($limit === null || $weightKg <= (float) $limit) {
                return (int) ($tier['price_cents'] ?? 0);
            }
        }

        return 0;
    }

    private function secondarySideSum(array $dimensions): float
    {
        rsort($dimensions);

        return (float) (($dimensions[1] ?? 0) + ($dimensions[2] ?? 0));
    }

    private function buildItem(string $key, array $rule, int $amountCents): array
    {
        return [
            'key' => $key,
            'label' => (string) ($rule['label'] ?? $key),
            'amount_cents' => max(0, $amountCents),
            'type' => 'automatic_supplement',
            'automatic' => true,
            'application' => (string) ($rule['application'] ?? ''),
        ];
    }
}
