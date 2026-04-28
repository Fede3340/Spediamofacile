<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class InertiaShipmentController extends Controller
{
    private const STEPS = ['colli', 'servizi', 'indirizzi', 'pagamento'];

    public function preventivo(): Response
    {
        return Inertia::render('Shipment/Preventivo', [
            'startingPrice' => '8,90',
        ]);
    }

    /** Calcolo prezzo AJAX dal form Preventivo. */
    public function calcola(Request $request): JsonResponse
    {
        $data = $request->validate([
            'origin_postal_code' => 'required|string|size:5',
            'dest_postal_code' => 'required|string|size:5',
            'weight' => 'required|numeric|min:0.1',
            'first_size' => 'required|integer|min:1',
            'second_size' => 'required|integer|min:1',
            'third_size' => 'required|integer|min:1',
            'package_type' => 'required|in:Pacco,Pallet,Valigia',
            'quantity' => 'integer|min:1',
        ]);

        // Stub: calcolo banale, da rimpiazzare con PriceEngineService reale.
        $weight = (float) $data['weight'];
        $price = match (true) {
            $weight <= 2 => 8.90,
            $weight <= 5 => 11.90,
            $weight <= 10 => 14.90,
            $weight <= 25 => 19.90,
            default => 29.90,
        };

        return response()->json([
            'total' => number_format($price, 2, ',', '.') . ' €',
            'total_cents' => (int) round($price * 100),
        ]);
    }

    public function step(Request $request, string $step): Response|RedirectResponse
    {
        if (! in_array($step, self::STEPS)) {
            return redirect('/preventivo');
        }
        return Inertia::render('Shipment/Funnel', [
            'step' => $step,
            'draft' => $request->session()->get('shipment_draft', []),
        ]);
    }

    public function inizia(Request $request): RedirectResponse
    {
        $request->session()->put('shipment_draft', $request->all());
        return redirect('/la-tua-spedizione/colli');
    }

    public function saveStep(Request $request, string $step): RedirectResponse
    {
        if (! in_array($step, self::STEPS)) {
            return redirect('/preventivo');
        }

        $draft = $request->session()->get('shipment_draft', []);
        $draft = array_merge($draft, $request->except('_token', 'step'));
        $request->session()->put('shipment_draft', $draft);

        $idx = array_search($step, self::STEPS);
        $next = self::STEPS[$idx + 1] ?? null;

        if (! $next) {
            return redirect('/checkout');
        }
        return redirect("/la-tua-spedizione/{$next}");
    }
}
