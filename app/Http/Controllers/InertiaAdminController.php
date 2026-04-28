<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PriceBand;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InertiaAdminController extends Controller
{
    public function dashboard(): Response
    {
        $stats = [
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'revenue_month' => number_format(Order::whereMonth('created_at', now()->month)->sum('payable_total_cents') / 100, 2, ',', '.') . ' €',
            'users_total' => User::count(),
            'bank_transfers_pending' => Order::where('status', 'awaiting_bank_transfer')->count(),
        ];
        return Inertia::render('Account/Admin/Dashboard', ['stats' => $stats]);
    }

    public function ordini(Request $request): Response
    {
        $orders = Order::with('user')
            ->latest()
            ->paginate(25)
            ->through(fn ($o) => [
                'id' => $o->id,
                'user_name' => $o->user?->name . ' ' . $o->user?->surname,
                'route' => '—',
                'total' => number_format($o->payable_total_cents / 100, 2, ',', '.') . ' €',
                'status' => $o->status,
                'status_class' => 'bg-[var(--color-brand-bg)] text-[var(--color-brand-text)]',
            ]);
        return Inertia::render('Account/Admin/Ordini', ['orders' => $orders]);
    }

    public function utenti(): Response
    {
        $users = User::latest()->paginate(25)
            ->through(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'surname' => $u->surname,
                'email' => $u->email, 'role' => $u->role ?? 'User',
                'email_verified_at' => $u->email_verified_at,
            ]);
        return Inertia::render('Account/Admin/Utenti', ['users' => $users]);
    }

    public function spedizioni(Request $request): Response
    {
        $orders = Order::whereIn('status', ['paid', 'label_generated', 'in_transit'])
            ->latest()
            ->paginate(25)
            ->through(fn ($o) => [
                'id' => $o->id,
                'user_name' => $o->user?->name,
                'route' => '—',
                'total' => number_format($o->payable_total_cents / 100, 2, ',', '.') . ' €',
                'status' => $o->status,
                'status_class' => 'bg-[var(--color-brand-bg)] text-[var(--color-brand-text)]',
            ]);
        return Inertia::render('Account/Admin/Ordini', ['orders' => $orders]);
    }

    public function bonifici(): Response
    {
        $orders = Order::where('status', 'awaiting_bank_transfer')
            ->with('user')
            ->latest()
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'user_name' => $o->user?->name . ' ' . $o->user?->surname,
                'total' => number_format($o->payable_total_cents / 100, 2, ',', '.') . ' €',
                'created_at' => $o->created_at?->format('d/m/Y'),
            ]);
        return Inertia::render('Account/Admin/Bonifici', ['orders' => $orders]);
    }

    public function prezzi(): Response
    {
        return Inertia::render('Account/Admin/Prezzi', [
            'weightBands' => PriceBand::where('type', 'weight')->orderBy('sort_order')->get(),
            'volumeBands' => PriceBand::where('type', 'volume')->orderBy('sort_order')->get(),
        ]);
    }

    public function impostazioni(): Response
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        return Inertia::render('Account/Admin/Impostazioni', ['settings' => $settings]);
    }

    public function updateImpostazioni(Request $request): RedirectResponse
    {
        foreach ($request->all() as $key => $value) {
            if (! is_string($key)) continue;
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return back()->with('success', 'Impostazioni salvate.');
    }

    /** Admin: cambia stato ordine. */
    public function changeOrderStatus(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|string']);
        $order = Order::findOrFail($id);
        $order->update(['status' => $data['status']]);
        return back()->with('success', "Stato ordine #{$id} aggiornato a {$data['status']}.");
    }

    /** Admin: conferma bonifico ricevuto → marca ordine come paid. */
    public function confermaBonifico(Request $request, int $id): RedirectResponse
    {
        $order = Order::where('id', $id)->where('status', 'awaiting_bank_transfer')->firstOrFail();
        $order->update(['status' => 'paid']);
        event(new \App\Events\OrderPaid($order));
        return back()->with('success', "Bonifico ordine #{$id} confermato.");
    }

    /** Admin: rigenera etichetta BRT per ordine pagato. */
    public function regeneraEtichetta(Request $request, int $id, \App\Services\BrtClient $brt): RedirectResponse
    {
        $order = Order::findOrFail($id);
        try {
            $brt->generateLabel($order, 'pdf');
            return back()->with('success', "Etichetta ordine #{$id} rigenerata.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Errore BRT: ' . $e->getMessage());
        }
    }

    /** Admin: salva price bands. */
    public function savePriceBands(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bands' => 'required|array',
            'bands.*.id' => 'required|integer|exists:price_bands,id',
            'bands.*.base_price' => 'required|integer|min:0',
        ]);
        foreach ($data['bands'] as $b) {
            PriceBand::where('id', $b['id'])->update(['base_price' => $b['base_price']]);
        }
        return back()->with('success', 'Prezzi aggiornati.');
    }
}
