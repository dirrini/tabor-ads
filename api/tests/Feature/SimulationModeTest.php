<?php

namespace Tests\Feature;

use App\Events\ImpressionRecorded;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class SimulationModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulation_campaigns_do_not_consume_the_free_campaign_limit(): void
    {
        [$user, $workspace] = $this->member('free');
        Campaign::factory()->count(3)->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'kind' => 'standard',
        ]);

        foreach (range(1, 5) as $number) {
            $this->actingAs($user)->postJson('/api/campaigns', [
                'name' => 'Simulation '.$number,
                'status' => 'active',
                'simulation' => true,
            ])->assertCreated()->assertJsonPath('data.kind', 'simulation');
        }

        $this->assertDatabaseCount('campaigns', 8);
        $this->actingAs($user)->getJson('/api/campaigns')
            ->assertOk()
            ->assertJsonPath('standard_count', 3)
            ->assertJsonCount(8, 'data');

        $this->actingAs($user)->postJson('/api/campaigns', ['name' => 'Fourth standard'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('plan');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_each_tick_generates_between_two_and_nine_impressions_for_every_simulation_campaign(): void
    {
        Event::fake([ImpressionRecorded::class]);
        [$user, $workspace] = $this->member('free');
        $first = Campaign::create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'name' => 'Live demo A',
            'kind' => 'simulation',
            'status' => 'active',
            'public_id' => Str::uuid(),
        ]);
        $second = Campaign::create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'name' => 'Live demo B',
            'kind' => 'simulation',
            'status' => 'active',
            'public_id' => Str::uuid(),
        ]);

        $token = $this->actingAs($user)->postJson('/api/simulation/start')
            ->assertOk()
            ->assertJsonPath('max_seconds', 180)
            ->json('token');

        $response = $this->actingAs($user)->postJson('/api/simulation/tick', ['token' => $token])
            ->assertOk()
            ->assertJsonCount(2, 'campaigns');

        $results = collect($response->json('campaigns'))->keyBy('campaign_id');
        $this->assertBetween($results[$first->id]['generated'], 2, 9);
        $this->assertBetween($results[$second->id]['generated'], 2, 9);
        $this->assertNotSame($results[$first->id]['weight'], $results[$second->id]['weight']);
        $this->assertSame($results->sum('generated'), $response->json('generated'));

        $this->assertDatabaseCount('ads', 2);
        $this->assertDatabaseCount('ad_impressions', $response->json('generated'));
        Event::assertDispatched(ImpressionRecorded::class);

        $nextResults = collect($this->actingAs($user)
            ->postJson('/api/simulation/tick', ['token' => $token])
            ->assertOk()
            ->json('campaigns'))
            ->keyBy('campaign_id');
        $this->assertSame($results[$first->id]['weight'], $nextResults[$first->id]['weight']);
        $this->assertSame($results[$second->id]['weight'], $nextResults[$second->id]['weight']);
    }

    public function test_simulation_requires_a_flagged_campaign(): void
    {
        [$user, $workspace] = $this->member('free');
        Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'kind' => 'standard',
        ]);

        $this->actingAs($user)->postJson('/api/simulation/start')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('simulation');
    }

    public function test_simulation_session_expires_after_three_minutes(): void
    {
        Carbon::setTestNow('2026-07-15 12:00:00');
        [$user, $workspace] = $this->member('free');
        Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'kind' => 'simulation',
        ]);

        $token = $this->actingAs($user)->postJson('/api/simulation/start')->assertOk()->json('token');
        Carbon::setTestNow(now()->addSeconds(181));

        $this->actingAs($user)->postJson('/api/simulation/tick', ['token' => $token])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('simulation');
    }

    public function test_free_user_can_authorize_only_simulation_realtime_channels(): void
    {
        [$user, $workspace] = $this->member('free');
        $simulation = Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'kind' => 'simulation',
        ]);
        $standard = Campaign::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'kind' => 'standard',
        ]);

        $payload = ['socket_id' => '123.456'];
        $this->actingAs($user)->postJson('/broadcasting/auth', [
            ...$payload,
            'channel_name' => 'private-workspaces.'.$workspace->id.'.campaigns.'.$simulation->id,
        ])->assertOk();

        $this->actingAs($user)->postJson('/broadcasting/auth', [
            ...$payload,
            'channel_name' => 'private-workspaces.'.$workspace->id.'.campaigns.'.$standard->id,
        ])->assertForbidden();
    }

    private function member(string $plan): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => Str::random(), 'slug' => Str::uuid(), 'plan_override' => $plan]);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);

        return [$user, $workspace];
    }

    private function assertBetween(int $value, int $minimum, int $maximum): void
    {
        $this->assertGreaterThanOrEqual($minimum, $value);
        $this->assertLessThanOrEqual($maximum, $value);
    }
}
