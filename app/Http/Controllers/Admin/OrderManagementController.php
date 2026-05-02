<?php

namespace App\Http\Controllers\Admin;

use App\Events\ShipmentStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderPudoRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\Admin\OrderManagementService;
use App\Services\Brt\ShipmentService;
use App\Services\OrderBrtFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    public function __construct(
        private readonly ShipmentService $shipment,
        private readonly OrderBrtFulfillmentService $fulfillment,
        private readonly OrderManagementService $orders,
    ) {}

    public function orders(Request $request): JsonResponse
    {
        return response()->json($this->orders->paginateOrders($request));
    }

    public function updateOrderStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $newStatus = $request->validated()['status'];
        $oldStatus = $order->status;
        $order->update(['status' => $newStatus]);

        if ($oldStatus !== $newStatus) {
            event(new ShipmentStatusChanged($order, $oldStatus, $newStatus));
        }

        return response()->json([
            'success' => true,
            'message' => "Stato ordine aggiornato da '{$oldStatus}' a '{$newStatus}'.",
            'data' => $order->fresh(),
        ]);
    }

    public function shipments(Request $request): JsonResponse
    {
        return response()->json($this->orders->paginateShipments($request));
    }

    public function updateOrderPudo(UpdateOrderPudoRequest $request, Order $order): JsonResponse
    {
        $pudoId = $request->validated()['pudo_id'];
        $order->update(['brt_pudo_id' => $pudoId]);

        return response()->json([
            'success' => true,
            'message' => $pudoId
                ? "Punto PUDO '{$pudoId}' impostato per ordine #{$order->id}."
                : "Punto PUDO rimosso dall'ordine #{$order->id}.",
            'data' => $order->fresh(),
        ]);
    }

    public function regenerateLabel(Order $order): JsonResponse
    {
        if (! config('services.brt.client_id')) {
            return response()->json(['success' => false, 'message' => 'BRT non configurato. Verifica le credenziali nel file .env.'], 422);
        }

        $result = $this->shipment->createShipment($order, $this->fulfillment->buildAutomaticShipmentOptions($order));

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Generazione etichetta BRT fallita: '.($result['error'] ?? 'Errore sconosciuto'),
            ], 422);
        }

        $this->fulfillment->finalizeSuccessfulShipment(
            $order, $result, [],
            'Post-elaborazione documenti fallita dopo rigenerazione admin',
            'Failed to complete shipment documents flow after admin label regeneration'
        );

        return response()->json([
            'success' => true,
            'message' => 'Etichetta BRT rigenerata con successo.',
            'data' => ['parcel_id' => $result['parcel_id'], 'tracking_url' => $result['tracking_url']],
        ]);
    }
}
