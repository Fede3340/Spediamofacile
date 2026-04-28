<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\ReferralUsage;
use Illuminate\Http\JsonResponse;

class ReferralStatsController extends Controller
{
    // Mostra le statistiche di tutti i codici referral utilizzati
    // Include: chi ha usato quale codice, quanto sconto e' stato dato, quante commissioni
    public function referralStats(): JsonResponse
    {
        // Calcola i totali complessivi su TUTTI i record (aggregazione DB, non in-memory)
        $summary = [
            'total_discount_given' => round((float) ReferralUsage::sum('discount_amount'), 2),
            'total_commissions' => round((float) ReferralUsage::sum('commission_amount'), 2),
            'total_order_amount' => round((float) ReferralUsage::sum('order_amount'), 2),
            'total_usages' => ReferralUsage::count(),
        ];

        $stats = ReferralUsage::with([
                'proUser:id,name,surname,email,referral_code',
                'buyer:id,name,surname,email',
            ])
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json([
            'data' => $stats,
            'summary' => $summary,
        ]);
    }
}
