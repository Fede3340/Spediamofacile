<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Brt\PudoService;
use App\Services\Brt\ShipmentService;
use App\Services\OrderBrtFulfillmentService;
use App\Services\OrderBrtTrackingReadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class BrtController extends Controller
{
    public function __construct(
        private readonly ShipmentService $shipment,
        private readonly PudoService $pudo,
        private readonly OrderBrtFulfillmentService $fulfillment,
        private readonly OrderBrtTrackingReadService $trackingRead,
    ) {}

    public function createShipment(\App\Http\Requests\BrtCreateLabelRequest $request)
    {
        // Endpoint admin/manuale: gli override qui sono eccezioni controllate.
        // Il flusso automatico BRT deve leggere dall'ordine persistito canonico.

        if ($request->boolean('is_cod') && (int) $request->cod_amount <= 0) {
            return response()->json(['error' => 'L\'importo del contrassegno deve essere maggiore di zero.'], 422);
        }

        $order = Order::findOrFail($request->order_id);

        Gate::authorize('manageShipment', $order);

        $rawStatus = $order->getRawOriginal('status') ?? $order->getAttributes()['status'] ?? 'pending';
        if (! in_array($rawStatus, [Order::COMPLETED, 'completed', 'processing'], true)) {
            return response()->json(['error' => 'L\'ordine deve essere pagato prima di creare la spedizione BRT.'], 422);
        }

        if ($order->brt_parcel_id) {
            return response()->json([
                'error' => 'Spedizione BRT gia\' creata per questo ordine.',
                'parcel_id' => $order->brt_parcel_id,
                'tracking_url' => $order->brt_tracking_url,
            ], 409);
        }

        $options = [
            'is_cod' => $request->boolean('is_cod'),
            'cod_amount' => $request->input('cod_amount'),
            'pudo_id' => $request->input('pudo_id'),
            'notes' => $request->input('notes'),
        ];

        $result = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $result = $this->shipment->createShipment($order, $options);
                if ($result['success']) {
                    break;
                }
            } catch (\Throwable $e) {
                Log::warning("BRT manual createShipment attempt {$attempt}/3 failed", [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                $result = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        if (! ($result['success'] ?? false)) {
            return response()->json(['error' => $result['error'] ?? 'Errore BRT sconosciuto.'], 502);
        }

        $order = $this->fulfillment->finalizeSuccessfulShipment(
            $order,
            $result,
            [
                'brt_pudo_id' => $request->input('pudo_id'),
                'is_cod' => $request->boolean('is_cod'),
                'cod_amount' => $request->input('cod_amount'),
            ],
            'Post-elaborazione documenti fallita dopo creazione spedizione manuale',
            'Failed to complete shipment documents flow after manual shipment creation'
        );

        return response()->json([
            'success' => true,
            'parcel_id' => $result['parcel_id'] ?? null,
            'tracking_number' => $result['tracking_number'] ?? null,
            'tracking_url' => $result['tracking_url'] ?? null,
            'order_status' => Order::LABEL_GENERATED,
        ]);
    }

    public function confirmShipment(\App\Http\Requests\BrtOrderActionRequest $request)
    {
        $order = Order::findOrFail($request->order_id);

        Gate::authorize('manageShipment', $order);

        if (! $order->brt_numeric_sender_reference) {
            return response()->json(['error' => 'Nessuna spedizione BRT trovata per questo ordine.'], 422);
        }

        $result = $this->shipment->confirmShipment((int) $order->brt_numeric_sender_reference);
        if (! ($result['success'] ?? false)) {
            return response()->json(['error' => $result['error']], 502);
        }

        return response()->json(['success' => true]);
    }

    public function deleteShipment(\App\Http\Requests\BrtOrderActionRequest $request)
    {
        $order = Order::findOrFail($request->order_id);

        if (! auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Solo gli admin possono eliminare le spedizioni BRT.'], 403);
        }
        if (! $order->brt_numeric_sender_reference) {
            return response()->json(['error' => 'Nessuna spedizione BRT trovata per questo ordine.'], 422);
        }

        $result = $this->shipment->deleteShipment((int) $order->brt_numeric_sender_reference);
        if (! ($result['success'] ?? false)) {
            return response()->json(['error' => $result['error']], 502);
        }

        $this->resetBrtData($order);

        return response()->json(['success' => true]);
    }

    public function downloadLabel(Order $order)
    {
        Gate::authorize('manageShipment', $order);

        if (! $order->brt_label_base64) {
            return response()->json(['error' => 'Nessuna etichetta trovata per questo ordine.'], 404);
        }

        return response(base64_decode($order->brt_label_base64), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="etichetta-brt-'.$order->id.'.pdf"');
    }

    public function tracking(Order $order)
    {
        Gate::authorize('manageShipment', $order);

        if (! $order->brt_parcel_id) {
            return response()->json(['error' => 'Nessuna spedizione BRT per questo ordine.'], 404);
        }

        return response()->json($this->trackingRead->buildOrderTrackingPayload($order));
    }

    public function publicTracking(\App\Http\Requests\BrtPublicTrackingRequest $request)
    {
        return response()->json(
            $this->trackingRead->buildPublicTrackingPayload((string) $request->code)
        );
    }

    public function pudoSearch(\App\Http\Requests\BrtPudoSearchRequest $request)
    {
        $data = $request->validated();

        $zipCode = preg_replace('/\D/', '', (string) ($data['zip_code'] ?? ''));
        $city = trim((string) ($data['city'] ?? ''));

        if ($zipCode === '' && $city === '') {
            return response()->json(['success' => false, 'error' => 'Inserisci almeno citta o CAP per cercare i punti PUDO.'], 422);
        }

        $result = $this->pudo->getPudoByAddress(
            trim((string) ($data['address'] ?? '')),
            $zipCode,
            $city,
            $data['country'] ?? 'ITA',
            (int) ($data['max_results'] ?? 50)
        );

        return response()->json($result);
    }

    public function pudoNearby(\App\Http\Requests\BrtPudoNearbyRequest $request)
    {

        $result = $this->pudo->getPudoByCoordinates(
            (float) $request->latitude,
            (float) $request->longitude,
            (int) ($request->max_results ?? 50)
        );

        return response()->json($result);
    }

    public function pudoDetails(string $pudoId)
    {
        return response()->json($this->pudo->getPudoDetails($pudoId));
    }

    public function testCreate(\App\Http\Requests\BrtTestCreateShipmentRequest $request)
    {
        return response()->json($this->shipment->testCreateShipment($request->validated()));
    }

    private function resetBrtData(Order $order): void
    {
        $brtFields = [
            'brt_parcel_id',
            'brt_numeric_sender_reference',
            'brt_tracking_url',
            'brt_label_base64',
            'brt_tracking_number',
            'brt_parcel_number_to',
            'brt_departure_depot',
            'brt_arrival_terminal',
            'brt_arrival_depot',
            'brt_delivery_zone',
            'brt_series_number',
            'brt_service_type',
            'brt_all_labels',
            'brt_raw_response',
            'brt_error',
        ];

        foreach ($brtFields as $field) {
            $order->{$field} = null;
        }
        $order->status = Order::COMPLETED;
        $order->save();
    }
}
