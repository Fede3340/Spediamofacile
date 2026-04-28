<?php

namespace App\Services\Brt;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class BrtPayloadBuilder
{
    private const SERVICE_MAPPING = [
        // Audit F16: allineati ai service_type strutturati del FE (snake_case)
        'consegna_al_piano'       => ['field' => 'particularitiesDeliveryManagement', 'value' => 'CP'],
        'consegna al piano'       => ['field' => 'particularitiesDeliveryManagement', 'value' => 'CP'],
        'delivery al piano'       => ['field' => 'particularitiesDeliveryManagement', 'value' => 'CP'],
        'ritiro_al_piano'         => ['field' => 'particularitiesPickupManagement', 'value' => 'RP'],
        'ritiro al piano'         => ['field' => 'particularitiesPickupManagement', 'value' => 'RP'],
        'pickup al piano'         => ['field' => 'particularitiesPickupManagement', 'value' => 'RP'],
        'sponda_idraulica'        => ['field' => 'particularitiesDeliveryManagement', 'value' => 'SU'],
        'sponda idraulica'        => ['field' => 'particularitiesDeliveryManagement', 'value' => 'SU'],
        'sponda idraulica ritiro' => ['field' => 'particularitiesPickupManagement', 'value' => 'SU'],
        'consegna_appuntamento'   => ['field' => 'particularitiesDeliveryManagement', 'value' => 'AP'],
        'consegna su appuntamento'=> ['field' => 'particularitiesDeliveryManagement', 'value' => 'AP'],
        'giacenza'                => ['field' => 'particularitiesDeliveryManagement', 'value' => 'GI'],
        'express'                 => ['field' => 'serviceType', 'value' => 'E'],
        'priority'                => ['field' => 'serviceType', 'value' => 'P'],
        '10:30'                   => ['field' => 'serviceType', 'value' => 'O'],
        'economy'                 => ['field' => 'serviceType', 'value' => 'N'],
    ];

