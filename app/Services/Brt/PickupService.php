<?php
namespace App\Services\Brt;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class PickupService
{
    public function __construct(
        private readonly BrtConfig $config,
        private readonly AddressNormalizer $addressNormalizer,
    ) {}

    /**
     * Richiede il ritiro a domicilio a BRT per un ordine con etichetta generata.
     *
     * @param Order $order L'ordine con brt_parcel_id e indirizzi caricati
     * @param array $pickupRequest Dati ritiro: time_slot, notes, date
     * @return array {success, status, pickup_reference?, error?}
     */
    public function requestPickup(Order $order, array $pickupRequest): array
    {
        if (! $this->config->pickupEnabled || ! $this->config->pickupEndpoint) {
            Log::warning('BRT pickup API not configured; manual pickup required', [
                'order_id' => $order->id,
                'pickup_enabled' => $this->config->pickupEnabled,
                'has_pickup_endpoint' => (bool) $this->config->pickupEndpoint,
            ]);

            return [
                'success' => false,
                'status' => 'manual_required',
                'error' => 'Ritiro BRT da gestire manualmente: endpoint pickup non configurato sul contratto API.',
            ];
        }

        $order->loadMissing(['packages.originAddress', 'user']);

        $origin = $order->packages->first()?->originAddress;
        if (! $origin) {
            return [
                'success' => false,
                'status' => 'failed',
                'error' => 'Indirizzo di ritiro non disponibile.',
            ];
        }

        $pickupDate = $pickupRequest['date'] ?? now()->addWeekday()->format('Y-m-d');
        $timeSlot = $pickupRequest['time_slot'] ?? '09:00-18:00';
        $notes = $pickupRequest['notes'] ?? '';
        ['from' => $pickupTimeFrom, 'to' => $pickupTimeTo] = $this->normalizeTimeSlot($timeSlot);

        $totalParcels = $order->packages->sum(fn ($pkg) => max(1, (int) ($pkg->quantity ?? 1)));
        $totalWeight = $order->packages->sum(function ($pkg) {
            $weight = $this->normalizeWeightValue($pkg->weight ?? '0');
            $quantity = max(1, (int) ($pkg->quantity ?? 1));

            return $weight * $quantity;
        });

        // Normalize origin address using the same normalizer used by ShipmentService,
        // so BRT receives consistent, validated city/postal_code/province values.
        $normalizedOrigin = $this->addressNormalizer->normalizeAddressForBrt($origin);
        $pickupCountry = $this->addressNormalizer->countryToIso2($origin->country ?? 'Italia');

        $payload = [
            'account' => $this->config->accountPayload(),
            'pickupData' => [
                'senderCustomerCode' => (int) $this->config->clientId,
                'numericSenderReference' => $order->brt_numeric_sender_reference ?? $order->id,
                'pickupContactName' => $origin->name ?? '',
                'pickupCompanyName' => $origin->name ?? '',
                'pickupAddress' => trim(($origin->address ?? '') . ' ' . ($origin->address_number ?? '')),
                'pickupZIPCode' => $normalizedOrigin['postal_code'],
                'pickupCity' => $normalizedOrigin['city'],
                'pickupProvinceAbbreviation' => $normalizedOrigin['province'],
                'pickupCountryAbbreviationISOAlpha2' => $pickupCountry,
                'pickupContactPhone' => $origin->telephone_number ?? '',
                'pickupContactEMail' => $origin->email ?? ($order->user?->email ?? ''),
                'pickupDate' => $pickupDate,
                'pickupTimeSlotFrom' => $pickupTimeFrom,
                'pickupTimeSlotTo' => $pickupTimeTo,
                'numberOfParcels' => $totalParcels,
                'weightKG' => max(1, (int) ceil($totalWeight)),
                'pickupNotes' => mb_substr($notes, 0, 120),
            ],
        ];

        try {
            $payloadForLog = $payload;
            $payloadForLog['account']['password'] = '***';
            Log::info('BRT requestPickup request', [
                'order_id' => $order->id,
                'payload' => $payloadForLog,
            ]);

            [$response, $body, $resolvedUrl] = $this->sendPickupRequest($order, $payload);

            if (! $response->successful()) {
                $errorSource = $body['createResponse'] ?? $body;
                $errorMsg = $errorSource['executionMessage']['message']
                    ?? 'Errore API BRT ritiro (HTTP ' . $response->status() . ')';
                return [
                    'success' => false,
                    'status' => 'failed',
                    'error' => $errorMsg,
                ];
            }

            $responseData = $body['createResponse'] ?? $body;
            $execCode = $responseData['executionMessage']['code'] ?? -1;
            if ($execCode < 0) {
                $errorMsg = $responseData['executionMessage']['message']
                    ?? 'Errore richiesta ritiro BRT (code: ' . $execCode . ')';
                Log::warning('BRT requestPickup error response', [
                    'order_id' => $order->id,
                    'url' => $resolvedUrl,
                    'exec_code' => $execCode,
                    'message' => $errorMsg,
                ]);
                return [
                    'success' => false,
                    'status' => 'failed',
                    'error' => $errorMsg,
                ];
            }

            $pickupReference = $responseData['pickupConfirmationNumber']
                ?? $responseData['confirmationNumber']
                ?? $body['pickupConfirmationNumber']
                ?? $body['confirmationNumber']
                ?? ('PU-' . $order->id . '-' . now()->format('Ymd'));

            Log::info('BRT requestPickup success', [
                'order_id' => $order->id,
                'url' => $resolvedUrl,
                'pickup_reference' => $pickupReference,
            ]);

            return [
                'success' => true,
                'status' => 'requested',
                'pickup_reference' => $pickupReference,
            ];
        } catch (\Exception $e) {
            Log::error('BRT requestPickup exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'status' => 'failed',
                'error' => 'Errore di connessione BRT ritiro: ' . $e->getMessage(),
            ];
        }
    }

    private function sendPickupRequest(Order $order, array $payload): array
    {
        $url = (string) $this->config->pickupEndpoint;
        $response = $this->config->shipmentClient()->post($url, $payload);
        $body = $response->json();
        if (! is_array($body)) {
            $body = [];
        }

        Log::info('BRT requestPickup response', [
            'order_id' => $order->id,
            'url' => $url,
            'http_status' => $response->status(),
        ]);

        if ($response->status() === 404) {
            Log::warning('BRT requestPickup endpoint not found', [
                'order_id' => $order->id,
                'url' => $url,
            ]);
        }

        return [$response, $body, $url];
    }

    /**
     * Estrae l'orario di inizio o fine da una fascia oraria "HH:MM-HH:MM".
     */
    private function normalizeTimeSlot(string $timeSlot): array
    {
        $normalized = preg_replace('/\s+/', '', trim($timeSlot));
        if (preg_match('/^(?<from>\d{2}:\d{2})-(?<to>\d{2}:\d{2})$/', $normalized, $matches)) {
            return [
                'from' => $matches['from'],
                'to' => $matches['to'],
            ];
        }

        Log::warning('BRT pickup time slot malformed, using safe default', [
            'time_slot' => $timeSlot,
        ]);

        return [
            'from' => '09:00',
            'to' => '18:00',
        ];
    }

    private function normalizeWeightValue(mixed $rawWeight): float
    {
        $normalized = str_replace(',', '.', (string) $rawWeight);
        $normalized = preg_replace('/[^0-9.]/', '', $normalized) ?? '0';
        if ($normalized === '' || substr_count($normalized, '.') > 1) {
            return 0.0;
        }

        return (float) $normalized;
    }
}
