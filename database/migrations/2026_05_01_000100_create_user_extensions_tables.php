<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrazione 2/6 — Estensioni utente: indirizzi, notifiche, preferenze.
 *
 * Tabelle create:
 *  - user_addresses: rubrica indirizzi cliente (multi mittente/destinatario)
 *  - user_notification_preferences: opt-in email/SMS/push referral + ordini + marketing
 *  - user_notifications: feed notifiche in-app
 *  - push_subscriptions: VAPID push subscription per browser/device
 *  - cookie_consents: GDPR cookie banner consensi
 *  - contact_messages: messaggi modulo contatti
 *  - pro_requests: richieste passaggio account a Pro
 *  - pro_api_keys: API key Pro per integrazione esterna
 */
return new class extends Migration
{
    public function up(): void
    {
        // Rubrica indirizzi cliente (mittente + destinatario, default flag)
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // mittente | destinatario
            $table->string('name');
            $table->string('additional_information')->nullable();
            $table->string('address');
            $table->string('number_type'); // civico | km | snc
            $table->string('address_number');
            $table->string('intercom_code')->nullable();
            $table->string('country');
            $table->string('city');
            $table->string('postal_code');
            $table->string('province');
            $table->string('telephone_number');
            $table->string('email')->nullable();
            $table->boolean('default')->default(false);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Preferenze notifiche multi-canale (referral, ordini, marketing)
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('referral_site_enabled')->default(true);
            $table->boolean('referral_email_enabled')->default(false);
            $table->boolean('referral_sms_enabled')->default(false);
            $table->dateTime('email_opt_in_at')->nullable();
            $table->dateTime('sms_opt_in_at')->nullable();
            $table->boolean('sms_order_updates')->default(false);
            $table->boolean('sms_marketing')->default(false);
            $table->boolean('push_order_updates')->default(false);
            $table->boolean('push_marketing')->default(false);
            $table->dateTime('push_opt_in_at')->nullable();
            $table->timestamps();
        });

        // Feed notifiche in-app (badge campanella header)
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('body');
            $table->text('payload')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });

        // VAPID push subscriptions (browser + mobile PWA)
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('p256dh');
            $table->string('auth');
            $table->string('user_agent')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->string('endpoint_hash')->unique();
            $table->timestamps();
        });

        // GDPR cookie consents (audit trail consensi banner)
        Schema::create('cookie_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('analytics')->default(false);
            $table->boolean('marketing')->default(false);
            $table->boolean('functional')->default(false);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->dateTime('consented_at');
            $table->timestamps();
        });

        // Messaggi modulo contatti (form pubblico)
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname');
            $table->string('email');
            $table->string('telephone_number')->nullable();
            $table->string('address')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->dateTime('read_at')->nullable()->index();
            $table->timestamps();

            $table->index('created_at');
        });

        // Richieste upgrade account a Pro (workflow approval admin)
        Schema::create('pro_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company_name')->default('');
            $table->string('vat_number')->default('');
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });

        // API key Pro (hash + last_four per riconoscimento, scope JSON)
        Schema::create('pro_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key_hash')->unique();
            $table->string('last_four', 4);
            $table->text('scopes');
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pro_api_keys');
        Schema::dropIfExists('pro_requests');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('cookie_consents');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('user_notification_preferences');
        Schema::dropIfExists('user_addresses');
    }
};
