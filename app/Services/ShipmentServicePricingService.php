<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\Pricing\AutomaticSupplementCalculator;
use App\Services\Pricing\PricingConfigNormalizer;

class ShipmentServicePricingService
{
    private const SETTINGS_KEY_CONFIG = 'pricing_service_rules';

    private const SETTINGS_KEY_VERSION = 'pricing_service_rules_version';

    private ?array $cachedConfig = null;

    private PricingConfigNormalizer $normalizer;

    private AutomaticSupplementCalculator $supplementCalculator;

    public function __construct(
        ?PricingConfigNormalizer $normalizer = null,
        ?AutomaticSupplementCalculator $supplementCalculator = null,
    ) {
        $this->normalizer = $normalizer ?? new PricingConfigNormalizer;
        $this->supplementCalculator = $supplementCalculator ?? new AutomaticSupplementCalculator;
    }

    public function getPricingConfig(): array
    {
        if ($this->cachedConfig !== null) {
            return $this->cachedConfig;
        }

        $raw = Setting::get(self::SETTINGS_KEY_CONFIG);
        $decoded = $raw ? json_decode($raw, true) : null;
        $normalized = $this->normalizer->normalize(
            is_array($decoded) ? $decoded : PricingConfigNormalizer::getDefaultConfig()
        );
        $normalized['version'] = Setting::get(self::SETTINGS_KEY_VERSION) ?: (string) time();

        $this->cachedConfig = $normalized;

        return $this->cachedConfig;
    }

    public function savePricingConfig(array $config): array
    {
        $normalized = $this->normalizer->normalize($config);
        $version = (string) time();

        Setting::set(self::SETTINGS_KEY_CONFIG, json_encode($normalized, JSON_UNESCAPED_UNICODE));
        Setting::set(self::SETTINGS_KEY_VERSION, $version);

        $this->cachedConfig = null;

        return $this->getPricingConfig();
    }

    public function invalidateLocalCache(): void
    {
        $this->cachedConfig = null;
    }

    public function calculateSurchargeBreakdown(
        array|string|null $serviceType = null,
        array $serviceData = [],
        bool $smsEmailNotification = false,
        array $context = [],
    ): array {
        $config = $this->getPricingConfig();
        $selected = $this->normalizeSelectedServices($serviceType);
        $items = [];

        $servicePricing = $config['service_pricing'] ?? [];

        if (in_array('senza_etichetta', $selected, true) && ($servicePricing['senza_etichetta']['enabled'] ?? false)) {
            $items[] = $this->buildFixedItem('senza_etichetta', $servicePricing['senza_etichetta'], (int) ($servicePricing['senza_etichetta']['price_cents'] ?? 0), false);
        }

        if (in_array('sponda_idraulica', $selected, true) && ($servicePricing['sponda_idraulica']['enabled'] ?? false)) {
            $items[] = $this->buildFixedItem('sponda_idraulica', $servicePricing['sponda_idraulica'], (int) ($servicePricing['sponda_idraulica']['price_cents'] ?? 0), false);
        }

        // Audit F16: consegna al piano acquistabile separatamente
        if (in_array('consegna_al_piano', $selected, true) && ($servicePricing['consegna_al_piano']['enabled'] ?? false)) {
            $items[] = $this->buildFixedItem('consegna_al_piano', $servicePricing['consegna_al_piano'], (int) ($servicePricing['consegna_al_piano']['price_cents'] ?? 0), false);
        }

        // Audit F16: consegna su appuntamento acquistabile separatamente
        if (in_array('consegna_appuntamento', $selected, true) && ($servicePricing['consegna_appuntamento']['enabled'] ?? false)) {
            $items[] = $this->buildFixedItem('consegna_appuntamento', $servicePricing['consegna_appuntamento'], (int) ($servicePricing['consegna_appuntamento']['price_cents'] ?? 0), false);
        }

        if (in_array('contrassegno', $selected, true) && ($servicePricing['contrassegno']['enabled'] ?? false)) {
            $amount = $this->extractContrassegnoAmount($serviceData);
            $fee = $this->calculateThresholdFeeCents($amount, $servicePricing['contrassegno']);
            if ($fee > 0) {
                $items[] = $this->buildFixedItem('contrassegno', $servicePricing['contrassegno'], $fee, false);
            }
        }

        if (in_array('assicurazione', $selected, true) && ($servicePricing['assicurazione']['enabled'] ?? false)) {
            $amount = $this->extractAssicurazioneAmount($serviceData);
            $fee = $this->calculateThresholdFeeCents($amount, $servicePricing['assicurazione']);
            if ($fee > 0) {
                $items[] = $this->buildFixedItem('assicurazione', $servicePricing['assicurazione'], $fee, false);
            }
        }

        $notificationsEnabled = $smsEmailNotification
            || (bool) ($serviceData['sms_email_notification'] ?? $serviceData['smsEmailNotification'] ?? false);

        if ($notificationsEnabled && ($servicePricing['notifications']['enabled'] ?? false)) {
            $items[] = $this->buildFixedItem('notifications', $servicePricing['notifications'], (int) ($servicePricing['notifications']['price_cents'] ?? 0), false);
        }

        $items = array_merge($items, $this->supplementCalculator->calculate($config['automatic_supplements'] ?? [], $serviceData, $context));

        $totalCents = array_sum(array_map(static fn (array $item) => (int) ($item['amount_cents'] ?? 0), $items));

        return [
            'total_cents' => (int) $totalCents,
            'items' => array_values($items),
        ];
    }

