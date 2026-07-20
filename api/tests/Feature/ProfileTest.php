<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_returns_workspace_plan_and_premium_expiration(): void
    {
        [$user, $workspace] = $this->member();
        $expiresAt = now()->addMonth()->startOfSecond();
        Subscription::create([
            'workspace_id' => $workspace->id,
            'provider' => 'mercadopago',
            'provider_subscription_id' => 'payment-profile-1',
            'provider_plan_id' => 'monthly',
            'plan_code' => 'premium',
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => $expiresAt,
        ]);

        $this->actingAs($user)->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.workspace.plan', 'premium')
            ->assertJsonPath('data.workspace.role', 'owner')
            ->assertJsonPath('data.user.has_password', true)
            ->assertJsonPath('data.subscription.provider_plan_id', 'monthly')
            ->assertJsonPath('data.subscription.current_period_end', $expiresAt->toIso8601String());
    }

    public function test_user_with_password_must_confirm_current_password(): void
    {
        [$user] = $this->member();

        $this->actingAs($user)->withHeader('Accept-Language', 'pt-BR')->putJson('/api/profile/password', [
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->actingAs($user)->withHeader('Accept-Language', 'pt-BR')->putJson('/api/profile/password', [
            'current_password' => 'password',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertOk()->assertJsonPath('message', 'Senha alterada com sucesso.');

        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_google_only_user_can_create_a_password(): void
    {
        [$user] = $this->member(['password' => null]);

        $this->actingAs($user)->putJson('/api/profile/password', [
            'password' => 'FirstPassword123',
            'password_confirmation' => 'FirstPassword123',
        ])->assertOk();

        $this->assertTrue(Hash::check('FirstPassword123', $user->fresh()->password));
    }

    public function test_user_can_update_their_name(): void
    {
        [$user] = $this->member(['name' => 'Previous Name']);

        $this->actingAs($user)->patchJson('/api/profile', ['name' => 'Updated Name'])
            ->assertOk()
            ->assertJsonPath('user.name', 'Updated Name');

        $this->assertSame('Updated Name', $user->fresh()->name);

        $this->actingAs($user)->patchJson('/api/profile', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    private function member(array $attributes = []): array
    {
        $user = User::factory()->create($attributes);
        $workspace = Workspace::create(['name' => 'Profile Workspace', 'slug' => Str::uuid()]);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $user->update(['current_workspace_id' => $workspace->id]);

        return [$user, $workspace];
    }
}
