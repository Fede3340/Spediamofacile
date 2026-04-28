<?php

/**
 * MIDDLEWARE: CONTROLLA SE L'UTENTE E' AMMINISTRATORE
 *
 * Un middleware e' come un "guardiano" che controlla ogni richiesta
 * prima che arrivi al controller. Questo middleware verifica che
 * l'utente sia un amministratore del sito.
 *
 * Se l'utente NON e' admin, la richiesta viene bloccata e viene
 * restituito un errore 403 (accesso vietato).
 *
 * Viene usato per proteggere le pagine di amministrazione:
 * solo gli admin possono vedere ordini, gestire utenti, ecc.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Controlla se l'utente collegato e' un amministratore.
     * Se non lo e', blocca la richiesta con errore 403.
     * Se lo e', lascia passare la richiesta al controller.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Accesso vietato. Non sei amministratore.',
            ], 403);
        }

        return $next($request);
    }
}