    public function calculateSurchargeCents(
        array|string|null $serviceType = null,
        array $serviceData = [],
        bool $smsEmailNotification = false,
        array $context = [],
    ): int {
        return (int) ($this->calculateSurchargeBreakdown($serviceType, $serviceData, $smsEmailNotification, $context)['total_cents'] ?? 0);
    }

    public function buildSelectionSignature(
        array|string|null $serviceType = null,
        array $serviceData = [],
        bool $smsEmailNotification = false,
    ): string {
        $payload = [
            'selected' => $this->normalizeSelectedServices($serviceType),
            'service_data' => $this->sortRecursive($serviceData),
            'sms_email_notification' => $smsEmailNotification
                || (bool) ($serviceData['sms_email_notification'] ?? $serviceData['smsEmailNotification'] ?? false),
        ];

        return md5(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function extractContrassegnoAmount(array $serviceData = []): float
    {
        $contrassegno = $serviceData['contrassegno'] ?? $serviceData['Contrassegno'] ?? [];

        return $this->parseCurrencyAmount($contrassegno['importo'] ?? null);
    }

    /**
     * Estrae la modalita' di rimborso contrassegno (bonifico/assegno/assegno_circolare)
     * e la converte in codice BRT: BM=bonifico, AS=assegno bancario, CC=assegno circolare.
     *
     * Audit F01: contrassegno acquistabile.
     *
     * @return string Uno tra: BM | AS | CC (default BM).
     */
    public function extractCodPaymentType(array $serviceData = []): string
    {
        $contrassegno = $serviceData['contrassegno'] ?? $serviceData['Contrassegno'] ?? [];
        $modalita = mb_strtolower(trim((string) ($contrassegno['modalita_rimborso'] ?? '')), 'UTF-8');

        return match ($modalita) {
            'assegno' => 'AS',
            'assegno_circolare' => 'CC',
            default => 'BM', // bonifico o vuoto
        };
    }

    /**
     * Estrae la modalita' di incasso (contanti|assegno): istruzione operativa
     * al corriere su cosa accettare dal destinatario. BRT non la richiede in API
     * ma la persistiamo per tracciabilita' e note operative.
     *
     * Audit F01.
     *
     * @return string|null Uno tra: contanti | assegno | null (se non specificato).
     */
    public function extractCodIncassoType(array $serviceData = []): ?string
    {
        $contrassegno = $serviceData['contrassegno'] ?? $serviceData['Contrassegno'] ?? [];
        $modalita = mb_strtolower(trim((string) ($contrassegno['modalita_incasso'] ?? '')), 'UTF-8');

        return in_array($modalita, ['contanti', 'assegno'], true) ? $modalita : null;
    }

    public function extractAssicurazioneAmount(array $serviceData = []): float
    {
        $assicurazione = $serviceData['assicurazione'] ?? $serviceData['Assicurazione'] ?? [];
        if (! is_array($assicurazione)) {
            return 0.0;
        }

        return array_reduce(
            array_values($assicurazione),
            fn (float $sum, mixed $value) => $sum + $this->parseCurrencyAmount($value),
            0.0
        );
    }

    public function normalizeSelectedServices(array|string|null $serviceType = null): array
    {
        $items = is_array($serviceType)
            ? $serviceType
            : explode(',', (string) ($serviceType ?? ''));

        $normalized = [];
        foreach ($items as $item) {
            $key = $this->normalizeServiceKey($item);
            if ($key !== '' && ! in_array($key, $normalized, true)) {
                $normalized[] = $key;
            }
        }

        return $normalized;
    }

    public function normalizeServiceKey(mixed $value): string
    {
        $raw = mb_strtolower(trim((string) ($value ?? '')), 'UTF-8');
        if ($raw === '' || $raw === 'nessuno') {
            return '';
        }

        $normalized = str_replace(
            ['à', 'è', 'é', 'ì', 'ò', 'ù'],
            ['a', 'e', 'e', 'i', 'o', 'u'],
            $raw
        );

        if (str_contains($normalized, 'senza') && str_contains($normalized, 'etichetta')) {
            return 'senza_etichetta';
        }
        if (str_contains($normalized, 'contrassegno')) {
            return 'contrassegno';
        }
        if (str_contains($normalized, 'assicurazione')) {
            return 'assicurazione';
        }
        if (str_contains($normalized, 'sponda')) {
            return 'sponda_idraulica';
        }
        if (str_contains($normalized, 'consegna') && str_contains($normalized, 'piano')) {
            return 'consegna_al_piano';
        }
        if (str_contains($normalized, 'appuntamento')) {
            return 'consegna_appuntamento';
        }
        if (str_contains($normalized, 'notifiche') || str_contains($normalized, 'sms')) {
            return 'sms_email_notification';
        }

        $sanitized = preg_replace('/[^a-z0-9]+/u', '_', $normalized) ?? '';

        return trim($sanitized, '_');
    }

    private function calculateThresholdFeeCents(float $amount, array $rule): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $threshold = (float) ($rule['threshold_amount_eur'] ?? 300);
        $minimum = (int) ($rule['min_fee_cents'] ?? 0);
        $percentageRate = (float) ($rule['percentage_rate'] ?? 0);

        if ($amount <= $threshold) {
            return max(0, $minimum);
        }

        return (int) round($amount * 100 * ($percentageRate / 100));
    }

    private function buildFixedItem(string $key, array $rule, int $amountCents, bool $automatic): array
    {
        return [
            'key' => $key,
            'label' => (string) ($rule['label'] ?? $key),
            'amount_cents' => max(0, $amountCents),
            'type' => $automatic ? 'automatic_supplement' : 'service',
            'automatic' => $automatic,
            'application' => (string) ($rule['application'] ?? ''),
        ];
    }

    private function parseCurrencyAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[€\s]/u', '', (string) $value) ?? '';
        $normalized = preg_replace('/\.(?=\d{3}(?:\D|$))/u', '', $normalized) ?? $normalized;
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function sortRecursive(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursive($item);
            }
        }

        ksort($value);

        return $value;
    }
}
