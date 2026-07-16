<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_login_links_an_existing_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'linked@example.com',
            'email_verified_at' => null,
            'locale' => 'pt-BR',
        ]);
        $password = $user->password;
        $workspace = $this->workspaceFor($user);
        $this->mockGoogleUser('google-existing-123', 'LINKED@example.com', 'Google Name');

        $response = $this->withSession(['oauth_locale' => 'en'])
            ->get('/api/auth/google/callback');

        $response->assertRedirect(config('app.frontend_url').'/app/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('workspaces', 1);
        $this->assertDatabaseHas('oauth_identities', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-existing-123',
        ]);

        $linkedUser = $user->fresh();
        $this->assertNotNull($linkedUser->email_verified_at);
        $this->assertSame($password, $linkedUser->password);
        $this->assertSame('pt-BR', $linkedUser->locale);
        $this->assertSame($workspace->id, $linkedUser->current_workspace_id);
    }

    public function test_google_login_creates_a_user_when_email_does_not_exist(): void
    {
        $this->mockGoogleUser('google-new-456', 'new-google@example.com', 'New Google User');

        $response = $this->withSession(['oauth_locale' => 'en'])
            ->get('/api/auth/google/callback');

        $response->assertRedirect(config('app.frontend_url').'/app/dashboard');
        $user = User::where('email', 'new-google@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('en', $user->locale);
        $this->assertNotNull($user->current_workspace_id);
        $this->assertDatabaseHas('workspace_members', ['user_id' => $user->id, 'role' => 'owner']);
        $this->assertDatabaseHas('oauth_identities', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-new-456',
        ]);
    }

    private function workspaceFor(User $user): Workspace
    {
        $workspace = Workspace::create([
            'name' => 'Existing Workspace',
            'slug' => 'existing-'.Str::lower(Str::random(8)),
        ]);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $user->update(['current_workspace_id' => $workspace->id]);

        return $workspace;
    }

    private function mockGoogleUser(string $id, string $email, string $name): void
    {
        $google = (new GoogleUser)->map([
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'avatar' => 'https://example.com/avatar.png',
        ]);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($google);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }
}
