<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailVerificationAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_email_verification_before_workspace_tools_are_available(): void
    {
        Notification::fake();
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New Owner',
            'email' => 'owner@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()
            ->assertJsonPath('user.email_verified', false);

        $user = User::where('email', 'owner@example.test')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->postJson('/api/campaigns', ['name' => 'Blocked'])->assertForbidden();
        $this->getJson('/api/billing/configuration')->assertForbidden();

        $verificationUrl = null;
        Notification::assertSentTo($user, VerifyEmailNotification::class, function (VerifyEmailNotification $notification) use ($user, &$verificationUrl) {
            $verificationUrl = $notification->toMail($user)->actionUrl;

            return $notification->locale === 'pt_BR';
        });

        $this->get($verificationUrl)
            ->assertRedirect(config('app.frontend_url').'/app/dashboard?verified=1');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->actingAs($user->fresh())->postJson('/api/campaigns', ['name' => 'Enabled'])->assertCreated();
    }

    public function test_owner_invites_a_member_with_selected_permissions(): void
    {
        Notification::fake();
        [$owner, $workspace] = $this->owner();
        $acceptUrl = null;

        $this->actingAs($owner)->postJson('/api/workspace/invitations', [
            'name' => 'Invited Member',
            'email' => 'member@example.test',
            'can_create_campaigns' => false,
            'can_view_metrics' => true,
            'locale' => 'en',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Invited Member')
            ->assertJsonPath('data.can_create_campaigns', false)
            ->assertJsonPath('data.can_view_metrics', true);

        $this->assertDatabaseHas('workspace_invitations', [
            'workspace_id' => $workspace->id,
            'name' => 'Invited Member',
            'email' => 'member@example.test',
            'can_create_campaigns' => false,
            'can_view_metrics' => true,
        ]);
        Notification::assertSentOnDemand(WorkspaceInvitationNotification::class, function (WorkspaceInvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable) use (&$acceptUrl) {
            $acceptUrl = $notification->toMail($notifiable)->actionUrl;

            return $notification->locale === 'en'
                && $notifiable->routeNotificationFor('mail') === 'member@example.test';
        });
        $this->assertStringStartsWith(config('app.frontend_url').'/invite/', $acceptUrl);

        $this->actingAs($owner)->getJson('/api/workspace')
            ->assertOk()
            ->assertJsonCount(1, 'data.pending_invitations')
            ->assertJsonPath('data.pending_invitations.0.name', 'Invited Member')
            ->assertJsonPath('data.pending_invitations.0.email', 'member@example.test')
            ->assertJsonPath('data.pending_invitations.0.can_create_campaigns', false)
            ->assertJsonPath('data.pending_invitations.0.can_view_metrics', true);
    }

    public function test_invited_registration_joins_the_workspace_and_verifies_the_email(): void
    {
        [$owner, $workspace] = $this->owner();
        $token = Str::random(64);
        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'invited_by' => $owner->id,
            'email' => 'invited@example.test',
            'role' => 'member',
            'can_create_campaigns' => true,
            'can_view_metrics' => false,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        $this->postJson('/api/auth/register', [
            'name' => 'Invited User',
            'email' => 'invited@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'invitation_token' => $token,
        ])->assertCreated()
            ->assertJsonPath('user.email_verified', true)
            ->assertJsonPath('workspace.id', $workspace->id)
            ->assertJsonPath('workspace.permissions.can_create_campaigns', true)
            ->assertJsonPath('workspace.permissions.can_view_metrics', false);

        $user = User::where('email', 'invited@example.test')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame($workspace->id, $user->current_workspace_id);
        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'can_create_campaigns' => true,
            'can_view_metrics' => false,
        ]);
    }

    public function test_member_permissions_are_enforced_and_metrics_are_reduced_to_a_summary(): void
    {
        [$owner, $workspace] = $this->owner();
        $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $workspace->members()->attach($member->id, [
            'role' => 'member',
            'can_create_campaigns' => false,
            'can_view_metrics' => false,
            'joined_at' => now(),
        ]);
        $campaign = Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'status' => 'active',
        ]);
        Ad::create([
            'campaign_id' => $campaign->id,
            'name' => 'Ad',
            'status' => 'active',
            'tracking_key' => Str::uuid(),
        ]);

        $this->actingAs($member)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('workspace.permissions.owner', false)
            ->assertJsonPath('workspace.permissions.can_create_campaigns', false)
            ->assertJsonPath('workspace.permissions.can_view_metrics', false);
        $this->actingAs($member)->getJson('/api/campaigns')->assertForbidden();
        $this->actingAs($member)->getJson('/api/workspace')->assertForbidden();
        $this->actingAs($member)->getJson('/api/billing/configuration')->assertForbidden();
        $this->actingAs($member)->getJson('/api/analytics')
            ->assertOk()
            ->assertJsonPath('data.metrics_allowed', false)
            ->assertJsonPath('data.summary.active_campaigns', 1)
            ->assertJsonPath('data.summary.ads', 1)
            ->assertJsonMissingPath('data.total');
        $this->actingAs($member)->getJson('/api/profile')
            ->assertOk()
            ->assertJsonMissingPath('data.workspace.plan')
            ->assertJsonPath('data.subscription', null);
    }

    public function test_owner_can_change_member_permissions(): void
    {
        [$owner, $workspace] = $this->owner();
        $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $workspace->members()->attach($member->id, [
            'role' => 'member',
            'can_create_campaigns' => false,
            'can_view_metrics' => false,
            'joined_at' => now(),
        ]);

        $this->actingAs($owner)->patchJson('/api/workspace/members/'.$member->id.'/permissions', [
            'can_create_campaigns' => true,
            'can_view_metrics' => false,
        ])->assertOk();

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'can_create_campaigns' => true,
            'can_view_metrics' => false,
        ]);
        $this->actingAs($member)->getJson('/api/campaigns')->assertOk();
        $this->actingAs($member)->getJson('/api/analytics')->assertJsonPath('data.metrics_allowed', false);
    }

    public function test_owner_can_remove_members_and_cancel_pending_invitations_but_cannot_remove_the_owner(): void
    {
        [$owner, $workspace] = $this->owner();
        $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $workspace->members()->attach($member->id, [
            'role' => 'member',
            'can_create_campaigns' => true,
            'can_view_metrics' => true,
            'joined_at' => now(),
        ]);
        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'invited_by' => $owner->id,
            'name' => 'Pending Person',
            'email' => 'pending@example.test',
            'role' => 'member',
            'can_create_campaigns' => true,
            'can_view_metrics' => false,
            'token' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)->deleteJson('/api/workspace/members/'.$owner->id)->assertUnprocessable();
        $this->actingAs($owner)->deleteJson('/api/workspace/members/'.$member->id)->assertOk();
        $this->assertDatabaseMissing('workspace_members', ['workspace_id' => $workspace->id, 'user_id' => $member->id]);
        $this->assertNull($member->fresh()->current_workspace_id);

        $this->actingAs($owner)->deleteJson('/api/workspace/invitations/'.$invitation->id)->assertOk();
        $this->assertDatabaseMissing('workspace_invitations', ['id' => $invitation->id]);
    }

    public function test_portuguese_invitation_renders_the_branded_layout_without_english_fallback_text(): void
    {
        [, $workspace] = $this->owner();
        $notification = new WorkspaceInvitationNotification(
            $workspace,
            'http://127.0.0.1:3000/invite/example',
            'pt-BR',
            'Maria',
        );

        $previousLocale = app()->getLocale();
        app()->setLocale('pt_BR');
        try {
            $html = $notification->toMail(new AnonymousNotifiable)->render();
        } finally {
            app()->setLocale($previousLocale);
        }

        $this->assertStringContainsString('Olá, Maria!', $html);
        $this->assertStringContainsString('Atenciosamente,', $html);
        $this->assertStringContainsString('Se você estiver com dificuldade', $html);
        $this->assertStringContainsString('/brand/tabor-ads-logo.svg', $html);
        $this->assertStringNotContainsString('Regards,', $html);
        $this->assertStringNotContainsString("If you're having trouble", $html);
    }

    private function owner(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Premium Team', 'slug' => Str::uuid(), 'plan_override' => 'premium']);
        $workspace->members()->attach($owner->id, [
            'role' => 'owner',
            'can_create_campaigns' => true,
            'can_view_metrics' => true,
            'joined_at' => now(),
        ]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        return [$owner, $workspace];
    }
}
