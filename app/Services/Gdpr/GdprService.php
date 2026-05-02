<?php

namespace App\Services\Gdpr;

use App\Models\Order;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Logica GDPR: cancellazione account (Art. 17), export dati (Art. 20),
 * registrazione consenso cookie (Art. 7).
 */
class GdprService
{
    /**
     * Cancella/anonimizza l'account secondo Art. 17 GDPR.
     * Conserva ordini pagati anonimizzati per obbligo legale (art. 2220 c.c.).
     */
    public function deleteAccount(User $user, ?string $ip = null): void
    {
        DB::transaction(function () use ($user, $ip) {
            $userId = $user->id;
            $this->cancelPendingOrders($userId);
            $this->anonymizeCompletedOrders($userId);
            UserAddress::where('user_id', $userId)->delete();
            DB::table('saved_shipments')->where('user_id', $userId)->delete();
            UserNotificationPreference::where('user_id', $userId)->delete();
            $user->tokens()->delete();
            $anonEmail = $this->anonymizeUserRecord($user);
            Log::info('GDPR: account eliminato', [
                'deleted_user_id' => $userId,
                'anon_email' => $anonEmail,
                'ip' => $ip,
                'timestamp' => now()->toIso8601String(),
            ]);
        });
    }

    private function cancelPendingOrders(int $userId): void
    {
        Order::where('user_id', $userId)
            ->whereIn('status', [Order::PENDING, Order::PAYMENT_FAILED])
            ->update(['status' => Order::CANCELLED]);
    }

    private function anonymizeCompletedOrders(int $userId): void
    {
        Order::where('user_id', $userId)
            ->whereNotIn('status', [Order::PENDING, Order::PAYMENT_FAILED, Order::CANCELLED])
            ->update(['user_id' => null, 'billing_data' => null]);
    }

    private function anonymizeUserRecord(User $user): string
    {
        $anonEmail = 'deleted_'.$user->id.'@removed.invalid';
        $user->forceFill([
            'name' => 'Utente eliminato',
            'surname' => null,
            'email' => $anonEmail,
            'telephone_number' => null,
            'password' => bin2hex(random_bytes(16)),
            'google_id' => null,
            'facebook_id' => null,
            'apple_id' => null,
            'avatar' => null,
            'referral_code' => null,
            'referred_by' => null,
            'stripe_account_id' => null,
            'customer_id' => null,
            'verification_code' => null,
            'verification_code_expires_at' => null,
            'remember_token' => null,
            'email_verified_at' => null,
        ])->save();

        return $anonEmail;
    }

    /**
     * Esporta tutti i dati personali dell'utente (Art. 20 GDPR — portabilita').
     */
    public function exportUserData(User $user): array
    {
        return [
            'export_date' => now()->toIso8601String(),
            'profile' => $this->exportProfile($user),
            'orders' => $this->exportOrders($user->id),
            'addresses' => $this->exportAddresses($user->id),
            'wallet_movements' => $user->walletMovements()
                ->select(['id', 'type', 'amount', 'currency', 'status', 'description', 'source', 'created_at'])
                ->get(),
            'saved_shipment_package_ids' => DB::table('saved_shipments')
                ->where('user_id', $user->id)->pluck('package_id'),
            'notification_preferences' => UserNotificationPreference::where('user_id', $user->id)
                ->select(['referral_site_enabled', 'referral_email_enabled', 'referral_sms_enabled',
                    'email_opt_in_at', 'sms_opt_in_at', 'created_at', 'updated_at'])
                ->first(),
            'cookie_consents' => DB::table('cookie_consents')
                ->where('user_id', $user->id)
                ->select(['analytics', 'marketing', 'functional', 'ip_address', 'user_agent', 'consented_at'])
                ->orderByDesc('consented_at')->get(),
            'login_sessions' => $this->exportLoginSessions($user->id),
        ];
    }

    private function exportProfile(User $user): array
    {
        return [
            'id' => $user->id, 'name' => $user->name, 'surname' => $user->surname,
            'email' => $user->email, 'telephone_number' => $user->telephone_number,
            'user_type' => $user->user_type, 'role' => $user->role,
            'referral_code' => $user->referral_code, 'referred_by' => $user->referred_by,
            'email_verified_at' => $user->email_verified_at,
            'privacy_accepted_at' => $user->privacy_accepted_at,
            'created_at' => $user->created_at,
        ];
    }

    private function exportOrders(int $userId)
    {
        return Order::where('user_id', $userId)
            ->select(['id', 'status', 'subtotal', 'payment_method',
                'refund_status', 'refund_amount', 'refunded_at',
                'brt_tracking_number', 'brt_tracking_url',
                'billing_data', 'created_at', 'updated_at'])
            ->get();
    }

    private function exportAddresses(int $userId)
    {
        return UserAddress::where('user_id', $userId)
            ->select(['id', 'name', 'address', 'address_number', 'city',
                'postal_code', 'province', 'country', 'telephone_number',
                'email', 'additional_information', 'default', 'created_at'])
            ->get();
    }

    private function exportLoginSessions(int $userId)
    {
        return DB::table('sessions')->where('user_id', $userId)
            ->select(['ip_address', 'user_agent', 'last_activity'])
            ->orderByDesc('last_activity')->get()
            ->map(fn ($s) => [
                'ip_address' => $s->ip_address,
                'user_agent' => $s->user_agent,
                'last_activity' => date('c', $s->last_activity),
            ]);
    }

    /**
     * Registra il consenso cookie (Art. 7 GDPR — prova del consenso).
     * Accetta formato legacy (type=all|necessary) o granulare (analytics/marketing/functional).
     */
    public function recordCookieConsent(array $data, ?int $userId, ?string $ip, ?string $userAgent): void
    {
        [$analytics, $marketing, $functional] = $this->resolveConsentFlags($data);

        DB::table('cookie_consents')->insert([
            'user_id' => $userId,
            'analytics' => $analytics,
            'marketing' => $marketing,
            'functional' => $functional,
            'ip_address' => $ip,
            'user_agent' => mb_substr((string) $userAgent, 0, 512),
            'consented_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('GDPR: consenso cookie registrato', [
            'user_id' => $userId,
            'analytics' => $analytics,
            'marketing' => $marketing,
            'functional' => $functional,
            'ip' => $ip,
        ]);
    }

    private function resolveConsentFlags(array $data): array
    {
        if (isset($data['type'])) {
            $all = $data['type'] === 'all';

            return [$all, $all, $all];
        }

        return [
            (bool) ($data['analytics'] ?? false),
            (bool) ($data['marketing'] ?? false),
            (bool) ($data['functional'] ?? false),
        ];
    }
}
