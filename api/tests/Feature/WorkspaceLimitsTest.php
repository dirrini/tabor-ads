<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkspaceLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_free_workspace(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Diego', 'email' => 'diego@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'workspace_name' => 'Acme Ads',
        ]);

        $response->assertCreated()->assertJsonPath('workspace.plan', 'free')->assertJsonPath('workspace.role', 'owner');
        $this->assertDatabaseHas('workspace_members', ['role' => 'owner']);
    }

    public function test_locale_is_saved_on_registration_and_can_be_changed(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'English User', 'email' => 'english@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'locale' => 'en',
        ]);

        $response->assertCreated()->assertJsonPath('user.locale', 'en');

        $user = User::where('email', 'english@example.com')->firstOrFail();
        $this->actingAs($user)->patchJson('/api/auth/preferences', ['locale' => 'pt-BR'])
            ->assertOk()->assertJsonPath('user.locale', 'pt-BR');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'locale' => 'pt-BR']);

        $this->actingAs($user)->patchJson('/api/auth/preferences', ['locale' => 'es'])
            ->assertUnprocessable()->assertJsonValidationErrors('locale');
    }

    public function test_authentication_error_uses_the_requested_language(): void
    {
        $this->withHeader('Accept-Language', 'en')
            ->postJson('/api/auth/login', ['email' => 'missing@example.com', 'password' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Invalid credentials.');

        $this->withHeader('Accept-Language', 'pt-BR')
            ->postJson('/api/auth/login', ['email' => 'missing@example.com', 'password' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Credenciais inválidas.');
    }

    public function test_free_workspace_is_limited_to_three_standard_campaigns(): void
    {
        [$user, $workspace] = $this->member('free');
        Campaign::factory()->count(3)->create(['workspace_id' => $workspace->id, 'created_by' => $user->id]);

        $this->actingAs($user)->postJson('/api/campaigns', ['name' => 'Quarta'])
            ->assertUnprocessable()->assertJsonValidationErrors('plan');
    }

    public function test_plan_limit_messages_follow_the_request_language(): void
    {
        [$user] = $this->member('free');

        $this->actingAs($user)->withHeader('Accept-Language', 'en')
            ->postJson('/api/workspace/invitations', ['email' => 'member@example.com'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.plan.0', 'Your plan member limit has been reached.');

        $this->actingAs($user)->withHeader('Accept-Language', 'pt-BR')
            ->postJson('/api/workspace/invitations', ['email' => 'member@example.com'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.plan.0', 'Limite de membros do plano atingido.');
    }

    public function test_campaigns_are_isolated_between_workspaces(): void
    {
        [$user] = $this->member('free');
        [$other, $otherWorkspace] = $this->member('free');
        $campaign = Campaign::factory()->create(['workspace_id' => $otherWorkspace->id, 'created_by' => $other->id]);

        $this->actingAs($user)->patchJson('/api/campaigns/'.$campaign->id, ['name' => 'Invadida'])->assertNotFound();
    }

    private function member(string $plan): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => Str::random(), 'slug' => Str::uuid(), 'plan_override' => $plan]);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);

        return [$user, $workspace];
    }
}
