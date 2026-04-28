<?php

/**
 * OrderListController -- Lista e paginazione degli ordini.
 *
 * Estratto da OrderController: gestisce solo index (lista ordini paginata).
 * Admin vede tutti gli ordini; utente normale vede solo i propri.
 */

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderListController extends Controller
{
    /**
     * Mostra la lista degli ordini.
     * Se l'utente e' un amministratore, vede gli ordini di TUTTI gli utenti.
     * Se e' un utente normale, vede solo i PROPRI ordini.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $user = $request->user();

        $eagerLoad = [
            'packages.originAddress',
            'packages.destinationAddress',
            'packages.service',
            'transactions',
        ];

        if ($user->isAdmin()) {
            $eagerLoad[] = 'user';
            $orders = Order::with($eagerLoad)
                ->orderByDesc('created_at')
                ->paginate(30);
        } else {
            $orders = Order::with($eagerLoad)
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->paginate(30);
        }

        return OrderResource::collection($orders);
    }
}
