<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\ContactMessage;
use App\Models\WithdrawalRequest;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * dashboard -- Mostra la panoramica completa del sito con tutte le statistiche.
     *
     * PERCHE': L'admin ha bisogno di una vista d'insieme su ordini, fatturato, utenti e spedizioni.
     * COME LEGGERLO: 1) Statistiche ordini (totali, per periodo)  2) Fatturato da transazioni
     *   3) Statistiche utenti  4) Statistiche spedizioni BRT  5) Grafico ordini 30 giorni
     *   6) Ultimi 5 ordini  7) Conteggio notifiche (messaggi, prelievi, richieste Pro)
     * COME MODIFICARLO: Per aggiungere una statistica, aggiungerla nel blocco appropriato
     *   e includerla nell'array di risposta JSON.
     * COSA EVITARE: Non fare query pesanti senza indici -- verificare le performance con molti ordini.
     */
    public function dashboard(): JsonResponse
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();

        // STATISTICHE ORDINI
        $totalOrders = Order::count();
        $completedOrders = Order::where('status', 'completed')->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $processingOrders = Order::where('status', 'processing')->count();
        $ordersToday = Order::where('created_at', '>=', $todayStart)->count();
        $ordersWeek = Order::where('created_at', '>=', $weekStart)->count();
        $ordersMonth = Order::where('created_at', '>=', $monthStart)->count();
        $paymentFailedOrders = Order::where('status', 'payment_failed')->count();

        // STATISTICHE FATTURATO (somma delle transazioni andate a buon fine)
        $totalRevenue = Transaction::where('status', 'succeeded')->sum('total');
        $revenueMonth = Transaction::where('status', 'succeeded')
            ->where('created_at', '>=', $monthStart)->sum('total');

        // STATISTICHE UTENTI
        $totalUsers = User::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $proUsers = User::where('role', 'Partner Pro')->count();

        // STATISTICHE SPEDIZIONI BRT
        $shipmentsWithLabel = Order::whereNotNull('brt_parcel_id')->count();
        $shipmentsInTransit = Order::where('status', 'in_transit')->count();
        $shipmentsDelivered = Order::where('status', 'delivered')->count();
        $ordersWithoutLabel = Order::whereNull('brt_parcel_id')
            ->whereIn('status', ['completed', 'paid', 'processing'])
            ->count();

        // GRAFICO ORDINI GIORNALIERI (ultimi 30 giorni)
        $dailyOrders = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->startOfDay();
            $dayEnd = $day->copy()->endOfDay();
            $dailyOrders[] = [
                'date' => $day->format('d/m'),
                'count' => Order::whereBetween('created_at', [$day, $dayEnd])->count(),
            ];
        }

        // Ultimi 5 ordini (per la sezione "ordini recenti" della dashboard)
        $recentOrders = Order::with('user:id,name,surname,email')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'user_id', 'status', 'subtotal', 'created_at']);

        // NOTIFICHE: conteggio di cose che richiedono attenzione dell'admin
        $unreadMessages = ContactMessage::whereNull('read_at')->count();
        $pendingWithdrawals = WithdrawalRequest::where('status', 'pending')->count();
        $pendingProRequests = \App\Models\ProRequest::where('status', 'pending')->count();

        return response()->json([
            'orders' => [
                'total' => $totalOrders,
                'completed' => $completedOrders,
                'pending' => $pendingOrders,
                'processing' => $processingOrders,
                'today' => $ordersToday,
                'week' => $ordersWeek,
                'month' => $ordersMonth,
                'payment_failed' => $paymentFailedOrders,
            ],
            'revenue' => $totalRevenue,
            'revenue_month' => $revenueMonth,
            'users' => [
                'total' => $totalUsers,
                'verified' => $verifiedUsers,
                'pro' => $proUsers,
            ],
            'shipments' => [
                'with_label' => $shipmentsWithLabel,
                'in_transit' => $shipmentsInTransit,
                'delivered' => $shipmentsDelivered,
                'without_label' => $ordersWithoutLabel,
            ],
            'daily_orders' => $dailyOrders,
            'recent_orders' => $recentOrders,
            'unread_messages' => $unreadMessages,
            'pending_withdrawals' => $pendingWithdrawals,
            'pending_pro_requests' => $pendingProRequests,
        ]);
    }
}
