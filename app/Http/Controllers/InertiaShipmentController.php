<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddress;
use App\Services\OrderCreationService;
use App\Services\PriceEngineService;
use App\Services\StripeCheckoutSession;
use App\Services\WalletOrderPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Funnel preventivo end-to-end (Inertia v2):
 * - calcola() → JSON prezzo via PriceEngineService reale.
 * - inizia()/step()/saveStep() → 4 step in session (colli, servizi, indirizzi, pagamento).
 * - confermaOrdine() → crea Package+Order via OrderCreationService, redirect a Stripe/Wallet/Bonifico.
 */
class InertiaShipmentController extends Controller
{
    private const STEPS = ['colli', 'servizi', 'indirizzi', 'pagamento'];

    public function preventivo(): Response
    {
        return Inertia::render('Shipment/Preventivo', ['startingPrice' => '8,90']);
    }

    public function calcola(Request $request, PriceEngineService $pricing): JsonResponse
    {
        $data = $request->validate([
            'origin_postal_code' => 'required|string|size:5',
            'dest_postal_code' => 'required|string|size:5',
            'weight' => 'required|numeric|min:0.1|max:1000',
            'first_size' => 'required|numeric|min:1',
            'second_size' => 'required|numeric|min:1',
            'third_size' => 'required|numeric|min:1',
            'package_type' => 'required|in:Pacco,Pallet,Valigia',
            'quantity' => 'integer|min:1',
        ]);

        $qty = max(1, (int) ($data['quantity'] ?? 1));
        $volumeM3 = ($data['first_size'] * $data['second_size'] * $data['third_size']) / 1_000_000;

        try {
            $weightCents = $pricing->calculateBandPriceCents('weight', (float) $data['weight']);
            $volumeCents = $pricing->calculateBandPriceCents('volume', $volumeM3);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $singleCents = max($weightCents, $volumeCents);
        $capSupplementCents = $pricing->calculateCapSupplementCents($data['origin_postal_code'], $data['dest_postal_code']);
        $totalCents = ($singleCents * $qty) + $capSupplementCents;

        return response()->json([
            'total' => number_format($totalCents / 100, 2, ',', '.') . ' €',
            'total_cents' => $totalCents,
            'single_cents' => $singleCents,
            'cap_supplement_cents' => $capSupplementCents,
        ]);
    }

    public function step(Request $request, string $step): Response|RedirectResponse
    {
        if (! in_array($step, self::STEPS, true)) {
            return redirect('/preventivo');
        }
        return Inertia::render('Shipment/Funnel', [
            'step' => $step,
            'draft' => $request->session()->get('shipment_draft', []),
        ]);
    }

    public function inizia(Request $request): RedirectResponse
    {
        $request->session()->put('shipment_draft', $request->except('_token'));
        return redirect('/la-tua-spedizione/colli');
    }

    public function saveStep(Request $request, string $step): RedirectResponse
    {
        if (! in_array($step, self::STEPS, true)) {
            return redirect('/preventivo');
        }

        $draft = $request->session()->get('shipment_draft', []);
        $draft = array_merge($draft, $request->except('_token', 'step'));
        $request->session()->put('shipment_draft', $draft);

        if ($step === 'pagamento') {
            return $this->confermaOrdine($request, $draft);
        }

        $idx = array_search($step, self::STEPS, true);
        $next = self::STEPS[$idx + 1] ?? null;
        return redirect("/la-tua-spedizione/{$next}");
    }

    /**
     * Step finale: crea Order reale via OrderCreationService + redirect al gateway scelto.
     * Stripe → Checkout Session hosted. Wallet → addebito immediato. Bonifico → status awaiting.
     */
    private function confermaOrdine(Request $request, array $draft): RedirectResponse
    {
        if (! $request->user()) {
            return redirect('/login')->with('error', 'Accedi per completare l\'ordine.');
        }

        $packagesData = $draft['packages'] ?? [];
        $origin = $draft['origin'] ?? [];
        $destination = $draft['destination'] ?? [];
        $services = $draft['services'] ?? [];
        $paymentMethod = $draft['payment_method'] ?? 'stripe';

        if (empty($packagesData) || empty($origin) || empty($destination)) {
            return redirect('/la-tua-spedizione/colli')->with('error', 'Dati incompleti.');
        }

        $pricing = app(PriceEngineService::class);

        // 1. Crea Package + indirizzi (1 pacco = 1 record per ora, semplificato)
        $packageModels = collect();
        foreach ($packagesData as $pkg) {
            $volumeM3 = (($pkg['first_size'] ?? 0) * ($pkg['second_size'] ?? 0) * ($pkg['third_size'] ?? 0)) / 1_000_000;
            $weightCents = $pricing->calculateBandPriceCents('weight', (float) ($pkg['weight'] ?? 1));
            $volumeCents = $pricing->calculateBandPriceCents('volume', max(0.001, $volumeM3));
            $singleCents = max($weightCents, $volumeCents);

            $package = Package::create([
                'user_id' => $request->user()->id,
                'package_type' => $pkg['package_type'] ?? 'Pacco',
                'quantity' => (int) ($pkg['quantity'] ?? 1),
                'weight' => (string) ($pkg['weight'] ?? 1),
                'first_size' => (int) ($pkg['first_size'] ?? 1),
                'second_size' => (int) ($pkg['second_size'] ?? 1),
                'third_size' => (int) ($pkg['third_size'] ?? 1),
                'single_price' => $singleCents,
                'weight_price' => $weightCents,
                'volume_price' => $volumeCents,
            ]);

            PackageAddress::create([
                'package_id' => $package->id,
                'type' => 'Partenza',
                'name' => $origin['name'] ?? '',
                'address' => $origin['address'] ?? '',
                'address_number' => $origin['address_number'] ?? '',
                'postal_code' => $origin['postal_code'] ?? '',
                'city' => $origin['city'] ?? '',
                'province' => strtoupper($origin['province'] ?? ''),
                'country' => 'Italia',
                'telephone_number' => $origin['telephone_number'] ?? '',
                'email' => $origin['email'] ?? '',
            ]);

            PackageAddress::create([
                'package_id' => $package->id,
                'type' => 'Destinazione',
                'name' => $destination['name'] ?? '',
                'address' => $destination['address'] ?? '',
                'address_number' => $destination['address_number'] ?? '',
                'postal_code' => $destination['postal_code'] ?? '',
                'city' => $destination['city'] ?? '',
                'province' => strtoupper($destination['province'] ?? ''),
                'country' => 'Italia',
                'telephone_number' => $destination['telephone_number'] ?? '',
                'email' => $destination['email'] ?? '',
            ]);

            $packageModels->push($package->fresh(['originAddress', 'destinationAddress']));
        }

        // 2. Crea Order via service esistente
        $orderService = app(OrderCreationService::class);
        $billingData = ['email' => $request->user()->email];
        $submissionContext = ['client_submission_id' => 'inertia-' . time() . '-' . $request->user()->id];

        try {
            $orders = $orderService->createOrdersFromPackages($packageModels, $request->user()->id, $billingData, $submissionContext);
        } catch (\Throwable $e) {
            return redirect('/la-tua-spedizione/pagamento')->with('error', 'Errore creazione ordine: ' . $e->getMessage());
        }

        $order = is_array($orders) ? ($orders[0] ?? null) : $orders;
        if (! $order instanceof Order) {
            return redirect('/la-tua-spedizione/pagamento')->with('error', 'Ordine non creato.');
        }

        // 3. Reset draft
        $request->session()->forget('shipment_draft');

        // 4. Redirect al gateway scelto
        if ($paymentMethod === 'wallet') {
            $wallet = app(WalletOrderPaymentService::class);
            try {
                $wallet->createOrReuseOrderDebit($request->user(), $order, $order->payable_total_cents / 100, "Ordine #{$order->id}");
                $order->update(['status' => 'paid']);
                return redirect('/checkout/success?order_id=' . $order->id);
            } catch (\Throwable $e) {
                return redirect('/account/portafoglio')->with('error', 'Saldo insufficiente: ' . $e->getMessage());
            }
        }

        if ($paymentMethod === 'bank_transfer') {
            $order->update(['status' => 'awaiting_bank_transfer']);
            return redirect('/checkout/success?order_id=' . $order->id)->with('success', 'Ordine creato. Effettua il bonifico secondo le istruzioni email.');
        }

        // Default: Stripe Checkout hosted
        $stripe = app(StripeCheckoutSession::class);
        try {
            $url = $stripe->create($order, url('/checkout/return'), url('/checkout/cancel'));
            return redirect()->away($url);
        } catch (\Throwable $e) {
            return redirect('/la-tua-spedizione/pagamento')->with('error', 'Stripe error: ' . $e->getMessage());
        }
    }
}
