<?php

namespace Tests\Feature\Characterization;

use App\Models\PriceBand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TEST DI CARATTERIZZAZIONE — Calcolo Preventivo e Prezzo
 *
 * Questi test documentano il comportamento attuale del calcolo preventivo
 * nel metodo SessionController::firstStep().
 *
 * File sorgente: app/Http/Controllers/SessionController.php
 */
class PreventivoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload base valido per il primo passo del preventivo.
     * Viene usato come punto di partenza per tutti i test.
     */
    private function basePayload(array $packageOverrides = [], array $shipmentOverrides = []): array
    {
        return [
            'shipment_details' => array_merge([
                'origin_city' => 'Milano',
                'origin_postal_code' => '20100',
                'destination_city' => 'Roma',
                'destination_postal_code' => '00100',
            ], $shipmentOverrides),
            'packages' => [
                array_merge([
                    'package_type' => 'Pacco',
                    'quantity' => 1,
                    'weight' => '5',
                    'first_size' => '30',
                    'second_size' => '20',
                    'third_size' => '15',
                ], $packageOverrides),
            ],
        ];
    }

    // ========================================================================
    // FASCE DI PREZZO PER PESO (fallback hardcoded)
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Fascia peso 0-2 kg = 8.90 EUR
     *
     * Cosa verifica: pacco da 1 kg ricade nella prima fascia peso (0-2 kg)
     * Comportamento attuale: il prezzo peso e' 8.90 EUR tramite fallbackPrice()
     * File sorgente: app/Http/Controllers/SessionController.php:70
     */
    public function test_fascia_peso_0_2_kg_costa_8_90(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(['weight' => '1']);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(8.90, $packages[0]['weight_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Fascia peso 2-5 kg = 11.90 EUR
     *
     * Cosa verifica: pacco da 3 kg ricade nella seconda fascia peso (2-5 kg)
     * Comportamento attuale: il prezzo peso e' 11.90 EUR tramite fallbackPrice()
     * File sorgente: app/Http/Controllers/SessionController.php:71
     */
    public function test_fascia_peso_2_5_kg_costa_11_90(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(['weight' => '3']);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(11.90, $packages[0]['weight_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Fascia peso 5-10 kg = 14.90 EUR
     *
     * Cosa verifica: pacco da 7 kg ricade nella terza fascia peso (5-10 kg)
     * Comportamento attuale: il prezzo peso e' 14.90 EUR tramite fallbackPrice()
     * File sorgente: app/Http/Controllers/SessionController.php:72
     */
    public function test_fascia_peso_5_10_kg_costa_14_90(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(['weight' => '7']);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(14.90, $packages[0]['weight_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Fascia peso 10-25 kg = 19.90 EUR
     *
     * Cosa verifica: pacco da 15 kg ricade nella quarta fascia peso (10-25 kg)
     * Comportamento attuale: il prezzo peso e' 19.90 EUR tramite fallbackPrice()
     * File sorgente: app/Http/Controllers/SessionController.php:73
     */
    public function test_fascia_peso_10_25_kg_costa_19_90(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(['weight' => '15']);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(19.90, $packages[0]['weight_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Fascia peso 25-50 kg = 29.90 EUR
     *
     * Cosa verifica: pacco da 30 kg ricade nella quinta fascia peso (25-50 kg)
     * Comportamento attuale: il prezzo peso e' 29.90 EUR tramite fallbackPrice()
     * File sorgente: app/Http/Controllers/SessionController.php:74
     */
    public function test_fascia_peso_25_50_kg_costa_29_90(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(['weight' => '30']);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(29.90, $packages[0]['weight_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Fascia peso 50-75 kg = 39.90 EUR
     *
     * Cosa verifica: pacco da 60 kg ricade nella sesta fascia peso (50-75 kg)
     * Comportamento attuale: il prezzo peso e' 39.90 EUR tramite fallbackPrice()
     * File sorgente: app/Http/Controllers/SessionController.php:75
     */
    public function test_fascia_peso_50_75_kg_costa_39_90(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(['weight' => '60']);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(39.90, $packages[0]['weight_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Fascia peso >75 kg = 49.90 EUR
     *
     * Cosa verifica: pacco da 80 kg ricade nell'ultima fascia peso (>75 kg)
     * Comportamento attuale: il prezzo peso e' 49.90 EUR tramite fallbackPrice()
     * File sorgente: app/Http/Controllers/SessionController.php:76
     */
    public function test_fascia_peso_oltre_75_kg_costa_49_90(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(['weight' => '80']);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(49.90, $packages[0]['weight_price']);
    }

    // ========================================================================
    // FASCE DI PREZZO PER VOLUME (fallback hardcoded)
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Fascia volume 0-0.010 m3 = 8.90 EUR
     *
     * Cosa verifica: pacco 10x10x10 cm = 0.001 m3 ricade nella prima fascia volume
     * Comportamento attuale: il prezzo volume e' 8.90 EUR tramite fallbackPrice()
     * File sorgente: app/Http/Controllers/SessionController.php:79
     */
    public function test_fascia_volume_sotto_0010_m3_costa_8_90(): void
    {
        $user = User::factory()->create();
        // 10x10x10 cm = 0.001 m3
        $payload = $this->basePayload([
            'weight' => '1',
            'first_size' => '10',
            'second_size' => '10',
            'third_size' => '10',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(8.90, $packages[0]['volume_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Fascia volume 0.010-0.020 m3 = 11.90 EUR
     *
     * Cosa verifica: pacco 25x25x20 cm = 0.0125 m3 ricade nella seconda fascia volume
     * Comportamento attuale: il prezzo volume e' 11.90 EUR tramite fallbackPrice()
     * File sorgente: app/Http/Controllers/SessionController.php:80
     */
    public function test_fascia_volume_0010_0020_m3_costa_11_90(): void
    {
        $user = User::factory()->create();
        // 25x25x20 cm = 0.0125 m3
        $payload = $this->basePayload([
            'weight' => '1',
            'first_size' => '25',
            'second_size' => '25',
            'third_size' => '20',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(11.90, $packages[0]['volume_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Fascia volume 0.020-0.040 m3 = 14.90 EUR
     *
     * Cosa verifica: pacco 30x30x25 cm = 0.0225 m3 ricade nella terza fascia volume
     * Comportamento attuale: il prezzo volume e' 14.90 EUR tramite fallbackPrice()
     * File sorgente: app/Http/Controllers/SessionController.php:81
     */
    public function test_fascia_volume_0020_0040_m3_costa_14_90(): void
    {
        $user = User::factory()->create();
        // 30x30x25 cm = 0.0225 m3
        $payload = $this->basePayload([
            'weight' => '1',
            'first_size' => '30',
            'second_size' => '30',
            'third_size' => '25',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(14.90, $packages[0]['volume_price']);
    }

    // ========================================================================
    // PREZZO = MAX(peso, volume)
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Il prezzo finale e' MAX(peso, volume)
     *
     * Cosa verifica: quando il prezzo peso (11.90) > prezzo volume (8.90), il prezzo base e' 11.90
     * Comportamento attuale: single_price = max(weight_price, volume_price) * quantity
     * File sorgente: app/Http/Controllers/SessionController.php:162
     */
    public function test_prezzo_e_il_massimo_tra_peso_e_volume(): void
    {
        $user = User::factory()->create();
        // Peso 3 kg -> weight_price = 11.90
        // Volume 10x10x10 cm = 0.001 m3 -> volume_price = 8.90
        // max(11.90, 8.90) = 11.90
        $payload = $this->basePayload([
            'weight' => '3',
            'first_size' => '10',
            'second_size' => '10',
            'third_size' => '10',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(11.90, $packages[0]['weight_price']);
        $this->assertEquals(8.90, $packages[0]['volume_price']);
        $this->assertEquals(11.90, $packages[0]['single_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Il prezzo volume prevale quando e' maggiore del peso
     *
     * Cosa verifica: quando il prezzo volume (14.90) > prezzo peso (8.90), il prezzo base e' 14.90
     * Comportamento attuale: single_price = max(weight_price, volume_price) * quantity
     * File sorgente: app/Http/Controllers/SessionController.php:162
     */
    public function test_prezzo_volume_prevale_quando_maggiore(): void
    {
        $user = User::factory()->create();
        // Peso 1 kg -> weight_price = 8.90
        // Volume 30x30x25 cm = 0.0225 m3 -> volume_price = 14.90
        // max(8.90, 14.90) = 14.90
        $payload = $this->basePayload([
            'weight' => '1',
            'first_size' => '30',
            'second_size' => '30',
            'third_size' => '25',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(8.90, $packages[0]['weight_price']);
        $this->assertEquals(14.90, $packages[0]['volume_price']);
        $this->assertEquals(14.90, $packages[0]['single_price']);
    }

    // ========================================================================
    // SUPPLEMENTO CAP 90
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Supplemento +2.50 EUR per CAP origine che inizia con "90"
     *
     * Cosa verifica: CAP di partenza "90100" (Palermo) aggiunge 2.50 EUR al prezzo
     * Comportamento attuale: str_starts_with($originCap, '90') => +2.50 EUR
     * File sorgente: app/Http/Controllers/SessionController.php:135
     */
    public function test_supplemento_cap90_origine(): void
    {
        $user = User::factory()->create();
        // Peso 1 kg -> weight_price = 8.90, volume piccolo -> volume_price = 8.90
        // CAP origine 90100 -> +2.50 EUR
        // single_price = max(8.90, 8.90) + 2.50 = 11.40
        $payload = $this->basePayload(
            ['weight' => '1', 'first_size' => '10', 'second_size' => '10', 'third_size' => '10'],
            ['origin_postal_code' => '90100']
        );

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(11.40, $packages[0]['single_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Supplemento +2.50 EUR per CAP destinazione che inizia con "90"
     *
     * Cosa verifica: CAP di destinazione "90100" (Palermo) aggiunge 2.50 EUR al prezzo
     * Comportamento attuale: str_starts_with($destCap, '90') => +2.50 EUR
     * File sorgente: app/Http/Controllers/SessionController.php:136
     */
    public function test_supplemento_cap90_destinazione(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(
            ['weight' => '1', 'first_size' => '10', 'second_size' => '10', 'third_size' => '10'],
            ['destination_postal_code' => '90100']
        );

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        // max(8.90, 8.90) + 2.50 = 11.40
        $this->assertEquals(11.40, $packages[0]['single_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Doppio supplemento CAP 90 (origine + destinazione)
     *
     * Cosa verifica: se sia CAP origine che destinazione iniziano con "90", si applica +5.00 EUR
     * Comportamento attuale: ogni CAP "90" aggiunge +2.50 EUR indipendentemente
     * File sorgente: app/Http/Controllers/SessionController.php:135-136
     */
    public function test_doppio_supplemento_cap90_origine_e_destinazione(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(
            ['weight' => '1', 'first_size' => '10', 'second_size' => '10', 'third_size' => '10'],
            ['origin_postal_code' => '90100', 'destination_postal_code' => '90200']
        );

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        // max(8.90, 8.90) + 2.50 + 2.50 = 13.90
        $this->assertEquals(13.90, $packages[0]['single_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Nessun supplemento per CAP che NON inizia con "90"
     *
     * Cosa verifica: CAP normali (es. 20100, 00100) non aggiungono supplemento
     * Comportamento attuale: supplemento solo per CAP che iniziano con "90"
     * File sorgente: app/Http/Controllers/SessionController.php:135-136
     */
    public function test_nessun_supplemento_cap_normale(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(
            ['weight' => '1', 'first_size' => '10', 'second_size' => '10', 'third_size' => '10'],
            ['origin_postal_code' => '20100', 'destination_postal_code' => '00100']
        );

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        // max(8.90, 8.90) + 0 = 8.90
        $this->assertEquals(8.90, $packages[0]['single_price']);
    }

    // ========================================================================
    // QUANTITA' MULTIPLE
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — single_price = prezzo_base * quantita'
     *
     * Cosa verifica: con quantity=3, il single_price e' il prezzo base moltiplicato per 3
     * Comportamento attuale: single_price = round(basePrice * quantity, 2)
     * File sorgente: app/Http/Controllers/SessionController.php:164
     */
    public function test_single_price_moltiplicato_per_quantita(): void
    {
        $user = User::factory()->create();
        // Peso 3 kg -> weight_price = 11.90
        // Volume piccolo -> volume_price = 8.90
        // base = max(11.90, 8.90) = 11.90
        // single_price = 11.90 * 3 = 35.70
        $payload = $this->basePayload([
            'weight' => '3',
            'quantity' => 3,
            'first_size' => '10',
            'second_size' => '10',
            'third_size' => '10',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(3, $packages[0]['quantity']);
        $this->assertEquals(35.70, $packages[0]['single_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — total_price e' la somma dei single_price di tutti i pacchi
     *
     * Cosa verifica: con piu' tipologie di pacco, total_price somma tutti i single_price
     * Comportamento attuale: total_price = sum(single_price) per tutti i pacchi
     * File sorgente: app/Http/Controllers/SessionController.php:170-172
     */
    public function test_total_price_somma_tutti_i_pacchi(): void
    {
        $user = User::factory()->create();
        $payload = [
            'shipment_details' => [
                'origin_city' => 'Milano',
                'origin_postal_code' => '20100',
                'destination_city' => 'Roma',
                'destination_postal_code' => '00100',
            ],
            'packages' => [
                [
                    'package_type' => 'Pacco',
                    'quantity' => 1,
                    'weight' => '1',       // weight_price = 8.90
                    'first_size' => '10',
                    'second_size' => '10',
                    'third_size' => '10',  // volume_price = 8.90
                ],
                [
                    'package_type' => 'Pacco',
                    'quantity' => 2,
                    'weight' => '3',       // weight_price = 11.90
                    'first_size' => '10',
                    'second_size' => '10',
                    'third_size' => '10',  // volume_price = 8.90
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $data = $response->json('data');
        // Pacco 1: max(8.90, 8.90) * 1 = 8.90
        // Pacco 2: max(11.90, 8.90) * 2 = 23.80
        // total = 8.90 + 23.80 = 32.70
        $this->assertEquals(8.90, $data['packages'][0]['single_price']);
        $this->assertEquals(23.80, $data['packages'][1]['single_price']);
        $this->assertEquals(32.70, $data['total_price']);
    }

    // ========================================================================
    // SESSIONE E STEP
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Dopo firstStep, lo step in sessione e' 2
     *
     * Cosa verifica: il sistema salva step=2 nella sessione dopo il primo passo
     * Comportamento attuale: session()->put('step', 2) dopo il calcolo del prezzo
     * File sorgente: app/Http/Controllers/SessionController.php:178
     */
    public function test_step_diventa_2_dopo_first_step(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload();

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.step'));
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Struttura dati restituita dal firstStep
     *
     * Cosa verifica: la risposta contiene tutte le chiavi attese nella struttura data
     * Comportamento attuale: restituisce shipment_details, packages, services, total_price, step
     * File sorgente: app/Http/Controllers/SessionController.php:181-189
     */
    public function test_struttura_risposta_first_step(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload();

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'shipment_details',
                'packages',
                'services',
                'total_price',
                'step',
            ],
        ]);
    }

    // ========================================================================
    // FASCE DA DATABASE (PriceBand)
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Le fasce dal DB hanno priorita' sul fallback
     *
     * Cosa verifica: se ci sono fasce nel DB, il prezzo viene da li' (non dal fallback hardcoded)
     * Comportamento attuale: findBandPrice() cerca prima nel DB, poi usa fallbackPrice()
     * File sorgente: app/Http/Controllers/SessionController.php:42-62
     */
    public function test_fasce_db_hanno_priorita_su_fallback(): void
    {
        $user = User::factory()->create();

        // Creiamo una fascia peso nel DB con prezzo diverso dal fallback
        PriceBand::create([
            'type' => 'weight',
            'min_value' => 0,
            'max_value' => 2,
            'base_price' => 790, // 7.90 EUR (diverso dal fallback 8.90)
            'discount_price' => null,
            'sort_order' => 1,
        ]);

        $payload = $this->basePayload([
            'weight' => '1',
            'first_size' => '10',
            'second_size' => '10',
            'third_size' => '10',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        // Il prezzo deve venire dal DB: 790 centesimi = 7.90 EUR
        $this->assertEquals(7.90, $packages[0]['weight_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Il discount_price ha priorita' su base_price nel DB
     *
     * Cosa verifica: se discount_price e' presente, viene usato al posto di base_price
     * Comportamento attuale: findBandPrice() usa ($band->discount_price ?? $band->base_price) / 100
     * File sorgente: app/Http/Controllers/SessionController.php:51
     */
    public function test_discount_price_ha_priorita_su_base_price(): void
    {
        $user = User::factory()->create();

        PriceBand::create([
            'type' => 'weight',
            'min_value' => 0,
            'max_value' => 2,
            'base_price' => 1190, // 11.90 EUR
            'discount_price' => 690, // 6.90 EUR (scontato)
            'sort_order' => 1,
        ]);

        $payload = $this->basePayload([
            'weight' => '1',
            'first_size' => '10',
            'second_size' => '10',
            'third_size' => '10',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        // Deve usare il discount_price: 690 centesimi = 6.90 EUR
        $this->assertEquals(6.90, $packages[0]['weight_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Il server ricalcola comunque i prezzi anche se il frontend li manda gia'
     *
     * Cosa verifica: weight_price e volume_price nel payload non sono considerati affidabili
     * e vengono sostituiti dal calcolo server-side.
     * File sorgente: app/Http/Controllers/SessionController.php:33-80
     */
    public function test_frontend_puo_mandare_prezzi_gia_calcolati(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload([
            'weight' => '1',
            'first_size' => '10',
            'second_size' => '10',
            'third_size' => '10',
            'weight_price' => 15.00,
            'volume_price' => 12.00,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        // Peso 1 kg -> 8.90 EUR, volume 10x10x10 cm = 0.001 m3 -> 8.90 EUR
        $this->assertEquals(8.90, $packages[0]['weight_price']);
        $this->assertEquals(8.90, $packages[0]['volume_price']);
        // single_price = max(8.90, 8.90) * 1 = 8.90
        $this->assertEquals(8.90, $packages[0]['single_price']);
    }

    // ========================================================================
    // VALIDAZIONE
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Errore 422 se mancano i dati obbligatori
     *
     * Cosa verifica: il server rifiuta la richiesta se mancano campi obbligatori
     * Comportamento attuale: validazione Laravel restituisce 422 con errori
     * File sorgente: app/Http/Controllers/SessionController.php:109-125
     */
    public function test_validazione_rifiuta_payload_vuoto(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', []);

        $response->assertStatus(422);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Errore 422 se manca la lista pacchi
     *
     * Cosa verifica: la lista packages e' obbligatoria e deve avere almeno 1 elemento
     * Comportamento attuale: 'packages' => ['required', 'array', 'min:1']
     * File sorgente: app/Http/Controllers/SessionController.php:115
     */
    public function test_validazione_richiede_almeno_un_pacco(): void
    {
        $user = User::factory()->create();

        $payload = [
            'shipment_details' => [
                'origin_city' => 'Milano',
                'origin_postal_code' => '20100',
                'destination_city' => 'Roma',
                'destination_postal_code' => '00100',
            ],
            'packages' => [],
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertStatus(422);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Il preventivo funziona anche senza autenticazione
     *
     * Cosa verifica: l'endpoint /api/session/first-step e' accessibile anche agli ospiti
     * Comportamento attuale: non richiede middleware auth (usato nella homepage)
     * File sorgente: routes/api.php
     */
    public function test_preventivo_funziona_senza_autenticazione(): void
    {
        $payload = $this->basePayload([
            'weight' => '1',
            'first_size' => '10',
            'second_size' => '10',
            'third_size' => '10',
        ]);

        $response = $this->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertEquals(8.90, $packages[0]['weight_price']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Lookup fascia: se il valore supera tutte le fasce DB, usa l'ultima
     *
     * Cosa verifica: un pacco con peso altissimo ricade nell'ultima fascia DB
     * Comportamento attuale: findBandPrice() prende l'ultima fascia per max_value se nessuna corrisponde
     * File sorgente: app/Http/Controllers/SessionController.php:54-57
     */
    public function test_valore_oltre_tutte_le_fasce_usa_ultima_fascia_db(): void
    {
        $user = User::factory()->create();

        // Creiamo solo 2 fasce peso nel DB
        PriceBand::create([
            'type' => 'weight',
            'min_value' => 0,
            'max_value' => 5,
            'base_price' => 890,
            'sort_order' => 1,
        ]);
        PriceBand::create([
            'type' => 'weight',
            'min_value' => 5,
            'max_value' => 10,
            'base_price' => 1490,
            'sort_order' => 2,
        ]);

        // Peso 50 kg, supera tutte le fasce DB (max=10)
        $payload = $this->basePayload([
            'weight' => '50',
            'first_size' => '10',
            'second_size' => '10',
            'third_size' => '10',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $packages = $response->json('data.packages');
        // Deve usare l'ultima fascia DB: 1490 centesimi = 14.90 EUR
        $this->assertEquals(14.90, $packages[0]['weight_price']);
    }

    public function test_preventivo_europa_monocollo_calcola_la_tariffa_del_listino(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(
            [
                'weight' => '8',
                'first_size' => '40',
                'second_size' => '30',
                'third_size' => '20',
            ],
            [
                'destination_city' => 'Vienna',
                'destination_postal_code' => '1010',
                'destination_country_code' => 'AT',
                'destination_country' => 'Austria',
            ],
        );

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.total_price', 30);
        $response->assertJsonPath('data.packages.0.pricing_scope', 'europe_monocollo');
        $response->assertJsonPath('data.packages.0.single_price', 30);
    }

    public function test_preventivo_europa_puo_calcolare_anche_senza_cap_destinazione(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(
            [
                'weight' => '8',
                'first_size' => '40',
                'second_size' => '30',
                'third_size' => '20',
            ],
            [
                'destination_city' => 'Vienna',
                'destination_postal_code' => '',
                'destination_country_code' => 'AT',
                'destination_country' => 'Austria',
            ],
        );

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.total_price', 30);
    }

    public function test_preventivo_europa_blocca_più_colli(): void
    {
        $user = User::factory()->create();
        $payload = [
            'shipment_details' => [
                'origin_city' => 'Milano',
                'origin_postal_code' => '20100',
                'origin_country_code' => 'IT',
                'origin_country' => 'Italia',
                'destination_city' => 'Vienna',
                'destination_postal_code' => '1010',
                'destination_country_code' => 'AT',
                'destination_country' => 'Austria',
            ],
            'packages' => [
                [
                    'package_type' => 'Pacco',
                    'quantity' => 1,
                    'weight' => '5',
                    'first_size' => '30',
                    'second_size' => '20',
                    'third_size' => '15',
                ],
                [
                    'package_type' => 'Pacco',
                    'quantity' => 1,
                    'weight' => '5',
                    'first_size' => '30',
                    'second_size' => '20',
                    'third_size' => '15',
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['packages']);
    }

    public function test_preventivo_europa_segnala_quando_serve_preventivo_manuale(): void
    {
        $user = User::factory()->create();
        $payload = $this->basePayload(
            [
                'weight' => '45',
                'first_size' => '55',
                'second_size' => '50',
                'third_size' => '58',
            ],
            [
                'destination_city' => 'Copenaghen',
                'destination_postal_code' => '2100',
                'destination_country_code' => 'DK',
                'destination_country' => 'Danimarca',
            ],
        );

        $response = $this->actingAs($user)
            ->postJson('/api/session/first-step', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['packages']);
    }

    public function test_session_payload_persists_submission_context_across_second_step_when_frontend_omits_it(): void
    {
        $user = User::factory()->create();

        $firstStep = $this->actingAs($user)
            ->postJson('/api/session/first-step', $this->basePayload())
            ->assertOk();

        $firstSubmissionId = $firstStep->json('data.client_submission_id');
        $this->assertNotEmpty($firstSubmissionId);

        $secondStep = $this->actingAs($user)
            ->postJson('/api/session/second-step', [
                'content_description' => 'Libri',
                'pickup_date' => '2026-04-22',
                'services' => [
                    'service_type' => 'Nessuno',
                    'date' => '2026-04-22',
                    'time' => '09:00-18:00',
                ],
            ])
            ->assertOk();

        $secondStep->assertJsonPath('data.client_submission_id', $firstSubmissionId);

        $this->actingAs($user)
            ->getJson('/api/session')
            ->assertOk()
            ->assertJsonPath('data.client_submission_id', $firstSubmissionId);
    }

    public function test_first_step_rotates_submission_context_when_quote_changes(): void
    {
        $user = User::factory()->create();

        $firstStep = $this->actingAs($user)
            ->postJson('/api/session/first-step', $this->basePayload())
            ->assertOk();

        $firstSubmissionId = $firstStep->json('data.client_submission_id');
        $this->assertNotEmpty($firstSubmissionId);

        $changedQuote = $this->basePayload([
            'weight' => '9',
            'first_size' => '45',
            'second_size' => '30',
            'third_size' => '22',
        ]);

        $secondQuote = $this->actingAs($user)
            ->postJson('/api/session/first-step', $changedQuote)
            ->assertOk();

        $this->assertNotSame($firstSubmissionId, $secondQuote->json('data.client_submission_id'));
    }
}
