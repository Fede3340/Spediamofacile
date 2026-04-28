<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_cart_flow_authenticated(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Step 1: Save session data (first-step)
        $sessionPayload = [
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
                    'weight' => '5',
                    'first_size' => '30',
                    'second_size' => '20',
                    'third_size' => '15',
                ],
            ],
        ];

        $sessionResponse = $this->actingAs($user)
            ->postJson('/api/session/first-step', $sessionPayload);

        $sessionResponse->assertOk();
        $sessionData = $sessionResponse->json('data');
        $this->assertNotEmpty($sessionData['packages']);

        $packages = $sessionData['packages'];

        echo "\n  Session packages: " . json_encode($packages);
        echo "\n  Total price: " . $sessionData['total_price'];

        // Step 2: Empty cart
        $emptyResponse = $this->actingAs($user)
            ->deleteJson('/api/empty-cart');

        $emptyResponse->assertOk();
        echo "\n  Empty cart: " . $emptyResponse->getStatusCode() . " - " . $emptyResponse->getContent();

        // Step 3: Store to cart (exact same payload structure as frontend)
        $cartPayload = [
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
                'date' => '14/02/2026',
                'time' => '',
            ],
            'packages' => $packages,
        ];

        echo "\n  Cart payload packages: " . json_encode($cartPayload['packages']);

        $storeResponse = $this->actingAs($user)
            ->postJson('/api/cart', $cartPayload);

        echo "\n  Store cart status: " . $storeResponse->getStatusCode();
        echo "\n  Store cart body: " . substr($storeResponse->getContent(), 0, 500);

        $storeResponse->assertOk();

        // Step 4: Read cart
        $cartResponse = $this->actingAs($user)
            ->getJson('/api/cart');

        $cartResponse->assertOk();
        echo "\n  Cart content: " . substr($cartResponse->getContent(), 0, 300);

        $cartData = $cartResponse->json();
        $this->assertNotEmpty($cartData['data'], 'Cart should not be empty');
    }

    public function test_full_cart_flow_guest(): void
    {
        // Step 1: Empty guest cart
        $emptyResponse = $this->withSession([])
            ->deleteJson('/api/empty-guest-cart');

        $emptyResponse->assertOk();
        echo "\n  Empty guest cart: " . $emptyResponse->getStatusCode();

        // Step 2: Store to guest cart
        $cartPayload = [
            'origin_address' => [
                'type' => 'Partenza',
                'name' => 'Guest User',
                'additional_information' => '',
                'address' => 'Via Test',
                'number_type' => 'Numero Civico',
                'address_number' => '1',
                'intercom_code' => '',
                'country' => 'Italia',
                'city' => 'Firenze',
                'postal_code' => '50100',
                'province' => 'FI',
                'telephone_number' => '3339999999',
                'email' => '',
            ],
            'destination_address' => [
                'type' => 'Destinazione',
                'name' => 'Dest Guest',
                'additional_information' => '',
                'address' => 'Via Prova',
                'number_type' => 'Numero Civico',
                'address_number' => '2',
                'intercom_code' => '',
                'country' => 'Italia',
                'city' => 'Napoli',
                'postal_code' => '80100',
                'province' => 'NA',
                'telephone_number' => '3338888888',
                'email' => '',
            ],
            'services' => [
                'service_type' => '',
                'date' => '',
                'time' => '',
            ],
            'packages' => [
                [
                    'package_type' => 'Pacco',
                    'quantity' => 1,
                    'weight' => 3,
                    'first_size' => 20,
                    'second_size' => 15,
                    'third_size' => 10,
                    'single_price' => 12,
                    'weight_price' => 12,
                    'volume_price' => 9,
                ],
            ],
        ];

        $storeResponse = $this->withSession([])
            ->postJson('/api/guest-cart', $cartPayload);

        echo "\n  Store guest cart status: " . $storeResponse->getStatusCode();
        echo "\n  Store guest cart body: " . substr($storeResponse->getContent(), 0, 300);

        $storeResponse->assertOk();

        // Step 3: Read guest cart
        $cartResponse = $this->getJson('/api/guest-cart');

        echo "\n  Guest cart status: " . $cartResponse->getStatusCode();
        echo "\n  Guest cart body: " . substr($cartResponse->getContent(), 0, 300);
    }
}
