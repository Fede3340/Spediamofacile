<?php
namespace App\Http\Controllers\Gdpr;

use App\Http\Controllers\Controller;

use App\Models\Order;
use App\Models\UserAddress;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GdprController extends Controller
{
    /**
     * DELETE /api/user/account
     *
     * Cancella l'account dell'utente autenticato (Art. 17 GDPR — Diritto all'oblio).
     *
     * Cosa viene cancellato:
     *   - Dati personali nel record utente (nome, email, telefono, ecc.)
     *   - Indirizzi della rubrica
     *   - Spedizioni salvate (tabella saved_shipments)
     *   - Preferenze notifiche
     *   - Tutti i token Sanctum (logout forzato da tutti i dispositivi)
     *
     * Cosa viene conservato (obbligo legale/contabile):
     *   - Ordini con stato completato/pagato (anonimizzati: user_id = null, billing_data = null)
     *   - Movimenti portafoglio (anonimizzati)
     *   - Transazioni di pagamento (audit trail)
     *
     * Gli ordini in stato 'pending' o 'payment_failed' vengono annullati.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            $userId = $user->id;

            // --- 1. Annulla gli ordini in sospeso ---
            // Gli ordini non pagati non hanno valore contabile, possono essere rimossi/annullati.
            Order::where('user_id', $userId)
                ->whereIn('status', [Order::PENDING, Order::PAYMENT_FAILED])
                ->update(['status' => Order::CANCELLED]);

            // --- 2. Anonimizza gli ordini completati/pagati ---
            // OBBLIGO LEGALE: i dati di spedizione e pagamento devono essere conservati
            // per almeno 10 anni (art. 2220 c.c. e normativa fiscale italiana).
            // Rimuoviamo solo le PII (dati personali identificativi).
            Order::where('user_id', $userId)
                ->whereNotIn('status', [Order::PENDING, Order::PAYMENT_FAILED, Order::CANCELLED])
                ->update([
                    'user_id'      => null,   // Scollega l'ordine dall'utente
                    'billing_data' => null,   // Rimuove i dati di fatturazione personali
                ]);

            // --- 3. Cancella indirizzi della rubrica ---
            UserAddress::where('user_id', $userId)->delete();

            // --- 4. Cancella spedizioni salvate ---
            DB::table('saved_shipments')->where('user_id', $userId)->delete();

            // --- 5. Cancella preferenze notifiche ---
            UserNotificationPreference::where('user_id', $userId)->delete();

            // --- 6. Revoca tutti i token Sanctum (logout da tutti i dispositivi) ---
            $user->tokens()->delete();

            // --- 7. Anonimizza il record utente ---
            // Sovrascriviamo i dati personali con valori anonimi.
            // L'account viene disabilitato definitivamente.
            $anonEmail = 'deleted_' . $userId . '@removed.invalid';
            $user->forceFill([
                'name'                       => 'Utente eliminato',
                'surname'                    => null,
                'email'                      => $anonEmail,
                'telephone_number'           => null,
                'password'                   => bin2hex(random_bytes(16)), // Cast 'hashed' su User hashera' automaticamente
                'google_id'                  => null,
                'facebook_id'                => null,
                'apple_id'                   => null,
                'avatar'                     => null,
                'referral_code'              => null,
                'referred_by'                => null,
                'stripe_account_id'          => null,
                'customer_id'                => null,
                'verification_code'          => null,
                'verification_code_expires_at' => null,
                'remember_token'             => null,
                'email_verified_at'          => null,
            ])->save();

            // --- 8. Registra l'evento nel log ---
            Log::info('GDPR: account eliminato', [
                'deleted_user_id' => $userId,
                'anon_email'      => $anonEmail,
                'ip'              => request()->ip(),
                'timestamp'       => now()->toIso8601String(),
            ]);
        });

        return response()->json([
            'message' => 'Account eliminato con successo. I tuoi dati personali sono stati rimossi.',
        ]);
    }

    /**
     * GET /api/user/data-export
     *
     * Esporta tutti i dati personali dell'utente autenticato in formato JSON
     * (Art. 20 GDPR — Diritto alla portabilita' dei dati).
     *
     * Dati inclusi:
     *   - Profilo utente (senza password e campi di sicurezza)
     *   - Ordini con dati di fatturazione (senza dati tecnici BRT)
     *   - Indirizzi della rubrica
     *   - Movimenti del portafoglio
     *   - Spedizioni salvate
     *   - Preferenze notifiche
     *   - Preferenze cookie e log consenso
     *   - Storico sessioni di login
     */
    public function dataExport(Request $request): JsonResponse
    {
        $user = $request->user();

        // Profilo utente (esclusi campi sensibili di sicurezza)
        $profile = [
            'id'               => $user->id,
            'name'             => $user->name,
            'surname'          => $user->surname,
            'email'            => $user->email,
            'telephone_number' => $user->telephone_number,
            'user_type'        => $user->user_type,
            'role'             => $user->role,
            'referral_code'    => $user->referral_code,
            'referred_by'      => $user->referred_by,
            'email_verified_at' => $user->email_verified_at,
            'privacy_accepted_at' => $user->privacy_accepted_at,
            'created_at'       => $user->created_at,
        ];

        // Ordini con dati di fatturazione completi (senza dati tecnici BRT voluminosi)
        $orders = Order::where('user_id', $user->id)
            ->select([
                'id', 'status', 'subtotal', 'payment_method',
                'refund_status', 'refund_amount', 'refunded_at',
                'brt_tracking_number', 'brt_tracking_url',
                'billing_data', 'created_at', 'updated_at',
            ])
            ->get();

        // Indirizzi della rubrica
        $addresses = UserAddress::where('user_id', $user->id)
            ->select([
                'id', 'name', 'address', 'address_number', 'city',
                'postal_code', 'province', 'country', 'telephone_number',
                'email', 'additional_information', 'default', 'created_at',
            ])
            ->get();

        // Movimenti del portafoglio
        $walletMovements = $user->walletMovements()
            ->select([
                'id', 'type', 'amount', 'currency', 'status',
                'description', 'source', 'created_at',
            ])
            ->get();

        // Spedizioni salvate (tramite i pacchi collegati)
        $savedPackageIds = DB::table('saved_shipments')
            ->where('user_id', $user->id)
            ->pluck('package_id');

        // Preferenze notifiche
        $notificationPreferences = UserNotificationPreference::where('user_id', $user->id)
            ->select([
                'referral_site_enabled', 'referral_email_enabled', 'referral_sms_enabled',
                'email_opt_in_at', 'sms_opt_in_at',
                'created_at', 'updated_at',
            ])
            ->first();

        // Log consenso cookie (GDPR Art. 7 — prova del consenso)
        $cookieConsents = DB::table('cookie_consents')
            ->where('user_id', $user->id)
            ->select([
                'analytics', 'marketing', 'functional',
                'ip_address', 'user_agent', 'consented_at',
            ])
            ->orderByDesc('consented_at')
            ->get();

        // Storico sessioni di login (dalla tabella sessions di Laravel)
        $loginSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->select(['ip_address', 'user_agent', 'last_activity'])
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session) => [
                'ip_address'    => $session->ip_address,
                'user_agent'    => $session->user_agent,
                'last_activity' => date('c', $session->last_activity),
            ]);

        return response()->json([
            'export_date'               => now()->toIso8601String(),
            'profile'                   => $profile,
            'orders'                    => $orders,
            'addresses'                 => $addresses,
            'wallet_movements'          => $walletMovements,
            'saved_shipment_package_ids' => $savedPackageIds,
            'notification_preferences'  => $notificationPreferences,
            'cookie_consents'           => $cookieConsents,
            'login_sessions'            => $loginSessions,
        ]);
    }

    /**
     * POST /api/cookie-consent
     *
     * Registra il consenso cookie dell'utente per conformita' GDPR.
     *
     * Accetta due formati:
     *   - Legacy: { type: 'all' | 'necessary' }
     *   - Granulare: { analytics: bool, marketing: bool, functional: bool }
     *
     * Il consenso viene salvato nella tabella cookie_consents per audit trail.
     */
    public function cookieConsent(\App\Http\Requests\CookieConsentRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Supporto formato legacy: type=all abilita tutto, type=necessary disabilita tutto
        if (isset($data['type'])) {
            $allEnabled = $data['type'] === 'all';
            $analytics  = $allEnabled;
            $marketing  = $allEnabled;
            $functional = $allEnabled;
        } else {
            $analytics  = (bool) ($data['analytics'] ?? false);
            $marketing  = (bool) ($data['marketing'] ?? false);
            $functional = (bool) ($data['functional'] ?? false);
        }

        DB::table('cookie_consents')->insert([
            'user_id'      => $request->user()?->id,
            'analytics'    => $analytics,
            'marketing'    => $marketing,
            'functional'   => $functional,
            'ip_address'   => $request->ip(),
            'user_agent'   => mb_substr((string) $request->userAgent(), 0, 512),
            'consented_at' => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        Log::info('GDPR: consenso cookie registrato', [
            'user_id'    => $request->user()?->id,
            'analytics'  => $analytics,
            'marketing'  => $marketing,
            'functional' => $functional,
            'ip'         => $request->ip(),
        ]);

        return response()->json(['ok' => true]);
    }
}
