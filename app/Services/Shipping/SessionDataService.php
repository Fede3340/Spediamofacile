<?php

namespace App\Services\Shipping;

use App\Services\EuropePriceEngineService;
use App\Services\PriceEngineService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * Logica di business per la gestione della sessione preventivo (step 1 + 2).
 * Fa: validazione cross-field, calcolo pricing pacchi, normalizzazione payload
 * indirizzi/pacchi/ritiro. Non tocca la sessione direttamente: il controller
 * orchestrerà session()->put() coi risultati di questo service.
 */
class SessionDataService
{
    public function __construct(
        private readonly PriceEngineService $priceEngine,
        private readonly EuropePriceEngineService $europePriceEngine,
    ) {}

    /**
     * Normalizza shipment_details (paesi/codici) e applica regole cross-field.
     *
     * @return array{shipment_details: array, is_europe: bool}
     */
    public function prepareFirstStep(array $validated): array
    {
        $shipmentDetails = $validated['shipment_details'];
        $shipmentDetails['origin_country_code'] = strtoupper(trim((string) ($shipmentDetails['origin_country_code'] ?? 'IT')));
        $shipmentDetails['destination_country_code'] = strtoupper(trim((string) ($shipmentDetails['destination_country_code'] ?? 'IT')));
        $shipmentDetails['origin_country'] = trim((string) ($shipmentDetails['origin_country'] ?? 'Italia'));
        $shipmentDetails['destination_country'] = trim((string) ($shipmentDetails['destination_country'] ?? 'Italia'));

        $isOriginItaly = $shipmentDetails['origin_country_code'] === 'IT';
        $isEuropeShipment = $this->europePriceEngine->isEuropeDestination($shipmentDetails['destination_country_code']);

        if ($isOriginItaly && blank($shipmentDetails['origin_postal_code'] ?? null)) {
            throw ValidationException::withMessages([
                'shipment_details.origin_postal_code' => ['Il CAP di partenza è obbligatorio per le spedizioni con origine nazionale.'],
            ]);
        }

        if (! $isEuropeShipment && blank($shipmentDetails['destination_postal_code'] ?? null)) {
            throw ValidationException::withMessages([
                'shipment_details.destination_postal_code' => ['Il CAP di destinazione è obbligatorio per le spedizioni nazionali.'],
            ]);
        }

        if ($isEuropeShipment) {
            $packages = $validated['packages'] ?? [];
            if (count($packages) !== 1) {
                throw ValidationException::withMessages([
                    'packages' => ["Le spedizioni verso l'Europa sono disponibili solo in modalità monocollo."],
                ]);
            }

            $quantity = (int) ($packages[0]['quantity'] ?? 1);
            if ($quantity !== 1) {
                throw ValidationException::withMessages([
                    'packages.0.quantity' => ["Per le spedizioni verso l'Europa la quantita deve essere 1."],
                ]);
            }
        }

        return [
            'shipment_details' => $shipmentDetails,
            'is_europe' => $isEuropeShipment,
        ];
    }

    /**
     * Calcola il pricing per ciascun package (peso/volume + supplemento CAP)
     * o usa l'engine Europe per spedizioni internazionali.
     *
     * @return array{packages: array, total_price: float}
     */
    public function calculatePackagesPricing(array $packages, array $shipmentDetails, bool $isEuropeShipment): array
    {
        $originCap = $shipmentDetails['origin_postal_code'] ?? null;
        $destCap = $shipmentDetails['destination_postal_code'] ?? null;
        $capSupplementCents = $this->priceEngine->calculateCapSupplementCents($originCap, $destCap);

        $priced = collect($packages)->map(function (array $package) use ($capSupplementCents, $shipmentDetails, $isEuropeShipment) {
            $weight = (float) preg_replace('/[^0-9.]/', '', (string) ($package['weight'] ?? '0'));
            $s1 = (float) preg_replace('/[^0-9.]/', '', (string) ($package['first_size'] ?? '0'));
            $s2 = (float) preg_replace('/[^0-9.]/', '', (string) ($package['second_size'] ?? '0'));
            $s3 = (float) preg_replace('/[^0-9.]/', '', (string) ($package['third_size'] ?? '0'));
            $vol = ($s1 / 100) * ($s2 / 100) * ($s3 / 100);
            $quantity = (int) ($package['quantity'] ?? 1);

            if ($isEuropeShipment) {
                return $this->priceEuropePackage($package, $weight, $vol, $shipmentDetails);
            }

            $weightPriceCents = $weight > 0 ? $this->priceEngine->calculateBandPriceCents('weight', $weight) : 0;
            $volumePriceCents = $vol > 0 ? $this->priceEngine->calculateBandPriceCents('volume', $vol) : 0;
            $package['weight_price'] = round($weightPriceCents / 100, 2);
            $package['volume_price'] = round($volumePriceCents / 100, 2);

            $basePriceCents = max($weightPriceCents, $volumePriceCents) + $capSupplementCents;
            $package['single_price'] = round(($basePriceCents / 100) * $quantity, 2);

            return $package;
        })->values()->all();

        $totalPrice = collect($priced)->sum(fn (array $package) => (float) $package['single_price']);

        return [
            'packages' => $priced,
            'total_price' => round($totalPrice, 2),
        ];
    }

