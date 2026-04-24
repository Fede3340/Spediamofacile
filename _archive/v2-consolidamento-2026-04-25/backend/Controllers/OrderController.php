<?php

/**
 * OrderController -- Thin orchestrator that delegates to focused controllers.
 *
 * Split into:
 *   - OrderListController   (index)
 *   - OrderDetailController (show, cancel, createDirectOrder, addPackage, invoice)
 *
 * This file is kept for backward compatibility. All route files now point directly
 * to the new controllers. If any code resolves OrderController via the container,
 * the methods below delegate transparently.
 *
 * @deprecated Use OrderListController or OrderDetailController directly.
 */

namespace App\Http\Controllers;

use App\Http\Requests\PackageStoreRequest;
use App\Models\Order;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return app(OrderListController::class)->index($request);
    }

    public function show(Order $order)
    {
        return app(OrderDetailController::class)->show($order);
    }

    public function createDirectOrder(PackageStoreRequest $request)
    {
        return app(OrderDetailController::class)->createDirectOrder($request);
    }

    public function cancel(Request $request, Order $order)
    {
        return app(OrderDetailController::class)->cancel($request, $order);
    }

    public function addPackage(Request $request, Order $order)
    {
        return app(OrderDetailController::class)->addPackage($request, $order);
    }

    public function invoice(Order $order, InvoicePdfService $invoicePdf)
    {
        return app(OrderDetailController::class)->invoice($order, $invoicePdf);
    }
}
