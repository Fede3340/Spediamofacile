<?php

/**
 * OrderActionsController -- Azioni mutative su ordini: cancellazione, aggiunta collo,
 * creazione diretta (senza carrello). La logica vive in OrderActionsService;
 * il controller resta thin (validation -> service -> response).
 */

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesOrderSubmissionContext;
use App\Http\Controllers\Traits\NormalizesServiceData;
use App\Http\Requests\AddOrderPackageRequest;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\PackageStoreRequest;
use App\Models\Order;
use App\Services\CheckoutSubmissionContextService;
use App\Services\Order\OrderActionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrderActionsController extends Controller
{
    use HandlesOrderSubmissionContext;
    use NormalizesServiceData;

    public function __construct(
        private readonly CheckoutSubmissionContextService $submissionContext,
        private readonly OrderActionsService $actions,
    ) {}

    protected function submissionContextService(): CheckoutSubmissionContextService
    {
        return $this->submissionContext;
    }

    /**
     * Crea un ordine direttamente dalla pagina di riepilogo (senza carrello).
     * I prezzi vengono RICALCOLATI lato server per evitare manipolazioni dal frontend.
     */
    public function createDirectOrder(PackageStoreRequest $request)
    {
        $data = $request->validated();
        $data['_normalized_services'] = $this->normalizeServiceData($data['services'] ?? []);
        $userId = auth()->id();
        $bundle = $this->actions->prepareDirectOrderPayload($data);

        return DB::transaction(function () use ($data, $userId, $bundle) {
            DB::table('users')->where('id', $userId)->lockForUpdate()->first();

            $submissionContext = $this->actions->buildDirectOrderSubmissionContext($data, $userId, $bundle);

            $existingOrder = Order::query()
                ->where('user_id', $userId)
                ->where('client_submission_id', $submissionContext['client_submission_id'])
                ->first();

            if ($existingOrder) {
                return $this->respondToExistingOrder($existingOrder, $submissionContext);
            }

            return response()->json($this->actions->persistDirectOrder($data, $userId, $bundle, $submissionContext));
        });
    }

    /**
     * Annulla un ordine con eventuale rimborso.
     */
    public function cancel(CancelOrderRequest $request, Order $order)
    {
        Gate::authorize('cancel', $order);

        return $this->actions->cancel($order, $request->reason);
    }

    /**
     * Aggiunge un collo a un ordine in attesa di pagamento.
     * Il prezzo viene ricalcolato lato server e il subtotale dell'ordine aggiornato.
     */
    public function addPackage(AddOrderPackageRequest $request, Order $order)
    {
        Gate::authorize('addPackage', $order);

        $payload = [
            'package_type' => $request->package_type,
            'quantity' => $request->quantity,
            'weight' => $request->weight,
            'first_size' => $request->first_size,
            'second_size' => $request->second_size,
            'third_size' => $request->third_size,
            'content_description' => $request->content_description,
            'user_id' => auth()->id(),
        ];

        return $this->actions->addPackage(
            $order,
            $payload,
            fn (Order $locked) => $this->rotatePendingOrderSubmissionContext($locked),
        );
    }

    private function respondToExistingOrder(Order $existingOrder, array $submissionContext): JsonResponse
    {
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
            'order_number' => 'SF-'.str_pad((string) $existingOrder->id, 6, '0', STR_PAD_LEFT),
            'amount_cents' => $existingOrder->payableTotalCents(),
            'client_submission_id' => (string) $existingOrder->client_submission_id,
        ]);
    }
}