    private function priceEuropePackage(array $package, float $weight, float $vol, array $shipmentDetails): array
    {
        $quote = $this->europePriceEngine->calculateQuote(
            $shipmentDetails['destination_country_code'] ?? null,
            $weight,
            $vol,
        );

        if (($quote['status'] ?? null) === 'requires_quote') {
            throw ValidationException::withMessages([
                'packages' => [$quote['message'] ?? 'Per questa spedizione europea serve un preventivo manuale.'],
            ]);
        }

        if (($quote['status'] ?? null) !== 'priced') {
            throw ValidationException::withMessages([
                'packages' => [$quote['message'] ?? 'Destinazione europea non supportata dal listino attuale.'],
            ]);
        }

        $singlePrice = round(((int) $quote['price_cents']) / 100, 2);
        $package['quantity'] = 1;
        $package['weight_price'] = $singlePrice;
        $package['volume_price'] = $singlePrice;
        $package['single_price'] = $singlePrice;
        $package['single_price_orig'] = $singlePrice;
        $package['pricing_scope'] = 'europe_monocollo';
        $package['europe_band'] = $quote['band']['label'] ?? null;
        $package['europe_rate_country'] = $quote['rate']['country_name'] ?? null;

        return $package;
    }

    /**
     * Costruisce il payload `services` da salvare in sessione (step 2).
     */
    public function prepareSecondStep(array $validated): array
    {
        $contentDescription = trim((string) ($validated['content_description'] ?? ''));
        $pickupDate = trim((string) ($validated['pickup_date'] ?? ''));
        $smsEmailNotification = (bool) ($validated['sms_email_notification'] ?? false);
        $serviceData = Arr::wrap($validated['services']['serviceData'] ?? []);
        $originAddress = $this->normalizeAddressPayload($validated['origin_address'] ?? null);
        $destinationAddress = $this->normalizeAddressPayload($validated['destination_address'] ?? null);
        $deliveryMode = (string) ($validated['delivery_mode'] ?? 'home');
        $selectedPudo = Arr::wrap($validated['selected_pudo'] ?? null);
        $clientSubmissionId = trim((string) ($validated['client_submission_id'] ?? ''));
        $pickupRequest = $this->normalizePickupRequest(
            Arr::wrap($serviceData['pickup_request'] ?? []),
            $pickupDate,
            trim((string) ($validated['services']['time'] ?? ''))
        );

        $services = [
            'service_type' => trim((string) ($validated['services']['service_type'] ?? '')),
            'date' => trim((string) ($validated['services']['date'] ?? $pickupDate)),
            'time' => $pickupRequest['time_slot'],
            'serviceData' => [
                ...$serviceData,
                'pickup_request' => $pickupRequest,
                'sms_email_notification' => $smsEmailNotification,
            ],
            'sms_email_notification' => $smsEmailNotification,
        ];

        return [
            'services' => $services,
            'content_description' => $contentDescription,
            'pickup_date' => $pickupDate,
            'sms_email_notification' => $smsEmailNotification,
            'delivery_mode' => $deliveryMode,
            'selected_pudo' => $selectedPudo ?: null,
            'origin_address' => $originAddress,
            'destination_address' => $destinationAddress,
            'client_submission_id' => $clientSubmissionId !== '' ? $clientSubmissionId : null,
            'packages' => array_key_exists('packages', $validated)
                ? $this->normalizePackagesPayload($validated['packages'])
                : null,
        ];
    }

    public function normalizeAddressPayload(mixed $address): ?array
    {
        if (! is_array($address) || empty($address)) {
            return null;
        }

        return [
            'type' => trim((string) ($address['type'] ?? '')),
            'name' => trim((string) ($address['name'] ?? '')),
            'additional_information' => trim((string) ($address['additional_information'] ?? '')),
            'address' => trim((string) ($address['address'] ?? '')),
            'number_type' => trim((string) ($address['number_type'] ?? 'Numero Civico')),
            'address_number' => trim((string) ($address['address_number'] ?? '')),
            'intercom_code' => trim((string) ($address['intercom_code'] ?? '')),
            'country' => trim((string) ($address['country'] ?? 'Italia')),
            'city' => trim((string) ($address['city'] ?? '')),
            'postal_code' => trim((string) ($address['postal_code'] ?? '')),
            'province' => trim((string) ($address['province'] ?? '')),
            'telephone_number' => trim((string) ($address['telephone_number'] ?? '')),
            'email' => trim((string) ($address['email'] ?? '')),
        ];
    }

    public function normalizePackagesPayload(array $packages): array
    {
        return collect($packages)
            ->map(fn (array $package): array => [
                ...$package,
                'package_type' => trim((string) ($package['package_type'] ?? '')),
                'quantity' => (int) ($package['quantity'] ?? 0),
                'weight' => trim((string) ($package['weight'] ?? '')),
                'first_size' => trim((string) ($package['first_size'] ?? '')),
                'second_size' => trim((string) ($package['second_size'] ?? '')),
                'third_size' => trim((string) ($package['third_size'] ?? '')),
            ])
            ->values()
            ->all();
    }

    private function normalizePickupRequest(array $pickupRequest, string $pickupDate, string $pickupTime): array
    {
        $resolvedDate = trim((string) ($pickupRequest['date'] ?? $pickupDate));
        $resolvedTime = trim((string) ($pickupRequest['time_slot'] ?? $pickupTime));

        return [
            'enabled' => (bool) ($pickupRequest['enabled'] ?? ($resolvedDate !== '')),
            'date' => $this->normalizePickupRequestDate($resolvedDate),
            'time_slot' => $resolvedTime !== '' ? $resolvedTime : '09:00-18:00',
            'notes' => trim((string) ($pickupRequest['notes'] ?? '')),
        ];
    }

    private function normalizePickupRequestDate(string $pickupDate): string
    {
        $pickupDate = trim($pickupDate);
        if ($pickupDate === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickupDate)) {
            return $pickupDate;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $pickupDate, $matches)) {
            return sprintf('%s-%02d-%02d', $matches[3], (int) $matches[2], (int) $matches[1]);
        }

        return $pickupDate;
    }
}
