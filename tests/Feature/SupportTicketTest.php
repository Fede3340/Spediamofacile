<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_support_ticket(): void
    {
        $user = User::factory()->create([
            'name' => 'Federico',
            'surname' => 'Mascia',
            'email' => 'federico@example.com',
            'telephone_number' => '3401234567',
        ]);

        $response = $this->actingAs($user)->postJson('/api/support-tickets', [
            'subject' => 'Problema checkout',
            'message' => 'Non riesco a completare il pagamento.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'federico@example.com')
            ->assertJsonPath('data.subject', 'Problema checkout');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'federico@example.com',
            'subject' => 'Problema checkout',
            'message' => 'Non riesco a completare il pagamento.',
        ]);
    }

    public function test_support_tickets_require_authentication(): void
    {
        $response = $this->postJson('/api/support-tickets', [
            'subject' => 'Problema checkout',
            'message' => 'Non riesco a completare il pagamento.',
        ]);

        $response->assertUnauthorized();
    }
}
