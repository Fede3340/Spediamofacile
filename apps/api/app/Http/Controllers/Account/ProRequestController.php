<?php
namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;

use App\Models\ProRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProRequestController extends Controller
{
    /**
     * Invia una nuova richiesta per diventare Partner Pro.
     * L'utente non deve gia' essere Pro e non deve avere una richiesta in attesa.
     */
    public function store(\App\Http\Requests\StoreProRequestRequest $request): JsonResponse
    {
        $user = auth()->user();

        // Se l'utente e' gia' Partner Pro, non puo' fare un'altra richiesta
        if ($user->isPro()) {
            return response()->json([
                'message' => 'Sei già un Partner Pro.',
            ], 422);
        }

        // Controlliamo che non ci sia gia' una richiesta in attesa di revisione
        $pendingRequest = ProRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            return response()->json([
                'message' => 'Hai già una richiesta in attesa di revisione.',
            ], 422);
        }

        $data = $request->validated();

        // Creiamo la richiesta con i dati dell'azienda
        $proRequest = ProRequest::create([
            'user_id' => $user->id,
            'company_name' => $data['company_name'] ?? '',
            'vat_number' => $data['vat_number'] ?? '',
            'message' => $data['message'] ?? '',
            'status' => 'pending', // In attesa di revisione da parte dell'admin
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Richiesta Pro inviata con successo.',
            'data' => $proRequest,
        ], 201);
    }

    /**
     * Mostra lo stato della richiesta Pro dell'utente corrente.
     * L'utente puo' vedere se ha una richiesta in attesa, approvata o rifiutata.
     */
    public function status(): JsonResponse
    {
        $user = auth()->user();

        // Cerchiamo la richiesta piu' recente dell'utente
        $proRequest = ProRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        if (! $proRequest) {
            return response()->json([
                'has_request' => false,
                'data' => null,
            ]);
        }

        return response()->json([
            'has_request' => true,
            'data' => $proRequest,
        ]);
    }

    /**
     * Funzione per l'AMMINISTRATORE: mostra la lista di tutte le richieste Pro
     * di tutti gli utenti, con i dati dell'utente che ha fatto la richiesta.
     */
    public function index(): JsonResponse
    {
        $proRequests = ProRequest::with('user:id,name,surname,email,role')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $proRequests]);
    }

    /**
     * Funzione per l'AMMINISTRATORE: approva una richiesta Pro.
     * Cambia lo stato della richiesta in "approvato" e aggiorna il ruolo dell'utente
     * a "Partner Pro", generando anche un codice referral unico.
     */
    public function approve(ProRequest $proRequest): JsonResponse
    {
        // La richiesta deve essere ancora in attesa per poter essere approvata
        if ($proRequest->status !== ProRequest::STATUS_PENDING) {
            return response()->json([
                'message' => 'Questa richiesta non è in attesa.',
            ], 422);
        }

        // Aggiorniamo lo stato della richiesta a "approvata"
        $proRequest->update([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        // Aggiorniamo il ruolo dell'utente a "Partner Pro"
        // e generiamo un codice referral se non ne ha gia' uno
        /** @var User $user */
        $user = $proRequest->user;
        // role e referral_code NON sono in $fillable (mass assignment risk):
        // assegnazione esplicita tramite property + save() invece di update(array).
        $user->role = 'Partner Pro';
        $user->referral_code = $user->referral_code ?: strtoupper(Str::random(8));
        $user->save();

        \App\Services\AuditLogService::log('admin.pro_request.approve', $proRequest, [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Richiesta approvata. L\'utente è ora Partner Pro.',
            'data' => $proRequest->fresh()->load('user:id,name,surname,email,role,referral_code'),
        ]);
    }

    /**
     * Funzione per l'AMMINISTRATORE: rifiuta una richiesta Pro.
     */
    public function reject(ProRequest $proRequest): JsonResponse
    {
        if ($proRequest->status !== ProRequest::STATUS_PENDING) {
            return response()->json([
                'message' => 'Questa richiesta non è in attesa.',
            ], 422);
        }

        $proRequest->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
        ]);

        \App\Services\AuditLogService::log('admin.pro_request.reject', $proRequest, [
            'user_id' => $proRequest->user_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Richiesta rifiutata.',
            'data' => $proRequest->fresh(),
        ]);
    }
}
