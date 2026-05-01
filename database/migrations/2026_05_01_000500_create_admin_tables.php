<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrazione 6/6 — Admin: coupons, referral, fasce prezzo, settings.
 *
 * Tabelle create:
 *  - coupons: codici sconto admin con expiration + uso massimo
 *  - coupon_user: tracciamento uso coupon per utente (anti-abuse)
 *  - referral_usages: tracciamento referral Pro→buyer con commissione
 *  - price_bands: fasce prezzo a peso/volume (catalogo amministrabile)
 *  - settings: chiave-valore generico admin (config runtime)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Coupon admin (codice, percentuale sconto, expiration, max usi)
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('stripe_connected_account_id')->nullable();
            $table->decimal('percentage', 5, 2);
            $table->boolean('active')->default(true);
            $table->dateTime('expires_at')->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('max_uses_per_user')->nullable();
            $table->integer('uses_count')->default(0);
            $table->timestamps();

            $table->index('code', 'coupons_code_idx');
        });

        // Pivot coupon/user/order (anti-abuse + tracciamento utilizzo)
        Schema::create('coupon_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('used_at')->useCurrent();

            $table->index(['coupon_id', 'user_id']);
        });

        // Tracciamento referral Pro→buyer con commissione attiva
        Schema::create('referral_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pro_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('referral_code');
            $table->foreignId('order_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->decimal('order_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->string('status')->default('pending'); // pending | confirmed | paid | rejected
            $table->timestamps();
        });

        // Fasce prezzo (peso o volume) con base_price + sconto opzionale
        Schema::create('price_bands', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['weight', 'volume']);
            $table->decimal('min_value', 10, 2);
            $table->decimal('max_value', 10, 2);
            $table->integer('base_price'); // cents
            $table->integer('discount_price')->nullable(); // cents
            $table->boolean('show_discount')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Key-value generico admin (config runtime: BRT credentials, feature flags, ...)
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('price_bands');
        Schema::dropIfExists('referral_usages');
        Schema::dropIfExists('coupon_user');
        Schema::dropIfExists('coupons');
    }
};
