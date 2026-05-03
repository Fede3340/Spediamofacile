<?php

/**
 * OrderDetailController -- Endpoint read-only per il dettaglio ordine + ricevuta PDF.
 *
 * Le azioni mutative (cancel, addPackage, createDirectOrder) vivono in
 * OrderActionsController per separazione responsabilita'.
 */

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesOrderSubmissionContext;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CheckoutSubmissionContextService;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\Gate;

class OrderDetailController extends Controller
{
    use HandlesOrderSubmissionContext;

    public function __construct(private readonly CheckoutSubmissionContextService $submissionContext) {}

    protected function submissionContextService(): CheckoutSubmissionContextService
    {
        return $this->submissionContext;
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
     * Genera e scarica la ricevuta PDF di un ordine.
     */
    public function invoice(Order $order, InvoicePdfService $invoicePdf)
    {
        Gate::authorize('view', $order);

        $pdfContent = $invoicePdf->generate($order);

        $orderNumber = 'SF-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="ricevuta-'.$orderNumber.'.pdf"',
            'Content-Length' => strlen($pdfContent),
        ]);
    }
}
