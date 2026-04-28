<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PriceBandValidator
{
    private const EPSILON = 0.0000001;

    private const ALLOWED_APPLY_TO = ['origin', 'destination', 'both'];

    public function normalizeBands(array $bands, string $type, float $resolution): array
    {
        if (empty($bands)) {
            throw ValidationException::withMessages([
                sprintf('%s_bands', $type) => sprintf('Devi configurare almeno una fascia %s.', $type),
            ]);
        }

        $normalized = [];

        foreach (array_values($bands) as $idx => $band) {
            $minValue = self::normalizeDecimal($band['min_value'] ?? 0);
            $maxValue = self::normalizeDecimal($band['max_value'] ?? 0);
            $basePrice = (int) ($band['base_price'] ?? 0);
            $discountPrice = isset($band['discount_price']) && $band['discount_price'] !== '' ? (int) $band['discount_price'] : null;
            $showDiscount = isset($band['show_discount']) ? (bool) $band['show_discount'] : true;

            $normalized[] = [
                'id' => (string) ($band['id'] ?? sprintf('%s-%d', $type, $idx + 1)),
                'type' => $type,
                'min_value' => $minValue,
                'max_value' => $maxValue,
                'base_price' => $basePrice,
                'discount_price' => $discountPrice,
                'show_discount' => $showDiscount,
                'sort_order' => (int) ($band['sort_order'] ?? ($idx + 1)),
            ];
        }

        usort($normalized, function (array $a, array $b) {
            return $a['min_value'] === $b['min_value']
                ? $a['max_value'] <=> $b['max_value']
                : $a['min_value'] <=> $b['min_value'];
        });

        foreach ($normalized as $index => &$band) {
            $band['sort_order'] = $index + 1;
        }
        unset($band);

        $this->validateBandRanges($normalized, $type, $resolution);

        return $normalized;
    }

    public function normalizeExtraRules(array $rules, array $defaults): array
    {
        $merged = array_merge($defaults, $rules);

        $weightLadder = $this->normalizeIncrementLadder(
            is_array($merged['weight_increment_ladder'] ?? null) ? $merged['weight_increment_ladder'] : [],
            (int) ($merged['increment_cents'] ?? 500)
        );
        $volumeLadder = $this->normalizeIncrementLadder(
            is_array($merged['volume_increment_ladder'] ?? null) ? $merged['volume_increment_ladder'] : [],
            (int) ($merged['increment_cents'] ?? 500)
        );

        return [
            'enabled' => (bool) ($merged['enabled'] ?? true),
            'weight_start' => self::normalizeDecimal($merged['weight_start'] ?? 101),
            'weight_step' => self::normalizeDecimal($merged['weight_step'] ?? 50),
            'volume_start' => self::normalizeDecimal($merged['volume_start'] ?? 0.401),
            'volume_step' => self::normalizeDecimal($merged['volume_step'] ?? 0.200),
            'increment_cents' => (int) ($merged['increment_cents'] ?? 500),
            'increment_mode' => 'flat',
            'weight_increment_ladder' => $weightLadder,
            'volume_increment_ladder' => $volumeLadder,
            'base_price_cents_mode' => ($merged['base_price_cents_mode'] ?? 'last_band_effective') === 'manual' ? 'manual' : 'last_band_effective',
            'base_price_cents_manual' => isset($merged['base_price_cents_manual']) && $merged['base_price_cents_manual'] !== '' ? (int) $merged['base_price_cents_manual'] : null,
            'weight_resolution' => self::normalizeDecimal($merged['weight_resolution'] ?? 1),
            'volume_resolution' => self::normalizeDecimal($merged['volume_resolution'] ?? 0.001),
        ];
    }

    public function normalizeSupplements(array $supplements): array
    {
        if (empty($supplements)) {
            return [];
        }

        $normalized = [];
        foreach (array_values($supplements) as $idx => $rule) {
            $applyTo = (string) ($rule['apply_to'] ?? 'both');
            if (! in_array($applyTo, self::ALLOWED_APPLY_TO, true)) {
                $applyTo = 'both';
            }

            $normalized[] = [
                'id' => (string) ($rule['id'] ?? sprintf('supplement-%d', $idx + 1)),
                'prefix' => preg_replace('/\D+/', '', (string) ($rule['prefix'] ?? '')),
                'amount_cents' => (int) ($rule['amount_cents'] ?? 0),
                'apply_to' => $applyTo,
                'enabled' => isset($rule['enabled']) ? (bool) $rule['enabled'] : true,
            ];
        }

        return array_values(array_filter($normalized, function (array $rule) {
            return $rule['prefix'] !== '' && $rule['amount_cents'] >= 0;
        }));
    }

    public function validateExtraRulesAgainstBands(array $weightBands, array $volumeBands, array $extraRules): void
    {
        $errors = [];

        if ((float) ($extraRules['weight_step'] ?? 0) <= 0) {
            $errors['extra_rules.weight_step'] = 'weight_step deve essere > 0.';
        }
        if ((float) ($extraRules['volume_step'] ?? 0) <= 0) {
            $errors['extra_rules.volume_step'] = 'volume_step deve essere > 0.';
        }
        if ((float) ($extraRules['weight_resolution'] ?? 0) <= 0) {
            $errors['extra_rules.weight_resolution'] = 'weight_resolution deve essere > 0.';
        }
        if ((float) ($extraRules['volume_resolution'] ?? 0) <= 0) {
            $errors['extra_rules.volume_resolution'] = 'volume_resolution deve essere > 0.';
        }
        if ((int) ($extraRules['increment_cents'] ?? -1) < 0) {
            $errors['extra_rules.increment_cents'] = 'increment_cents non può essere negativo.';
        }

        $baseMode = (string) ($extraRules['base_price_cents_mode'] ?? 'last_band_effective');
        $baseManual = $extraRules['base_price_cents_manual'] ?? null;
        if ($baseMode === 'manual' && (! is_int($baseManual) || $baseManual < 0)) {
            $errors['extra_rules.base_price_cents_manual'] = 'base_price_cents_manual è obbligatorio e non negativo in modalità manual.';
        }

        $lastWeight = end($weightBands);
        $lastVolume = end($volumeBands);
        $lastWeightMax = $lastWeight !== false ? (float) $lastWeight['max_value'] : 0;
        $lastVolumeMax = $lastVolume !== false ? (float) $lastVolume['max_value'] : 0;

        $weightStart = (float) ($extraRules['weight_start'] ?? 0);
        $volumeStart = (float) ($extraRules['volume_start'] ?? 0);

        if ($weightStart <= ($lastWeightMax + self::EPSILON)) {
            $errors['extra_rules.weight_start'] = sprintf('weight_start deve essere oltre l\'ultima fascia peso (%s).', $lastWeightMax);
        }
        if ($volumeStart <= ($lastVolumeMax + self::EPSILON)) {
            $errors['extra_rules.volume_start'] = sprintf('volume_start deve essere oltre l\'ultima fascia volume (%s).', $lastVolumeMax);
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    public static function normalizeDecimal($value): float
    {
        return (float) number_format((float) $value, 4, '.', '');
    }

    private function validateBandRanges(array $normalized, string $type, float $resolution): void
    {
        $errors = [];
        $safeResolution = $resolution > 0 ? $resolution : ($type === 'weight' ? 1.0 : 0.001);

        foreach ($normalized as $idx => $band) {
            $rowNum = $idx + 1;
            $min = (float) $band['min_value'];
            $max = (float) $band['max_value'];
            $base = (int) $band['base_price'];
            $discount = $band['discount_price'] !== null ? (int) $band['discount_price'] : null;

            if ($min < 0 || $max <= 0) {
                $errors[sprintf('%s.%d.range', $type, $idx)] = sprintf('Fascia %s #%d: min/max devono essere positivi.', $type, $rowNum);
            }
            if ($max <= $min) {
                $errors[sprintf('%s.%d.max_value', $type, $idx)] = sprintf('Fascia %s #%d: max deve essere maggiore di min.', $type, $rowNum);
            }
            if ($base < 0) {
                $errors[sprintf('%s.%d.base_price', $type, $idx)] = sprintf('Fascia %s #%d: prezzo base non valido.', $type, $rowNum);
            }
            if ($discount !== null && $discount <= 0) {
                $errors[sprintf('%s.%d.discount_price', $type, $idx)] = sprintf('Fascia %s #%d: prezzo scontato deve essere maggiore di zero (usare null per nessuno sconto).', $type, $rowNum);
            }
            if ($discount !== null && $discount > $base) {
                $errors[sprintf('%s.%d.discount_price', $type, $idx)] = sprintf('Fascia %s #%d: prezzo scontato non può superare il prezzo base.', $type, $rowNum);
            }

            if ($idx === 0) {
                continue;
            }

            $prev = $normalized[$idx - 1];
            $prevMax = (float) $prev['max_value'];

            if ($min < ($prevMax - self::EPSILON)) {
                $errors[sprintf('%s.%d.overlap', $type, $idx)] = sprintf('Fascia %s #%d: range sovrapposto con la fascia precedente.', $type, $rowNum);
            }
            if ($min > ($prevMax + $safeResolution + self::EPSILON)) {
                $errors[sprintf('%s.%d.gap', $type, $idx)] = sprintf('Fascia %s #%d: gap incoerente con risoluzione %s.', $type, $rowNum, $safeResolution);
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function normalizeIncrementLadder(array $ladder, int $fallbackIncrement): array
    {
        $rows = [];
        foreach (array_values($ladder) as $idx => $row) {
            $from = max(1, (int) ($row['from_step'] ?? ($idx + 1)));
            $toRaw = $row['to_step'] ?? null;
            $to = ($toRaw === null || $toRaw === '') ? null : max($from, (int) $toRaw);
            $increment = max(0, (int) ($row['increment_cents'] ?? $fallbackIncrement));
            $rows[] = ['from_step' => $from, 'to_step' => $to, 'increment_cents' => $increment];
        }

        if (empty($rows)) {
            return [['from_step' => 1, 'to_step' => null, 'increment_cents' => max(0, $fallbackIncrement)]];
        }

        usort($rows, fn (array $a, array $b) => $a['from_step'] <=> $b['from_step']);
        $rows[count($rows) - 1]['to_step'] = null;

        return $rows;
    }
}
