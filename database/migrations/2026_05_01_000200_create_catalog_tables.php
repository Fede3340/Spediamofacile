<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrazione 3/6 — Catalogo: località, servizi, pacchi, punti BRT.
 *
 * Tabelle create:
 *  - locations: dataset CAP/comuni Italia (lookup autocomplete preventivo)
 *  - pudo_points: rete punti BRT Fermopoint per ritiro/consegna alternativa
 *  - services: tipologia servizio (BRT Standard, Express, Formato lettera)
 *  - package_addresses: indirizzi mittente/destinatario per pacco specifico
 *  - packages: pacco con dimensioni, peso, prezzo unitario, indirizzi linkati
 *  - articles: contenuti CMS (guide, blog, banner promozionali)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Dataset località italiane (CAP + comune + provincia, ~8000 record)
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('postal_code')->index();
            $table->string('place_name')->index();
            $table->string('province');
            $table->string('country_code')->default('IT')->index();

            $table->index(['place_name', 'postal_code'], 'locations_city_postal_idx');
        });

        // Punti BRT Fermopoint (mappa interattiva ritiro/consegna)
        Schema::create('pudo_points', function (Blueprint $table) {
            $table->id();
            $table->string('pudo_id')->unique();
            $table->string('name');
            $table->string('address');
            $table->string('city')->index();
            $table->string('zip_code')->index();
            $table->string('province');
            $table->string('country')->default('ITA');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('opening_hours')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // Tipologia servizio BRT (standard, express, lettera, …)
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_type');
            $table->string('date')->nullable();
            $table->string('time')->nullable();
            $table->text('service_data')->nullable();
            $table->timestamps();
        });

        // Indirizzi mittente/destinatario per pacco specifico (NON è la rubrica utente)
        Schema::create('package_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // mittente | destinatario
            $table->string('name');
            $table->string('additional_information')->nullable();
            $table->string('address');
            $table->string('number_type');
            $table->string('address_number');
            $table->string('intercom_code')->nullable();
            $table->string('country');
            $table->string('city');
            $table->string('postal_code');
            $table->string('province');
            $table->string('telephone_number');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        // Pacchi (carrello + ordine): peso, dimensioni, prezzo unitario, FK servizio + indirizzi
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('package_type');
            $table->integer('quantity');
            $table->string('weight');
            $table->string('first_size');
            $table->string('second_size');
            $table->string('third_size');
            $table->string('weight_price')->nullable();
            $table->string('volume_price')->nullable();
            $table->string('single_price')->nullable();
            $table->foreignId('origin_address_id')->constrained('package_addresses');
            $table->foreignId('destination_address_id')->constrained('package_addresses');
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('content_description')->nullable();
            $table->timestamps();
        });

        // Contenuti CMS (guide, blog, banner homepage promozionali)
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->default('guide'); // guide | blog | banner
            $table->text('meta_description')->nullable();
            $table->text('intro')->nullable();
            $table->text('sections')->nullable();
            $table->text('faqs')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);

            // Banner promozionale (opzionale per type=banner)
            $table->string('banner_image')->nullable();
            $table->string('banner_title')->nullable();
            $table->string('banner_subtitle')->nullable();
            $table->string('banner_cta_text')->nullable();
            $table->string('banner_cta_url')->nullable();
            $table->string('banner_bg_color')->default('#095866');
            $table->string('banner_text_color')->default('#ffffff');
            $table->string('banner_position')->default('homepage_top');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('package_addresses');
        Schema::dropIfExists('services');
        Schema::dropIfExists('pudo_points');
        Schema::dropIfExists('locations');
    }
};
