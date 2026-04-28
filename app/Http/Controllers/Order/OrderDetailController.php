<?php

/**
 * OrderDetailController -- Dettaglio ordine, creazione diretta, annullamento, aggiunta collo, fattura.
 *
 * Estratto da OrderController: gestisce show, createDirectOrder, cancel, addPackage, invoice.
 * Prezzi in centesimi. createDirectOrder() ricalcola server-side via PriceEngineService.
 * Annullamento delegato a RefundController.
 */

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\NormalizesServiceData;

use App\Http\Requests\PackageStoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Package;
use App\Services\CartService;
use App\Services\CheckoutSubmissionContextService;
use App\Services\DirectOrderService;
use App\Services\InvoicePdfService;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrderDetailController extends Controller
{
    use NormalizesServiceData;

    public function __construct(
        private readonly CheckoutSubmissionContextService $submissionContext,
        private readonly DirectOrderService $directOrder,
        private readonly CartService $cartService,
    ) {}

    private function hydrateMissingOrderSubmissionContext(Order $order, array $incomingContext = []): void
    {
        $needsHydration = blank($order->client_submission_id)
            || blank($order->pricing_signature)
            || blank($order->pricing_snapshot)
            || blank($order->pricing_snapshot_version);

        if (! $needsHydration) {
            return;
        }

        $packages = $order->packages()->with(['originAddress', 'destinationAddress', 'service'])->get();
        if ($packages->isEmpty()) {
            return;
        }

        $contextSeed = [];
        $preferredSubmissionId = trim((string) ($order->client_submission_id ?: ($incomingContext['client_submission_id'] ?? '')));
        if ($preferredSubmissionId !== '') {
            $contextSeed['client_submission_id'] = $preferredSubmissionId;
        }
        if (array_key_exists('discount_context', $incomingContext)) {
            $contextSeed['discount_context'] = $incomingContext['discount_context'];
        }

        $context = $this->submissionContext->enrich(
            $contextSeed,
            $this->submissionContext->snapshotFromPackages($packages),
            [
                'user_id' => (int) $order->user_id,
                'order_id' => (int) $order->id,
                'flow' => 'existing-order',
            ],
        );

        $updates = [];
        foreach (['client_submission_id', 'pricing_signature', 'pricing_snapshot_version', 'pricing_snapshot'] as $field) {
            if (blank($order->getAttribute($field))) {
                $updates[$field] = $context[$field];
            }
        }

        if ($updates !== []) {
            $order->forceFill($updates)->save();
        }
    }

    private function rotatePendingOrderSubmissionContext(Order $order): void
    {
        $packages = $order->packages()->with(['originAddress', 'destinationAddress', 'service'])->get();
        if ($packages->isEmpty()) {
            return;
        }

        $context = $this->submissionContext->enrich(
            [],
            $this->submissionContext->snapshotFromPackages($packages),
            [
                'user_id' => (int) $order->user_id,
                'order_id' => (int) $order->id,
                'flow' => 'pending-order-refresh',
                'previous_submission_id' => (string) ($order->client_submission_id ?? ''),
            ],
        );

        $order->forceFill([
            'client_submission_id' => $context['client_submission_id'],
            'pricing_signature' => $context['pricing_signature'],
            'pricing_snapshot_version' => $context['pricing_snapshot_version'],
            'pricing_snapshot' => $context['pricing_snapshot'],
        ])->save();
    }

    /**
     * Mostra i dettagli di un singolo ordine.
     */
    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        $this->hydrateMissingOrderSubmissionContext($order);
        $order->refresh();

        $order->load([
            'packages.originAddress',
            'packages.destinationAddress',
            'packages.service',
            'transactions',
        ]);

        return new OrderResource($order);
    }

    /**
     * createDirectOrder -- Crea un ordine direttamente dalla pagina di riepilogo (senza carrello).
     *
     * I prezzi vengono RICALCOLATI lato server per evitare manipolazioni dal frontend.
     */
    public function createDirectOrder(PackageStoreRequest $request)
    {
        $data = $request->validated();
        $userId = auth()->id();
        $servicesData = $this->normalizeServiceData($data['services'] ?? []);
        $servicesData = $this->cartService->applyPudoData($servicesData, $data);
        $serviceData = $servicesData['service_data'] ?? [];

        // 1. Price packages server-side (security: never trust frontend prices)
        $pricing = $this->directOrder->pricePackages(
            $data['packages'],
            $data['origin_address']['postal_code'] ?? null,
            $data['destination_address']['postal_code'] ?? null,
        );
        $pricedPackages = $pricing['priced_packages'];
        $subtotalCents = $pricing['subtotal_cents'];

        // 2. COD, Insurance and service surcharges (audit F01/F02)
        $cod = $this->directOrder->resolveCodDetails($servicesData, $serviceData);
        $insurance = $this->directOrder->resolveInsuranceDetails($servicesData, $serviceData);
        $serviceSurchargeCents = $this->directOrder->calculateServiceSurcharge(
            $servicesData, $serviceData, $pricedPackages, $data,
        );
        $orderSubtotalCents = $subtotalCents + $serviceSurchargeCents;

        $pudoId = null;
        if (! empty($data['pudo']['pudo_id']) && ($data['delivery_mode'] ?? 'home') === 'pudo') {
            $pudoId = $data['pudo']['pudo_id'];
        }

        // 3. Persist inside a transaction with idempotency check
        return DB::transaction(function () use (
            $data, $userId, $pricedPackages, $servicesData, $cod, $insurance,
            $pudoId, $orderSubtotalCents, $request,
        ) {
            DB::table('users')->where('id', $userId)->lockForUpdate()->first();

            $submissionContext = $this->submissionContext->enrich(
                $this->submissionContext->fromRequestArray($request->validated()),
                $this->submissionContext->snapshotFromDirectOrderPayload([
                    ...$data,
                    'packages' => $pricedPackages,
                    'services' => $servicesData,
                ], $orderSubtotalCents),
                [
                    'user_id' => $userId,
                    'flow' => 'direct-order',
                    'billing_data' => $data['billing_data'] ?? null,
                ],
            );

            // Idempotency: return existing order if submission already processed
            $existingOrder = Order::query()
                ->where('user_id', $userId)
                ->where('client_submission_id', $submissionContext['client_submission_id'])
                ->first();

            if ($existingOrder) {
                $this->hydrateMissingOrderSubmissionContext($existingOrder, [
                    'client_submission_id' => $submissionContext['client_submission_id'],
                    'discount_context' => $submissionContext['discount_context'] ?? null,
                ]);
                $existingOrder->refresh();

                if ($discountContextError = $this->syncDiscountContextOnOrder($existingOrder, $submissionContext)) {
                    return $discountContextError;
                }

                if (
                    filled($existingOrder->pricing_signature)
                    && $existingOrder->pricing_signature !== $submissionContext['pricing_signature']
                ) {
                    return response()->json(['error' => 'Contesto preventivo non coerente con l\'ordine.'], 422);
                }

                return response()->json([
                    'order_id' => $existingOrder->id,
                    'order_number' => 'SF-' . str_pad((string) $existingOrder->id, 6, '0', STR_PAD_LEFT),
                    'amount_cents' => $existingOrder->payableTotalCents(),
                    'client_submission_id' => (string) $existingOrder->client_submission_id,
                ]);
            }

            $result = $this->directOrder->persistDirectOrder(
                $data, $userId, $pricedPackages, $servicesData,
                $cod['is_cod'], $cod['cod_amount'], $pudoId,
                $orderSubtotalCents, $submissionContext,
                $cod['cod_payment_type'] ?? null,
                $cod['cod_incasso_type'] ?? null,
                $insurance['insurance_amount_cents'] ?? null,
            );

            return response()->json($result);
        });
    }

    /**
     * Annulla un ordine con eventuale rimborso.
     *
     * Calls RefundService directly (not RefundController) to avoid bypassing
     * middleware. Authorization is handled via OrderPolicy::cancel().
     */
    public function cancel(\App\Http\Requests\CancelOrderRequest $request, Order $order)
    {
        Gate::authorize('cancel', $order);

        // Quick pre-check outside the transaction for a fast 422 response.
        $refundService = app(RefundService::class);
        $preCheck = $refundService->calculateEligibility($order);
        if (! $preCheck['eligible']) {
            return response()->json(['error' => $preCheck['reason']], 422);
        }

        try {
            $result = $refundService->processCancellation($order, $request->reason);

            $refundEur     = number_format($result['refund_amount_cents'] / 100, 2, ',', '.');
            $commissionEur = number_format($result['commission_cents'] / 100, 2, ',', '.');

            return response()->json([
                'success'        => true,
                'message'        => $result['refund_amount_cents'] > 0
                    ? "Ordine annullato. Rimborso di {$refundEur} EUR processato (commissione: {$commissionEur} EUR)."
                    : 'Ordine annullato con successo.',
                'refund_amount'  => $refundEur,
                'commission'     => $commissionEur,
                'refund_method'  => $result['refund_method'],
                'brt_cancelled'  => $result['brt_cancelled'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Order cancellation failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Errore durante l\'annullamento dell\'ordine. Riprova o contatta l\'assistenza.',
            ], 500);
        }
    }

    private function syncDiscountContextOnOrder(Order $order, array $context): ?\Illuminate\Http\JsonResponse
    {
        $incomingDiscountContext = $this->normalizeDiscountContextValue($context['discount_context'] ?? null);

        if ($incomingDiscountContext === null) {
            return null;
        }

        $currentSnapshot = $order->getAttribute('pricing_snapshot');
        $resolvedSnapshot = is_array($currentSnapshot) ? $currentSnapshot : [];
        $currentDiscountContext = $this->normalizeDiscountContextValue($resolvedSnapshot['discount_context'] ?? null);

        if ($currentDiscountContext !== null && $currentDiscountContext !== $incomingDiscountContext) {
            return response()->json(['error' => 'Contesto preventivo non coerente con l\'ordine.'], 422);
        }

        if ($currentDiscountContext === null) {
            $resolvedSnapshot['discount_context'] = $incomingDiscountContext;
            $order->forceFill([
                'pricing_snapshot' => $resolvedSnapshot,
            ])->save();
        }

        return null;
    }

    private function normalizeDiscountContextValue(mixed $value): ?array
    {
        $normalized = $this->submissionContext->fromRequestArray([
            'discount_context' => $value,
        ]);

        return is_array($normalized['discount_context'] ?? null)
            ? $normalized['discount_context']
            : null;
    }

    /**
     * addPackage -- Aggiunge un collo a un ordine in attesa di pagamento.
     *
     * Il prezzo viene ricalcolato lato server e il subtotale dell'ordine aggiornato.
     */
    public function addPackage(\App\Http\Requests\AddOrderPackageRequest $request, Order $order)
    {
        Gate::authorize('addPackage', $order);

        if (! in_array($order->status, [Order::PENDING, Order::PAYMENT_FAILED])) {
            return response()->json(['error' => 'Si possono aggiungere colli solo agli ordini in attesa di pagamento.'], 422);
        }

        DB::transaction(function () use ($request, $order) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $weight = (float) $request->weight;
            $s1 = (float) $request->first_size;
            $s2 = (float) $request->second_size;
            $s3 = (float) $request->third_size;
            $quantity = (int) $request->quantity;

            $lockedOrder->loadMissing(['packages.originAddress', 'packages.destinationAddress']);
            /** @var Package|null $existingPackage */
            $existingPackage = $lockedOrder->packages->first();
            $originCap = $existingPackage?->originAddress?->postal_code ?? null;
            $destinationCap = $existingPackage?->destinationAddress?->postal_code ?? null;

            // Price the new package server-side
            $priced = $this->directOrder->priceSinglePackage(
                $weight, $s1, $s2, $s3, $quantity, $originCap, $destinationCap,
            );

            // Reuse origin/destination from existing packages
            $package = Package::create([
                'package_type' => $request->package_type,
                'quantity' => $quantity,
                'weight' => $request->weight,
                'first_size' => $request->first_size,
                'second_size' => $request->second_size,
                'third_size' => $request->third_size,
                'weight_price' => $priced['weight_price'],
                'volume_price' => $priced['volume_price'],
                'single_price' => $priced['single_price_cents'],
                'content_description' => $request->content_description,
                'origin_address_id' => $existingPackage?->origin_address_id,
                'destination_address_id' => $existingPackage?->destination_address_id,
                'service_id' => $existingPackage?->service_id,
                'user_id' => auth()->id(),
            ]);

            Order::attachPackage($lockedOrder->id, $package->id, $quantity);

            // Recalculate subtotal including service surcharge
            $newSubtotal = DB::table('package_order')
                ->join('packages', 'package_order.package_id', '=', 'packages.id')
                ->where('package_order.order_id', $lockedOrder->id)
                ->sum('packages.single_price');

            $serviceSurchargeCents = $this->directOrder->recalculateOrderServiceSurcharge(
                $lockedOrder, $existingPackage?->service,
            );

            $lockedOrder->subtotal = (int) $newSubtotal + $serviceSurchargeCents;
            $lockedOrder->save();
            $this->rotatePendingOrderSubmissionContext($lockedOrder);

            return $package;
        });

        $order = $order->fresh();
        $order->load(['packages.originAddress', 'packages.destinationAddress', 'packages.service']);

        return response()->json([
            'success' => true,
            'message' => 'Collo aggiunto con successo.',
            'order' => new OrderResource($order),
        ]);
    }

    /**
     * invoice -- Genera e scarica la ricevuta PDF di un ordine.
     */
    public function invoice(Order $order, InvoicePdfService $invoicePdf)
    {
        Gate::authorize('view', $order);

        $pdfContent = $invoicePdf->generate($order);

        $orderNumber = 'SF-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="ricevuta-' . $orderNumber . '.pdf"',
            'Content-Length' => strlen($pdfContent),
        ]);
    }
}
