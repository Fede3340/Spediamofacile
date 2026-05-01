<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrazione 1/6 — Tabelle autenticazione + infrastruttura Laravel.
 *
 * Tabelle create:
 *  - users: anagrafica clienti (privati, business, Pro), OAuth provider, Stripe Connect
 *  - password_reset_tokens, sessions: gestione sessione standard Laravel
 *  - personal_access_tokens: Sanctum SPA + Pro API keys
 *  - audit_logs: tracciato azioni admin/sistema
 *  - cache, cache_locks, jobs, job_batches, failed_jobs: infrastruttura Laravel
 */
return new class extends Migration
{
    public function up(): void
    {
        // Anagrafica utenti (privati, business, Pro) con OAuth + Stripe Connect
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname');
            $table->string('email')->unique();
            $table->string('telephone_number');
            $table->string('role');
            $table->string('customer_id')->nullable();
            $table->dateTime('email_verified_at')->nullable();

            // Stripe Connect (account collegato per Pro)
            $table->string('stripe_account_id')->nullable();
            $table->boolean('stripe_charges_enabled')->default(false);
            $table->boolean('stripe_payouts_enabled')->default(false);
            $table->boolean('stripe_details_submitted')->default(false);
            $table->text('stripe_capabilities')->nullable();
            $table->text('stripe_requirements')->nullable();

            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->timestamps();

            // Sistema referral (codice univoco + tracciamento referrer)
            $table->string('referral_code')->nullable()->unique();
            $table->string('verification_code')->nullable();
            $table->dateTime('verification_code_expires_at')->nullable();
            $table->string('referred_by')->nullable();

            // Tipo utente: privato | business | pro
            $table->string('user_type')->default('privato');

            // OAuth providers (Google, Facebook, Apple)
            $table->string('google_id')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->string('facebook_id')->nullable()->unique();
            $table->string('apple_id')->nullable()->unique();

            // Soft delete + privacy GDPR
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('privacy_accepted_at')->nullable();

            // Telefono separato (verifica SMS)
            $table->string('phone_number')->nullable();
            $table->dateTime('phone_number_verified_at')->nullable();
        });

        // Password reset (Laravel default)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->dateTime('created_at')->nullable();
        });

        // Sessions (Laravel default per session driver=database)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
        });

        // Cache (Laravel default per cache driver=database)
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // Queue (Laravel default per queue driver=database)
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // Sanctum personal access tokens (SPA cookie + Pro API keys)
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });

        // Audit logs (azioni admin + sistema, retention 7 anni)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_type')->default('user');
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('context')->nullable();
            $table->dateTime('created_at')->nullable();

            $table->index(['target_type', 'target_id'], 'audit_logs_target_idx');
            $table->index(['action', 'created_at'], 'audit_logs_action_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
