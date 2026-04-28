<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserNotificationPreference;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_index_returns_only_current_users_notifications_in_descending_order(): void
    {
        $user = $this->actingAsUser();
        $other = User::factory()->create();

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'referral',
            'title' => 'Vecchia notifica',
            'body' => 'Messaggio meno recente',
            'payload' => ['source' => 'old'],
            'created_at' => CarbonImmutable::parse('2026-04-01 10:00:00'),
            'updated_at' => CarbonImmutable::parse('2026-04-01 10:00:00'),
        ]);

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'referral',
            'title' => 'Notifica recente',
            'body' => 'Messaggio piu recente',
            'payload' => ['source' => 'new'],
            'created_at' => CarbonImmutable::parse('2026-04-02 10:00:00'),
            'updated_at' => CarbonImmutable::parse('2026-04-02 10:00:00'),
        ]);

        UserNotification::create([
            'user_id' => $other->id,
            'type' => 'system',
            'title' => 'Altra notifica',
            'body' => 'Non deve comparire',
            'payload' => ['source' => 'other'],
            'created_at' => CarbonImmutable::parse('2026-04-03 10:00:00'),
            'updated_at' => CarbonImmutable::parse('2026-04-03 10:00:00'),
        ]);

        $response = $this->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Notifica recente', $titles);
        $this->assertContains('Vecchia notifica', $titles);
        $this->assertNotContains('Altra notifica', $titles);
    }

    public function test_unread_count_returns_only_unread_notifications(): void
    {
        $user = $this->actingAsUser();

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'referral',
            'title' => 'Letta',
            'body' => 'Gia letta',
            'payload' => null,
            'read_at' => CarbonImmutable::parse('2026-04-02 08:00:00'),
        ]);

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'referral',
            'title' => 'Non letta 1',
            'body' => 'Da leggere',
            'payload' => null,
        ]);

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'referral',
            'title' => 'Non letta 2',
            'body' => 'Da leggere anche questa',
            'payload' => null,
        ]);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 2);
    }

    public function test_mark_read_blocks_notifications_owned_by_other_users(): void
    {
        $user = $this->actingAsUser();
        $other = User::factory()->create();

        $notification = UserNotification::create([
            'user_id' => $other->id,
            'type' => 'referral',
            'title' => 'Notifica altrui',
            'body' => 'Non accessibile',
            'payload' => null,
        ]);

        $this->patchJson("/api/notifications/{$notification->id}/read")
            ->assertStatus(403)
            ->assertJsonPath('message', 'Non autorizzato.');

        $this->assertNull(UserNotification::find($notification->id)->read_at);
    }

    public function test_mark_all_read_updates_only_current_users_unread_notifications(): void
    {
        $user = $this->actingAsUser();
        $other = User::factory()->create();

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'referral',
            'title' => 'Da leggere 1',
            'body' => 'Prima notifica',
            'payload' => null,
        ]);

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'referral',
            'title' => 'Da leggere 2',
            'body' => 'Seconda notifica',
            'payload' => null,
        ]);

        UserNotification::create([
            'user_id' => $other->id,
            'type' => 'system',
            'title' => 'Altra notifica',
            'body' => 'Non deve cambiare',
            'payload' => null,
        ]);

        $this->patchJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('user_notifications', 3);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'title' => 'Da leggere 1',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'title' => 'Da leggere 2',
        ]);
        $this->assertSame(0, UserNotification::where('user_id', $user->id)->whereNull('read_at')->count());
        $this->assertSame(1, UserNotification::where('user_id', $other->id)->whereNull('read_at')->count());
    }

    public function test_preferences_endpoint_returns_default_preferences_and_creates_record(): void
    {
        $user = $this->actingAsUser();

        $response = $this->getJson('/api/notifications/preferences');

        $response->assertOk();
        $response->assertJsonPath('data.user_id', $user->id);
        $response->assertJsonPath('data.referral_site_enabled', true);
        $response->assertJsonPath('data.referral_email_enabled', false);
        $response->assertJsonPath('data.referral_sms_enabled', false);

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $user->id,
            'referral_site_enabled' => 1,
            'referral_email_enabled' => 0,
            'referral_sms_enabled' => 0,
        ]);
    }

    public function test_update_preferences_sets_opt_in_timestamps_for_email_and_sms(): void
    {
        $user = $this->actingAsUser();

        $response = $this->putJson('/api/notifications/preferences', [
            'referral_site_enabled' => false,
            'referral_email_enabled' => true,
            'referral_sms_enabled' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user_id', $user->id);
        $response->assertJsonPath('data.referral_site_enabled', false);
        $response->assertJsonPath('data.referral_email_enabled', true);
        $response->assertJsonPath('data.referral_sms_enabled', true);

        $this->assertNotNull(data_get($response->json(), 'data.email_opt_in_at'));
        $this->assertNotNull(data_get($response->json(), 'data.sms_opt_in_at'));

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $user->id,
            'referral_site_enabled' => 0,
            'referral_email_enabled' => 1,
            'referral_sms_enabled' => 1,
        ]);

        $this->assertNotNull(UserNotificationPreference::where('user_id', $user->id)->value('email_opt_in_at'));
        $this->assertNotNull(UserNotificationPreference::where('user_id', $user->id)->value('sms_opt_in_at'));
    }
}
