<?php
namespace App\Services\Brt;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class TrackingService
{
    private const DIRECT_STATUS_MAP = [
        'DELIVERED' => Order::DELIVERED,
        'CONSEGNATO' => Order::DELIVERED,
        'RETURNED' => Order::RETURNED,
        'RESO' => Order::RETURNED,
        'REFUSED' => Order::REFUSED,
        'RIFIUTATO' => Order::REFUSED,
        'IN_STORAGE' => Order::IN_GIACENZA,
        'GIACENZA' => Order::IN_GIACENZA,
        'DELIVERY_FAILED' => Order::IN_GIACENZA,
        'MANCATA_CONSEGNA' => Order::IN_GIACENZA,
        'OUT_FOR_DELIVERY' => Order::OUT_FOR_DELIVERY,
        'IN_CONSEGNA' => Order::OUT_FOR_DELIVERY,
        'IN_DISTRIBUZIONE' => Order::OUT_FOR_DELIVERY,
        'IN_TRANSIT' => Order::IN_TRANSIT,
        'IN_TRANSITO' => Order::IN_TRANSIT,
        'PICKED_UP' => Order::IN_TRANSIT,
        'RITIRATO' => Order::IN_TRANSIT,
        'SHIPMENT_CREATED' => Order::IN_TRANSIT,
        'SPEDIZIONE_CREATA' => Order::IN_TRANSIT,
        'LABEL_GENERATED' => Order::LABEL_GENERATED,
        'ETICHETTA_GENERATA' => Order::LABEL_GENERATED,
    ];

    public function __construct(
        private readonly BrtConfig $config,
    ) {}

    /**
     * Genera l'URL per seguire il tracking di un pacco BRT.
     * Usa il sistema VAS di BRT che accetta il numero di collo come riferimento.
     *
     * @param string $parcelNumber Il numero di collo BRT (parcelNumberFrom) o parcelId
     */
    public function getTrackingUrl(string $parcelNumber): string
    {
        if (empty($parcelNumber)) {
            return '';
        }
        return 'https://vas.brt.it/vas/sped_det_show.hsm?refnr=' . urlencode($parcelNumber);
    }

