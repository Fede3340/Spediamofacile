<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Package;
use App\Services\Brt\AddressNormalizer;
use App\Services\Brt\BrtConfig;
use App\Services\Brt\ErrorTranslator;
use App\Services\Brt\PickupService;
use App\Services\Brt\PudoService;
use App\Services\Brt\ShipmentService;
use App\Services\Brt\TrackingService;

class BrtService
{
    private BrtConfig $config;
    private ShipmentService $shipmentService;
    private PudoService $pudoService;
    private TrackingService $trackingService;

    public function __construct()
    {
        $this->config = new BrtConfig();
        $addressNormalizer = new AddressNormalizer();
        $errorTranslator = new ErrorTranslator();
        $this->shipmentService = new ShipmentService($this->config, $addressNormalizer, $errorTranslator);
        $this->pudoService = new PudoService($this->config);
        $this->trackingService = new TrackingService($this->config);
    }

    // ── Shipment operations (delegated to Brt\ShipmentService) ───

    public function createShipment(Order $order, array $options = []): array
    {
        return $this->shipmentService->createShipment($order, $options);
    }

    public function testCreateShipment(array $data): array
    {
        return $this->shipmentService->testCreateShipment($data);
    }

    public function confirmShipment(int $numericSenderReference): array
    {
        return $this->shipmentService->confirmShipment($numericSenderReference);
    }

    public function deleteShipment(int $numericSenderReference): array
    {
        return $this->shipmentService->deleteShipment($numericSenderReference);
    }

    // ── PUDO operations (delegated to Brt\PudoService) ───────────

    public function getPudoByAddress(string $address, string $zipCode, string $city, string $countryCode = 'ITA', int $maxResults = 50): array
    {
        return $this->pudoService->getPudoByAddress($address, $zipCode, $city, $countryCode, $maxResults);
    }

    public function getPudoByCoordinates(float $latitude, float $longitude, int $maxResults = 50): array
    {
        return $this->pudoService->getPudoByCoordinates($latitude, $longitude, $maxResults);
    }

    public function getPudoDetails(string $pudoId): array
    {
        return $this->pudoService->getPudoDetails($pudoId);
    }

    // ── Tracking operations (delegated to Brt\TrackingService) ───

    public function getTrackingUrl(string $parcelNumber): string
    {
        return $this->trackingService->getTrackingUrl($parcelNumber);
    }

    public function getTrackingStatus(Order $order): array
    {
        return $this->trackingService->getTrackingStatus($order);
    }

    // ── Home pickup (delegated to Brt\PickupService) ──────────

    public function requestHomePickup(Order $order, array $pickupRequest): array
    {
        if (! ((bool) ($pickupRequest['enabled'] ?? false))) {
            return ['success' => true, 'status' => 'not_requested'];
        }

        if (empty($order->brt_parcel_id)) {
            return [
                'success' => false,
                'status' => 'failed',
                'error' => 'Impossibile richiedere il ritiro senza etichetta BRT generata.',
            ];
        }

        return app(PickupService::class)->requestPickup($order, $pickupRequest);
    }

    // ── Bordero generation ───────────────────────────────────────

    public function createBordero(Order $order): array
    {
        $order->loadMissing(['packages.originAddress', 'packages.destinationAddress', 'packages.service', 'user']);

        /** @var Package|null $package */
        $package = $order->packages->first();
        if (! $package || ! $package->originAddress || ! $package->destinationAddress) {
            return [
                'success' => false,
                'error' => 'Dati spedizione insufficienti per generare il bordero.',
            ];
        }

        $origin = $package->originAddress;
        $destination = $package->destinationAddress;
        $parcelCount = (int) $order->packages->sum(fn (Package $item) => max(1, (int) ($item->quantity ?? 1)));
        $reference = 'BORD-' . str_pad((string) $order->id, 8, '0', STR_PAD_LEFT);

        $pdf = app(BorderoPdfBuilder::class)->build([
            'bordero_date' => now()->format('d/m/Y'),
            'bordero_number' => (string) $order->id,
            'bordero_reference' => $reference,
            'localita' => (string) ($destination->city ?? ''),
            'prov' => (string) ($destination->province ?? ''),
            'lna' => (string) ($destination->postal_code ?? ''),
            'rif_num' => (string) $order->id,
            'rif_alpha' => (string) ($order->brt_parcel_id ?? $order->id),
            'cod_bolla' => (string) ($order->brt_parcel_id ?? 'n/d'),
            'incasso' => $order->is_cod ? 'COD' : 'NO',
            'importo_incasso' => $order->is_cod ? number_format(((int) $order->cod_amount) / 100, 2, ',', '.') : '0,00',
            'importo_assicurare' => '0,00',
            'colli' => (string) $parcelCount,
            'sender_name' => (string) ($origin->name ?? ''),
            'sender_address' => trim((string) (($origin->address ?? '') . ' ' . ($origin->address_number ?? ''))),
            'sender_city_line' => trim((string) (($origin->postal_code ?? '') . ' ' . ($origin->city ?? '') . ' (' . ($origin->province ?? '') . ')')),
            'sender_phone' => (string) ($origin->telephone_number ?? ''),
            'recipient_name' => (string) ($destination->name ?? ''),
            'recipient_address' => trim((string) (($destination->address ?? '') . ' ' . ($destination->address_number ?? ''))),
            'recipient_city_line' => trim((string) (($destination->postal_code ?? '') . ' ' . ($destination->city ?? '') . ' (' . ($destination->province ?? '') . ')')),
            'recipient_phone' => (string) ($destination->telephone_number ?? ''),
            'created_at' => now()->format('d/m/Y H:i'),
        ]);

        return [
            'success' => true,
            'bordero_reference' => $reference,
            'document_base64' => base64_encode($pdf),
            'document_mime' => 'application/pdf',
            'document_filename' => 'bordero-' . $order->id . '.pdf',
        ];
    }
}
