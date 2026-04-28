<?php
namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
class AddressController extends Controller
{
    // Restituisce la lista di tutti gli indirizzi (al momento ritorna una lista vuota)
    public function index()
    {
        return response()->json([]);
    }

    // Salva un nuovo indirizzo (al momento non fa nulla, ritorna solo una risposta vuota con codice 201 = "creato")
    public function store(Request $request)
    {
        return response()->json([], 201);
    }

    // Mostra un singolo indirizzo cercandolo per il suo identificativo
    // Al momento ritorna sempre "non trovato" (codice 404)
    public function show($id)
    {
        return response()->json(null, 404);
    }

    // Aggiorna un indirizzo esistente (al momento non fa nulla)
    public function update(Request $request, $id)
    {
        return response()->json([], 200);
    }

    // Elimina un indirizzo (al momento non fa nulla, ritorna codice 204 = "eliminato senza contenuto")
    public function destroy($id)
    {
        return response()->json([], 204);
    }
}
