<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Brt\TrackingService;

class OrderBrtTrackingReadService
{
    private const STATUS_PRESENTATION = [
        Order::PENDING => ['label' => 'In attesa', 'description' => 'Ordine in attesa di pagamento.'],
        Order::PROCESSING => ['label' => 'In lavorazione', 'description' => 'Pagamento ricevuto, preparazione in corso.'],
        Order::COMPLETED => ['label' => 'Completato', 'description' => 'Ordine pagato, in attesa della generazione etichetta.'],
        Order::IN_TRANSIT => ['label' => 'In transito', 'description' => 'Il pacco e\' stato affidato al corriere BRT ed e\' in viaggio.'],
        Order::DELIVERED => ['label' => 'Consegnato', 'description' => 'Il pacco e\' stato consegnato.'],
        Order::IN_GIACENZA => ['label' => 'In giacenza', 'description' => 'Il pacco e\' in giacenza presso il corriere.'],
        Order::PAYMENT_FAILED => ['label' => 'Pagamento fallito', 'description' => 'Il pagamento non e\' andato a buon fine.'],
        Order::CANCELLED => ['label' => 'Annullato', 'description' => 'L\'ordine e\' stato annullato.'],
        Order::LABEL_GENERATED => ['label' => 'Etichetta generata', 'description' => 'L\'etichetta e\' stata generata, in attesa di ritiro.'],
        Order::OUT_FOR_DELIVERY => ['label' => 'In consegna', 'description' => 'Il pacco e\' in consegna al destinatario.'],
        Order::RETURNED => ['label' => 'Reso', 'description' => 'Il pacco e\' stato reso al mittente.'],
        Order::REFUSED => ['label' => 'Rifiutato', 'description' => 'Il pacco e\' stato rifiutato dal destinatario.'],
        Order::REFUNDED => ['label' => 'Rimborsato', 'description' => 'L\'ordine e\' stato rimborsato.'],
        'paid' => ['label' => 'Pagato', 'description' => 'Il pagamento e\' stato confermato.'],
    ];

    public function __construct(
        private readonly TrackingService $tracking,
    ) {}

    /**
     * Lookup pubblico per tracking. Priorità:
     * 1. public_tracking_token (ULID 26 char base32, opaco anti-IDOR — OWASP A01)
     * 2. brt_parcel_id / brt_tracking_number / brt_numeric_sender_reference (codici corriere reali)
     * 3. SF-{id} legacy (BACK-COMPAT solo per ordini vecchi senza token, deprecato)
     */
    private function findPublicTrackingOrder(string $code): ?Order
    {
        $normalized = trim($code);
        if ($normalized === '') {
            return null;
        }

        // 1. ULID base32 (26 char, A-Z + 0-9, opaco non sequenziale)
        if (preg_match('/^[0-9A-Z]{26}$/', $normalized)) {
            return Order::where('public_tracking_token', $normalized)->first();
        }

        // 2. Codici corriere reali (BRT-issued, non enumerabili)
        $byRef = Order::where('brt_parcel_id', $normalized)->first()
            ?? Order::where('brt_tracking_number', $normalized)->first()
            ?? Order::where('brt_numeric_sender_reference', $normalized)->first();
        if ($byRef) {
            return $byRef;
        }

        // 3. SF-{id} legacy: ammesso SOLO per ordini privi di token (back-compat)
        $cleanCode = preg_replace('/^(SF-|#|sf-)/i', '', $normalized);
        if (is_numeric($cleanCode)) {
            return Order::where('id', (int) $cleanCode)
                ->whereNull('public_tracking_token')
                ->whereNotNull('brt_parcel_id')
                ->first();
        }

        return null;
    }

    public function buildOrderTrackingPayload(Order $order): array
    {
        return [
            'parcel_id' => $order->brt_parcel_id,
            'tracking_number' => $order->brt_tracking_number,
            'tracking_url' => $this->trackingUrlForOrder($order),
            'status' => $order->rawStatus(),
            'departure_depot' => $order->brt_departure_depot,
            'arrival_depot' => $order->brt_arrival_depot,
            'service_type' => $order->brt_service_type,
        ];
    }

    public function buildPublicTrackingPayload(string $code): array
    {
        $normalizedCode = trim($code);
        $order = $this->findPublicTrackingOrder($normalizedCode);

        if (! $order) {
            return [
                'found' => false,
                'message' => 'Nessuna spedizione trovata con il codice inserito.',
                'brt_tracking_url' => $normalizedCode !== '' ? $this->tracking->getTrackingUrl($normalizedCode) : null,
            ];
        }

        $rawStatus = $order->rawStatus();
        $statusInfo = self::STATUS_PRESENTATION[$rawStatus]
            ?? ['label' => $order->getStatus($rawStatus), 'description' => ''];

        return [
            'found' => true,
            // OWASP IDOR: NO order_id interno esposto. Solo token opaco.
            'public_tracking_token' => $order->public_tracking_token,
            'status' => $statusInfo['label'],
            'status_description' => $statusInfo['description'],
            'raw_status' => $rawStatus,
            'brt_parcel_id' => $order->brt_parcel_id,
            'brt_tracking_number' => $order->brt_tracking_number,
            'brt_tracking_url' => $this->trackingUrlForOrder($order),
            'created_at' => $order->created_at?->setTimezone('Europe/Rome')->format('d/m/Y H:i'),
        ];
    }

    public function trackingUrlForOrder(Order $order): ?string
    {
        if ($order->brt_tracking_url) {
            return $order->brt_tracking_url;
        }

        $reference = $order->brt_tracking_number ?: $order->brt_parcel_id;
        if (! $reference) {
            return null;
        }

        return $this->tracking->getTrackingUrl($reference);
    }
}
