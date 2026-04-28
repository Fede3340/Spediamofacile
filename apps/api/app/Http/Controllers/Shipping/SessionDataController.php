<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Traits\BuildsSessionPayload;
use App\Services\EuropePriceEngineService;
use App\Services\PriceEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class SessionDataController extends Controller
{
    use BuildsSessionPayload;

    public function __construct(
        private readonly PriceEngineService $priceEngine,
        private readonly EuropePriceEngineService $europePriceEngine,
    ) {
    }

    public static function findBandPrice(string $type, float $value): float
    {
        return app(PriceEngineService::class)->calculateBandPrice($type, $value);
    }

    public static function calculateCapSupplement(?string $originCap, ?string $destinationCap): float
    {
        return app(PriceEngineService::class)->calculateCapSupplement($originCap, $destinationCap);
    }

    public static function calculateCapSupplementCents(?string $originCap, ?string $destinationCap): int
    {
        return app(PriceEngineService::class)->calculateCapSupplementCents($originCap, $destinationCap);
    }

    public function firstStep(\App\Http\Requests\StoreSessionFirstStepRequest $request)
    {
        $validated = $request->validated();

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
            if (count($validated['packages']) !== 1) {
                throw ValidationException::withMessages([
                    'packages' => ["Le spedizioni verso l'Europa sono disponibili solo in modalità monocollo."],
                ]);
            }

            $firstPackage = $validated['packages'][0];
            $quantity = (int) ($firstPackage['quantity'] ?? 1);
            if ($quantity !== 1) {
                throw ValidationException::withMessages([
                    'packages.0.quantity' => ["Per le spedizioni verso l'Europa la quantita deve essere 1."],
                ]);
            }
        }

        $originCap = $shipmentDetails['origin_postal_code'] ?? null;
        $destCap = $shipmentDetails['destination_postal_code'] ?? null;
        $capSupplementCents = self::calculateCapSupplementCents($originCap, $destCap);

        $packages = collect($validated['packages'])->map(function (array $package) use ($capSupplementCents, $shipmentDetails, $isEuropeShipment) {
            $weight = (float) preg_replace('/[^0-9.]/', '', (string) ($package['weight'] ?? '0'));
            $s1 = (float) preg_replace('/[^0-9.]/', '', (string) ($package['first_size'] ?? '0'));
            $s2 = (float) preg_replace('/[^0-9.]/', '', (string) ($package['second_size'] ?? '0'));
            $s3 = (float) preg_replace('/[^0-9.]/', '', (string) ($package['third_size'] ?? '0'));
            $vol = ($s1 / 100) * ($s2 / 100) * ($s3 / 100);
            $quantity = (int) ($package['quantity'] ?? 1);

            if ($isEuropeShipment) {
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

            $weightPriceCents = $weight > 0 ? app(PriceEngineService::class)->calculateBandPriceCents('weight', $weight) : 0;
            $volumePriceCents = $vol > 0 ? app(PriceEngineService::class)->calculateBandPriceCents('volume', $vol) : 0;
            $package['weight_price'] = round($weightPriceCents / 100, 2);
            $package['volume_price'] = round($volumePriceCents / 100, 2);

            $basePriceCents = max($weightPriceCents, $volumePriceCents) + $capSupplementCents;
            $package['single_price'] = round(($basePriceCents / 100) * $quantity, 2);

            return $package;
        })->values()->all();

        $totalPrice = collect($packages)->sum(fn (array $package) => (float) $package['single_price']);

        session()->put('shipment_details', $shipmentDetails);
        session()->put('packages', $packages);
        session()->put('total_price', round($totalPrice, 2));
        session()->put('step', 2);
        $this->forgetDownstreamFlowState();

        return response()->json([
            'data' => $this->buildSessionPayload(),
        ]);
    }

    public function secondStep(\App\Http\Requests\StoreSessionSecondStepRequest $request)
    {
        $validated = $request->validated();

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

        session()->put('services', $services);
        session()->put('content_description', $contentDescription);
        session()->put('pickup_date', $pickupDate);
        session()->put('sms_email_notification', $smsEmailNotification);
        session()->put('service_data', $services['serviceData']);
        session()->put('delivery_mode', $deliveryMode);
        session()->put('selected_pudo', $selectedPudo ?: null);
        if ($clientSubmissionId !== '') {
            session()->put('client_submission_id', $clientSubmissionId);
        }

        if (array_key_exists('packages', $validated)) {
            session()->put('packages', $this->normalizePackagesPayload($validated['packages']));
        }

        if ($originAddress !== null) {
            session()->put('origin_address', $originAddress);
        }

        if ($destinationAddress !== null) {
            session()->put('destination_address', $destinationAddress);
        }

        $flowState = $this->buildFlowState();
        session()->put('step', $flowState['summary_ready'] ? 4 : 3);

        return response()->json([
            'data' => $this->buildSessionPayload(),
        ]);
    }

    private function normalizeAddressPayload(mixed $address): ?array
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

    private function normalizePackagesPayload(array $packages): array
    {
        return collect($packages)
            ->map(function (array $package): array {
                return [
                    ...$package,
                    'package_type' => trim((string) ($package['package_type'] ?? '')),
                    'quantity' => (int) ($package['quantity'] ?? 0),
                    'weight' => trim((string) ($package['weight'] ?? '')),
                    'first_size' => trim((string) ($package['first_size'] ?? '')),
                    'second_size' => trim((string) ($package['second_size'] ?? '')),
                    'third_size' => trim((string) ($package['third_size'] ?? '')),
                ];
            })
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

    private function forgetDownstreamFlowState(): void
    {
        session()->forget([
            'client_submission_id',
            'pricing_signature',
            'pricing_snapshot_version',
            'pricing_snapshot',
            'services',
            'content_description',
            'pickup_date',
            'sms_email_notification',
            'service_data',
            'origin_address',
            'destination_address',
            'delivery_mode',
            'selected_pudo',
        ]);
    }

    /**
     * Mostra lo stato corrente della sessione preventivo (post-rewrite v2).
     * Mantiene la struttura "data.*" per compat con test esistenti.
     */
    public function show(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'data' => [
                'shipment_details' => session('shipment_details'),
                'packages' => session('packages'),
                'total_price' => session('total_price'),
                'step' => session('step'),
                'client_submission_id' => session('client_submission_id'),
                'pricing_signature' => session('pricing_signature'),
                'pricing_snapshot_version' => session('pricing_snapshot_version'),
                'pricing_snapshot' => session('pricing_snapshot'),
                'services' => session('services'),
                'content_description' => session('content_description'),
                'pickup_date' => session('pickup_date'),
                'sms_email_notification' => session('sms_email_notification'),
                'service_data' => session('service_data'),
                'origin_address' => session('origin_address'),
                'destination_address' => session('destination_address'),
                'delivery_mode' => session('delivery_mode'),
                'selected_pudo' => session('selected_pudo'),
            ],
            'authenticated' => $request->user() !== null,
            'user' => $request->user(),
        ]);
    }

    /**
     * Reset COMPLETO della sessione preventivo. Da chiamare dopo che
     * il pagamento e' andato a buon fine: cosi' un nuovo preventivo
     * parte completamente pulito senza dati dell'ordine precedente.
     */
    public function reset()
    {
        session()->forget([
            'shipment_details',
            'packages',
            'total_price',
            'step',
            'client_submission_id',
            'pricing_signature',
            'pricing_snapshot_version',
            'pricing_snapshot',
            'services',
            'content_description',
            'pickup_date',
            'sms_email_notification',
            'service_data',
            'origin_address',
            'destination_address',
            'delivery_mode',
            'selected_pudo',
        ]);

        return response()->json(['success' => true]);
    }
}
