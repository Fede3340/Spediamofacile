<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\WalletMovement;
use App\Services\WalletOrderLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletOrderLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_and_parses_canonical_wallet_and_order_identifiers(): void
    {
        $service = app(WalletOrderLinkService::class);

        $this->assertSame('order-42', $service->orderReference(42));
        $this->assertSame('wallet-17', $service->walletExternalId(17));
        $this->assertSame(42, $service->extractOrderId('order-42'));
        $this->assertSame(42, $service->extractOrderId('42'));
        $this->assertSame('order-42', $service->normalizeOrderReference('42'));
        $this->assertSame(17, $service->extractWalletMovementId('wallet-17'));
        $this->assertNull($service->extractOrderId('order-foo'));
        $this->assertNull($service->extractWalletMovementId('wallet-foo'));
    }

    public function test_it_resolves_only_a_verified_wallet_movement_for_the_expected_order(): void
    {
        $service = app(WalletOrderLinkService::class);
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 1190,
        ]);

        $movement = WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => 11.90,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'wallet-order-link-test',
            'reference' => 'order-'.$order->id,
            'description' => 'Pagamento ordine',
            'source' => 'wallet',
        ]);

        $resolved = $service->resolveVerifiedWalletMovement($order, 'wallet-'.$movement->id);

        $this->assertNotNull($resolved);
        $this->assertSame($movement->id, $resolved?->id);
    }

    public function test_it_rejects_movements_that_do_not_match_the_expected_canonical_order_reference(): void
    {
        $service = app(WalletOrderLinkService::class);
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => 1190,
        ]);

        $movement = WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => 11.90,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'wallet-order-link-mismatch-test',
            'reference' => 'order-999',
            'description' => 'Pagamento ordine',
            'source' => 'wallet',
        ]);

        $resolved = $service->resolveVerifiedWalletMovement($order, 'wallet-'.$movement->id);

        $this->assertNull($resolved);
    }
}
