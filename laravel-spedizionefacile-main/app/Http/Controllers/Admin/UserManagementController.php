<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    use LogsAudit;

    // Mostra la lista di tutti gli utenti registrati sul sito
    public function users(): JsonResponse
    {
        $users = User::orderByDesc('created_at')
            ->paginate(30, ['id', 'name', 'surname', 'email', 'role', 'user_type', 'telephone_number', 'email_verified_at', 'created_at']);

        return response()->json($users);
    }

    // Cambia il ruolo di un utente (es. da "User" a "Partner Pro" o "Admin")
    // Se l'utente viene promosso a Partner Pro, gli viene generato un codice referral
    public function updateUserRole(\App\Http\Requests\UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        $oldRole = $user->role;
        $user->role = $data['role'];

        // Se l'utente diventa Partner Pro, generiamo un codice referral se non ne ha gia' uno
        if ($data['role'] === 'Partner Pro' && !$user->referral_code) {
            $user->referral_code = strtoupper(Str::random(8));
        }

        $user->save();

        $this->audit('admin.user.role_change', $user, [
            'from' => $oldRole,
            'to' => $data['role'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Ruolo aggiornato da '{$oldRole}' a '{$data['role']}'.",
            'data' => $user->fresh(),
        ]);
    }

    // Cambia il tipo di account (privato/commerciante)
    public function updateUserType(User $user, \App\Http\Requests\UpdateUserTypeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $oldType = $user->user_type;
        $user->update(['user_type' => $validated['user_type']]);

        $this->audit('admin.user.user_type_change', $user, [
            'from' => $oldType,
            'to' => $validated['user_type'],
        ]);

        return response()->json(['success' => true, 'message' => "Tipo account aggiornato a {$validated['user_type']}."]);
    }

    // Approva (verifica) manualmente un account utente
    // Utile quando l'utente non riesce a verificare la sua email automaticamente
    public function approveUser(User $user): JsonResponse
    {
        if ($user->email_verified_at) {
            return response()->json([
                'success' => true,
                'message' => 'Account gia verificato.',
                'data' => $user,
            ]);
        }

        // Segniamo l'email come verificata
        $user->update([
            'email_verified_at' => now(),
        ]);

        $this->audit('admin.user.approve', $user);

        return response()->json([
            'success' => true,
            'message' => 'Account verificato con successo.',
            'data' => $user->fresh(),
        ]);
    }

    // Elimina un utente dal sistema
    // L'admin non puo' eliminare se stesso (per sicurezza)
    public function deleteUser(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non puoi eliminare il tuo account amministratore attivo.',
            ], 422);
        }

        // Audit prima del delete cosi' user/role sono ancora disponibili nel context
        $this->audit('admin.user.delete', $user, [
            'email' => $user->email,
            'role' => $user->role,
        ]);

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utente eliminato con successo.',
        ]);
    }
}
