<?php

namespace App\Services\Brt;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class ShipmentService
{
    private BrtPayloadBuilder $payloadBuilder;

    public function __construct(
        private readonly BrtConfig $config,
        private readonly AddressNormalizer $addressNormalizer,
        private readonly ErrorTranslator $errorTranslator,
        ?BrtPayloadBuilder $payloadBuilder = null,
    ) {
        $this->payloadBuilder = $payloadBuilder ?? new BrtPayloadBuilder();
    }

    public function createShipment(Order $order, array $options = []): array
    {
        $order->loadMissing(['packages.originAddress', 'packages.destinationAddress', 'packages.service', 'user']);

        $package = $order->packages->first();
        if (!$package) return ['success' => false, 'error' => 'Nessun collo trovato nell\'ordine.'];

        $origin = $package->originAddress;
        $destination = $package->destinationAddress;
        if (!$origin || !$destination) return ['success' => false, 'error' => 'Indirizzi di partenza o destinazione mancanti.'];

        $totalWeight = $order->packages->sum(function ($pkg) {
            $weight = (float) preg_replace('/[^0-9.]/', '', $pkg->weight ?? '0');
            $quantity = max(1, (int) ($pkg->quantity ?? 1));

            return $weight * $quantity;
        });
        $totalParcels = $order->packages->sum(fn ($pkg) => max(1, (int) ($pkg->quantity ?? 1)));

        $missingOriginFields = $this->validateOrigin($origin);
        if (!empty($missingOriginFields)) {
            return ['success' => false, 'error' => 'Dati mittente mancanti per BRT: ' . implode(', ', $missingOriginFields) . '.'];
        }

        $missingFields = $this->validateDestination($destination);
        if (!empty($missingFields)) {
            return ['success' => false, 'error' => 'Dati mancanti per BRT: ' . implode(', ', $missingFields) . '.'];
        }

        $payloadErrors = $this->validatePayloadRequirements($totalParcels, $totalWeight);
        if (!empty($payloadErrors)) {
            return ['success' => false, 'error' => 'Dati spedizione non validi per BRT: ' . implode(', ', $payloadErrors) . '.'];
        }

        $dimensionErrors = $this->validatePackageDimensions($order->packages);
        if (!empty($dimensionErrors)) {
            return ['success' => false, 'error' => 'Dimensioni colli non valide per BRT: ' . implode(', ', $dimensionErrors) . '.'];
        }

        $normalizedDest = $this->addressNormalizer->normalizeAddressForBrt($destination);
        $normalizedOrigin = $this->addressNormalizer->normalizeAddressForBrt($origin);

        // Post-normalization: verify province abbreviations are exactly 2 chars.
        // AddressNormalizer may fall back to the raw value if it cannot resolve the province.
        if (strlen($normalizedOrigin['province']) !== 2) {
            return ['success' => false, 'error' => 'Provincia mittente non valida per BRT: "' . ($origin->province ?? '') . '" non riconosciuta come sigla provincia.'];
        }
        if (strlen($normalizedDest['province']) !== 2) {
            return ['success' => false, 'error' => 'Provincia destinatario non valida per BRT: "' . ($destination->province ?? '') . '" non riconosciuta come sigla provincia.'];
        }

        $departureDepot = FilialeLookup::resolveFilialeByCap($origin->postal_code ?? '')
            ?? $this->config->departureDepot;

        $payload = [
            'account' => $this->config->accountPayload(),
            'createData' => [
                'departureDepot' => $departureDepot,
                'senderCustomerCode' => (int) $this->config->clientId,
                'deliveryFreightTypeCode' => $options['delivery_freight_type'] ?? 'DAP',
                // Mittente
                'senderCompanyName' => $origin->name ?? '',
                'senderAddress' => trim(($origin->address ?? '') . ' ' . ($origin->address_number ?? '')),
                'senderZIPCode' => $normalizedOrigin['postal_code'],
                'senderCity' => $normalizedOrigin['city'],
                'senderProvinceAbbreviation' => $normalizedOrigin['province'],
                'senderCountryAbbreviationISOAlpha2' => $this->addressNormalizer->countryToIso2($origin->country ?? 'Italia'),
                'senderContactName' => $origin->name ?? '',
                'senderTelephone' => $origin->telephone_number ?? '',
                'senderEMail' => $origin->email ?? ($order->user?->email ?? ''),
                // Destinatario
                'consigneeCompanyName' => $destination->name ?? '',
                'consigneeAddress' => trim(($destination->address ?? '') . ' ' . ($destination->address_number ?? '')),
                'consigneeZIPCode' => $normalizedDest['postal_code'],
                'consigneeCity' => $normalizedDest['city'],
                'consigneeProvinceAbbreviation' => $normalizedDest['province'],
                'consigneeCountryAbbreviationISOAlpha2' => $this->addressNormalizer->countryToIso2($destination->country ?? 'Italia'),
                'consigneeContactName' => $destination->name ?? '',
                'consigneeTelephone' => $destination->telephone_number ?? '',
                'consigneeEMail' => $destination->email ?? ($order->user?->email ?? ''),
                'consigneeMobilePhoneNumber' => $destination->telephone_number ?? '',
                'numberOfParcels' => $totalParcels,
                'weightKG' => max(1, (int) ceil($totalWeight)),
                'numericSenderReference' => $order->id,
                'alphanumericSenderReference' => 'SF-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                'notes' => $this->payloadBuilder->buildNotes($order, $options),
                'isAlertRequired' => 1,
                'isCODMandatory' => 0,
            ],
            'isLabelRequired' => 1,
            'labelParameters' => BrtPayloadBuilder::defaultLabelParameters(),
        ];

        if (!empty($options['is_cod']) && !empty($options['cod_amount'])) {
            $payload['createData']['isCODMandatory'] = 1;
            $payload['createData']['cashOnDelivery'] = round((float) ($options['cod_amount'] / 100), 2);
            $payload['createData']['codPaymentType'] = $options['cod_payment_type'] ?? $order->cod_payment_type ?? 'BM';
            $payload['createData']['codCurrency'] = 'EUR';
        }

        if (!empty($options['pudo_id'])) {
            $payload['createData']['pudoId'] = $options['pudo_id'];
        }

        $this->payloadBuilder->addServicesToPayload($payload, $order, $options);

        if (empty($origin->telephone_number)) {
            Log::warning('BRT sender telephone missing, proceeding without it', ['order_id' => $order->id, 'origin_id' => $origin->id ?? null]);
        }

        $payload = BrtPayloadBuilder::sanitizeCreateData($payload);

        try {
            $payloadForLog = $payload;
            $payloadForLog['account']['password'] = '***';
            Log::info('BRT createShipment request', ['order_id' => $order->id, 'payload' => $payloadForLog]);

            $response = $this->config->shipmentClient()->post($this->config->apiUrl . '/shipment', $payload);
            $body = $response->json();
            $responseData = $body['createResponse'] ?? $body;

            Log::info('BRT createShipment response', ['order_id' => $order->id, 'http_status' => $response->status()]);

            if (!$response->successful()) {
                return ['success' => false, 'error' => $responseData['executionMessage']['message'] ?? 'Errore API BRT (HTTP ' . $response->status() . ')'];
            }

            $execCode = $responseData['executionMessage']['code'] ?? -1;
            if ($execCode < 0) {
                $errorMsg = $this->errorTranslator->translate($execCode, $responseData['executionMessage']['codeDesc'] ?? '', $responseData['executionMessage']['message'] ?? '', $payload['createData'] ?? []);
                Log::warning('BRT createShipment error response', ['order_id' => $order->id, 'exec_code' => $execCode]);
                return ['success' => false, 'error' => $errorMsg];
            }

            return $this->extractShipmentResult($body, $responseData, $order->id);
        } catch (\Exception $e) {
            Log::error('BRT createShipment exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Errore di connessione BRT: ' . $e->getMessage()];
        }
    }

