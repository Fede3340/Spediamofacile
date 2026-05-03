<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\BuildsSessionPayload;
use App\Http\Requests\StoreSessionFirstStepRequest;
use App\Http\Requests\StoreSessionSecondStepRequest;
use App\Services\PriceEngineService;
use App\Services\Shipping\SessionDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionDataController extends Controller
{
    use BuildsSessionPayload;

    public function __construct(private readonly SessionDataService $sessionData) {}

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

    public function firstStep(StoreSessionFirstStepRequest $request)
    {
        $validated = $request->validated();
        $prepared = $this->sessionData->prepareFirstStep($validated);
        $pricing = $this->sessionData->calculatePackagesPricing(
            $validated['packages'],
            $prepared['shipment_details'],
            $prepared['is_europe']
        );

        session()->put('shipment_details', $prepared['shipment_details']);
        session()->put('packages', $pricing['packages']);
        session()->put('total_price', $pricing['total_price']);
        session()->put('step', 2);
        $this->forgetDownstreamFlowState();

        return response()->json(['data' => $this->buildSessionPayload()]);
    }

    public function secondStep(StoreSessionSecondStepRequest $request)
    {
        $payload = $this->sessionData->prepareSecondStep($request->validated());

        session()->put('services', $payload['services']);
        session()->put('content_description', $payload['content_description']);
        session()->put('pickup_date', $payload['pickup_date']);
        session()->put('sms_email_notification', $payload['sms_email_notification']);
        session()->put('service_data', $payload['services']['serviceData']);
        session()->put('delivery_mode', $payload['delivery_mode']);
        session()->put('selected_pudo', $payload['selected_pudo']);
        if ($payload['client_submission_id'] !== null) {
            session()->put('client_submission_id', $payload['client_submission_id']);
        }
        if ($payload['packages'] !== null) {
            session()->put('packages', $payload['packages']);
        }
        if ($payload['origin_address'] !== null) {
            session()->put('origin_address', $payload['origin_address']);
        }
        if ($payload['destination_address'] !== null) {
            session()->put('destination_address', $payload['destination_address']);
        }

        $flowState = $this->buildFlowState();
        session()->put('step', $flowState['summary_ready'] ? 4 : 3);

        return response()->json(['data' => $this->buildSessionPayload()]);
    }

    /**
     * Mostra lo stato corrente della sessione preventivo.
     * Mantiene la struttura "data.*" per compat con test esistenti.
     */
    public function show(Request $request): JsonResponse
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
        session()->forget(self::SESSION_KEYS_TO_FORGET);

        return response()->json(['success' => true]);
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

    private const SESSION_KEYS_TO_FORGET = [
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
    ];
}
