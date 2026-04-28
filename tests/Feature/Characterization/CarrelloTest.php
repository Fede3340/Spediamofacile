<?php

namespace Tests\Feature\Characterization;

use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TEST DI CARATTERIZZAZIONE — Carrello
 *
 * Questi test documentano il comportamento attuale del carrello
 * nel CartController: aggiunta, visualizzazione, auto-merge, modifica, eliminazione.
 *
 * File sorgente: app/Http/Controllers/CartController.php
 */
class CarrelloTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea un payload di carrello valido per il metodo store.
     */
    private function cartPayload(array $packageOverrides = []): array
    {
        return [
            'origin_address' => [
                'type' => 'Partenza',
                'name' => 'Mario Rossi',
                'additional_information' => '',
                'address' => 'Via Roma',
                'number_type' => 'Numero Civico',
                'address_number' => '10',
                'intercom_code' => '',
                'country' => 'Italia',
                'city' => 'Milano',
                'postal_code' => '20100',
                'province' => 'MI',
                'telephone_number' => '3331234567',
                'email' => '',
            ],
            'destination_address' => [
                'type' => 'Destinazione',
                'name' => 'Luigi Verdi',
                'additional_information' => '',
                'address' => 'Via Napoli',
                'number_type' => 'Numero Civico',
                'address_number' => '5',
                'intercom_code' => '',
                'country' => 'Italia',
                'city' => 'Roma',
                'postal_code' => '00100',
                'province' => 'RM',
                'telephone_number' => '3337654321',
                'email' => '',
            ],
            'services' => [
                'service_type' => '',
                'date' => '',
                'time' => '',
            ],
            'packages' => [
                array_merge([
                    'package_type' => 'Pacco',
                    'quantity' => 1,
                    'weight' => 5,
                    'first_size' => 30,
                    'second_size' => 20,
                    'third_size' => 15,
                ], $packageOverrides),
            ],
        ];
    }

    /**
     * Aggiunge un pacco direttamente nel DB per i test che non passano dall'API.
     */
    private function addPackageToCart(User $user, array $pkgData = []): Package
    {
        $origin = PackageAddress::create([
            'type' => 'Partenza', 'name' => 'Test Origin', 'address' => 'Via Test',
            'number_type' => 'Numero Civico', 'address_number' => '1', 'country' => 'Italia',
            'city' => 'Milano', 'postal_code' => '20100', 'province' => 'MI',
            'telephone_number' => '3331234567',
        ]);
        $dest = PackageAddress::create([
            'type' => 'Destinazione', 'name' => 'Test Dest', 'address' => 'Via Dest',
            'number_type' => 'Numero Civico', 'address_number' => '2', 'country' => 'Italia',
            'city' => 'Roma', 'postal_code' => '00100', 'province' => 'RM',
            'telephone_number' => '3337654321',
        ]);
        $service = Service::create([
            'service_type' => 'Nessuno', 'date' => '', 'time' => '',
        ]);

        $package = Package::create(array_merge([
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => 5,
            'first_size' => 30,
            'second_size' => 20,
            'third_size' => 15,
            'weight_price' => 11.90,
            'volume_price' => 8.90,
            'single_price' => 1190, // centesimi
            'origin_address_id' => $origin->id,
            'destination_address_id' => $dest->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
        ], $pkgData));

        DB::table('cart_user')->insert([
            'user_id' => $user->id,
            'package_id' => $package->id,
        ]);

        return $package;
    }

    // ========================================================================
    // AGGIUNTA AL CARRELLO (store)
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Aggiunta pacco al carrello
     *
     * Cosa verifica: POST /api/cart crea un pacco nel DB con i dati corretti
     * Comportamento attuale: il pacco viene creato con indirizzi, servizio, e collegato a cart_user
     * File sorgente: app/Http/Controllers/CartController.php:351-486
     */
    public function test_aggiunta_pacco_al_carrello(): void
    {
        $user = User::factory()->create();
        $payload = $this->cartPayload();

        $response = $this->actingAs($user)
            ->postJson('/api/cart', $payload);

        $response->assertOk();

        // Verifica che il pacco sia stato creato nel DB
        $this->assertDatabaseHas('packages', [
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => 5,
            'user_id' => $user->id,
        ]);

        // Verifica che il collegamento cart_user esista
        $this->assertDatabaseHas('cart_user', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — I prezzi client vengono ignorati e ricalcolati lato server
     *
     * Cosa verifica: weight_price, volume_price e single_price nel payload non sono autorevoli
     * e il DB salva il prezzo ricalcolato dal server.
     * File sorgente: app/Http/Controllers/CartController.php
     */
    public function test_prezzi_client_ignorati_e_ricalcolati_lato_server(): void
    {
        $user = User::factory()->create();
        $payload = $this->cartPayload([
            'weight_price' => 999.99,
            'volume_price' => 888.88,
            'single_price' => 777.77,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/cart', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('packages', [
            'user_id' => $user->id,
            'single_price' => 1190,
        ]);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Servizio vuoto diventa "Nessuno"
     *
     * Cosa verifica: se service_type e' vuoto, viene impostato a "Nessuno"
     * Comportamento attuale: $servicesData['service_type'] = !empty(...) ? ... : 'Nessuno'
     * File sorgente: app/Http/Controllers/CartController.php:369
     */
    public function test_servizio_vuoto_diventa_nessuno(): void
    {
        $user = User::factory()->create();
        $payload = $this->cartPayload();
        $payload['services']['service_type'] = '';

        $response = $this->actingAs($user)
            ->postJson('/api/cart', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('services', [
            'service_type' => 'Nessuno',
        ]);
    }

    // ========================================================================
    // VISUALIZZAZIONE CARRELLO (index)
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Carrello vuoto restituisce meta.empty = true
     *
     * Cosa verifica: se il carrello e' vuoto, la risposta ha meta.empty = true
     * Comportamento attuale: meta contiene 'empty' => $packages->isEmpty()
     * File sorgente: app/Http/Controllers/CartController.php:118-119
     */
    public function test_carrello_vuoto_ha_meta_empty_true(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/cart');

        $response->assertOk();
        $response->assertJsonPath('meta.empty', true);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Carrello con pacco restituisce meta.empty = false
     *
     * Cosa verifica: se il carrello ha pacchi, meta.empty = false
     * Comportamento attuale: meta contiene 'empty' => $packages->isEmpty()
     * File sorgente: app/Http/Controllers/CartController.php:118-119
     */
    public function test_carrello_con_pacchi_ha_meta_empty_false(): void
    {
        $user = User::factory()->create();
        $this->addPackageToCart($user);

        $response = $this->actingAs($user)
            ->getJson('/api/cart');

        $response->assertOk();
        $response->assertJsonPath('meta.empty', false);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — La risposta del carrello contiene subtotal e total
     *
     * Cosa verifica: la risposta include meta con subtotal, total e address_groups
     * Comportamento attuale: meta() restituisce empty, subtotal, total, address_groups
     * File sorgente: app/Http/Controllers/CartController.php:117-124
     */
    public function test_risposta_carrello_contiene_meta_completo(): void
    {
        $user = User::factory()->create();
        $this->addPackageToCart($user);

        $response = $this->actingAs($user)
            ->getJson('/api/cart');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'empty',
                'subtotal',
                'total',
                'address_groups',
            ],
        ]);
    }

    // ========================================================================
    // AUTO-MERGE PACCHI IDENTICI
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Auto-merge: pacchi identici vengono uniti nel carrello
     *
     * Cosa verifica: due pacchi con stessi tipo/peso/dimensioni/indirizzi/servizio vengono uniti
     * Comportamento attuale: autoMergePackages() raggruppa per chiave e somma le quantita'
     * File sorgente: app/Http/Controllers/CartController.php:130-181
     */
    public function test_auto_merge_pacchi_identici(): void
    {
        $user = User::factory()->create();

        // Creiamo indirizzi e servizio condivisi per i due pacchi
        $origin = PackageAddress::create([
            'type' => 'Partenza', 'name' => 'Mario Rossi', 'address' => 'Via Roma',
            'number_type' => 'Numero Civico', 'address_number' => '10', 'country' => 'Italia',
            'city' => 'Milano', 'postal_code' => '20100', 'province' => 'MI',
            'telephone_number' => '3331234567',
        ]);
        $dest = PackageAddress::create([
            'type' => 'Destinazione', 'name' => 'Luigi Verdi', 'address' => 'Via Napoli',
            'number_type' => 'Numero Civico', 'address_number' => '5', 'country' => 'Italia',
            'city' => 'Roma', 'postal_code' => '00100', 'province' => 'RM',
            'telephone_number' => '3337654321',
        ]);
        $service = Service::create([
            'service_type' => 'Nessuno', 'date' => '', 'time' => '',
        ]);

        // Due pacchi identici: stessi tipo, peso, dimensioni, indirizzi, servizio
        $pkg1 = Package::create([
            'package_type' => 'Pacco', 'quantity' => 1, 'weight' => 5,
            'first_size' => 30, 'second_size' => 20, 'third_size' => 15,
            'single_price' => 1190, 'user_id' => $user->id,
            'origin_address_id' => $origin->id, 'destination_address_id' => $dest->id,
            'service_id' => $service->id,
        ]);
        $pkg2 = Package::create([
            'package_type' => 'Pacco', 'quantity' => 2, 'weight' => 5,
            'first_size' => 30, 'second_size' => 20, 'third_size' => 15,
            'single_price' => 2380, 'user_id' => $user->id,
            'origin_address_id' => $origin->id, 'destination_address_id' => $dest->id,
            'service_id' => $service->id,
        ]);

        DB::table('cart_user')->insert([
            ['user_id' => $user->id, 'package_id' => $pkg1->id],
            ['user_id' => $user->id, 'package_id' => $pkg2->id],
        ]);

        // L'index del carrello triggera auto-merge
        $response = $this->actingAs($user)
            ->getJson('/api/cart');

        $response->assertOk();

        // Dopo il merge, deve esserci un solo pacco con quantita' = 3
        $cartCount = DB::table('cart_user')->where('user_id', $user->id)->count();
        $this->assertEquals(1, $cartCount);

        $mergedPackage = Package::where('user_id', $user->id)->first();
        $this->assertEquals(3, $mergedPackage->quantity);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Auto-merge NON unisce pacchi con dimensioni diverse
     *
     * Cosa verifica: pacchi con dimensioni diverse non vengono uniti
     * Comportamento attuale: la chiave di merge include peso e dimensioni
     * File sorgente: app/Http/Controllers/CartController.php:142-151
     */
    public function test_auto_merge_non_unisce_pacchi_diversi(): void
    {
        $user = User::factory()->create();

        // Pacco 1: dimensioni 30x20x15
        $this->addPackageToCart($user, [
            'first_size' => 30, 'second_size' => 20, 'third_size' => 15,
        ]);
        // Pacco 2: dimensioni diverse 40x30x20
        $this->addPackageToCart($user, [
            'first_size' => 40, 'second_size' => 30, 'third_size' => 20,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/cart');

        $response->assertOk();

        // I pacchi hanno dimensioni diverse, non devono essere uniti
        $cartCount = DB::table('cart_user')->where('user_id', $user->id)->count();
        $this->assertEquals(2, $cartCount);
    }

    // ========================================================================
    // MODIFICA QUANTITA'
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Modifica quantita' ricalcola il prezzo
     *
     * Cosa verifica: PATCH /api/cart/{id}/quantity ricalcola single_price in base alla nuova quantita'
     * Comportamento attuale: unitPrice = single_price / oldQty; new single_price = unitPrice * newQty
     * File sorgente: app/Http/Controllers/CartController.php:490-516
     */
    public function test_modifica_quantita_ricalcola_prezzo(): void
    {
        $user = User::factory()->create();
        $package = $this->addPackageToCart($user, [
            'quantity' => 1,
            'single_price' => 1190, // 1 pacco a 11.90 EUR = 1190 centesimi
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/cart/{$package->id}/quantity", [
                'quantity' => 3,
            ]);

        $response->assertOk();
        $this->assertEquals(3, $response->json('quantity'));
        // unitPrice = 1190 / 1 = 1190; newPrice = 1190 * 3 = 3570
        $this->assertEquals(3570, $response->json('single_price'));
    }

    // ========================================================================
    // ELIMINAZIONE E SVUOTAMENTO
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Eliminazione singolo pacco dal carrello
     *
     * Cosa verifica: DELETE /api/cart/{id} rimuove il pacco dal carrello e dal DB
     * Comportamento attuale: elimina da cart_user e da packages
     * File sorgente: app/Http/Controllers/CartController.php:519-532
     */
    public function test_eliminazione_singolo_pacco(): void
    {
        $user = User::factory()->create();
        $package = $this->addPackageToCart($user);

        $response = $this->actingAs($user)
            ->deleteJson("/api/cart/{$package->id}");

        $response->assertOk();
        $response->assertJsonPath('message', 'Spedizione rimossa dal carrello');

        // Verifica che il pacco sia stato rimosso da cart_user e dal DB
        $this->assertDatabaseMissing('cart_user', [
            'user_id' => $user->id,
            'package_id' => $package->id,
        ]);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Svuotamento completo del carrello
     *
     * Cosa verifica: DELETE /api/empty-cart rimuove tutti i pacchi dal carrello
     * Comportamento attuale: elimina tutti i pacchi in cart_user e i pacchi dal DB
     * File sorgente: app/Http/Controllers/CartController.php:621-642
     */
    public function test_svuotamento_completo_carrello(): void
    {
        $user = User::factory()->create();
        $this->addPackageToCart($user);
        $this->addPackageToCart($user, ['first_size' => 40]);

        // Verifica che ci siano 2 pacchi
        $this->assertEquals(2, DB::table('cart_user')->where('user_id', $user->id)->count());

        $response = $this->actingAs($user)
            ->deleteJson('/api/empty-cart');

        $response->assertOk();
        $response->assertJsonPath('message', 'Carrello svuotato');

        // Verifica che il carrello sia vuoto
        $this->assertEquals(0, DB::table('cart_user')->where('user_id', $user->id)->count());
    }

    // ========================================================================
    // PULIZIA AUTOMATICA
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Pulizia pacchi senza tipo nel carrello
     *
     * Cosa verifica: pacchi con package_type vuoto vengono eliminati durante l'index
     * Comportamento attuale: index() filtra e rimuove pacchi con package_type vuoto
     * File sorgente: app/Http/Controllers/CartController.php:76-96
     */
    public function test_pulizia_automatica_pacchi_invalidi(): void
    {
        $user = User::factory()->create();

        // Pacco valido
        $validPkg = $this->addPackageToCart($user);

        // Pacco invalido (package_type vuoto)
        $origin = PackageAddress::create([
            'type' => 'Partenza', 'name' => 'Test', 'address' => 'Via Test',
            'number_type' => 'Numero Civico', 'address_number' => '1', 'country' => 'Italia',
            'city' => 'Milano', 'postal_code' => '20100', 'province' => 'MI',
            'telephone_number' => '333',
        ]);
        $dest = PackageAddress::create([
            'type' => 'Dest', 'name' => 'Test2', 'address' => 'Via Test2',
            'number_type' => 'Numero Civico', 'address_number' => '2', 'country' => 'Italia',
            'city' => 'Roma', 'postal_code' => '00100', 'province' => 'RM',
            'telephone_number' => '333',
        ]);
        $service = Service::create(['service_type' => 'Nessuno', 'date' => '', 'time' => '']);

        $invalidPkg = Package::create([
            'package_type' => '', // <-- invalido
            'quantity' => 1, 'weight' => 0, 'first_size' => 0, 'second_size' => 0, 'third_size' => 0,
            'single_price' => 0, 'user_id' => $user->id,
            'origin_address_id' => $origin->id, 'destination_address_id' => $dest->id,
            'service_id' => $service->id,
        ]);
        DB::table('cart_user')->insert([
            'user_id' => $user->id,
            'package_id' => $invalidPkg->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/cart');

        $response->assertOk();

        // Il pacco invalido deve essere stato rimosso
        $this->assertDatabaseMissing('cart_user', [
            'package_id' => $invalidPkg->id,
        ]);
    }

    // ========================================================================
    // SHOW SINGOLO PACCO
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Show restituisce il pacco se presente nel carrello
     *
     * Cosa verifica: GET /api/cart/{id} restituisce i dati del pacco con indirizzi e servizio
     * Comportamento attuale: verifica appartenenza in cart_user poi restituisce PackageResource
     * File sorgente: app/Http/Controllers/CartController.php:251-269
     */
    public function test_show_pacco_nel_carrello(): void
    {
        $user = User::factory()->create();
        $package = $this->addPackageToCart($user);

        $response = $this->actingAs($user)
            ->getJson("/api/cart/{$package->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $package->id);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Show 404 per pacco non nel carrello dell'utente
     *
     * Cosa verifica: GET /api/cart/{id} restituisce 404 se il pacco non e' nel carrello
     * Comportamento attuale: controlla cart_user e ritorna 404 se non trovato
     * File sorgente: app/Http/Controllers/CartController.php:260-262
     */
    public function test_show_pacco_404_se_non_nel_carrello(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $package = $this->addPackageToCart($otherUser);

        $response = $this->actingAs($user)
            ->getJson("/api/cart/{$package->id}");

        $response->assertStatus(404);
    }

    // ========================================================================
    // DUPLICATI (store con pacco gia' presente)
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Store con pacco identico incrementa la quantita'
     *
     * Cosa verifica: se si aggiunge un pacco identico a uno gia' nel carrello, la quantita' aumenta
     * Comportamento attuale: cerca duplicati per tipo/peso/dimensioni/indirizzi/servizio, se trovato
     *   incrementa la quantita' del pacco esistente
     * File sorgente: app/Http/Controllers/CartController.php:402-438
     */
    public function test_store_pacco_identico_incrementa_quantita(): void
    {
        $user = User::factory()->create();
        $payload = $this->cartPayload(['quantity' => 1]);

        // Prima aggiunta
        $this->actingAs($user)->postJson('/api/cart', $payload);

        // Seconda aggiunta con lo stesso payload
        $this->actingAs($user)->postJson('/api/cart', $payload);

        // Deve esserci un solo record con quantita' 2
        $cartIds = DB::table('cart_user')
            ->where('user_id', $user->id)
            ->pluck('package_id');

        $this->assertCount(1, $cartIds);

        $package = Package::find($cartIds->first());
        $this->assertEquals(2, $package->quantity);
    }
}