    /**
     * Interroga BRT per ottenere lo stato tracking di un ordine.
     * Usato dal comando SyncBrtTracking per aggiornamenti automatici.
     *
     * Endpoint BRT: POST /rest/v1/shipments/tracking
     * Accetta account + spedition_id (numericSenderReference) oppure parcelId.
     *
     * @param Order $order L'ordine con brt_numeric_sender_reference o brt_parcel_id
     * @return array {status: ?string, brt_event: ?string, description: ?string, error: ?string}
     */
    public function getTrackingStatus(Order $order): array
    {
        $reference = $order->brt_tracking_number ?: $order->brt_parcel_id;
        $numericRef = $order->brt_numeric_sender_reference;

        if (empty($reference) && empty($numericRef)) {
            return ['status' => null, 'brt_event' => null, 'description' => null, 'error' => 'Nessun riferimento BRT'];
        }

        try {
            $payload = [
                'account' => $this->config->accountPayload(),
                'trackingData' => [
                    'senderCustomerCode' => (int) $this->config->clientId,
                ],
            ];

            // Preferiamo numericSenderReference (piu' affidabile), altrimenti parcelId
            if (! empty($numericRef)) {
                $payload['trackingData']['numericSenderReference'] = (int) $numericRef;
            } else {
                $payload['trackingData']['parcelId'] = $reference;
            }

            $response = $this->config->shipmentClient()
                ->post($this->config->apiUrl . '/tracking', $payload);

            if (! $response->successful()) {
                return [
                    'status' => null,
                    'brt_event' => null,
                    'description' => null,
                    'error' => 'HTTP ' . $response->status(),
                ];
            }

            $body = $response->json();

            // BRT puo' restituire executionMessage con errore
            $execCode = $body['executionMessage']['code'] ?? 0;
            if ($execCode < 0) {
                return [
                    'status' => null,
                    'brt_event' => null,
                    'description' => null,
                    'error' => $body['executionMessage']['message'] ?? 'Errore tracking BRT',
                ];
            }

            $events = $body['events'] ?? $body['trackingEvents'] ?? [];

            if (empty($events)) {
                return [
                    'status' => null,
                    'brt_event' => null,
                    'description' => 'Nessun evento tracking',
                    'error' => null,
                ];
            }

            // L'ultimo evento e' il piu' recente (primo dell'array)
            $lastEvent = $events[0];
            $eventCode = $lastEvent['eventCode'] ?? $lastEvent['id'] ?? '';
            $eventDesc = $lastEvent['eventDescription'] ?? $lastEvent['description'] ?? '';

            $newStatus = $this->mapCarrierStatus($eventCode, $eventDesc);

            return [
                'status' => $newStatus,
                'brt_event' => $eventCode,
                'description' => $eventDesc,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('BRT getTrackingStatus exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return ['status' => null, 'brt_event' => null, 'description' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Mappa un evento BRT a uno stato dell'ordine.
     * I codici evento BRT sono stringhe come "DELIVERED", "IN_TRANSIT", ecc.
     * Alcuni eventi arrivano in italiano dal sistema BRT.
     *
     * ORDINE DI PRIORITA' (importante):
     * 1. Consegnato — stato finale positivo, priorita' massima
     * 2. Reso — pacco tornato al mittente, PRIMA di rifiutato (un rifiuto puo' diventare reso)
     * 3. Rifiutato — destinatario ha rifiutato
     * 4. In giacenza — problemi di consegna temporanei
     * 5. In consegna — ultimo miglio, PRIMA di in_transit per evitare falso in_transit
     * 6. In transito — stato generico di movimento
     */
    public function mapCarrierStatus(string $eventCode, string $eventDesc = ''): ?string
    {
        $normalizedCode = strtoupper(trim($eventCode));
        if ($normalizedCode !== '' && isset(self::DIRECT_STATUS_MAP[$normalizedCode])) {
            return self::DIRECT_STATUS_MAP[$normalizedCode];
        }

        $combined = strtoupper(trim($eventCode . ' ' . $eventDesc));

        // Consegnato (stato finale)
        $deliveredKeywords = ['DELIVERED', 'CONSEGNAT', 'CONSEGNA EFFETTUATA', 'RECAPITATO'];
        foreach ($deliveredKeywords as $kw) {
            if (str_contains($combined, $kw)) {
                return Order::DELIVERED;
            }
        }

        // Reso (pacco restituito al mittente) — PRIMA di in_transit per evitare conflitti
        $resoKeywords = [
            'RESO', 'RESTITUITO', 'RETURNED', 'RETURN TO SENDER',
            'RITORNO AL MITTENTE', 'RESO AL MITTENTE', 'RESTITUZIONE',
        ];
        foreach ($resoKeywords as $kw) {
            if (str_contains($combined, $kw)) {
                return Order::RETURNED;
            }
        }

        // Rifiutato (destinatario ha rifiutato la consegna)
        $rifiutatoKeywords = ['RIFIUTATO', 'RIFIUTO', 'REFUSED', 'RESPINTO', 'REJECTED'];
        foreach ($rifiutatoKeywords as $kw) {
            if (str_contains($combined, $kw)) {
                return Order::REFUSED;
            }
        }

        // In giacenza (problemi consegna)
        $giacenzaKeywords = [
            'GIACENZA', 'STORAGE', 'MANCATA CONSEGNA', 'DESTINATARIO ASSENTE',
            'FERMA DEPOSITO', 'NON CONSEGNATO', 'TENTATIVO DI CONSEGNA',
            'INDIRIZZO ERRATO', 'INDIRIZZO INSUFFICIENTE',
        ];
        foreach ($giacenzaKeywords as $kw) {
            if (str_contains($combined, $kw)) {
                return Order::IN_GIACENZA;
            }
        }

        // In consegna (ultimo miglio) — PRIMA di in_transit per catturare lo stato piu' specifico
        $consegnaKeywords = [
            'IN CONSEGNA', 'OUT FOR DELIVERY', 'IN DISTRIBUZIONE',
            'SVINCOLO GIACENZA', 'MESSO IN CONSEGNA',
        ];
        foreach ($consegnaKeywords as $kw) {
            if (str_contains($combined, $kw)) {
                return Order::OUT_FOR_DELIVERY;
            }
        }

        // In transito
        $transitKeywords = [
            'IN_TRANSIT', 'IN TRANSITO', 'PARTITA', 'PRESA IN CARICO',
            'RITIRAT', 'HUB', 'SPEDIZIONE CREATA',
        ];
        foreach ($transitKeywords as $kw) {
            if (str_contains($combined, $kw)) {
                return Order::IN_TRANSIT;
            }
        }

        return null;
    }
}