    public function testCreateShipment(array $data): array
    {
        $built = $this->payloadBuilder->buildTestPayload($this->config, $this->addressNormalizer, $data);
        $payload = $built['payload'];

        try {
            Log::info('BRT TEST createShipment request', ['payload' => array_merge($payload, ['account' => ['userID' => $this->config->clientId, 'password' => '***']])]);
            $response = $this->config->shipmentClient()->post($this->config->apiUrl . '/shipment', $payload);
            $body = $response->json();

            $createResponse = $body['createResponse'] ?? $body;
            $execCode = $createResponse['executionMessage']['code'] ?? $body['executionMessage']['code'] ?? -1;

            if ($execCode < 0) {
                return ['success' => false, 'error' => $createResponse['executionMessage']['message'] ?? 'Errore BRT', 'exec_code' => $execCode, 'raw_response' => $body, 'payload_sent' => array_merge($payload, ['account' => ['userID' => $this->config->clientId, 'password' => '***']])];
            }

            $labels = $createResponse['labels']['label'] ?? $body['labels'] ?? [];
            $labelBase64 = '';
            $parcelId = '';
            if (!empty($labels) && is_array($labels) && ($first = $labels[0] ?? null)) {
                $parcelId = $first['parcelID'] ?? $first['parcelId'] ?? '';
                $labelBase64 = $first['stream'] ?? '';
            }

            return ['success' => true, 'parcel_id' => $parcelId, 'label_base64' => $labelBase64, 'tracking_url' => $parcelId ? 'https://www.brt.it/it/tracking?parcelId=' . urlencode($parcelId) : '', 'raw_response' => $body];
        } catch (\Exception $e) {
            Log::error('BRT TEST createShipment exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Errore connessione BRT: ' . $e->getMessage()];
        }
    }

