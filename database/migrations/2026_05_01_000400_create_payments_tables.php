<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrazione 5/6 — Pagamenti: wallet, prelievi, webhook events, archivio fatture.
 *
 * Tabelle create:
 *  - wallet_movements: ledger movimenti wallet utente (credit, debit, hold)
 *  - withdrawal_requests: richieste prelievo wallet con workflow approval
 *  - stripe_webhook_events: idempotency Stripe (no doppia esecuzione)
 *  - brt_webhook_events: idempotency BRT tracking webhook
 *  - invoice_archive: archivio fatture XML SDI con retention legale
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ledger movimenti wallet (credit, debit, hold per fee referral)
        Schema::create('wallet_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // credit | debit | hold
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('EUR');
            $table->string('status')->default('confirmed')->index();
            $table->string('idempotency_key')->unique();
            $table->string('reference')->nullable();
            $table->string('source')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'type'], 'wallet_user_status_type_idx');
            $table->index(['user_id', 'created_at'], 'wallet_movements_user_created_idx');
        });

        // Richieste prelievo wallet (workflow approve/reject admin)
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('EUR');
            $table->string('status')->default('pending'); // pending | approved | rejected | paid
            $table->text('admin_notes')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Idempotency webhook Stripe (no doppia esecuzione su retry)
        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id')->unique();
            $table->string('event_type')->index();
            $table->timestamp('processed_at')->useCurrent()->index();
        });

        // Idempotency webhook BRT tracking (fingerprint = parcel + status + timestamp)
        Schema::create('brt_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint')->unique();
            $table->string('parcel_id')->index();
            $table->string('status');
            $table->string('event_timestamp');
            $table->timestamp('processed_at')->useCurrent()->index();
        });

        // Archivio fatture XML SDI (retention legale 10 anni)
        Schema::create('invoice_archive', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type'); // xml_sdi | pdf_invoice
            $table->string('file_path');
            $table->string('mime_type')->default('application/xml');
            $table->string('sha256_hash');
            $table->integer('size_bytes')->default(0);
            $table->string('invoice_number')->nullable()->index();
            $table->date('invoice_date')->nullable()->index();
            $table->string('archive_status')->default('pending')->index();
            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable();
            $table->date('retain_until')->index();
            $table->text('metadata')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'archive_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_archive');
        Schema::dropIfExists('brt_webhook_events');
        Schema::dropIfExists('stripe_webhook_events');
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('wallet_movements');
    }
};
