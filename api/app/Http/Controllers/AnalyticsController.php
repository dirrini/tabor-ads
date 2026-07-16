<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Services\PlanService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AnalyticsController extends Controller
{
    use ResolvesWorkspace;

    public function __invoke(Request $request, PlanService $plans): JsonResponse
    {
        $validated = $request->validate([
            'campaign_ids' => ['sometimes', 'array'],
            'campaign_ids.*' => ['integer'],
            'ad_ids' => ['sometimes', 'array'],
            'ad_ids.*' => ['integer'],
            'days' => ['sometimes', 'integer', Rule::in([7, 14, 30, 90])],
        ]);

        $workspace = $this->workspace($request);
        $days = (int) ($validated['days'] ?? 30);

        $campaignCatalog = DB::table('campaigns')
            ->where('workspace_id', $workspace->id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get(['id', 'name', 'kind'])
            ->map(fn ($campaign) => [
                'id' => (int) $campaign->id,
                'name' => $campaign->name,
                'kind' => $campaign->kind,
            ]);

        $catalogCampaignIds = $campaignCatalog->pluck('id');
        $requestedCampaignIds = collect($validated['campaign_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique();
        $campaignIds = ($requestedCampaignIds->isEmpty() ? $catalogCampaignIds : $requestedCampaignIds)
            ->intersect($catalogCampaignIds)
            ->values();

        $adCatalog = DB::table('ads')
            ->join('campaigns', 'campaigns.id', '=', 'ads.campaign_id')
            ->where('campaigns.workspace_id', $workspace->id)
            ->whereNull('campaigns.archived_at')
            ->whereNull('ads.archived_at')
            ->orderBy('ads.name')
            ->get(['ads.id', 'ads.campaign_id', 'ads.name'])
            ->map(fn ($ad) => [
                'id' => (int) $ad->id,
                'campaign_id' => (int) $ad->campaign_id,
                'name' => $ad->name,
            ]);

        $availableAdIds = $adCatalog
            ->whereIn('campaign_id', $campaignIds)
            ->pluck('id');
        $requestedAdIds = collect($validated['ad_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique();
        $adIds = ($requestedAdIds->isEmpty() ? $availableAdIds : $requestedAdIds)
            ->intersect($availableAdIds)
            ->values();

        $start = now()->startOfDay()->subDays($days - 1);

        $campaignTotals = $this->impressions($workspace->id, $campaignIds, $adIds)
            ->groupBy('campaigns.id')
            ->select('campaigns.id', DB::raw('COUNT(ad_impressions.id) as total'))
            ->pluck('total', 'id');

        $campaigns = $campaignCatalog
            ->whereIn('id', $campaignIds)
            ->map(fn ($campaign) => [
                ...$campaign,
                'total' => (int) ($campaignTotals[$campaign['id']] ?? 0),
            ])
            ->values();

        $browsers = $this->impressions($workspace->id, $campaignIds, $adIds)
            ->groupBy('ad_impressions.browser')
            ->select('ad_impressions.browser as browser', DB::raw('COUNT(ad_impressions.id) as total'))
            ->orderByDesc('total')
            ->get()
            ->map(fn ($browser) => [
                'browser' => $browser->browser,
                'total' => (int) $browser->total,
            ]);

        $dailyTotals = $this->impressions($workspace->id, $campaignIds, $adIds)
            ->where('ad_impressions.created_at', '>=', $start)
            ->groupBy(DB::raw('DATE(ad_impressions.created_at)'))
            ->select(DB::raw('DATE(ad_impressions.created_at) as day'), DB::raw('COUNT(ad_impressions.id) as total'))
            ->pluck('total', 'day');

        $timeline = collect(range(0, $days - 1))->map(function (int $offset) use ($start, $dailyTotals) {
            $date = $start->copy()->addDays($offset)->toDateString();

            return ['date' => $date, 'total' => (int) ($dailyTotals[$date] ?? 0)];
        });

        $midpoint = intdiv($days, 2);
        $previousTotal = $timeline->slice($days - ($midpoint * 2), $midpoint)->sum('total');
        $currentTotal = $timeline->slice($days - $midpoint, $midpoint)->sum('total');
        $trendPercentage = $previousTotal === 0
            ? ($currentTotal > 0 ? 100.0 : 0.0)
            : round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1);

        return response()->json(['data' => [
            'campaigns' => $campaigns,
            'browsers' => $browsers,
            'timeline' => $timeline,
            'total' => $campaigns->sum('total'),
            'trend_percentage' => $trendPercentage,
            'trend_direction' => $trendPercentage > 0 ? 'growth' : ($trendPercentage < 0 ? 'decline' : 'stable'),
            'filters' => [
                'campaigns' => $campaignCatalog->values(),
                'ads' => $adCatalog->values(),
                'days' => $days,
            ],
            'realtime' => $plans->realtime($workspace),
            'refreshed_at' => now()->toIso8601String(),
        ]]);
    }

    private function impressions(int $workspaceId, $campaignIds, $adIds): Builder
    {
        $query = DB::table('ad_impressions')
            ->join('ads', 'ads.id', '=', 'ad_impressions.ad_id')
            ->join('campaigns', 'campaigns.id', '=', 'ads.campaign_id')
            ->where('campaigns.workspace_id', $workspaceId)
            ->whereNull('campaigns.archived_at')
            ->whereNull('ads.archived_at')
            ->whereIn('campaigns.id', $campaignIds);

        if ($adIds->isNotEmpty()) {
            $query->whereIn('ads.id', $adIds);
        } else {
            // An explicitly selected campaign without eligible ads must yield no impressions.
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
