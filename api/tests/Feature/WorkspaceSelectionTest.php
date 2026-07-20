<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkspaceSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_create_an_independent_free_workspace(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $invitingWorkspace = Workspace::create(['name' => 'Inviting Team', 'slug' => Str::uuid()]);
        $invitingWorkspace->members()->attach($user->id, [
            'role' => 'member',
            'can_create_campaigns' => false,
            'can_view_metrics' => true,
            'joined_at' => now(),
        ]);
        Subscription::create([
            'workspace_id' => $invitingWorkspace->id,
            'provider' => 'mercadopago',
            'provider_subscription_id' => 'existing-premium-payment',
            'provider_plan_id' => 'monthly',
            'plan_code' => 'premium',
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
        $user->update(['current_workspace_id' => $invitingWorkspace->id]);

        $response = $this->actingAs($user)->postJson('/api/workspaces', ['name' => 'My Own Workspace'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'My Own Workspace')
            ->assertJsonPath('data.plan', 'free');

        $createdId = $response->json('data.id');
        $this->assertSame($createdId, $user->fresh()->current_workspace_id);
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $createdId,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
        $this->assertDatabaseMissing('subscriptions', ['workspace_id' => $createdId]);
        $this->assertSame('premium', $invitingWorkspace->fresh()->planCode());

        $this->actingAs($user)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('workspace.id', $createdId)
            ->assertJsonPath('workspace.plan', 'free')
            ->assertJsonPath('workspace.permissions.owner', true)
            ->assertJsonCount(2, 'workspaces');
    }

    public function test_user_can_only_switch_to_a_workspace_they_belong_to(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $owned = Workspace::create(['name' => 'Owned', 'slug' => Str::uuid()]);
        $shared = Workspace::create(['name' => 'Shared', 'slug' => Str::uuid()]);
        $unrelated = Workspace::create(['name' => 'Unrelated', 'slug' => Str::uuid()]);
        $owned->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $shared->members()->attach($user->id, [
            'role' => 'member',
            'can_create_campaigns' => false,
            'can_view_metrics' => false,
            'joined_at' => now(),
        ]);
        $user->update(['current_workspace_id' => $owned->id]);

        $this->actingAs($user)->putJson('/api/workspaces/current', ['workspace_id' => $shared->id])
            ->assertOk()
            ->assertJsonPath('data.id', $shared->id);

        $this->actingAs($user)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('workspace.id', $shared->id)
            ->assertJsonPath('workspace.permissions.owner', false)
            ->assertJsonPath('workspace.permissions.can_create_campaigns', false)
            ->assertJsonPath('workspace.permissions.can_view_metrics', false);

        $this->actingAs($user)->putJson('/api/workspaces/current', ['workspace_id' => $unrelated->id])
            ->assertForbidden();

        $this->assertSame($shared->id, $user->fresh()->current_workspace_id);
    }
}
