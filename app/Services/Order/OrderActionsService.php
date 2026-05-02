<?php

namespace App\Services\Order;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Package;
use App\Services\CartService;
use App\Services\CheckoutSubmissionContextService;
use App\Services\DirectOrderService;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OrderActionsService -- Logica delle azioni mutative su ordini estratta dal
 * controller per mantenere quest'ultimo "thin". Le chiamate a Stripe/refund e
 * l'idempotency restano in RefundService/DirectOrderService (NON toccate qui).
 */
class OrderActionsService
{
    public function __construct(
        private readonly CheckoutSubmissionContextService $submissionContext,
        private readonly DirectOrderService $directOrder,
        private readonly CartService $cartService,
        private readonly RefundService $refundService,
    ) {
    }

    /**
     * Pre-elabora i dati per la creazione diretta dell'ordine (pricing,
     * surcharge, COD, assicurazione, pudo). Ritorna il bundle pronto per la
     * transazione DB.
     */
    public function prepareDirectOrderPayload(array $data): array
    {
        $servicesData = $this->cartService->applyPudoData($data['_normalized_services'], $data);
        $serviceData = $servicesData['service_data'] ?? [];

        $pricing = $this->directOrder->pricePackages(
            $data['packages'],
            $data['origin_address']['postal_code'] ?? null,
            $data['destination_address']['postal_code'] ?? null,
        );

        $cod = $this->directOrder->resolveCodDetails($servicesData, $serviceData);
        $insurance = $this->directOrder->resolveInsuranceDetails($servicesData, $serviceData);
        $serviceSurchargeCents = $this->directOrder->calculateServiceSurcharge(
            $servicesData, $serviceData, $pricing['priced_packages'], $data,
        );

        $pudoId = (! empty($data['pudo']['pudo_id']) && ($data['delivery_mode'] ?? 'home') === 'pudo')
            ? $data['pudo']['pudo_id']
            : null;

        return [
            'priced_packages' => $pricing['priced_packages'],
            'subtotal_cents' => $pricing['subtotal_cents'] + $serviceSurchargeCents,
            'services_data' => $servicesData,
            'cod' => $cod,
            'insurance' => $insurance,
            'pudo_id' => $pudoId,
        ];
    }

    /**
     * Costruisce il submission context per un ordine diretto.
     */
    public function buildDirectOrderSubmissionContext(array $data, int $userId, array $bundle): array
    {
        return $this->submissionContext->enrich(
            $this->submissionContext->fromRequestArray($data),
            $this->submissionContext->snapshotFromDirectOrderPayload([
                ...$data,
                'packages' => $bundle['priced_packages'],
                'services' => $bundle['services_data'],
            ], $bundle['subtotal_cents']),
            [
                'user_id' => $userId,
                'flow' => 'direct-order',
                'billing_data' => $data['billing_data'] ?? null,
            ],
        );
    }

    /**
     * Persiste l'ordine diretto delegando a DirectOrderService.
     */
    public function persistDirectOrder(array $data, int $userId, array $bundle, array $submissionContext): array
    {
        return $this->directOrder->persistDirectOrder(
            $data, $userId, $bundle['priced_packages'], $bundle['services_data'],
            $bundle['cod']['is_cod'], $bundle['cod']['cod_amount'], $bundle['pudo_id'],
            $bundle['subtotal_cents'], $submissionContext,
            $bundle['cod']['cod_payment_type'] ?? null,
            $bundle['cod']['cod_incasso_type'] ?? null,
            $bundle['insurance']['insurance_amount_cents'] ?? null,
        );
    }

    /**
     * Esegue la cancellazione di un ordine con eventuale rimborso. La logica
     * idempotency Stripe resta integralmente in RefundService.
     */
    public function cancel(Order $order, ?string $reason): JsonResponse
    {
        $preCheck = $this->refundService->calculateEligibility($order);
        if (! $preCheck['eligible']) {
            return response()->json(['error' => $preCheck['reason']], 422);
        }

        try {
            $result = $this->refundService->processCancellation($order, $reason);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Order cancellation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Errore durante l\'annullamento dell\'ordine. Riprova o contatta l\'assistenza.',
            ], 500);
        }

        $refundEur = number_format($result['refund_amount_cents'] / 100, 2, ',', '.');
        $commissionEur = number_format($result['commission_cents'] / 100, 2, ',', '.');

        return response()->json([
            'success' => true,
            'message' => $result['refund_amount_cents'] > 0
                ? "Ordine annullato. Rimborso di {$refundEur} EUR processato (commissione: {$commissionEur} EUR)."
                : 'Ordine annullato con successo.',
            'refund_amount' => $refundEur,
            'commission' => $commissionEur,
            'refund_method' => $result['refund_method'],
            'brt_cancelled' => $result['brt_cancelled'],
        ]);
    }

    /**
     * Aggiunge un collo a un ordine pending/payment_failed; ricalcola subtotale
     * e ruota il submission context.
     */
    public function addPackage(Order $order, array $payload, callable $rotateContext): JsonResponse
    {
        if (! in_array($order->status, [Order::PENDING, Order::PAYMENT_FAILED])) {
            return response()->json(['error' => 'Si possono aggiungere colli solo agli ordini in attesa di pagamento.'], 422);
        }

        DB::transaction(function () use ($order, $payload, $rotateContext) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $lockedOrder->loadMissing(['packages.originAddress', 'packages.destinationAddress']);
            /** @var Package|null $existingPackage */
            $existingPackage = $lockedOrder->packages->first();
            $originCap = $existingPackage?->originAddress?->postal_code;
            $destinationCap = $existingPackage?->destinationAddress?->postal_code;

            $priced = $this->directOrder->priceSinglePackage(
                (float) $payload['weight'], (float) $payload['first_size'], (float) $payload['second_size'],
                (float) $payload['third_size'], (int) $payload['quantity'], $originCap, $destinationCap,
            );

            $package = Package::create([
                'package_type' => $payload['package_type'],
                'quantity' => (int) $payload['quantity'],
                'weight' => $payload['weight'],
                'first_size' => $payload['first_size'],
                'second_size' => $payload['second_size'],
                'third_size' => $payload['third_size'],
                'weight_price' => $priced['weight_price'],
                'volume_price' => $priced['volume_price'],
                'single_price' => $priced['single_price_cents'],
                'content_description' => $payload['content_description'],
                'origin_address_id' => $existingPackage?->origin_address_id,
                'destination_address_id' => $existingPackage?->destination_address_id,
                'service_id' => $existingPackage?->service_id,
                'user_id' => $payload['user_id'],
            ]);

            Order::attachPackage($lockedOrder->id, $package->id, (int) $payload['quantity']);

            $newSubtotal = DB::table('package_order')
                ->join('packages', 'package_order.package_id', '=', 'packages.id')
                ->where('package_order.order_id', $lockedOrder->id)
                ->sum('packages.single_price');

            $serviceSurchargeCents = $this->directOrder->recalculateOrderServiceSurcharge(
                $lockedOrder, $existingPackage?->service,
            );

            $lockedOrder->subtotal = (int) $newSubtotal + $serviceSurchargeCents;
            $lockedOrder->save();
            $rotateContext($lockedOrder);
        });

        $order = $order->fresh();
        $order->load(['packages.originAddress', 'packages.destinationAddress', 'packages.service']);

        return response()->json([
            'success' => true,
            'message' => 'Collo aggiunto con successo.',
            'order' => new OrderResource($order),
        ]);
    }
}
