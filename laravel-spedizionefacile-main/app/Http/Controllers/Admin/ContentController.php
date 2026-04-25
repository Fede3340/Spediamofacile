<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    // Mostra tutti i messaggi di contatto ricevuti dalla pagina "Contattaci"
    public function contactMessages(): JsonResponse
    {
        $messages = ContactMessage::orderByDesc('created_at')->paginate(30);

        return response()->json($messages);
    }

    // Segna un messaggio di contatto come letto
    public function markContactMessageRead($id): JsonResponse
    {
        $msg = ContactMessage::findOrFail($id);
        $msg->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => $msg->fresh(),
        ]);
    }

    // Mostra le impostazioni del sito (chiavi API, configurazioni, ecc.)
    public function settings(): JsonResponse
    {
        $keys = [
            'stripe_public_key', 'stripe_secret_key', 'stripe_webhook_secret',
            'brt_customer_id', 'brt_username', 'brt_password',
            'site_name', 'support_email', 'cod_surcharge',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::get($key, '');
        }

        return response()->json(['data' => $settings]);
    }

    // Aggiorna le impostazioni del sito
    // Accetta solo le chiavi autorizzate (per sicurezza)
    public function updateSettings(\App\Http\Requests\UpdateAdminSettingsRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Validazione formato chiavi Stripe per evitare salvataggio di valori invalidi
        if (! empty($data['stripe_public_key']) && ! preg_match('/^pk_(test|live)_/', $data['stripe_public_key'])) {
            return response()->json([
                'message' => 'Publishable Key non valida. Usa una chiave completa pk_test_... o pk_live_....',
            ], 422);
        }
        if (! empty($data['stripe_secret_key']) && ! preg_match('/^sk_(test|live)_/', $data['stripe_secret_key'])) {
            return response()->json([
                'message' => 'Secret Key non valida. Usa una chiave completa sk_test_... o sk_live_....',
            ], 422);
        }
        if (! empty($data['stripe_webhook_secret']) && ! str_starts_with($data['stripe_webhook_secret'], 'whsec_')) {
            return response()->json([
                'message' => 'Webhook Secret non valido. Usa il valore completo whsec_... fornito da Stripe.',
            ], 422);
        }

        foreach ($data as $key => $value) {
            if (!is_null($value)) {
                Setting::set($key, $value);
            }
        }

        // Mirroring chiavi Stripe su nomi legacy per compatibilità
        if (! empty($data['stripe_public_key'])) {
            Setting::set('stripe_key', $data['stripe_public_key']);
        }
        if (! empty($data['stripe_secret_key'])) {
            Setting::set('stripe_secret', $data['stripe_secret_key']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Impostazioni aggiornate con successo.',
        ]);
    }

}