    public function addServicesToPayload(array &$payload, Order $order, array $options): void
    {
        $appliedServices = [];

        // Accumulate delivery/pickup management codes separately.
        // BRT accepts multiple codes joined with a space (e.g. "CP SU").
        // Previously only the first code was kept; now ALL active codes are sent.
        $deliveryCodes = [];
        $pickupCodes = [];

        foreach ($order->packages as $package) {
            if (! $package->service) {
                continue;
            }

            // Audit F16: service_type puo' essere una lista csv (es. "contrassegno, sponda_idraulica").
            // Esplodiamo e normalizziamo ogni token prima del lookup SERVICE_MAPPING.
            $tokens = [];
            if (! empty($package->service->service_type)) {
                foreach (explode(',', (string) $package->service->service_type) as $raw) {
                    $token = mb_strtolower(trim($raw), 'UTF-8');
                    if ($token !== '' && $token !== 'nessuno') {
                        $tokens[] = $token;
                    }
                }
            }

            // Audit F16: anche i flag in service_data.flags[] vengono mappati.
            $serviceDataFlags = is_array($package->service->service_data ?? null)
                ? ($package->service->service_data['flags'] ?? [])
                : [];
            if (is_array($serviceDataFlags)) {
                foreach ($serviceDataFlags as $flag) {
                    $token = mb_strtolower(trim((string) $flag), 'UTF-8');
                    if ($token !== '' && ! in_array($token, $tokens, true)) {
                        $tokens[] = $token;
                    }
                }
            }

            foreach ($tokens as $serviceType) {
                if (! isset(self::SERVICE_MAPPING[$serviceType])) {
                    Log::info('BRT service not mapped', [
                        'order_id' => $order->id,
                        'service_type' => $serviceType,
                    ]);
                    continue;
                }

                $mapping = self::SERVICE_MAPPING[$serviceType];

                if ($mapping['field'] === 'particularitiesDeliveryManagement') {
                    if (! in_array($mapping['value'], $deliveryCodes, true)) {
                        $deliveryCodes[] = $mapping['value'];
                        $appliedServices[] = ['app_service' => $serviceType, 'brt_field' => $mapping['field'], 'brt_value' => $mapping['value']];
                    }
                } elseif ($mapping['field'] === 'particularitiesPickupManagement') {
                    if (! in_array($mapping['value'], $pickupCodes, true)) {
                        $pickupCodes[] = $mapping['value'];
                        $appliedServices[] = ['app_service' => $serviceType, 'brt_field' => $mapping['field'], 'brt_value' => $mapping['value']];
                    }
                } else {
                    // Other scalar fields (e.g. serviceType): first-write wins
                    if (! isset($payload['createData'][$mapping['field']])) {
                        $payload['createData'][$mapping['field']] = $mapping['value'];
                        $appliedServices[] = ['app_service' => $serviceType, 'brt_field' => $mapping['field'], 'brt_value' => $mapping['value']];
                    }
                }
            }
        }

        if (!empty($options['insurance_amount'])) {
            $payload['createData']['insuranceAmount'] = round((float) ($options['insurance_amount'] / 100), 2);
            $payload['createData']['insuranceCurrency'] = 'EUR';
            $appliedServices[] = ['app_service' => 'assicurazione', 'brt_field' => 'insuranceAmount', 'brt_value' => $payload['createData']['insuranceAmount']];
        }

        if (!empty($options['delivery_appointment'])) {
            $payload['createData']['isAlertRequired'] = 1;
            if (!in_array('AP', $deliveryCodes, true)) {
                $deliveryCodes[] = 'AP';
            }
            $appliedServices[] = ['app_service' => 'appuntamento_consegna', 'brt_field' => 'particularitiesDeliveryManagement', 'brt_value' => 'AP'];
        }

        // Assign accumulated management codes to payload (all codes joined with a space)
        if (!empty($deliveryCodes)) {
            $payload['createData']['particularitiesDeliveryManagement'] = implode(' ', $deliveryCodes);
        }
        if (!empty($pickupCodes)) {
            $payload['createData']['particularitiesPickupManagement'] = implode(' ', $pickupCodes);
        }

        if (!empty($options['no_label'])) {
            $payload['isLabelRequired'] = 0;
            $appliedServices[] = ['app_service' => 'senza_etichetta', 'brt_field' => 'isLabelRequired', 'brt_value' => 0];
        }

        $parcelsWithDimensions = [];
        foreach ($order->packages as $package) {
            $l = (int) ($package->first_size ?? 0);
            $w = (int) ($package->second_size ?? 0);
            $h = (int) ($package->third_size ?? 0);
            if ($l > 0 && $w > 0 && $h > 0) {
                $qty = max(1, (int) ($package->quantity ?? 1));
                for ($i = 0; $i < $qty; $i++) {
                    $parcelsWithDimensions[] = [
                        'lengthInCm' => $l, 'heightInCm' => $h, 'widthInCm' => $w,
                        'weightInKg' => max(1, (int) ceil((float) preg_replace('/[^0-9.]/', '', $package->weight ?? '1'))),
                    ];
                }
            }
        }

        // NOTE: campo "pParcelID" rimosso dal payload: BRT REST API rigetta con
        // errore -68 "Unrecognized field". Dimensioni colli opzionali per label
        // base. Se servono, scoprire il nome corretto dalla doc BRT REST 3.x
        // (candidati: "parcels", "parcelsData", "parcelDetails") e testare.
        // if (!empty($parcelsWithDimensions)) {
        //     $payload['createData']['pParcelID'] = $parcelsWithDimensions;
        //     $appliedServices[] = ['app_service' => 'dimensioni_colli', 'brt_field' => 'pParcelID', 'brt_value' => count($parcelsWithDimensions) . ' colli'];
        // }

        if (!empty($appliedServices)) {
            Log::info('BRT services applied to shipment', ['order_id' => $order->id, 'services' => $appliedServices]);
        }
    }

    public function buildNotes(Order $order, array $options): string
    {
        if (!empty($options['notes'])) return $options['notes'];

        $notes = 'SpediamoFacile ordine #' . $order->id;
        $descriptions = $order->packages->pluck('content_description')->filter()->unique()->implode(', ');
        if ($descriptions) $notes .= ' - Contenuto: ' . $descriptions;

        return mb_substr($notes, 0, 120);
    }

