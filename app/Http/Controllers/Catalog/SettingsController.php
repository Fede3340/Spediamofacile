<?php
namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;

use App\Models\Setting;
use App\Services\StripeConfigService;
use Illuminate\Http\Request;
class SettingsController extends Controller
{
    public function __construct(
        private readonly StripeConfigService $stripeConfig,
    ) {}

    /**
     * Restituisce lo stato della configurazione Stripe.
     * Dice al frontend se Stripe e' configurato (cioe' se le chiavi sono state inserite)
     * e fornisce solo la chiave pubblica (mai la segreta!).
     */
    public function getStripeConfig()
    {
        return response()->json([
            'configured' => $this->stripeConfig->isConfigured(),
            'publishable_key' => $this->stripeConfig->getPublicKey() ?: '',
        ]);
    }

    /**
     * Salva le chiavi Stripe nel database.
     * Solo l'amministratore puo' fare questa operazione.
     * Verifica che le chiavi abbiano il formato corretto (pk_ e sk_).
     */
    public function saveStripeConfig(\App\Http\Requests\UpdateStripeKeysRequest $request)
    {
        // Ripulisce eventuali spazi/newline da copia-incolla
        $publishable = preg_replace('/\s+/', '', (string) $request->publishable_key);
        $secret = preg_replace('/\s+/', '', (string) $request->secret_key);

        if (!preg_match('/^pk_(test|live)_[A-Za-z0-9]+$/', $publishable)) {
            return response()->json([
                'message' => 'Publishable Key non valida. Incolla la chiave completa (pk_test_... o pk_live_...) senza caratteri extra.',
            ], 422);
        }

        if (!preg_match('/^sk_(test|live)_[A-Za-z0-9]+$/', $secret)) {
            return response()->json([
                'message' => 'Secret Key non valida. Incolla la chiave completa (sk_test_... o sk_live_...) senza caratteri extra.',
            ], 422);
        }

        // Salviamo in doppia chiave (nuovo + legacy) per compatibilita' con tutto il progetto
        Setting::set('stripe_key', $publishable);
        Setting::set('stripe_public_key', $publishable);
        Setting::set('stripe_secret', $secret);
        Setting::set('stripe_secret_key', $secret);

        return response()->json([
            'success' => true,
            'message' => 'Stripe configurato con successo!',
        ]);
    }
}
