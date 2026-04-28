<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminBankTransferController extends Controller
{
    /**
     * Lista dei bonifici in attesa di conferma (paginata).
     */
    public function pending(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['user:id,name,surname,email', 'packages.originAddress', 'packages.destinationAddress'])
            ->where('status', Order::AWAITING_BANK_TRANSFER)
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response()->json($orders);
    }

    /**
     * Conferma la ricezione del bonifico e sblocca il flusso spedizione.
     */
    public function confirm(\App\Http\Requests\ConfirmBankTransferRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();

        if ($order->getRawOriginal('status') !== Order::AWAITING_BANK_TRANSFER) {
            return response()->json([
                'success' => false,
                'message' => 'L\'ordine non è in attesa di bonifico.',
            ], 422);
        }

        $adminId = (int) ($request->user()->id ?? 0);
        $extId = 'bank_transfer_order_'.$order->id.'_'.now()->format('YmdHis');

        $transaction = null;
        DB::transaction(function () use ($order, $data, $adminId, $extId, &$transaction) {
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->getRawOriginal('status') !== Order::AWAITING_BANK_TRANSFER) {
                return;
            }

            // Aggiorna eventuale transazione bonifico pending → succeeded, oppure ne crea una nuova.
            $existing = $lockedOrder->transactions()
                ->where('type', 'bonifico')
                ->latest('id')
                ->first();

            if ($existing) {
                $existing->status = 'succeeded';
                $existing->provider_status = 'succeeded';
                $existing->total = $lockedOrder->payableTotalCents();
                $existing->save();
                $transaction = $existing;
            } else {
                $transaction = Transaction::create([
                    'order_id' => $lockedOrder->id,
                    'ext_id' => $extId,
                    'type' => 'bank_transfer_received',
                    'status' => 'succeeded',
                    'provider_status' => 'succeeded',
                    'total' => $lockedOrder->payableTotalCents(),
                ]);
            }

            $lockedOrder->status = Order::COMPLETED;
            $lockedOrder->bank_transfer_confirmed_at = now();
            $lockedOrder->bank_transfer_reference = $data['bank_transfer_reference'] ?? null;
            $lockedOrder->bank_transfer_confirmed_by = $adminId ?: null;
            $lockedOrder->save();
        });

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile confermare il bonifico: stato cambiato concorrentemente.',
            ], 409);
        }

        $fresh = $order->fresh();

        // Scatena OrderPaid per trigger generazione etichetta BRT + mail conferma.
        try {
            event(new OrderPaid($fresh, $transaction));
        } catch (\Throwable $e) {
            Log::error('OrderPaid dispatch failed after bank transfer confirm', [
                'order_id' => $fresh->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bonifico confermato. Ordine in lavorazione.',
            'data' => $fresh->load('transactions'),
        ]);
    }
}
