<?php
namespace App\Services\Brt;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\PendingRequest;

class BrtConfig
{
    public readonly string $apiUrl;
    public readonly string $pudoApiUrl;
    public readonly string $clientId;
    public readonly string $password;
    public readonly string $pudoToken;
    public readonly int $departureDepot;
    public readonly bool $verifySsl;
    public readonly bool $pickupEnabled;
    public readonly ?string $pickupEndpoint;

    public function __construct()
    {
        $this->apiUrl = config('services.brt.api_url', 'https://api.brt.it/rest/v1/shipments');
        $this->pudoApiUrl = config('services.brt.pudo_api_url', 'https://api.brt.it');
        $this->clientId = config('services.brt.client_id', '');
        $this->password = config('services.brt.password', '');
        $this->pudoToken = config('services.brt.pudo_token', '');
        $this->departureDepot = (int) config('services.brt.departure_depot', 0);
        $this->verifySsl = (bool) config('services.brt.verify_ssl', true);
        $this->pickupEnabled = (bool) config('services.brt.pickup_enabled', false);
        $pickupEndpoint = trim((string) config('services.brt.pickup_endpoint', ''));
        $this->pickupEndpoint = $pickupEndpoint !== '' ? $pickupEndpoint : null;

        if ($this->departureDepot === 0) {
            Log::warning('BRT departure depot not configured, using fallback 0');
        }
    }

    /**
     * Client HTTP per le API spedizioni BRT (con SSL configurabile e timeout 30s).
     */
    public function shipmentClient(): PendingRequest
    {
        return Http::withOptions(['verify' => $this->verifySsl])
            ->timeout(30)
            ->withHeaders(['Content-Type' => 'application/json']);
    }

    /**
     * Client HTTP per le API PUDO BRT (SSL configurabile tramite BRT_VERIFY_SSL, timeout 15s).
     *
     * La verifica SSL usa lo stesso flag `verifySsl` del client spedizioni, configurabile in .env:
     *   BRT_VERIFY_SSL=false  — disabilita SSL solo in locale/testing
     *   BRT_VERIFY_SSL=true   — (default) verifica SSL in produzione
     */
    public function pudoClient(): PendingRequest
    {
        $headers = ['Accept' => 'application/json'];
        if (!empty($this->pudoToken)) {
            $headers['X-API-Auth'] = $this->pudoToken;
        }

        return Http::withOptions(['verify' => $this->verifySsl])
            ->timeout(15)
            ->withHeaders($headers);
    }

    /**
     * Array credenziali account per i payload BRT.
     */
    public function accountPayload(): array
    {
        return [
            'userID' => $this->clientId,
            'password' => $this->password,
        ];
    }
}
