<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        // Referral
        'referral_site_enabled',
        'referral_email_enabled',
        'referral_sms_enabled',
        // F08 SMS — opt-in transazionale + marketing
        'sms_order_updates',
        'sms_marketing',
        // F09 PWA push
        'push_order_updates',
        'push_marketing',
        // Timestamp opt-in (audit GDPR)
        'email_opt_in_at',
        'sms_opt_in_at',
        'push_opt_in_at',
    ];

    protected $casts = [
        'referral_site_enabled' => 'boolean',
        'referral_email_enabled' => 'boolean',
        'referral_sms_enabled' => 'boolean',
        'sms_order_updates' => 'boolean',
        'sms_marketing' => 'boolean',
        'push_order_updates' => 'boolean',
        'push_marketing' => 'boolean',
        'email_opt_in_at' => 'datetime',
        'sms_opt_in_at' => 'datetime',
        'push_opt_in_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
