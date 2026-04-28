<?php
namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreContactMessageRequest;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    // Salva un nuovo messaggio di contatto nel database.
    // Validazione delegata a StoreContactMessageRequest (testabile + riusabile).
    public function store(StoreContactMessageRequest $request)
    {
        $contactMessage = ContactMessage::create($request->validated());

        return response()->json([
            'message' => 'Messaggio inviato con successo.',
            'data' => $contactMessage,
        ], 201);
    }

    // Salva una richiesta di supporto aperta da un utente autenticato nell'area account.
    // Validazione delegata a StoreSupportTicketRequest (testabile + auth check uniforme).
    public function storeSupportTicket(StoreSupportTicketRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $contactMessage = ContactMessage::create([
            'name' => $user->name,
            'surname' => $user->surname,
            'email' => $user->email,
            'subject' => $validated['subject'],
            'telephone_number' => $user->telephone_number,
            'address' => $user->address ?? null,
            'message' => $validated['message'],
        ]);

        return response()->json([
            'message' => 'Richiesta di assistenza inviata con successo.',
            'data' => $contactMessage,
        ], 201);
    }

    // Funzione per l'AMMINISTRATORE: mostra tutti i messaggi di contatto ricevuti
    // Ordinati dal piu' recente al piu' vecchio
    public function index(Request $request)
    {
        $messages = ContactMessage::orderByDesc('created_at')->get();

        return response()->json([
            'data' => $messages,
        ]);
    }

    // Funzione per l'AMMINISTRATORE: segna un messaggio come "letto"
    // Salva la data e ora in cui l'admin ha letto il messaggio
    public function markAsRead($id)
    {
        $contactMessage = ContactMessage::findOrFail($id);

        $contactMessage->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Messaggio segnato come letto.',
            'data' => $contactMessage,
        ]);
    }
}
