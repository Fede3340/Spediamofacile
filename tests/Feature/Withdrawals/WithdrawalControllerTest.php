<?php

namespace Tests\Feature\Withdrawals;

use App\Models\ReferralUsage;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WithdrawalControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createProUserWithCommission(float $commissionAmount = 12.50): User
    {
        $user = User::factory()->partnerPro()->create([
            'email_verified_at' => now(),
        ]);

        ReferralUsage::create([
            'buyer_id' => User::factory()->create()->id,
            'pro_user_id' => $user->id,
            'referral_code' => $user->referral_code,
            'order_id' => null,
            'order_amount' => $commissionAmount * 20,
            'discount_amount' => $commissionAmount,
            'commission_amount' => $commissionAmount,
            'status' => 'confirmed',
        ]);

        return $user;
    }

    public function test_withdrawal_store_creates_pending_and_reserves_balance(): void
    {
        $user = $this->createProUserWithCommission(12.50);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/withdrawals');

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('withdrawal_requests', [
            'user_id' => $user->id,
            'amount' => '12.50',
            'currency' => 'EUR',
            'status' => 'pending',
        ]);

        $this->assertSame(0.0, $user->fresh()->commissionBalance());
    }

    public function test_withdrawal_store_rejects_second_pending_request(): void
    {
        $user = $this->createProUserWithCommission(18.00);
        Sanctum::actingAs($user);

        $this->postJson('/api/withdrawals')->assertStatus(201);

        $second = $this->postJson('/api/withdrawals');
        $second->assertStatus(422);
        $second->assertJsonFragment(['message' => 'Hai gia una richiesta di prelievo in attesa di approvazione.']);

        $this->assertSame(1, WithdrawalRequest::query()->where('user_id', $user->id)->where('status', 'pending')->count());
    }
}
