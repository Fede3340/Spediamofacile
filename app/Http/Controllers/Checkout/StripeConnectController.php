<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Stripe\StripeClient;

/**
 * CONTROLLER STRIPE CONNECT
 *
 * Questo controller gestisce il collegamento degli account Stripe per i Partner Pro.
 * Stripe Connect e' un servizio che permette di inviare pagamenti (commissioni)
 * direttamente sul conto del Partner Pro.
 *
 * Il flusso funziona cosi':
 * 1. L'utente clicca "Collega Stripe" sul sito
 * 2. Viene mandato su Stripe per completare la registrazione
 * 3. Stripe rimanda l'utente indietro al nostro sito con i dati del collegamento
 *
 * Contiene le funzioni per: avviare il collegamento, gestire il ritorno da Stripe,
 * e creare un nuovo account Stripe Express.
 */
class StripeConnectController extends Controller
{
    // Genera l'indirizzo web (URL) per mandare l'utente sulla pagina di autorizzazione Stripe
    // L'utente verra' reindirizzato su Stripe dove autorizzerà il collegamento
    public function connect()
    {
        // Prepariamo i parametri necessari per la richiesta a Stripe
        $query = http_build_query([
            'client_id' => config('services.stripe.client_id'),
            'response_type' => 'code',
            'scope' => 'read_write',
            'redirect_uri' => url('/api/stripe/callback'),
        ]);

        // Restituiamo l'URL completo al frontend, che reindirizzerà l'utente
        return response()->json([
            'url' => 'https://connect.stripe.com/oauth/authorize?' . $query,
        ]);
    }

    // Questa funzione viene chiamata automaticamente quando Stripe rimanda l'utente al nostro sito
    // Stripe ci invia un codice che usiamo per completare il collegamento
    public function callback(Request $request)
    {
        // Inviamo il codice ricevuto da Stripe per ottenere l'identificativo dell'account collegato
        $response = Http::asForm()->post('https://connect.stripe.com/oauth/token', [
            'client_secret' => config('services.stripe.secret'),
            'code' => $request->code,
            'grant_type' => 'authorization_code',
        ]);

        // Se la richiesta a Stripe non e' andata a buon fine, reindirizziamo con un errore
        if ($response->failed()) {
            return redirect(config('app.frontend_url') . '/account?stripe_error=1');
        }

        // Salviamo l'identificativo dell'account Stripe dell'utente nel nostro database
        $stripeAccountId = $response['stripe_user_id'];

        $user = Auth::user();
        $user->stripe_account_id = $stripeAccountId;
        $user->save();

        // Reindirizziamo l'utente alla sua pagina account con un messaggio di successo
        return redirect(config('app.frontend_url') . '/account?stripe_connected=1');
    }

    // Crea un nuovo account Stripe Express per l'utente
    // Stripe Express e' un tipo di account semplificato, adatto per chi riceve pagamenti
    // Dopo la creazione, l'utente viene mandato su Stripe per completare i dati richiesti
    public function createAccount()
    {
        $user = Auth::user();
        $stripe = new StripeClient(config('services.stripe.secret'));

        // Creiamo l'account Stripe Express con i dati base dell'utente
        $account = $stripe->accounts->create([
            'type' => 'express',
            'country' => 'IT',
            'email' => $user->email,
            'capabilities' => [
                'transfers' => ['requested' => true], // Abilita la possibilita' di ricevere trasferimenti di denaro
            ],
        ]);

        // Salviamo l'identificativo dell'account Stripe nel database
        $user->stripe_account_id = $account->id;
        $user->save();

        // Creiamo un link per mandare l'utente su Stripe a completare la registrazione
        // refresh_url = dove mandarlo se deve ricominciare
        // return_url = dove mandarlo quando ha finito
        $accountLink = $stripe->accountLinks->create([
            'account' => $account->id,
            'refresh_url' => config('app.frontend_url') . '/account/account-pro?refresh=1',
            'return_url' => config('app.frontend_url') . '/account/account-pro?connected=1',
            'type' => 'account_onboarding',
        ]);

        return response()->json([
            'url' => $accountLink->url,
            'account_id' => $account->id,
        ]);
    }
}
