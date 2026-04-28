<?php

/**
 * MIDDLEWARE: CONTROLLA SE IL CARRELLO HA DEI PRODOTTI
 *
 * Questo middleware verifica che il carrello dell'utente non sia vuoto
 * prima di procedere con operazioni come il pagamento o la creazione dell'ordine.
 *
 * Se il carrello e' vuoto, la richiesta viene bloccata con un errore 400
 * e il messaggio "Carrello vuoto".
 *
 * Ha senso: non puoi pagare se non hai niente nel carrello!
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCart
{
    /**
     * Controlla se il carrello dell'utente ha almeno un prodotto.
     * Cerca nella tabella "cart_user" del database i pacchi dell'utente.
     * Se non ne trova, blocca la richiesta.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cart = DB::table('cart_user')
            ->where('user_id', auth()->id())
            ->get();

        if ($cart->isEmpty()) {
            return response()->json([
                'message' => 'Carrello vuoto',
            ], 400);
        }

        return $next($request);
    }
}
