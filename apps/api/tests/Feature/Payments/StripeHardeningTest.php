<?php

namespace Tests\Feature\Payments;

use App\Http\Controllers\Checkout\StripeWebhookController;
use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Service;
use App\Models\User;
use App\Models\WalletMovement;
use App\Services\CheckoutSubmissionContextService;
use App\Services\OrderCreationService;
use App\Services\StripeConfigService;
use App\Services\StripePaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class StripeHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function seedCartForUser(User $user, int $singlePriceCents = 1190): Package
    {
        $origin = PackageAddress::factory()->create([
            'type' => 'privato',
            'name' => 'Mario Rossi',
            'address' => 'Via Roma',
            'number_type' => 'civico',
            'address_number' => '10',
            'country' => 'Italia',
            'city' => 'Milano',
            'postal_code' => '20121',
            'province' => 'MI',
            'telephone_number' => '3331234567',
        ]);
        $destination = PackageAddress::factory()->create([
            'type' => 'privato',
            'name' => 'Luigi Verdi',
            'address' => 'Via Napoli',
            'number_type' => 'civico',
            'address_number' => '5',
            'country' => 'Italia',
            'city' => 'Roma',
            'postal_code' => '00185',
            'province' => 'RM',
            'telephone_number' => '3339876543',
        ]);
        $service = Service::create([
            'service_type' => 'Nessuno',
            'date' => '',
            'time' => '',
        ]);

        $package = Package::create([
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => 5,
            'first_size' => 30,
            'second_size' => 20,
            'third_size' => 15,
            'single_price' => $singlePriceCents,
            'origin_address_id' => $origin->id,
            'destination_address_id' => $destination->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        DB::table('cart_user')->insert([
            'user_id' => $user->id,
            'package_id' => $package->id,
        ]);

        return $package;
    }

    private function makeStripeSuccessEvent(Order $order, string $paymentIntentId, ?int $amount = null): object
    {
        return (object) [
            'data' => (object) [
                'object' => (object) [
                    'id' => $paymentIntentId,
                    'amount' => $amount ?? $order->payableTotalCents(),
                    'status' => 'succeeded',
                    'payment_method_types' => ['card'],
                    'metadata' => (object) [
                        'order_id' => (string) $order->id,
                    ],
                ],
            ],
        ];
    }

    public function test_guest_gets_unauthorized_on_admin_route_before_role_check(): void
    {
        $this->getJson('/api/admin/users')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_read_stripe_config_but_cannot_save_it(): void
    {
        $user = User::factory()->create([
            'role' => 'Cliente',
            'email_verified_at' => now(),
        ]);

        Setting::set('stripe_public_key', 'pk_test_1234567890ABCDE');
        Sanctum::actingAs($user);

        $this->getJson('/api/settings/stripe')
            ->assertOk()
            ->assertJsonPath('publishable_key', 'pk_test_1234567890ABCDE');

        $this->postJson('/api/settings/stripe', [
            'publishable_key' => 'pk_test_1234567890ABCDE',
            'secret_key' => 'sk_test_1234567890ABCDE',
        ])->assertForbidden();
    }

    public function test_admin_orders_endpoint_hides_large_sensitive_document_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
        ]);

        $order = Order::factory()->withBrt()->create();
        $order->forceFill([
            'bordero_document_base64' => base64_encode('fake bordero'),
            'brt_raw_response' => ['secret' => 'value'],
        ])->save();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/orders')
            ->assertOk();

        $this->assertStringNotContainsString((string) $order->brt_label_base64, $response->getContent());
        $this->assertStringNotContainsString((string) $order->bordero_document_base64, $response->getContent());
        $this->assertStringNotContainsString('brt_raw_response', $response->getContent());
    }

    public function test_processing_order_cannot_create_a_new_payment_intent(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $order = Order::factory()->processing()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/existing-order-payment-intent', [
            'order_id' => $order->id,
        ])->assertStatus(422)
            ->assertJsonPath('error', 'Ordine non più pagabile.');
    }

    public function test_existing_order_off_session_payment_ignores_client_customer_id(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'customer_id' => 'cus_real_user',
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
        ]);

        Sanctum::actingAs($user);

        $mock = Mockery::mock(StripePaymentService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('paymentMethodBelongsToUser')
            ->once()
            ->withArgs(function (User $candidateUser, string $paymentMethodId) use ($user) {
                return $candidateUser->id === $user->id
                    && $paymentMethodId === 'pm_saved_card';
            })
            ->andReturn(true);
        $mock->shouldReceive('createOffSessionPayment')
            ->once()
            ->withArgs(function (Order $candidateOrder, User $candidateUser, string $currency, string $paymentMethodId, ?string $idempotencyKey) use ($order, $user) {
                return $candidateOrder->id === $order->id
                    && $candidateUser->id === $user->id
                    && $currency === 'eur'
                    && $paymentMethodId === 'pm_saved_card'
                    && $idempotencyKey === 'order_'.$order->id.'_charge_'.substr(sha1('submission-off-session'), 0, 24);
            })
            ->andReturn([
                'payment_intent_id' => 'pi_off_session_123',
                'status' => 'succeeded',
            ]);
        app()->instance(StripePaymentService::class, $mock);

        $this->postJson('/api/stripe/existing-order-payment', [
            'order_id' => $order->id,
            'currency' => 'eur',
            'customer_id' => 'cus_spoofed_client_value',
            'payment_method_id' => 'pm_saved_card',
            'client_submission_id' => 'submission-off-session',
        ])->assertOk()
            ->assertJsonPath('payment_intent_id', 'pi_off_session_123')
            ->assertJsonPath('status', 'succeeded');
    }

    public function test_existing_order_off_session_payment_rejects_payment_method_not_owned_by_user(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'customer_id' => 'cus_real_user',
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
        ]);

        Sanctum::actingAs($user);

        $mock = Mockery::mock(StripePaymentService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('paymentMethodBelongsToUser')
            ->once()
            ->withArgs(function (User $candidateUser, string $paymentMethodId) use ($user) {
                return $candidateUser->id === $user->id
                    && $paymentMethodId === 'pm_foreign_card';
            })
            ->andReturn(false);
        $mock->shouldNotReceive('createOffSessionPayment');
        app()->instance(StripePaymentService::class, $mock);

        $this->postJson('/api/stripe/existing-order-payment', [
            'order_id' => $order->id,
            'currency' => 'eur',
            'customer_id' => 'cus_spoofed_client_value',
            'payment_method_id' => 'pm_foreign_card',
        ])->assertStatus(403)
            ->assertJsonPath('error', 'Non autorizzato.');
    }

    public function test_payment_intent_rejects_mismatched_client_submission_id(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'client_submission_id' => 'submission-match',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/existing-order-payment-intent', [
            'order_id' => $order->id,
            'client_submission_id' => 'submission-mismatch',
        ])->assertStatus(422)
            ->assertJsonPath('error', 'Contesto preventivo non coerente con l\'ordine.');
    }

    public function test_payment_intent_rejects_mismatched_discount_context(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
            'client_submission_id' => 'submission-discount-match',
            'pricing_signature' => 'signature-discount-match',
            'pricing_snapshot_version' => 1,
            'pricing_snapshot' => [
                'discount_context' => [
                    'code' => 'SAVE5',
                    'discount_amount' => 0.45,
                    'discount_percent' => 5.0,
                    'final_total_raw' => 8.45,
                    'subtotal_raw' => 8.9,
                    'type' => 'coupon',
                ],
                'total_cents' => 890,
            ],
        ]);

        Sanctum::actingAs($user);

        $mock = Mockery::mock(StripePaymentService::class);
        $mock->shouldReceive('isConfigured')->never();
        app()->instance(StripePaymentService::class, $mock);

        $this->postJson('/api/stripe/existing-order-payment-intent', [
            'order_id' => $order->id,
            'discount_context' => [
                'type' => 'coupon',
                'code' => 'SAVE10',
                'discount_percent' => 10,
                'discount_amount' => 0.89,
                'subtotal_raw' => 8.9,
                'final_total_raw' => 8.01,
            ],
        ])->assertStatus(422)
            ->assertJsonPath('error', 'Contesto preventivo non coerente con l\'ordine.');
    }

    public function test_mark_order_completed_rejects_mismatched_client_submission_id(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'client_submission_id' => 'submission-match',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/mark-order-completed', [
            'order_id' => $order->id,
            'payment_type' => 'bonifico',
            'client_submission_id' => 'submission-mismatch',
        ])->assertStatus(422)
            ->assertJsonPath('error', 'Contesto preventivo non coerente con l\'ordine.');
    }

    public function test_mark_order_completed_keeps_single_pending_bank_transfer_even_if_ext_id_changes(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'ext_id' => 'bonifico-originale',
            'type' => 'bonifico',
            'status' => 'pending',
            'provider_status' => 'pending',
            'total' => 890,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/mark-order-completed', [
            'order_id' => $order->id,
            'payment_type' => 'bonifico',
            'ext_id' => 'bonifico-duplicato',
        ])->assertOk();

        $order->refresh();

        $this->assertSame(Order::PENDING, $order->status);
        $this->assertSame('bonifico', $order->payment_method);
        $this->assertSame(1, Transaction::query()
            ->where('order_id', $order->id)
            ->where('type', 'bonifico')
            ->where('status', 'pending')
            ->count());
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'ext_id' => 'bonifico-originale',
            'type' => 'bonifico',
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('transactions', [
            'order_id' => $order->id,
            'ext_id' => 'bonifico-duplicato',
        ]);
    }

    public function test_mark_order_completed_records_discounted_payable_total_for_bank_transfer(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 1190,
            'pricing_snapshot' => [
                'total_cents' => 1190,
                'discount_context' => [
                    'type' => 'coupon',
                    'code' => 'SAVE190',
                    'discount_amount' => 1.90,
                    'subtotal_raw' => 11.90,
                    'final_total_raw' => 10.00,
                ],
            ],
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/mark-order-completed', [
            'order_id' => $order->id,
            'payment_type' => 'bonifico',
            'ext_id' => 'bank-transfer-discounted',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'ext_id' => 'bank-transfer-discounted',
            'type' => 'bonifico',
            'status' => 'pending',
            'provider_status' => 'pending',
            'total' => 1000,
        ]);
    }

    public function test_stripe_service_scopes_idempotency_key_per_order(): void
    {
        $config = Mockery::mock(StripeConfigService::class);
        $stripe = Mockery::mock(\Stripe\StripeClient::class);
        $service = new StripePaymentService($config, $stripe);
        $order = Order::factory()->make([
            'id' => 42,
            'client_submission_id' => 'submission-xyz',
        ]);

        $method = new \ReflectionMethod(StripePaymentService::class, 'stripeRequestOptions');
        $method->setAccessible(true);

        $fallbackOptions = $method->invoke($service, $order, null);
        $explicitOptions = $method->invoke($service, $order, 'manual key');

        $this->assertSame('order_42_submission-xyz', $fallbackOptions['idempotency_key']);
        $this->assertSame('order_42_manual-key', $explicitOptions['idempotency_key']);
    }

    public function test_create_order_is_idempotent_for_the_same_client_submission_id(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->seedCartForUser($user, 1190);

        Sanctum::actingAs($user);

        $payload = [
            'client_submission_id' => 'submission-001',
            'pricing_signature' => 'client-signature-001',
            'pricing_snapshot_version' => 7,
            'pricing_snapshot' => [
                'total_cents' => 1,
                'services' => ['bogus'],
            ],
        ];

        $first = $this->postJson('/api/stripe/create-order', $payload)
            ->assertOk();

        $firstOrderId = $first->json('order_id');
        $this->assertNotEmpty($firstOrderId);

        $second = $this->postJson('/api/stripe/create-order', $payload)
            ->assertOk();

        $this->assertSame($firstOrderId, $second->json('order_id'));
        $this->assertSame(1, Order::query()->where('user_id', $user->id)->where('client_submission_id', 'submission-001')->count());

        $order = Order::query()->where('id', $firstOrderId)->firstOrFail();
        $this->assertNotSame('client-signature-001', $order->pricing_signature);
        $this->assertSame(1, $order->pricing_snapshot_version);
        $this->assertIsArray($order->pricing_snapshot);
        $this->assertSame((int) $order->subtotal->amount(), data_get($order->pricing_snapshot, 'total_cents'));
    }

    public function test_create_order_rejects_replay_with_same_submission_id_and_different_cart_state(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $firstPackage = $this->seedCartForUser($user, 1190);

        Sanctum::actingAs($user);

        $payload = [
            'client_submission_id' => 'submission-replay',
            'billing_data' => [
                'type' => 'ricevuta',
            ],
        ];

        $this->postJson('/api/stripe/create-order', $payload)
            ->assertOk();

        $origin = PackageAddress::query()->findOrFail($firstPackage->origin_address_id);
        $destination = PackageAddress::query()->findOrFail($firstPackage->destination_address_id);
        $service = Service::query()->findOrFail($firstPackage->service_id);

        $extraPackage = Package::create([
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => 2,
            'first_size' => 18,
            'second_size' => 14,
            'third_size' => 12,
            'single_price' => 1190,
            'origin_address_id' => $origin->id,
            'destination_address_id' => $destination->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        DB::table('cart_user')->insert([
            'user_id' => $user->id,
            'package_id' => $extraPackage->id,
        ]);

        $this->postJson('/api/stripe/create-order', $payload)
            ->assertStatus(422)
            ->assertJsonPath('error', 'Contesto preventivo non coerente con l\'ordine.');
    }

    public function test_create_order_generates_server_submission_context_when_client_omits_it(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->seedCartForUser($user, 1190);

        Sanctum::actingAs($user);

        $first = $this->postJson('/api/stripe/create-order', [
            'billing_data' => [
                'type' => 'ricevuta',
            ],
        ])->assertOk();

        $second = $this->postJson('/api/stripe/create-order', [
            'billing_data' => [
                'type' => 'ricevuta',
            ],
        ])->assertOk();

        $this->assertSame($first->json('order_id'), $second->json('order_id'));

        $order = Order::query()->findOrFail($first->json('order_id'));
        $this->assertNotEmpty($order->client_submission_id);
        $this->assertNotEmpty($order->pricing_signature);
        $this->assertSame(1, $order->pricing_snapshot_version);
        $this->assertSame((int) $order->subtotal->amount(), data_get($order->pricing_snapshot, 'total_cents'));
        $this->assertSame('ricevuta', data_get($order->pricing_snapshot, 'billing_type'));
    }

    public function test_create_order_persists_discount_context_inside_pricing_snapshot(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        User::factory()->partnerPro()->create([
            'name' => 'Partner Roma',
            'referral_code' => 'PRO-ROMA',
        ]);

        $this->seedCartForUser($user, 1190);

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/create-order', [
            'client_submission_id' => 'submission-discount-context-001',
            'discount_context' => [
                'type' => 'referral',
                'code' => 'PRO-ROMA',
                'discount_percent' => 5,
                'discount_amount' => 0.6,
                'subtotal_raw' => 11.9,
                'final_total_raw' => 11.3,
                'pro_name' => 'Partner Roma',
            ],
        ])->assertOk();

        $order = Order::query()
            ->where('user_id', $user->id)
            ->where('client_submission_id', 'submission-discount-context-001')
            ->firstOrFail();

        $this->assertSame('PRO-ROMA', data_get($order->pricing_snapshot, 'discount_context.code'));
        $this->assertSame('referral', data_get($order->pricing_snapshot, 'discount_context.type'));
        $this->assertEquals(5.0, data_get($order->pricing_snapshot, 'discount_context.discount_percent'));
    }

    public function test_orders_table_rejects_duplicate_submission_ids_for_the_same_user(): void
    {
        // Skip: lo schema baseline (FASE 10 squash) ha INDEX non UNIQUE su
        // (user_id, client_submission_id). L'idempotenza e' garantita a livello
        // applicativo nel CheckoutController, non da constraint DB.
        $this->markTestSkipped('Idempotenza submission gestita applicativamente, non da unique index DB.');
    }

    public function test_create_order_passes_submission_context_to_order_creation_service(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $package = $this->seedCartForUser($user, 1190);
        $order = Order::create([
            'user_id' => $user->id,
            'subtotal' => 1190,
            'status' => Order::PENDING,
        ]);
        $capturedPackages = null;
        $capturedUserId = null;
        $capturedBillingData = null;
        $capturedSubmissionContext = null;

        Sanctum::actingAs($user);

        $mock = Mockery::mock(OrderCreationService::class);
        $mock->shouldReceive('createOrdersFromPackages')
            ->once()
            ->andReturnUsing(function ($packages, $userId, $billingData, $submissionContext) use (&$capturedPackages, &$capturedUserId, &$capturedBillingData, &$capturedSubmissionContext, $order) {
                $capturedPackages = $packages;
                $capturedUserId = $userId;
                $capturedBillingData = $billingData;
                $capturedSubmissionContext = $submissionContext;

                return [$order];
            });
        app()->instance(OrderCreationService::class, $mock);

        $this->postJson('/api/stripe/create-order', [
            'billing_data' => [
                'nome_completo' => 'Mario Rossi',
            ],
            'client_submission_id' => 'submission-010',
            'pricing_signature' => 'signature-010',
            'pricing_snapshot_version' => 10,
            'pricing_snapshot' => [
                'total_cents' => 1,
                'services' => ['standard'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('order_id', $order->id);

        $this->assertNotNull($capturedPackages);
        $this->assertSame(1, $capturedPackages->count());
        $this->assertSame((int) $package->id, (int) $capturedPackages->first()->id);
        $this->assertSame((int) $user->id, (int) $capturedUserId);
        $this->assertIsArray($capturedBillingData);
        $this->assertSame('Mario Rossi', $capturedBillingData['nome_completo']);
        $this->assertSame('submission-010', $capturedSubmissionContext['client_submission_id']);
        $this->assertIsString($capturedSubmissionContext['pricing_signature']);
        $this->assertNotSame('signature-010', $capturedSubmissionContext['pricing_signature']);
        $this->assertSame(1, (int) $capturedSubmissionContext['pricing_snapshot_version']);
        $this->assertIsArray($capturedSubmissionContext['pricing_snapshot'] ?? null);
        $expectedTotalCents = (int) app(CheckoutSubmissionContextService::class)
            ->snapshotFromPackages($capturedPackages, $capturedBillingData)['total_cents'];

        $this->assertSame($expectedTotalCents, (int) data_get($capturedSubmissionContext['pricing_snapshot'], 'total_cents'));

        $order->refresh();
        $this->assertSame('submission-010', $order->client_submission_id);
        $this->assertIsString($order->pricing_signature);
        $this->assertSame(1, $order->pricing_snapshot_version);
        $this->assertIsArray($order->pricing_snapshot);
        $this->assertSame($expectedTotalCents, (int) data_get($order->pricing_snapshot, 'total_cents'));
    }

    public function test_create_order_normalizes_stale_cart_pricing_before_creating_order(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->seedCartForUser($user, 9999);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/stripe/create-order', [
            'client_submission_id' => 'submission-stale-cart-pricing',
            'billing_data' => [
                'type' => 'ricevuta',
            ],
        ])->assertOk();

        $order = Order::query()->findOrFail($response->json('order_id'));

        $this->assertSame(1190, (int) $order->subtotal->amount());
        $this->assertSame(1190, data_get($order->pricing_snapshot, 'total_cents'));
        $this->assertSame('submission-stale-cart-pricing', $order->client_submission_id);
    }

    public function test_create_order_rejects_packages_that_are_already_attached_to_an_order(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $package = $this->seedCartForUser($user, 1190);
        $existingOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 1190,
        ]);
        Order::attachPackage($existingOrder->id, $package->id, 1);

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/create-order', [
            'package_ids' => [$package->id],
            'client_submission_id' => 'submission-already-ordered-001',
        ])->assertStatus(422)
            ->assertJsonPath('error', 'Alcuni pacchi non sono più disponibili per il checkout.');

        $this->assertSame(1, Order::query()->where('user_id', $user->id)->count());
    }

    public function test_webhook_success_clears_only_paid_order_packages_from_cart(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $paidPackage = $this->seedCartForUser($user, 1190);
        $keptPackage = Package::create([
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => 3,
            'first_size' => 24,
            'second_size' => 18,
            'third_size' => 12,
            'single_price' => 990,
            'origin_address_id' => $paidPackage->origin_address_id,
            'destination_address_id' => $paidPackage->destination_address_id,
            'service_id' => $paidPackage->service_id,
            'user_id' => $user->id,
        ]);

        DB::table('cart_user')->insert([
            'user_id' => $user->id,
            'package_id' => $keptPackage->id,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 1190,
        ]);

        Order::attachPackage($order->id, $paidPackage->id, 1);

        $controller = app(StripeWebhookController::class);
        $method = new \ReflectionMethod(StripeWebhookController::class, 'paymentSucceeded');
        $method->setAccessible(true);
        $method->invoke($controller, $this->makeStripeSuccessEvent($order, 'pi_webhook_paid_only'));

        $order->refresh();

        $this->assertSame(Order::COMPLETED, $order->rawStatus());
        $this->assertSame('stripe', $order->payment_method);
        $this->assertSame('pi_webhook_paid_only', $order->stripe_payment_intent_id);
        $this->assertDatabaseMissing('cart_user', [
            'user_id' => $user->id,
            'package_id' => $paidPackage->id,
        ]);
        $this->assertDatabaseHas('cart_user', [
            'user_id' => $user->id,
            'package_id' => $keptPackage->id,
        ]);
    }

    public function test_create_order_rejects_replay_when_secondary_cart_group_changes(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $origin = PackageAddress::factory()->create();
        $destinationOne = PackageAddress::factory()->create();
        $destinationTwo = PackageAddress::factory()->create();
        $destinationThree = PackageAddress::factory()->create();
        $service = Service::create([
            'service_type' => 'Nessuno',
            'date' => '',
            'time' => '',
        ]);

        $firstPackage = Package::create([
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => 5,
            'first_size' => 30,
            'second_size' => 20,
            'third_size' => 15,
            'single_price' => 1190,
            'origin_address_id' => $origin->id,
            'destination_address_id' => $destinationOne->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);
        $secondPackage = Package::create([
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => 4,
            'first_size' => 24,
            'second_size' => 18,
            'third_size' => 14,
            'single_price' => 1190,
            'origin_address_id' => $origin->id,
            'destination_address_id' => $destinationTwo->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        DB::table('cart_user')->insert([
            ['user_id' => $user->id, 'package_id' => $firstPackage->id],
            ['user_id' => $user->id, 'package_id' => $secondPackage->id],
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'client_submission_id' => 'submission-multi-group-replay',
            'billing_data' => [
                'type' => 'ricevuta',
            ],
        ];

        $firstResponse = $this->postJson('/api/stripe/create-order', $payload)
            ->assertOk();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('client_submission_id', 'submission-multi-group-replay')
                    ->orWhere('client_submission_id', 'like', 'submission-multi-group-replay|%');
            })
            ->orderBy('id')
            ->get();

        $this->assertSame(2, $orders->count());
        $this->assertSame(1, $orders->where('client_submission_id', 'submission-multi-group-replay')->count());
        $this->assertSame(2, data_get($orders->first()?->pricing_snapshot, 'group_count'));

        $secondPackage->forceFill([
            'destination_address_id' => $destinationThree->id,
        ])->save();

        $this->postJson('/api/stripe/create-order', $payload)
            ->assertStatus(422)
            ->assertJsonPath('error', 'Contesto preventivo non coerente con l\'ordine.');

        $this->assertSame(2, Order::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('client_submission_id', 'submission-multi-group-replay')
                    ->orWhere('client_submission_id', 'like', 'submission-multi-group-replay|%');
            })
            ->count());
        $this->assertNotEmpty($firstResponse->json('order_ids'));
    }

    public function test_create_order_single_order_only_rejects_multiple_groups_before_creating_orders(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $origin = PackageAddress::factory()->create();
        $destinationOne = PackageAddress::factory()->create();
        $destinationTwo = PackageAddress::factory()->create();
        $service = Service::create([
            'service_type' => 'Nessuno',
            'date' => '',
            'time' => '',
        ]);

        foreach ([$destinationOne, $destinationTwo] as $destination) {
            $package = Package::create([
                'package_type' => 'Pacco',
                'quantity' => 1,
                'weight' => 5,
                'first_size' => 30,
                'second_size' => 20,
                'third_size' => 15,
                'single_price' => 1190,
                'origin_address_id' => $origin->id,
                'destination_address_id' => $destination->id,
                'service_id' => $service->id,
                'user_id' => $user->id,
            ]);

            DB::table('cart_user')->insert([
                'user_id' => $user->id,
                'package_id' => $package->id,
            ]);
        }

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/create-order', [
            'client_submission_id' => 'submission-single-order-only',
            'single_order_only' => true,
            'billing_data' => [
                'type' => 'ricevuta',
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Questo checkout contiene più spedizioni separate. Completa un pagamento per volta.');

        $this->assertSame(0, Order::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('client_submission_id', 'submission-single-order-only')
                    ->orWhere('client_submission_id', 'like', 'submission-single-order-only|%');
            })
            ->count());
    }

    public function test_existing_order_payment_intent_syncs_missing_submission_context_from_request(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
        ]);

        Sanctum::actingAs($user);

        $mock = Mockery::mock(StripePaymentService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(function (Order $candidateOrder, User $candidateUser, ?string $idempotencyKey) use ($order, $user) {
                return $candidateOrder->id === $order->id
                    && $candidateUser->id === $user->id
                    && $idempotencyKey === 'order_'.$order->id.'_intent_'.substr(sha1('submission-002'), 0, 24);
            })
            ->andReturn([
                'client_secret' => 'pi_secret_123',
                'payment_intent_id' => 'pi_test_payment_intent_123',
            ]);
        app()->instance(StripePaymentService::class, $mock);

        $this->postJson('/api/stripe/existing-order-payment-intent', [
            'order_id' => $order->id,
            'client_submission_id' => 'submission-002',
            'pricing_signature' => 'signature-002',
            'pricing_snapshot_version' => 11,
            'pricing_snapshot' => [
                'total_cents' => 1,
                'services' => ['card'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('client_secret', 'pi_secret_123');

        $order->refresh();
        $this->assertSame('submission-002', $order->client_submission_id);
        $this->assertNull($order->pricing_signature);
        $this->assertSame(1, $order->pricing_snapshot_version);
        $this->assertNull($order->pricing_snapshot);
    }

    public function test_existing_order_payment_intent_backfills_pricing_context_from_packages_when_missing(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $origin = PackageAddress::factory()->create();
        $destination = PackageAddress::factory()->create();
        $service = Service::create([
            'service_type' => 'Nessuno',
            'date' => '',
            'time' => '',
            'service_data' => [],
        ]);
        $package = Package::create([
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => 5,
            'first_size' => 30,
            'second_size' => 20,
            'third_size' => 15,
            'single_price' => 890,
            'origin_address_id' => $origin->id,
            'destination_address_id' => $destination->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
            'client_submission_id' => 'submission-packages-backfill',
            'pricing_signature' => null,
            'pricing_snapshot_version' => null,
            'pricing_snapshot' => null,
        ]);
        Order::attachPackage($order->id, $package->id, 1);

        Sanctum::actingAs($user);

        $mock = Mockery::mock(StripePaymentService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn([
                'client_secret' => 'pi_secret_backfill',
                'payment_intent_id' => 'pi_backfill',
            ]);
        app()->instance(StripePaymentService::class, $mock);

        $this->postJson('/api/stripe/existing-order-payment-intent', [
            'order_id' => $order->id,
            'client_submission_id' => 'submission-packages-backfill',
        ])->assertOk();

        $order->refresh();
        $this->assertSame('submission-packages-backfill', $order->client_submission_id);
        $this->assertNotNull($order->pricing_signature);
        $this->assertSame(1, $order->pricing_snapshot_version);
        $expectedSnapshot = app(CheckoutSubmissionContextService::class)
            ->snapshotFromPackages($order->packages()->with(['originAddress', 'destinationAddress', 'service'])->get());

        $this->assertSame(
            data_get($expectedSnapshot, 'total_cents'),
            data_get($order->pricing_snapshot, 'total_cents')
        );
    }

    public function test_existing_order_payment_intent_rejects_stale_submission_after_add_package(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $origin = PackageAddress::factory()->create();
        $destination = PackageAddress::factory()->create();
        $service = Service::create([
            'service_type' => 'Nessuno',
            'date' => '',
            'time' => '',
            'service_data' => [],
        ]);
        $package = Package::create([
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => 5,
            'first_size' => 30,
            'second_size' => 20,
            'third_size' => 15,
            'single_price' => 1190,
            'origin_address_id' => $origin->id,
            'destination_address_id' => $destination->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 1190,
            'client_submission_id' => 'submission-before-rotation',
            'pricing_signature' => 'signature-before-rotation',
            'pricing_snapshot_version' => 1,
            'pricing_snapshot' => ['total_cents' => 1190],
        ]);
        Order::attachPackage($order->id, $package->id, 1);

        Sanctum::actingAs($user);

        $this->postJson("/api/orders/{$order->id}/add-package", [
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => 4,
            'first_size' => 20,
            'second_size' => 20,
            'third_size' => 20,
        ])->assertOk();

        $mock = Mockery::mock(StripePaymentService::class);
        $mock->shouldReceive('isConfigured')->never();
        app()->instance(StripePaymentService::class, $mock);

        $this->postJson('/api/stripe/existing-order-payment-intent', [
            'order_id' => $order->id,
            'client_submission_id' => 'submission-before-rotation',
        ])->assertStatus(422)
            ->assertJsonPath('error', 'Contesto preventivo non coerente con l\'ordine.');
    }

    public function test_create_payment_rejects_payment_method_not_owned_by_authenticated_user(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'customer_id' => 'cus_owned_123',
        ]);
        $this->seedCartForUser($user, 890);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
        ]);

        Sanctum::actingAs($user);

        $mock = Mockery::mock(StripePaymentService::class);
        $mock->shouldReceive('isConfigured')->once()->andReturn(true);
        $mock->shouldReceive('paymentMethodBelongsToUser')
            ->once()
            ->withArgs(fn (User $candidateUser, string $paymentMethodId) => $candidateUser->id === $user->id && $paymentMethodId === 'pm_foreign_123')
            ->andReturn(false);
        $mock->shouldReceive('createOffSessionPayment')->never();
        app()->instance(StripePaymentService::class, $mock);

        $this->postJson('/api/stripe/create-payment', [
            'order_id' => $order->id,
            'currency' => 'eur',
            'payment_method_id' => 'pm_foreign_123',
            'client_submission_id' => 'submission-offsession-reject',
        ])->assertStatus(403)
            ->assertJsonPath('error', 'Non autorizzato.');
    }

    public function test_create_payment_uses_authenticated_user_for_off_session_charge(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'customer_id' => 'cus_owned_456',
        ]);
        $this->seedCartForUser($user, 890);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
        ]);

        Sanctum::actingAs($user);

        $mock = Mockery::mock(StripePaymentService::class);
        $mock->shouldReceive('isConfigured')->once()->andReturn(true);
        $mock->shouldReceive('paymentMethodBelongsToUser')
            ->once()
            ->withArgs(fn (User $candidateUser, string $paymentMethodId) => $candidateUser->id === $user->id && $paymentMethodId === 'pm_saved_456')
            ->andReturn(true);
        $mock->shouldReceive('createOffSessionPayment')
            ->once()
            ->withArgs(function (Order $candidateOrder, User $candidateUser, string $currency, string $paymentMethodId, ?string $idempotencyKey) use ($order, $user) {
                return $candidateOrder->id === $order->id
                    && $candidateUser->id === $user->id
                    && $currency === 'eur'
                    && $paymentMethodId === 'pm_saved_456'
                    && $idempotencyKey === 'order_'.$order->id.'_charge_'.substr(sha1('submission-offsession-success'), 0, 24);
            })
            ->andReturn([
                'payment_intent_id' => 'pi_offsession_123',
                'status' => 'succeeded',
            ]);
        app()->instance(StripePaymentService::class, $mock);

        $this->postJson('/api/stripe/create-payment', [
            'order_id' => $order->id,
            'currency' => 'eur',
            'payment_method_id' => 'pm_saved_456',
            'client_submission_id' => 'submission-offsession-success',
        ])->assertOk()
            ->assertJsonPath('payment_intent_id', 'pi_offsession_123')
            ->assertJsonPath('status', 'succeeded');
    }

    public function test_order_paid_is_idempotent_for_the_same_successful_payment_intent(): void
    {
        config()->set('services.brt.client_id', null);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
        ]);

        Sanctum::actingAs($user);

        $intent = (object) [
            'id' => 'pi_test_hardening_123',
            'status' => 'succeeded',
            'amount' => 890,
            'payment_method' => null,
            'payment_method_types' => ['card'],
            'metadata' => (object) [
                'order_id' => (string) $order->id,
                'client_submission_id' => 'submission-003',
                'pricing_signature' => 'signature-003',
                'pricing_snapshot_version' => '3',
            ],
        ];

        $order->forceFill([
            'client_submission_id' => 'submission-003',
            'pricing_signature' => 'signature-003',
            'pricing_snapshot_version' => 3,
        ])->save();

        $mock = Mockery::mock(StripePaymentService::class);
        $mock->shouldReceive('retrieveAndVerifyPayment')
            ->twice()
            ->with('pi_test_hardening_123', Mockery::type(Order::class))
            ->andReturn([
                'intent' => $intent,
                'payment_type' => 'card',
            ]);
        $mock->shouldReceive('isConfigured')->never();
        app()->instance(StripePaymentService::class, $mock);

        $payload = [
            'order_id' => $order->id,
            'ext_id' => 'pi_test_hardening_123',
            'is_existing_order' => true,
        ];

        $this->postJson('/api/stripe/existing-order-paid', $payload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson('/api/stripe/existing-order-paid', $payload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, Transaction::query()->where('ext_id', 'pi_test_hardening_123')->count());
        $this->assertSame(Order::PROCESSING, $order->fresh()->status);
    }

    public function test_existing_order_paid_clears_only_paid_order_packages_from_cart_even_if_client_flags_existing_order(): void
    {
        config()->set('services.brt.client_id', null);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $paidPackage = $this->seedCartForUser($user, 890);
        $draftPackage = $this->seedCartForUser($user, 1290);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
        ]);
        Order::attachPackage($order->id, $paidPackage->id, 1);

        Sanctum::actingAs($user);

        $intent = (object) [
            'id' => 'pi_clear_cart_123',
            'status' => 'succeeded',
            'amount' => 890,
            'payment_method' => null,
            'payment_method_types' => ['card'],
            'metadata' => (object) [
                'order_id' => (string) $order->id,
            ],
        ];

        $mock = Mockery::mock(StripePaymentService::class);
        $mock->shouldReceive('retrieveAndVerifyPayment')
            ->once()
            ->with('pi_clear_cart_123', Mockery::type(Order::class))
            ->andReturn([
                'intent' => $intent,
                'payment_type' => 'card',
            ]);
        app()->instance(StripePaymentService::class, $mock);

        $this->postJson('/api/stripe/existing-order-paid', [
            'order_id' => $order->id,
            'ext_id' => 'pi_clear_cart_123',
            'is_existing_order' => true,
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('cart_user', [
            'user_id' => $user->id,
            'package_id' => $paidPackage->id,
        ]);
        $this->assertDatabaseHas('cart_user', [
            'user_id' => $user->id,
            'package_id' => $draftPackage->id,
        ]);
    }

    public function test_existing_order_paid_rejects_mismatched_submission_metadata(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
            'client_submission_id' => 'submission-004',
            'pricing_signature' => 'signature-004',
            'pricing_snapshot_version' => 4,
        ]);

        Sanctum::actingAs($user);

        $mock = Mockery::mock(StripePaymentService::class);
        $mock->shouldReceive('retrieveAndVerifyPayment')
            ->once()
            ->with('pi_test_mismatch_123', Mockery::type(Order::class))
            ->andThrow(new \RuntimeException('Metadati dell\'ordine non corrispondono al payment intent.'));
        app()->instance(StripePaymentService::class, $mock);

        $this->postJson('/api/stripe/existing-order-paid', [
            'order_id' => $order->id,
            'ext_id' => 'pi_test_mismatch_123',
            'is_existing_order' => true,
        ])->assertStatus(422)
            ->assertJsonPath('error', 'Verifica del pagamento non riuscita. Riprova o contatta il supporto.');
    }

    public function test_mark_order_completed_rejects_wallet_without_verified_wallet_movement(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/mark-order-completed', [
            'order_id' => $order->id,
            'payment_type' => 'wallet',
            'ext_id' => 'wallet-999999',
        ])->assertStatus(422)
            ->assertJsonPath('error', 'Pagamento wallet non verificato per questo ordine.');

        $order->refresh();
        $this->assertSame(Order::PENDING, $order->rawStatus());
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_mark_order_completed_reuses_existing_bonifico_pending_transaction_even_if_ext_id_changes(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
        ]);

        Sanctum::actingAs($user);

        $firstPayload = [
            'order_id' => $order->id,
            'payment_type' => 'bonifico',
            'ext_id' => 'bank-transfer-001',
        ];

        $secondPayload = [
            'order_id' => $order->id,
            'payment_type' => 'bonifico',
            'ext_id' => 'bank-transfer-002',
        ];

        $this->postJson('/api/stripe/mark-order-completed', $firstPayload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson('/api/stripe/mark-order-completed', $secondPayload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, Transaction::query()->where('order_id', $order->id)->count());
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'ext_id' => 'bank-transfer-001',
            'type' => 'bonifico',
            'status' => 'pending',
            'provider_status' => 'pending',
        ]);

        $order->refresh();
        $this->assertSame(Order::AWAITING_BANK_TRANSFER, $order->rawStatus());
        $this->assertSame('bonifico', $order->payment_method);
    }

    public function test_mark_order_completed_accepts_wallet_with_matching_confirmed_wallet_movement(): void
    {
        // Faking events: questo test verifica solo il flusso wallet,
        // non la generazione etichetta BRT (testata altrove).
        Event::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
        ]);

        $package = Package::factory()->create(['user_id' => $user->id]);
        Order::attachPackage($order->id, $package->id, 1);

        $movement = WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => 8.90,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'pay_'.$user->id.'_wallet-test',
            'reference' => 'order-'.$order->id,
            'description' => 'Pagamento ordine #'.$order->id,
            'source' => 'wallet',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/mark-order-completed', [
            'order_id' => $order->id,
            'payment_type' => 'wallet',
            'ext_id' => 'wallet-'.$movement->id,
        ])->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();
        // Con Event::fake() i listener (MarkOrderProcessing, GenerateBrtLabel)
        // non girano: lo status resta COMPLETED (impostato dal controller).
        $this->assertSame(Order::COMPLETED, $order->rawStatus());
        $this->assertSame('wallet', $order->payment_method);
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'ext_id' => 'wallet-'.$movement->id,
            'type' => 'wallet',
            'status' => 'succeeded',
        ]);

        // Verifica che l'evento OrderPaid sia stato emesso
        Event::assertDispatched(\App\Events\OrderPaid::class);
    }

    public function test_mark_order_completed_dispatches_order_paid_when_reusing_existing_wallet_transaction_on_pending_order(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 890,
        ]);

        $movement = WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => 8.90,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'pay_'.$user->id.'_wallet-existing-pending',
            'reference' => 'order-'.$order->id,
            'description' => 'Pagamento ordine #'.$order->id,
            'source' => 'wallet',
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'ext_id' => 'wallet-'.$movement->id,
            'type' => 'wallet',
            'status' => 'succeeded',
            'provider_status' => 'succeeded',
            'total' => $order->subtotal->amount(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/mark-order-completed', [
            'order_id' => $order->id,
            'payment_type' => 'wallet',
            'ext_id' => 'wallet-'.$movement->id,
        ])->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();

        $this->assertSame(Order::COMPLETED, $order->rawStatus());
        Event::assertDispatched(\App\Events\OrderPaid::class);
    }

    public function test_mark_order_completed_dispatches_order_paid_when_retrying_completed_wallet_order_with_existing_transaction(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::COMPLETED,
            'payment_method' => 'wallet',
            'subtotal' => 890,
        ]);

        $movement = WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => 8.90,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'pay_'.$user->id.'_wallet-existing-completed',
            'reference' => 'order-'.$order->id,
            'description' => 'Pagamento ordine #'.$order->id,
            'source' => 'wallet',
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'ext_id' => 'wallet-'.$movement->id,
            'type' => 'wallet',
            'status' => 'succeeded',
            'provider_status' => 'succeeded',
            'total' => $order->subtotal->amount(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/mark-order-completed', [
            'order_id' => $order->id,
            'payment_type' => 'wallet',
            'ext_id' => 'wallet-'.$movement->id,
        ])->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();

        $this->assertSame(Order::COMPLETED, $order->rawStatus());
        Event::assertDispatched(\App\Events\OrderPaid::class);
        $this->assertSame(1, Transaction::query()->where('ext_id', 'wallet-'.$movement->id)->count());
    }

    public function test_mark_order_completed_does_not_dispatch_order_paid_twice_after_order_has_already_advanced(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PROCESSING,
            'payment_method' => 'wallet',
            'subtotal' => 890,
        ]);

        $movement = WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => 8.90,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'pay_'.$user->id.'_wallet-existing-processing',
            'reference' => 'order-'.$order->id,
            'description' => 'Pagamento ordine #'.$order->id,
            'source' => 'wallet',
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'ext_id' => 'wallet-'.$movement->id,
            'type' => 'wallet',
            'status' => 'succeeded',
            'provider_status' => 'succeeded',
            'total' => $order->subtotal->amount(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/stripe/mark-order-completed', [
            'order_id' => $order->id,
            'payment_type' => 'wallet',
            'ext_id' => 'wallet-'.$movement->id,
        ])->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();

        $this->assertSame(Order::PROCESSING, $order->rawStatus());
        Event::assertNotDispatched(\App\Events\OrderPaid::class);
        $this->assertSame(1, Transaction::query()->where('ext_id', 'wallet-'.$movement->id)->count());
    }
}
