<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use App\Models\WalletMovement;
use App\Services\WalletOrderPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletOrderPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function fundWallet(User $user, float $amountEur): void
    {
        WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => $amountEur,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'seed_'.$user->id.'_'.uniqid(),
            'description' => 'Seed balance for tests',
            'source' => 'admin',
        ]);
    }

    private function createPendingOrder(User $user, int $subtotalCents = 1190): Order
    {
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'subtotal' => $subtotalCents,
        ]);

        $package = Package::factory()->create([
            'user_id' => $user->id,
            'single_price' => $subtotalCents,
        ]);

        Order::attachPackage($order->id, $package->id, 1);

        return $order;
    }

    public function test_it_creates_a_wallet_debit_for_a_pending_order(): void
    {
        $user = User::factory()->create();
        $this->fundWallet($user, 50.00);
        $order = $this->createPendingOrder($user, 1190);

        /** @var WalletOrderPaymentService $service */
        $service = app(WalletOrderPaymentService::class);

        $result = $service->createOrReuseOrderDebit($user, $order, 11.90, 'Pagamento spedizione');

        $this->assertFalse(isset($result['error']));
        $this->assertTrue($result['created']);
        $this->assertSame('wallet', $result['movement']->source);
        $this->assertSame('debit', $result['movement']->type);
        $this->assertSame('order-'.$order->id, $result['movement']->reference);
        $this->assertEquals(38.10, $result['new_balance']);
    }

    public function test_it_reuses_the_existing_wallet_debit_for_the_same_order(): void
    {
        $user = User::factory()->create();
        $this->fundWallet($user, 100.00);
        $order = $this->createPendingOrder($user, 2990);

        /** @var WalletOrderPaymentService $service */
        $service = app(WalletOrderPaymentService::class);

        $first = $service->createOrReuseOrderDebit($user, $order, 29.90, 'Retry wallet payment');
        $second = $service->createOrReuseOrderDebit($user, $order, 29.90, 'Retry wallet payment');

        $this->assertTrue($first['created']);
        $this->assertFalse($second['created']);
        $this->assertSame($first['movement']->id, $second['movement']->id);
        $this->assertSame(1, WalletMovement::query()
            ->where('user_id', $user->id)
            ->where('source', 'wallet')
            ->where('type', 'debit')
            ->where('reference', 'order-'.$order->id)
            ->count());
        $this->assertEquals(70.10, $second['new_balance']);
    }

    public function test_it_returns_an_error_when_wallet_balance_is_insufficient(): void
    {
        $user = User::factory()->create();
        $this->fundWallet($user, 10.00);
        $order = $this->createPendingOrder($user, 5000);

        /** @var WalletOrderPaymentService $service */
        $service = app(WalletOrderPaymentService::class);

        $result = $service->createOrReuseOrderDebit($user, $order, 50.00, 'Insufficient balance');

        $this->assertSame('Saldo insufficiente. Disponibile: 10.00 EUR', $result['error']);
        $this->assertDatabaseMissing('wallet_movements', [
            'user_id' => $user->id,
            'type' => 'debit',
            'reference' => 'order-'.$order->id,
        ]);
    }
}
