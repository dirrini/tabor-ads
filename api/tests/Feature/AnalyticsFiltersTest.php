<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdImpression;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_analytics_can_be_filtered_by_campaigns_and_ads(): void
    {
        Carbon::setTestNow('2026-07-15 12:00:00');
        [$user, $workspace] = $this->member();

        $firstCampaign = $this->campaign($workspace, $user, 'Summer');
        $firstAd = $this->ad($firstCampaign, 'Banner A');
        $secondAd = $this->ad($firstCampaign, 'Banner B');
        $secondCampaign = $this->campaign($workspace, $user, 'Winter');
        $thirdAd = $this->ad($secondCampaign, 'Video');

        $this->impression($firstAd, 'Chrome', '2026-07-14 10:00:00');
        $this->impression($firstAd, 'Chrome', '2026-07-15 10:00:00');
        $this->impression($secondAd, 'Firefox', '2026-07-15 11:00:00');
        $this->impression($thirdAd, 'Safari', '2026-07-15 11:30:00');

        $response = $this->actingAs($user)->getJson('/api/analytics?days=7&campaign_ids[]='.$firstCampaign->id.'&ad_ids[]='.$firstAd->id);

        $response->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonCount(1, 'data.campaigns')
            ->assertJsonPath('data.campaigns.0.id', $firstCampaign->id)
            ->assertJsonPath('data.campaigns.0.total', 2)
            ->assertJsonCount(1, 'data.browsers')
            ->assertJsonPath('data.browsers.0.browser', 'Chrome')
            ->assertJsonPath('data.timeline.5.total', 1)
            ->assertJsonPath('data.timeline.6.total', 1)
            ->assertJsonCount(2, 'data.filters.campaigns')
            ->assertJsonCount(3, 'data.filters.ads');
    }

    public function test_analytics_ignores_ids_from_another_workspace(): void
    {
        [$user, $workspace] = $this->member();
        [$otherUser, $otherWorkspace] = $this->member();
        $ownCampaign = $this->campaign($workspace, $user, 'Own');
        $otherCampaign = $this->campaign($otherWorkspace, $otherUser, 'Private');
        $this->impression($this->ad($ownCampaign, 'Own Ad'), 'Chrome', now());
        $otherAd = $this->ad($otherCampaign, 'Private Ad');
        $this->impression($otherAd, 'Safari', now());

        $response = $this->actingAs($user)->getJson('/api/analytics?campaign_ids[]='.$otherCampaign->id.'&ad_ids[]='.$otherAd->id);

        $response->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonCount(0, 'data.campaigns')
            ->assertJsonCount(1, 'data.filters.campaigns')
            ->assertJsonCount(1, 'data.filters.ads');
    }

    private function member(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => Str::random(), 'slug' => Str::uuid(), 'plan_override' => 'premium']);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);

        return [$user, $workspace];
    }

    private function campaign(Workspace $workspace, User $user, string $name): Campaign
    {
        return Campaign::create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
            'name' => $name,
            'kind' => 'standard',
            'status' => 'active',
            'public_id' => Str::uuid(),
        ]);
    }

    private function ad(Campaign $campaign, string $name): Ad
    {
        return Ad::create([
            'campaign_id' => $campaign->id,
            'name' => $name,
            'status' => 'active',
            'tracking_key' => Str::uuid(),
        ]);
    }

    private function impression(Ad $ad, string $browser, Carbon|string $createdAt): void
    {
        AdImpression::create([
            'ad_id' => $ad->id,
            'source' => 'tracking_pixel',
            'browser' => $browser,
            'platform' => 'Desktop',
            'created_at' => $createdAt,
        ]);
    }
}
