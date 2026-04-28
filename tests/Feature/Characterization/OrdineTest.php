<?php

namespace Tests\Feature\Characterization;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TEST DI CARATTERIZZAZIONE — Ordine
 *
 * Questi test documentano il comportamento attuale della creazione e gestione ordini
 * nel OrderController e nel modello Order.
 *
 * File sorgente: app/Http/Controllers/OrderController.php
 */
class OrdineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload per createDirectOrder
     */
    private function directOrderPayload(array $packageOverrides = []): array
    {
        return [
            'origin_address' => [
                'type' => 'Partenza', 'name' => 'Mario Rossi',
                'additional_information' => '', 'address' => 'Via Roma',
                'number_type' => 'Numero Civico', 'address_number' => '10',
                'intercom_code' => '', 'country' => 'Italia', 'city' => 'Milano',
                'postal_code' => '20100', 'province' => 'MI',
                'telephone_number' => '3331234567', 'email' => '',
            ],
            'destination_address' => [
                'type' => 'Destinazione', 'name' => 'Luigi Verdi',
                'additional_information' => '', 'address' => 'Via Napoli',
                'number_type' => 'Numero Civico', 'address_number' => '5',
                'intercom_code' => '', 'country' => 'Italia', 'city' => 'Roma',
                'postal_code' => '00100', 'province' => 'RM',
                'telephone_number' => '3337654321', 'email' => '',
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
                    'weight' => 3,
                    'first_size' => 20,
                    'second_size' => 15,
                    'third_size' => 10,
                    'single_price' => 12.00,
                ], $packageOverrides),
            ],
        ];
    }

    /**
     * Crea un ordine direttamente nel DB per test che non passano dall'API.
     */
    private function createOrderInDb(User $user, string $status = 'pending', int $subtotal = 1200): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'subtotal' => $subtotal,
            'status' => $status,
        ]);
        return $order;
    }

    // ========================================================================
    // CREAZIONE ORDINE DIRETTO
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Creazione ordine diretto restituisce order_id e order_number
     *
     * Cosa verifica: POST /api/create-direct-order crea un ordine e restituisce id e numero formattato
     * Comportamento attuale: restituisce order_id e order_number con formato "SF-XXXXXX"
     * File sorgente: app/Http/Controllers/OrderController.php:112-235
     */
    public function test_creazione_ordine_diretto(): void
    {
        $user = User::factory()->create();
        $payload = $this->directOrderPayload();

        $response = $this->actingAs($user)
            ->postJson('/api/create-direct-order', $payload);

        $response->assertOk();
        $response->assertJsonStructure(['order_id', 'order_number']);

        $orderId = $response->json('order_id');
        $orderNumber = $response->json('order_number');

        // Il formato order_number e' "SF-" + 6 cifre con zeri davanti
        $this->assertStringStartsWith('SF-', $orderNumber);
        $this->assertEquals('SF-' . str_pad($orderId, 6, '0', STR_PAD_LEFT), $orderNumber);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — L'ordine viene creato con stato "pending"
     *
     * Cosa verifica: un nuovo ordine ha sempre lo stato iniziale "pending"
     * Comportamento attuale: Order::PENDING e' lo stato di default nel boot del modello
     * File sorgente: app/Models/Order.php:123-129
     */
    public function test_ordine_creato_con_stato_pending(): void
    {
        $user = User::factory()->create();
        $payload = $this->directOrderPayload();

        $response = $this->actingAs($user)
            ->postJson('/api/create-direct-order', $payload);

        $response->assertOk();
        $orderId = $response->json('order_id');
        $order = Order::find($orderId);

        $this->assertEquals('pending', $order->getAttributes()['status']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Il prezzo viene RICALCOLATO lato server in createDirectOrder
     *
     * Cosa verifica: il server non si fida del prezzo dal frontend e lo ricalcola
     * Comportamento attuale: usa PriceEngineService, quindi segue lo stesso pricing
     * centrale del preventivo/sessione.
     * File sorgente: app/Http/Controllers/OrderController.php:133-170
     */
    public function test_prezzo_ricalcolato_lato_server_in_direct_order(): void
    {
        $user = User::factory()->create();
        // Peso 3 kg -> fascia 2-5 kg del motore centrale = 11.90 EUR
        // Volume 20x15x10 cm = 0.003 m3 -> fascia 0-0.010 = 8.90 EUR
        // max(11.90, 8.90) = 11.90 EUR
        $payload = $this->directOrderPayload([
            'weight' => 3,
            'first_size' => 20,
            'second_size' => 15,
            'third_size' => 10,
            'single_price' => 999.99, // prezzo falso dal frontend
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/create-direct-order', $payload);

        $response->assertOk();
        $orderId = $response->json('order_id');
        $order = Order::find($orderId);

        // Il subtotale deve essere 1190 centesimi (11.90 EUR), NON il prezzo del frontend
        $this->assertEquals(1190, (int) $order->subtotal->amount());
    }

    /**
     * TEST DI CARATTERIZZAZIONE — I pacchi dell'ordine sono collegati via package_order
     *
     * Cosa verifica: i pacchi vengono collegati all'ordine tramite la tabella pivot package_order
     * Comportamento attuale: inserisce record in package_order per ogni pacco
     * File sorgente: app/Http/Controllers/OrderController.php:218-226
     */
    public function test_pacchi_collegati_tramite_package_order(): void
    {
        $user = User::factory()->create();
        $payload = $this->directOrderPayload();

        $response = $this->actingAs($user)
            ->postJson('/api/create-direct-order', $payload);

        $response->assertOk();
        $orderId = $response->json('order_id');

        $packageOrderCount = DB::table('package_order')
            ->where('order_id', $orderId)
            ->count();
        $this->assertEquals(1, $packageOrderCount);
    }

    // ========================================================================
    // COSTANTI STATO ORDINE
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Costanti di stato nel modello Order
     *
     * Cosa verifica: le costanti di stato corrispondono ai valori attesi
     * Comportamento attuale: 7 costanti di stato definite nel modello
     * File sorgente: app/Models/Order.php:89-96
     */
    public function test_costanti_stato_ordine(): void
    {
        $this->assertEquals('pending', Order::PENDING);
        $this->assertEquals('processing', Order::PROCESSING);
        $this->assertEquals('payment_failed', Order::PAYMENT_FAILED);
        $this->assertEquals('in_transit', Order::IN_TRANSIT);
        $this->assertEquals('completed', Order::COMPLETED);
        $this->assertEquals('cancelled', Order::CANCELLED);
        $this->assertEquals('refunded', Order::REFUNDED);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Traduzione stati in italiano
     *
     * Cosa verifica: il metodo getStatus() traduce correttamente gli stati in italiano
     * Comportamento attuale: mappa hardcoded di traduzioni en->it
     * File sorgente: app/Models/Order.php:101-116
     */
    public function test_traduzione_stati_in_italiano(): void
    {
        $order = new Order();

        $this->assertEquals('In attesa', $order->getStatus('pending'));
        $this->assertEquals('In lavorazione', $order->getStatus('processing'));
        $this->assertEquals('Completato', $order->getStatus('completed'));
        $this->assertEquals('Fallito', $order->getStatus('payment_failed'));
        $this->assertEquals('Annullato', $order->getStatus('cancelled'));
        $this->assertEquals('Rimborsato', $order->getStatus('refunded'));
        $this->assertEquals('In transito', $order->getStatus('in_transit'));
        $this->assertEquals('Consegnato', $order->getStatus('delivered'));
        $this->assertEquals('In giacenza', $order->getStatus('in_giacenza'));
    }

    /**
     * TEST DI CARATTERIZZAZIONE — getStatus() restituisce lo stato originale se non trovato
     *
     * Cosa verifica: stati non previsti vengono restituiti cosi' come sono
     * Comportamento attuale: $data[$status] ?? $status
     * File sorgente: app/Models/Order.php:115
     */
    public function test_stato_non_previsto_restituito_invariato(): void
    {
        $order = new Order();
        $this->assertEquals('stato_inventato', $order->getStatus('stato_inventato'));
    }

    // ========================================================================
    // SUBTOTALE IN CENTESIMI
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Il subtotale e' un oggetto MyMoney (centesimi)
     *
     * Cosa verifica: l'accessor subtotal converte il valore in un oggetto MyMoney
     * Comportamento attuale: getSubtotalAttribute() restituisce new MyMoney($subtotal)
     * File sorgente: app/Models/Order.php:136-138
     */
    public function test_subtotale_e_oggetto_mymoney(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrderInDb($user, 'pending', 1190);

        // amount() restituisce il valore in centesimi
        $this->assertEquals('1190', $order->subtotal->amount());
    }

    // ========================================================================
    // ANNULLAMENTO ORDINE (delega a RefundController)
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Annullamento ordine pending senza rimborso
     *
     * Cosa verifica: un ordine pending viene annullato senza rimborso (non era pagato)
     * Comportamento attuale: delega a RefundController::requestCancellation()
     * File sorgente: app/Http/Controllers/OrderController.php:244-247
     */
    public function test_annullamento_ordine_pending(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrderInDb($user, 'pending', 1200);

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $order->refresh();
        $this->assertEquals('cancelled', $order->getAttributes()['status']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Non si puo' annullare ordine di un altro utente
     *
     * Cosa verifica: l'annullamento fallisce con 403 se l'utente non e' il proprietario
     * Comportamento attuale: verifica order->user_id !== auth()->id()
     * File sorgente: app/Http/Controllers/RefundController.php:99
     */
    public function test_annullamento_403_se_non_proprietario(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = $this->createOrderInDb($otherUser, 'pending', 1200);

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(403);
    }

    // ========================================================================
    // PULIZIA ORDINI VUOTI
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Il modello Order ha stato iniziale "pending" se non specificato
     *
     * Cosa verifica: alla creazione senza status, viene impostato "pending"
     * Comportamento attuale: hook booted() imposta status = PENDING se vuoto
     * File sorgente: app/Models/Order.php:123-129
     */
    public function test_stato_iniziale_pending_se_non_specificato(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'subtotal' => 1000,
        ]);

        $this->assertEquals('pending', $order->getAttributes()['status']);
    }

    // ========================================================================
    // VISUALIZZAZIONE ORDINI
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — L'utente vede solo i propri ordini
     *
     * Cosa verifica: GET /api/orders restituisce solo gli ordini dell'utente autenticato
     * Comportamento attuale: filtra per user_id dell'utente autenticato
     * File sorgente: app/Http/Controllers/OrderController.php:80-84
     */
    public function test_utente_vede_solo_propri_ordini(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createOrderInDb($user, 'pending', 1200);
        $this->createOrderInDb($user, 'processing', 2400);
        $this->createOrderInDb($otherUser, 'pending', 3600);

        $response = $this->actingAs($user)
            ->getJson('/api/orders');

        $response->assertOk();
        $orders = $response->json('data');
        $this->assertCount(2, $orders);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — L'admin vede gli ordini di tutti
     *
     * Cosa verifica: un utente admin vede tutti gli ordini del sistema
     * Comportamento attuale: se user->isAdmin(), non filtra per user_id
     * File sorgente: app/Http/Controllers/OrderController.php:79-84
     */
    public function test_admin_vede_tutti_gli_ordini(): void
    {
        $admin = User::factory()->create();
        $admin->role = 'Admin';
        $admin->save();

        $user = User::factory()->create();

        $this->createOrderInDb($admin, 'pending', 1200);
        $this->createOrderInDb($user, 'pending', 2400);

        $response = $this->actingAs($admin)
            ->getJson('/api/orders');

        $response->assertOk();
        $orders = $response->json('data');
        $this->assertCount(2, $orders);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Ordine diretto con quantita' multipla
     *
     * Cosa verifica: un ordine con quantity > 1 calcola correttamente il subtotale
     * Comportamento attuale: singlePriceEur = round(basePrice * quantity, 2)
     * File sorgente: app/Http/Controllers/OrderController.php:163
     */
    public function test_ordine_diretto_con_quantita_multipla(): void
    {
        $user = User::factory()->create();
        // Peso 3 kg -> fascia 2-5 kg del motore centrale = 11.90 EUR
        $payload = $this->directOrderPayload([
            'weight' => 3,
            'quantity' => 3,
            'first_size' => 20,
            'second_size' => 15,
            'third_size' => 10,
            'single_price' => 36.00,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/create-direct-order', $payload);

        $response->assertOk();
        $orderId = $response->json('order_id');
        $order = Order::find($orderId);

        // 11.90 EUR * 3 = 35.70 EUR = 3570 centesimi
        $this->assertEquals(3570, (int) $order->subtotal->amount());
    }
}
