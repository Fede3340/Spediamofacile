<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\User;
use App\Services\StripeConfigService;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Service\CustomerService;
use Stripe\Service\PaymentIntentService;
use Stripe\Service\SetupIntentService;
use Stripe\StripeClient;
use Tests\TestCase;

class StripePaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_is_configured_ritorna_true_quando_secret_presente(): void
    {
        $configMock = Mockery::mock(StripeConfigService::class);
        $configMock->shouldReceive('getSecret')->andReturn('sk_test_abc123');
        $stripeClient = Mockery::mock(StripeClient::class);

        $service = new StripePaymentService($configMock, $stripeClient);

        $this->assertTrue($service->isConfigured());
    }

    public function test_is_configured_ritorna_false_quando_secret_vuoto(): void
    {
        $configMock = Mockery::mock(StripeConfigService::class);
        $configMock->shouldReceive('getSecret')->andReturn(null);
        $stripeClient = Mockery::mock(StripeClient::class);

        $service = new StripePaymentService($configMock, $stripeClient);

        $this->assertFalse($service->isConfigured());
    }

    public function test_create_or_get_customer_ritorna_customer_id_esistente_senza_chiamate(): void
    {
        $user = User::factory()->create(['customer_id' => 'cus_existing_123']);

        $configMock = Mockery::mock(StripeConfigService::class);
        $stripeClient = Mockery::mock(StripeClient::class);
        // Non deve chiamare customers->create: la proprieta' customers non verra' mai acceduta.

        $service = new StripePaymentService($configMock, $stripeClient);
        $customerId = $service->createOrGetCustomer($user);

        $this->assertSame('cus_existing_123', $customerId);
    }

    public function test_create_or_get_customer_crea_nuovo_customer_su_stripe_e_salva_su_utente(): void
    {
        $user = User::factory()->create(['customer_id' => null, 'name' => 'Mario', 'surname' => 'Rossi']);

        $stripeCustomer = (object) ['id' => 'cus_new_abc'];
        $customersService = Mockery::mock(CustomerService::class);
        $customersService->shouldReceive('create')
            ->once()
            ->withArgs(function (array $args) use ($user) {
                return $args['email'] === $user->email
                    && $args['name'] === 'Mario Rossi';
            })
            ->andReturn($stripeCustomer);

        $stripeClient = Mockery::mock(StripeClient::class);
        $stripeClient->customers = $customersService;

        $configMock = Mockery::mock(StripeConfigService::class);
        $service = new StripePaymentService($configMock, $stripeClient);

        $customerId = $service->createOrGetCustomer($user);

        $this->assertSame('cus_new_abc', $customerId);
        $this->assertSame('cus_new_abc', $user->fresh()->customer_id);
    }

    public function test_create_setup_intent_ritorna_client_secret_dal_mock_stripe(): void
    {
        $user = User::factory()->create(['customer_id' => 'cus_xyz']);

        $setupIntent = (object) ['client_secret' => 'seti_secret_123'];
        $setupIntents = Mockery::mock(SetupIntentService::class);
        $setupIntents->shouldReceive('create')
            ->once()
            ->withArgs(function (array $args) {
                return $args['customer'] === 'cus_xyz'
                    && $args['payment_method_types'] === ['card'];
            })
            ->andReturn($setupIntent);

        $stripeClient = Mockery::mock(StripeClient::class);
        $stripeClient->setupIntents = $setupIntents;

        $service = new StripePaymentService(Mockery::mock(StripeConfigService::class), $stripeClient);

        $result = $service->createSetupIntent($user);

        $this->assertSame('seti_secret_123', $result['client_secret']);
    }

    public function test_create_payment_intent_passa_amount_in_centesimi_e_idempotency_key(): void
    {
        $user = User::factory()->create(['customer_id' => 'cus_test']);
        $order = Order::factory()->create(['user_id' => $user->id, 'subtotal' => 1500]);

        $stripeIntent = (object) [
            'id' => 'pi_test_abc',
            'client_secret' => 'pi_test_abc_secret',
        ];

        $paymentIntents = Mockery::mock(PaymentIntentService::class);
        $paymentIntents->shouldReceive('create')
            ->once()
            ->withArgs(function (array $args, array $opts) {
                // Valida che l'amount sia in centesimi (1500) e che idempotency_key sia presente
                return $args['amount'] === 1500
                    && $args['currency'] === 'eur'
                    && isset($opts['idempotency_key'])
                    && $opts['idempotency_key'] !== '';
            })
            ->andReturn($stripeIntent);

        $stripeClient = Mockery::mock(StripeClient::class);
        $stripeClient->paymentIntents = $paymentIntents;

        $service = new StripePaymentService(Mockery::mock(StripeConfigService::class), $stripeClient);

        $result = $service->createPaymentIntent($order, $user, 'idempotency-abc');

        $this->assertSame('pi_test_abc', $result['payment_intent_id']);
        $this->assertSame('pi_test_abc_secret', $result['client_secret']);
    }

    public function test_create_payment_intent_usa_totale_pagabile_scontato(): void
    {
        $user = User::factory()->create(['customer_id' => 'cus_test_discount']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
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

        $stripeIntent = (object) [
            'id' => 'pi_discount_abc',
            'client_secret' => 'pi_discount_abc_secret',
        ];

        $paymentIntents = Mockery::mock(PaymentIntentService::class);
        $paymentIntents->shouldReceive('create')
            ->once()
            ->withArgs(function (array $args) {
                return $args['amount'] === 1000
                    && ($args['metadata']['gross_subtotal_cents'] ?? null) === '1190'
                    && ($args['metadata']['discount_amount_cents'] ?? null) === '190'
                    && ($args['metadata']['payable_total_cents'] ?? null) === '1000';
            })
            ->andReturn($stripeIntent);

        $stripeClient = Mockery::mock(StripeClient::class);
        $stripeClient->paymentIntents = $paymentIntents;

        $service = new StripePaymentService(Mockery::mock(StripeConfigService::class), $stripeClient);

        $result = $service->createPaymentIntent($order, $user, 'idempotency-discount');

        $this->assertSame('pi_discount_abc', $result['payment_intent_id']);
    }
}
