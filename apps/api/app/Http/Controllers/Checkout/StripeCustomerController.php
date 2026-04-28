<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;

use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeCustomerController extends Controller
{
    public function __construct(
        private readonly StripePaymentService $stripe,
    ) {}

    public function createOrGetCustomer($user)
    {
        return $this->stripe->createOrGetCustomer($user);
    }

    public function createSetupIntent(Request $request)
    {
        if (!$this->stripe->isConfigured()) return response()->json(['error' => 'Stripe non configurato.'], 503);
        try {
            return response()->json($this->stripe->createSetupIntent($request->user()));
        } catch (\Exception $e) {
            Log::error('SetupIntent creation error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Errore durante la configurazione del metodo di pagamento.'], 500);
        }
    }

    public function listPaymentMethods(Request $request)
    {
        $user = $request->user();
        if (!$user->customer_id || !$this->stripe->isConfigured()) return response()->json(['data' => [], 'default' => null]);
        return response()->json($this->stripe->listPaymentMethods($user));
    }

    public function setDefaultPaymentMethod(\App\Http\Requests\StripePaymentMethodRequest $request)
    {
        $user = $request->user();
        if (!$user->customer_id) return response()->json(['error' => 'No Stripe customer'], 400);
        try { return response()->json($this->stripe->setDefaultPaymentMethod($user, $request->payment_method)); }
        catch (\Exception $e) {
            Log::warning('setDefaultPaymentMethod failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Impostazione metodo di pagamento non riuscita.'], 400);
        }
    }

    public function changeDefaultPaymentMethod(\App\Http\Requests\StripePaymentMethodRequest $request)
    {
        $user = $request->user();
        if (!$user->customer_id) return response()->json(['error' => 'No Stripe customer'], 400);
        try { return response()->json($this->stripe->changeDefaultPaymentMethod($user, $request->payment_method_id)); }
        catch (\Exception $e) {
            Log::warning('changeDefaultPaymentMethod failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Modifica metodo di pagamento non riuscita.'], 400);
        }
    }

    public function deleteCard(\App\Http\Requests\StripePaymentMethodRequest $request)
    {
        $user = $request->user();
        if (!$user->customer_id) return response()->json(['error' => 'Nessun profilo Stripe associato.'], 400);
        try {
            $this->stripe->deleteCard($user, $request->payment_method_id);
            return response()->json(['success' => true]);
        } catch (\RuntimeException $e) {
            Log::warning('deleteCard ownership/permission error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Non autorizzato a eliminare questa carta.'], 403);
        } catch (\Exception $e) {
            Log::warning('deleteCard failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Eliminazione carta non riuscita.'], 400);
        }
    }

    public function getDefaultPaymentMethod(Request $request)
    {
        $user = $request->user();
        if (!$user || !$this->stripe->isConfigured()) {
            return response()->json(['card' => null]);
        }
        try {
            // Accesso a customer_id (cast 'encrypted') puo' fallire con
            // DecryptException se il dato nel DB e' stato cifrato con una APP_KEY
            // diversa da quella attuale. In quel caso trattiamo come "nessuna carta"
            // e ripuliamo silenziosamente il campo invece di 500.
            $customerId = $user->customer_id;
            if (!$customerId) {
                return response()->json(['card' => null]);
            }
            return response()->json(['card' => $this->stripe->getDefaultPaymentMethod($user)]);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            Log::warning('customer_id decrypt failed, resetting', ['user_id' => $user->id]);
            $user->forceFill(['customer_id' => null])->saveQuietly();
            return response()->json(['card' => null]);
        } catch (\Throwable $e) {
            Log::warning('getDefaultPaymentMethod failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            return response()->json(['card' => null]);
        }
    }
}
