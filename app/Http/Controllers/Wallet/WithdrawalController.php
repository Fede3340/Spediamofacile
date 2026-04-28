<?php
namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class WithdrawalController extends Controller
{
    // Mostra la lista di tutte le richieste di prelievo dell'utente che ha fatto la richiesta
    // Ordinate dalla piu' recente alla piu' vecchia
    public function index(): JsonResponse
    {
        $requests = WithdrawalRequest::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $requests]);
    }

    // Crea una nuova richiesta di prelievo
    // Solo gli utenti Partner Pro possono fare questa operazione
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Controlliamo che l'utente sia un Partner Pro
        // Solo i Partner Pro possono prelevare le commissioni
        if (!$user->isPro()) {
            return response()->json(['message' => 'Solo gli account Pro possono richiedere prelievi.'], 403);
        }

        $result = DB::transaction(function () use ($user) {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Trattiamo i pending come saldo riservato: una sola richiesta aperta per volta.
            $pendingExists = WithdrawalRequest::query()
                ->where('user_id', $lockedUser->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->exists();

            if ($pendingExists) {
                return [
                    'error' => 'Hai gia una richiesta di prelievo in attesa di approvazione.',
                ];
            }

            // Controlliamo quanto ha guadagnato in commissioni e blocchiamo se il saldo riservabile e' insufficiente.
            $available = $lockedUser->commissionBalance();
            if ($available < 1) {
                return [
                    'error' => 'Saldo commissioni insufficiente. Disponibile: ' . number_format($available, 2) . ' EUR',
                ];
            }

            $withdrawal = WithdrawalRequest::create([
                'user_id' => $lockedUser->id,
                'amount' => $available,
                'currency' => 'EUR',
                'status' => 'pending',
            ]);

            return ['withdrawal' => $withdrawal];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result['withdrawal'],
        ], 201);
    }
}
