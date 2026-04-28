<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InertiaAccountController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $request->user();
        $stats = [
            'shipments' => $user->orders()->count(),
            'in_transit' => $user->orders()->where('status', 'in_transit')->count(),
            'wallet_balance' => number_format(($user->wallet_balance_cents ?? 0) / 100, 2, ',', '.') . ' €',
            'invoices' => $user->orders()->whereNotNull('invoice_number')->count(),
        ];

        $recentOrders = $user->orders()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'route_label' => $o->packages->first()?->origin_address?->city . ' → ' . $o->packages->first()?->destination_address?->city,
                'created_at' => $o->created_at?->format('d/m/Y'),
                'total' => number_format($o->payable_total_cents / 100, 2, ',', '.') . ' €',
            ]);

        return Inertia::render('Account/Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }

    public function spedizioni(Request $request): Response
    {
        $orders = $request->user()->orders()
            ->latest()
            ->paginate(20)
            ->through(fn ($o) => [
                'id' => $o->id,
                'route_label' => ($o->packages->first()?->origin_address?->city ?? '—') . ' → ' . ($o->packages->first()?->destination_address?->city ?? '—'),
                'payable_total' => number_format($o->payable_total_cents / 100, 2, ',', '.') . ' €',
                'status' => $o->status,
                'status_label' => __('order.status.' . $o->status, [], 'it') ?: $o->status,
                'status_class' => 'bg-[var(--color-brand-bg)] text-[var(--color-brand-text)]',
            ]);

        return Inertia::render('Account/Spedizioni', ['orders' => $orders]);
    }

    public function profilo(Request $request): Response
    {
        return Inertia::render('Account/Profilo', [
            'user' => $request->user()->only('id', 'name', 'surname', 'email', 'telephone_number'),
        ]);
    }

    public function updateProfilo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'surname' => 'nullable|string|max:50',
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
            'telephone_number' => 'nullable|string|max:30',
        ]);
        $request->user()->update($data);
        return back()->with('success', 'Profilo aggiornato.');
    }

    public function indirizzi(Request $request): Response
    {
        return Inertia::render('Account/Indirizzi', [
            'addresses' => UserAddress::where('user_id', $request->user()->id)->get(),
        ]);
    }

    public function fatture(Request $request): Response
    {
        $invoices = $request->user()->orders()
            ->whereNotNull('invoice_number')
            ->latest()
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'number' => $o->invoice_number,
                'date' => $o->invoice_date?->format('d/m/Y'),
                'amount' => number_format($o->payable_total_cents / 100, 2, ',', '.') . ' €',
            ]);
        return Inertia::render('Account/Fatture', ['invoices' => $invoices]);
    }

    public function portafoglio(Request $request): Response
    {
        $user = $request->user();
        $movements = $user->walletMovements()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'description' => $m->description,
                'date' => $m->created_at?->format('d/m/Y H:i'),
                'amount' => ($m->amount_cents > 0 ? '+' : '') . number_format($m->amount_cents / 100, 2, ',', '.') . ' €',
            ]);

        return Inertia::render('Account/Portafoglio', [
            'balance' => number_format(($user->wallet_balance_cents ?? 0) / 100, 2, ',', '.') . ' €',
            'movements' => $movements,
        ]);
    }

    public function assistenza(): Response
    {
        return Inertia::render('Account/Assistenza');
    }
}