    public function confirmShipment(int $numericSenderReference): array
    {
        $payload = ['account' => $this->config->accountPayload(), 'confirmData' => ['senderCustomerCode' => (int) $this->config->clientId, 'numericSenderReference' => $numericSenderReference]];
        try {
            $response = $this->config->shipmentClient()->put($this->config->apiUrl . '/shipment', $payload);
            $body = $response->json();
            Log::info('BRT confirmShipment response', ['reference' => $numericSenderReference]);
            $execCode = $body['executionMessage']['code'] ?? -1;
            if ($execCode < 0) return ['success' => false, 'error' => $body['executionMessage']['message'] ?? 'Errore conferma BRT.'];
            return ['success' => true, 'raw_response' => $body];
        } catch (\Exception $e) {
            Log::error('BRT confirmShipment exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteShipment(int $numericSenderReference): array
    {
        $payload = ['account' => $this->config->accountPayload(), 'deleteData' => ['senderCustomerCode' => (int) $this->config->clientId, 'numericSenderReference' => $numericSenderReference]];
        try {
            $response = $this->config->shipmentClient()->put($this->config->apiUrl . '/delete', $payload);
            $body = $response->json();
            $execCode = $body['executionMessage']['code'] ?? -1;
            return ['success' => $execCode >= 0, 'error' => $execCode < 0 ? ($body['executionMessage']['message'] ?? 'Errore') : null, 'raw_response' => $body];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function validatePayloadRequirements(int $totalParcels, float $totalWeight): array
    {
        $errors = [];
        if ($totalParcels < 1) {
            $errors[] = 'numero colli deve essere almeno 1';
        }
        if ($totalWeight <= 0) {
            $errors[] = 'peso totale deve essere maggiore di 0';
        }
        return $errors;
    }

    /**
     * Validates package dimensions and individual weights before sending to BRT.
     *
     * BRT requires positive dimensions for parcels included in pParcelID.
     * Missing or zero dimensions cause silent delivery routing failures.
     *
     * @param \Illuminate\Support\Collection $packages
     * @return array List of validation error strings (empty = valid)
     */
    private function validatePackageDimensions($packages): array
    {
        $errors = [];
        foreach ($packages as $index => $package) {
            $label = 'collo #' . ($index + 1);
            $weight = (float) preg_replace('/[^0-9.]/', '', $package->weight ?? '0');
            $length = (int) ($package->first_size ?? 0);
            $width = (int) ($package->second_size ?? 0);
            $height = (int) ($package->third_size ?? 0);

            if ($weight <= 0) {
                $errors[] = $label . ': peso deve essere maggiore di 0';
            }

            // Dimensions are optional (BRT accepts shipments without pParcelID),
            // but if ANY dimension is set, ALL three must be positive.
            $hasDimensions = $length > 0 || $width > 0 || $height > 0;
            if ($hasDimensions) {
                if ($length <= 0) {
                    $errors[] = $label . ': lunghezza deve essere maggiore di 0';
                }
                if ($width <= 0) {
                    $errors[] = $label . ': larghezza deve essere maggiore di 0';
                }
                if ($height <= 0) {
                    $errors[] = $label . ': altezza deve essere maggiore di 0';
                }
            }
        }
        return $errors;
    }

    private function validateOrigin($origin): array
    {
        $missing = [];
        if (empty(trim($origin->name ?? ''))) $missing[] = 'nome mittente';
        if (empty(trim(($origin->address ?? '') . ' ' . ($origin->address_number ?? '')))) $missing[] = 'indirizzo mittente';
        if (empty(trim($origin->postal_code ?? ''))) $missing[] = 'CAP mittente';
        if (empty(trim($origin->city ?? ''))) $missing[] = 'città mittente';
        if (empty(trim($origin->province ?? ''))) $missing[] = 'provincia mittente';
        return $missing;
    }

    private function validateDestination($destination): array
    {
        $missing = [];
        if (empty(trim($destination->name ?? ''))) $missing[] = 'nome destinatario';
        if (empty(trim(($destination->address ?? '') . ' ' . ($destination->address_number ?? '')))) $missing[] = 'indirizzo destinatario';
        if (empty(trim($destination->postal_code ?? ''))) $missing[] = 'CAP destinatario';
        if (empty(trim($destination->city ?? ''))) $missing[] = 'città destinatario';
        if (empty(trim($destination->province ?? ''))) $missing[] = 'provincia destinatario';
        return $missing;
    }

    private function extractShipmentResult(array $body, array $responseData, int $orderId): array
    {
        $parcelId = '';
        $labelBase64 = '';
        $allLabels = [];
        $labels = $responseData['labels']['label'] ?? $responseData['labels'] ?? [];

        if (!empty($labels) && is_array($labels)) {
            // Primo collo: usato come etichetta principale
            $firstLabel = $labels[0] ?? null;
            if ($firstLabel) {
                $parcelId = $firstLabel['parcelID'] ?? $firstLabel['parcelId'] ?? '';
                $labelBase64 = $firstLabel['stream'] ?? '';
            }

            // Multi-collo: salva tutte le etichette individualmente
            foreach ($labels as $index => $label) {
                $allLabels[] = [
                    'collo_index' => $index,
                    'parcel_id' => $label['parcelID'] ?? $label['parcelId'] ?? '',
                    'stream' => $label['stream'] ?? '',
                ];
            }
        }

        $parcelNumberFrom = (string) ($responseData['parcelNumberFrom'] ?? '');
        $trackingNumber = $parcelNumberFrom ?: $parcelId;
        $trackingUrl = $trackingNumber ? 'https://vas.brt.it/vas/sped_det_show.hsm?refnr=' . urlencode($trackingNumber) : '';

        Log::info('BRT createShipment tracking data extracted', [
            'order_id' => $orderId,
            'parcel_id' => $parcelId,
            'tracking_number' => $trackingNumber,
            'total_labels' => count($allLabels),
        ]);

        return [
            'success' => true,
            'parcel_id' => $parcelId,
            'numeric_sender_reference' => $orderId,
            'label_base64' => $labelBase64,
            'all_labels' => $allLabels,
            'tracking_url' => $trackingUrl,
            'tracking_number' => $trackingNumber,
            'parcel_number_from' => $parcelNumberFrom,
            'parcel_number_to' => (string) ($responseData['parcelNumberTo'] ?? ''),
            'departure_depot' => (string) ($responseData['departureDepot'] ?? ''),
            'arrival_terminal' => (string) ($responseData['arrivalTerminal'] ?? ''),
            'arrival_depot' => (string) ($responseData['arrivalDepot'] ?? ''),
            'delivery_zone' => (string) ($responseData['deliveryZone'] ?? ''),
            'series_number' => (string) ($responseData['seriesNumber'] ?? ''),
            'service_type' => (string) ($responseData['serviceType'] ?? ''),
            'raw_response' => $body,
        ];
    }
}
