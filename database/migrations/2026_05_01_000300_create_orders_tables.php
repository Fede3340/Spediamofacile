<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrazione 4/6 — Carrello + Ordini + Reclami.
 *
 * Tabelle create:
 *  - billing_addresses: dati fatturazione cliente (CF/P.IVA/SDI)
 *  - cart_user: pivot carrello utente (user_id + package_id)
 *  - saved_shipments: spedizioni salvate per riuso rapido
 *  - orders: ordine completo (status, BRT, Stripe, pricing snapshot, SDI)
 *  - package_order: pivot pacchi/ordine con quantità
 *  - transactions: transazioni Stripe + tracciamento provider status
 *  - claims: reclami danno/perdita/ritardo con workflow status
 *  - claim_attachments: allegati reclami (foto, PDF)
 *  - invoice_counters: numerazione fatture progressiva annuale
 */
return new class extends Migration
{
    public function up(): void
    {
        // Indirizzi fatturazione (CF privati / P.IVA business + SDI fattura elettronica)
        Schema::create('billing_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('city');
            $table->string('province_name');
            $table->string('postal_code');
            $table->string('country')->default('IT');

            // Fatturazione business (P.IVA + SDI obbligatori)
            $table->boolean('is_business')->default(false);
            $table->string('company_name')->nullable();
            $table->string('fiscal_code')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('sdi_code')->default('0000000');
            $table->string('pec_email')->nullable();

            $table->timestamps();
        });

        // Carrello utente (pivot user→package). Per guest: GuestCartController in session
        Schema::create('cart_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->dateTime('abandoned_cart_sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'package_id'], 'cart_user_user_package_idx');
        });

        // Spedizioni salvate (cliente riusa indirizzi+servizio frequente)
        Schema::create('saved_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Ordini — il cuore del sistema. BRT integration + Stripe + SDI fattura
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('pending')->index();
            $table->integer('subtotal'); // cents
            $table->foreignId('user_id')->constrained();
            $table->timestamps();

            // BRT integration (parcel ID, label, tracking)
            $table->string('brt_parcel_id')->nullable()->index();
            $table->string('brt_numeric_sender_reference')->nullable();
            $table->string('brt_tracking_url')->nullable();
            $table->longText('brt_label_base64')->nullable();
            $table->longText('brt_all_labels')->nullable();
            $table->string('brt_pudo_id')->nullable();
            $table->string('brt_tracking_number')->nullable()->index();
            $table->string('brt_parcel_number_to')->nullable();
            $table->string('brt_departure_depot')->nullable();
            $table->string('brt_arrival_terminal')->nullable();
            $table->string('brt_arrival_depot')->nullable();
            $table->string('brt_delivery_zone')->nullable();
            $table->string('brt_series_number')->nullable();
            $table->string('brt_service_type')->nullable();
            $table->text('brt_raw_response')->nullable();
            $table->text('brt_error')->nullable();
            $table->dateTime('brt_last_tracking_check')->nullable();

            // Contrassegno (COD)
            $table->boolean('is_cod')->default(false);
            $table->integer('cod_amount')->nullable();
            $table->string('cod_payment_type')->nullable();
            $table->string('cod_incasso_type')->nullable();

            // Refund
            $table->string('refund_status')->nullable();
            $table->integer('refund_amount')->nullable();
            $table->string('refund_method')->nullable();
            $table->string('refund_reason')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->integer('cancellation_fee')->nullable();

            // Pagamento
            $table->string('payment_method')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('nowpayments_invoice_id')->nullable();
            $table->dateTime('bank_transfer_confirmed_at')->nullable()->index();
            $table->string('bank_transfer_reference')->nullable();
            $table->unsignedBigInteger('bank_transfer_confirmed_by')->nullable();

            // Pickup (ritiro presso mittente)
            $table->string('pickup_status')->nullable();
            $table->string('pickup_reference')->nullable();
            $table->dateTime('pickup_requested_at')->nullable();
            $table->string('pickup_time_slot')->nullable();
            $table->string('pickup_notes')->nullable();
            $table->date('pickup_date')->nullable();

            // Bordero (documento partenza)
            $table->string('bordero_status')->nullable();
            $table->string('bordero_reference')->nullable();
            $table->longText('bordero_document_base64')->nullable();
            $table->string('bordero_document_mime')->nullable();
            $table->string('bordero_document_filename')->nullable();

            // Documenti (consegna cliente/admin)
            $table->string('documents_status')->nullable();
            $table->dateTime('documents_sent_customer_at')->nullable();
            $table->dateTime('documents_sent_admin_at')->nullable();
            $table->text('execution_error')->nullable();

            // Idempotency + pricing snapshot (autorità fatturazione storica)
            $table->string('client_submission_id')->nullable();
            $table->string('pricing_signature')->nullable();
            $table->integer('pricing_snapshot_version')->nullable();
            $table->text('pricing_snapshot')->nullable();
            $table->string('coupon_code')->nullable();
            $table->text('billing_data')->nullable();

            // SDI fattura elettronica
            $table->string('sdi_status')->nullable()->index();
            $table->string('sdi_xml_path')->nullable();
            $table->string('sdi_transmission_id')->nullable()->index();
            $table->string('sdi_invoice_number')->nullable();
            $table->dateTime('sdi_sent_at')->nullable();
            $table->dateTime('sdi_accepted_at')->nullable();
            $table->dateTime('sdi_rejected_at')->nullable();
            $table->text('sdi_last_error')->nullable();

            // Assicurazione
            $table->integer('insurance_amount_cents')->nullable();

            // Soft delete (cancellazione audit)
            $table->dateTime('deleted_at')->nullable();

            $table->index(['user_id', 'status'], 'orders_user_status_idx');
            $table->index(['user_id', 'client_submission_id'], 'orders_user_submission_idx');
        });

        // Pivot pacchi/ordine (M2M con quantità)
        Schema::create('package_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->timestamps();

            $table->unique(['order_id', 'package_id'], 'package_order_unique');
        });

        // Transazioni Stripe (provider status, failure code, idempotency)
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->string('ext_id')->nullable()->unique();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->string('provider_status')->nullable();
            $table->string('failure_code')->nullable();
            $table->string('failure_message')->nullable();
            $table->integer('total'); // cents
            $table->timestamps();
        });

        // Reclami (danno, perdita, ritardo) con workflow status
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('claim_type', ['damage', 'loss', 'delay', 'wrong_delivery', 'other'])
                ->default('other');
            $table->enum('status', ['open', 'in_review', 'resolved', 'rejected'])->default('open');
            $table->text('description');
            $table->text('resolution_notes')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // Allegati reclami (foto danni, PDF perizia)
        Schema::create('claim_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type');
            $table->integer('size_bytes');
            $table->timestamps();
        });

        // Numerazione fatture (counter atomico annuale per prefix)
        Schema::create('invoice_counters', function (Blueprint $table) {
            $table->id();
            $table->string('prefix')->default('INV');
            $table->integer('year');
            $table->integer('last_number')->default(0);
            $table->timestamps();

            $table->unique(['prefix', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_counters');
        Schema::dropIfExists('claim_attachments');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('package_order');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('saved_shipments');
        Schema::dropIfExists('cart_user');
        Schema::dropIfExists('billing_addresses');
    }
};
