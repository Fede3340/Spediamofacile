<?php

namespace App\Services\Checkout;

/**
 * Helper privati per "compactare" snapshot di submission checkout.
 *
 * Estratto da CheckoutSubmissionContextService per ridurre il file principale
 * dalla soglia 614 a < 400 LOC, isolando la logica di normalizzazione/serializzazione
 * (address, service, pudo, monetary, flags) dalla logica di business (fingerprint,
 * enrich, snapshot orchestration).
 *
 * Tutti i metodi sono `private` (uso solo dalla classe host); il trait è interno.
 */
trait SnapshotCompactingHelpers
{
    private function compactAddressSignature(array $address, callable $normalize): string
    {
        return implode('|', [
            $normalize($address['name'] ?? $address['full_name'] ?? ''),
            $normalize($address['address'] ?? ''),
            $normalize($address['address_number'] ?? ''),
            $normalize($address['city'] ?? ''),
            $normalize($address['postal_code'] ?? $address['zip_code'] ?? ''),
            $normalize($address['province'] ?? ''),
        ]);
    }

    private function compactServiceData(array $serviceData): array
    {
        $payload = [];

        $payload['delivery_mode'] = (string) ($serviceData['delivery_mode'] ?? 'home');
        $payload['sms_email_notification'] = (bool) ($serviceData['sms_email_notification'] ?? false);
        $payload['requires_manual_quote'] = (bool) ($serviceData['requires_manual_quote'] ?? false);

        if (isset($serviceData['pudo']) && is_array($serviceData['pudo'])) {
            $payload['pudo'] = $this->compactPudo($serviceData['pudo']);
        }

        $contrassegno = $serviceData['contrassegno'] ?? $serviceData['Contrassegno'] ?? null;
        if (is_array($contrassegno)) {
            $payload['contrassegno'] = $this->compactMonetaryServiceSection($contrassegno, [
                'importo',
                'modalita_incasso',
                'modalita_rimborso',
                'dettaglio_rimborso',
            ]);
        }

        $assicurazione = $serviceData['assicurazione'] ?? $serviceData['Assicurazione'] ?? null;
        if (is_array($assicurazione)) {
            $payload['assicurazione'] = $this->compactMonetarySection($assicurazione);
        }

        $flags = $this->compactServiceFlags($serviceData);
        if ($flags !== []) {
            $payload['flags'] = $flags;
        }

        return $this->sortRecursive($payload);
    }

    private function compactMonetaryServiceSection(array $section, array $allowedKeys): array
    {
        $payload = [];

        foreach ($allowedKeys as $key) {
            if (! array_key_exists($key, $section)) {
                continue;
            }

            $value = $section[$key];
            $payload[$key] = $key === 'importo'
                ? $this->normalizeCurrencyToCents($value)
                : trim((string) $value);
        }

        return $this->sortRecursive($payload);
    }

    private function compactMonetarySection(array $section): array
    {
        $payload = [];

        foreach ($section as $key => $value) {
            $payload[$key] = is_array($value)
                ? $this->compactMonetarySection($value)
                : $this->normalizeCurrencyToCents($value);
        }

        return $this->sortRecursive($payload);
    }

    private function compactServiceFlags(array $serviceData): array
    {
        $flagKeys = [
            'fuori_sagoma',
            'out_of_gauge',
            'oversized',
            'lato_superiore_130cm',
            'aste_tubi',
            'tubi',
            'tubo',
            'rod_tube',
            // Audit F16: servizi acquistabili separatamente
            'consegna_al_piano',
            'consegna_appuntamento',
            'sponda_idraulica',
        ];

        $fromTopLevel = collect($flagKeys)
            ->filter(fn (string $key) => $this->hasMeaningfulServiceFlag($serviceData[$key] ?? null))
            ->values()
            ->all();

        $nestedFlags = [];
        if (! empty($serviceData['flags']) && is_array($serviceData['flags'])) {
            foreach ($serviceData['flags'] as $flag) {
                $key = (string) $flag;
                if (in_array($key, $flagKeys, true) && ! in_array($key, $fromTopLevel, true)) {
                    $nestedFlags[] = $key;
                }
            }
        }

        return array_values(array_unique(array_merge($fromTopLevel, $nestedFlags)));
    }

    private function hasMeaningfulServiceFlag(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $nested) {
                if ($this->hasMeaningfulServiceFlag($nested)) {
                    return true;
                }
            }

            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        return trim((string) $value) !== '';
    }

    private function normalizeCurrencyToCents(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) round(((float) $value) * 100);
        }

        $normalized = preg_replace('/[€\s]/u', '', (string) $value) ?? '';
        $normalized = preg_replace('/\.(?=\d{3}(?:\D|$))/u', '', $normalized) ?? $normalized;
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (int) round(((float) $normalized) * 100) : 0;
    }

    private function compactAddress(array $address): array
    {
        return [
            'name' => trim((string) ($address['name'] ?? $address['full_name'] ?? '')),
            'address' => trim((string) ($address['address'] ?? '')),
            'address_number' => trim((string) ($address['address_number'] ?? '')),
            'city' => trim((string) ($address['city'] ?? '')),
            'postal_code' => trim((string) ($address['postal_code'] ?? $address['zip_code'] ?? '')),
            'province' => trim((string) ($address['province'] ?? '')),
            'country' => trim((string) ($address['country'] ?? 'Italia')),
        ];
    }

    private function compactPudo(array $pudo): array
    {
        return [
            'pudo_id' => trim((string) ($pudo['pudo_id'] ?? '')),
            'name' => trim((string) ($pudo['name'] ?? '')),
            'address' => trim((string) ($pudo['address'] ?? '')),
            'city' => trim((string) ($pudo['city'] ?? '')),
            'zip_code' => trim((string) ($pudo['zip_code'] ?? $pudo['postal_code'] ?? '')),
        ];
    }

    private function normalizeServiceTypeList(string $serviceType): array
    {
        return collect(explode(',', $serviceType))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeDecimalAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $normalized = preg_replace('/[â‚¬\sEUR\xa0]/iu', '', (string) $value) ?? '';
        $normalized = preg_replace('/\.(?=\d{3}(?:\D|$))/u', '', $normalized) ?? $normalized;
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? round((float) $normalized, 2) : 0.0;
    }

    private function sortRecursive(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->sortRecursive($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->sortRecursive($item);
        }

        return $value;
    }
}