    public function buildTestPayload(BrtConfig $config, AddressNormalizer $normalizer, array $data): array
    {
        $numericSenderReference = (int) (time() % 1000000000);
        $testAddress = (object) [
            'city' => $data['consignee_city'] ?? '',
            'postal_code' => $data['consignee_zip'] ?? '',
            'province' => $data['consignee_province'] ?? '',
        ];
        $normalizedTest = $normalizer->normalizeAddressForBrt($testAddress);

        $senderZip = $data['sender_zip'] ?? '';
        $departureDepot = FilialeLookup::resolveFilialeByCap($senderZip)
            ?? $config->departureDepot;

        $createData = [
            'departureDepot' => $departureDepot,
            'senderCustomerCode' => (int) $config->clientId,
            'deliveryFreightTypeCode' => 'DAP',
            // Mittente (opzionale nei test, ma utile per validazione completa)
            'senderCompanyName' => $data['sender_name'] ?? '',
            'senderAddress' => $data['sender_address'] ?? '',
            'senderZIPCode' => $senderZip,
            'senderCity' => $data['sender_city'] ?? '',
            'senderProvinceAbbreviation' => $data['sender_province'] ?? '',
            'senderCountryAbbreviationISOAlpha2' => $data['sender_country'] ?? 'IT',
            // Destinatario
            'consigneeCompanyName' => $data['consignee_name'],
            'consigneeAddress' => $data['consignee_address'],
            'consigneeZIPCode' => $normalizedTest['postal_code'],
            'consigneeCity' => $normalizedTest['city'],
            'consigneeProvinceAbbreviation' => $normalizedTest['province'],
            'consigneeCountryAbbreviationISOAlpha2' => $data['consignee_country'],
            'consigneeContactName' => $data['consignee_name'],
            'consigneeTelephone' => $data['consignee_phone'] ?? '',
            'consigneeEMail' => $data['consignee_email'] ?? '',
            'consigneeMobilePhoneNumber' => $data['consignee_phone'] ?? '',
            'numberOfParcels' => (int) ($data['parcels'] ?? 1),
            'weightKG' => max(1, (int) ($data['weight_kg'] ?? 1)),
            'numericSenderReference' => $numericSenderReference,
            'alphanumericSenderReference' => 'TEST-' . $numericSenderReference,
            'notes' => $data['notes'] ?? 'Test SpediamoFacile',
            'isAlertRequired' => 1,
            'isCODMandatory' => !empty($data['is_cod']) ? 1 : 0,
        ];

        if (!empty($data['is_cod']) && !empty($data['cod_amount'])) {
            $createData['cashOnDelivery'] = round((float) ($data['cod_amount'] / 100), 2);
            $createData['codPaymentType'] = $data['cod_payment_type'] ?? 'BM';
            $createData['codCurrency'] = 'EUR';
        }

        // Rimuovi campi mittente vuoti per non interferire con i test
        foreach (['senderCompanyName', 'senderAddress', 'senderZIPCode', 'senderCity', 'senderProvinceAbbreviation'] as $field) {
            if (empty($createData[$field])) {
                unset($createData[$field]);
            }
        }

        $payload = [
            'payload' => [
                'account' => $config->accountPayload(),
                'createData' => $createData,
                'isLabelRequired' => 1,
                'labelParameters' => self::defaultLabelParameters(),
            ],
            'numericSenderReference' => $numericSenderReference,
        ];

        $payload['payload'] = self::sanitizeCreateData($payload['payload']);

        return $payload;
    }

    /**
     * Rimuove i campi sender override non supportati dall'API BRT.
     * BRT richiede solo senderCustomerCode — tutti gli altri campi sender
     * vengono ignorati o causano errore.
     */
    public static function sanitizeCreateData(array $data): array
    {
        // BRT REST API non riconosce NESSUN campo sender* tranne senderCustomerCode
        // nel payload di creazione shipment (il mittente è dedotto dal client code).
        // L'invio di altri senderXxx genera errore -68 "Unrecognized field".
        // Whitelist per essere future-proof: se Laravel aggiunge senderQualcosaNuovo,
        // viene sanitizzato automaticamente senza dover aggiornare una blacklist.
        $allowedSenderFields = ['senderCustomerCode'];

        if (isset($data['createData']) && is_array($data['createData'])) {
            foreach (array_keys($data['createData']) as $key) {
                if (str_starts_with((string) $key, 'sender') && !in_array($key, $allowedSenderFields, true)) {
                    unset($data['createData'][$key]);
                }
            }
        }

        return $data;
    }

    public static function defaultLabelParameters(): array
    {
        return [
            'outputType' => 'PDF', 'offsetX' => 0, 'offsetY' => 0,
            'isBorderRequired' => 0, 'isLogoRequired' => 1, 'isBarcodeControlRowRequired' => 1,
        ];
    }
}
