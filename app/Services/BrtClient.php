<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Brt\AddressNormalizer;
use App\Services\Brt\BrtBordereauGenerator;
use App\Services\Brt\BrtConfig;
use App\Services\Brt\BrtPayloadBuilder;
use App\Services\Brt\ErrorTranslator;
use App\Services\Brt\FilialeLookup;
use App\Services\Brt\PickupService;
use App\Services\Brt\PudoPointMapper;
use App\Services\Brt\PudoService;
use App\Services\Brt\ShipmentService;
use App\Services\Brt\TrackingService;

/**
 * BrtClient — facade unificato per tutta l'integrazione BRT REST 3.x.
 *
 * Sostituisce l'accesso diretto agli 11 sub-service (Brt\*) con un singolo
 * entry point. I sub-service restano in `Services/Brt/` come implementazione
 * privata (dependency injection), ma callers esterni dovrebbero usare solo
 * BrtClient per riflettere il contratto pubblico.
 *
 * Caller esistenti che usano direttamente i sub-service continuano a funzionare
 * (DI Laravel autoload), ma nuovi caller passano da qui.
 */
class BrtClient
{
    public function __construct(
        public readonly ShipmentService $shipment,
        public readonly TrackingService $tracking,
        public readonly PudoService $pudo,
        public readonly PudoPointMapper $pudoMapper,
        public readonly PickupService $pickup,
        public readonly AddressNormalizer $addresses,
        public readonly FilialeLookup $filiali,
        public readonly ErrorTranslator $errors,
        public readonly BrtPayloadBuilder $payload,
        public readonly BrtBordereauGenerator $bordereau,
        public readonly BrtConfig $config,
    ) {}

    /** Crea spedizione BRT + ottiene numero tracking. */
    public function createShipment(Order $order): array
    {
        return $this->shipment->createForOrder($order);
    }

    /** Genera etichetta PDF/ZPL per ordine pagato. */
    public function generateLabel(Order $order, string $format = 'pdf'): string
    {
        return $this->shipment->generateLabel($order, $format);
    }

    /** Ottiene stato tracking corrente di un ordine. */
    public function getTrackingStatus(Order $order): array
    {
        return $this->tracking->getTrackingStatus($order);
    }

    /** URL tracking pubblico BRT per codice. */
    public function getTrackingUrl(string $code): string
    {
        return $this->tracking->getTrackingUrl($code);
    }

    /** Cerca PUDO per CAP/citta. */
    public function searchPudo(string $postalCode, ?string $city = null): array
    {
        return $this->pudo->search($postalCode, $city);
    }

    /** Prenota ritiro a domicilio. */
    public function bookPickup(Order $order, string $date): array
    {
        return $this->pickup->book($order, $date);
    }

    /** Lookup filiale BRT da CAP. */
    public function lookupFiliale(string $postalCode): ?array
    {
        return $this->filiali->byPostalCode($postalCode);
    }

    /** Traduce codice errore BRT in messaggio italiano user-friendly. */
    public function translateError(int|string $code, ?string $detail = null): string
    {
        return $this->errors->translate($code, $detail);
    }

    /** Genera bordereau PDF (distinta corriere). */
    public function generateBordereau(array $orders): string
    {
        return $this->bordereau->generate($orders);
    }
}
