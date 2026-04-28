<?php

namespace App\Listeners;

use App\Events\ReferralApplied;
use App\Mail\ReferralUsedMail;
use App\Models\ReferralUsage;
use App\Models\UserNotification;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Facades\Mail;

class DispatchReferralNotifications
{
    public function handle(ReferralApplied $event): void
    {
        $usage = ReferralUsage::query()
            ->with(['buyer', 'proUser'])
            ->find($event->referralUsageId);

        if (! $usage || ! $usage->proUser) {
            return;
        }

        $proUser = $usage->proUser;
        $buyerName = trim((string) ($usage->buyer?->name ?? 'Un cliente'));

        $preferences = UserNotificationPreference::firstOrCreate(
            ['user_id' => $proUser->id],
            [
                'referral_site_enabled' => true,
                'referral_email_enabled' => false,
                'referral_sms_enabled' => false,
            ]
        );

        $alreadyNotified = UserNotification::query()
            ->where('user_id', $proUser->id)
            ->where('type', 'referral')
            ->where('payload->referral_usage_id', $usage->id)
            ->exists();

        if ($preferences->referral_site_enabled && ! $alreadyNotified) {
            UserNotification::create([
                'user_id' => $proUser->id,
                'type' => 'referral',
                'title' => 'Nuovo utilizzo del tuo referral',
                'body' => sprintf(
                    '%s ha usato il tuo codice sull\'ordine #%d. Commissione maturata: %s EUR.',
                    $buyerName,
                    $usage->order_id,
                    number_format((float) $usage->commission_amount, 2, '.', '')
                ),
                'payload' => [
                    'referral_usage_id' => $usage->id,
                    'order_id' => $usage->order_id,
                    'buyer_id' => $usage->buyer_id,
                    'commission_amount' => (float) $usage->commission_amount,
                ],
            ]);
        }

        if ($preferences->referral_email_enabled && filled($proUser->email) && ! $alreadyNotified) {
            Mail::to($proUser->email)->queue(new ReferralUsedMail($usage));
        }
    }
}
