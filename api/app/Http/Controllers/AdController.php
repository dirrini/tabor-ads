<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\Ad;
use App\Models\Campaign;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdController extends Controller
{
    use ResolvesWorkspace;

    public function store(Request $request, Campaign $campaign, PlanService $plans): JsonResponse
    {
        abort_unless($campaign->workspace_id === $this->workspace($request)->id, 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'destination_url' => ['nullable', 'url', 'max:2000']]);
        $ad = DB::transaction(function () use ($campaign, $data, $plans) {
            $campaign = Campaign::with('workspace')->lockForUpdate()->findOrFail($campaign->id);
            $plans->assertCanCreateAd($campaign);

            return Ad::create([...$data, 'campaign_id' => $campaign->id, 'tracking_key' => Str::uuid(), 'status' => 'active']);
        });

        return response()->json(['data' => $ad, 'pixel_url' => url('/t/'.$ad->tracking_key.'.gif')], 201);
    }

    public function archive(Request $request, Ad $ad): JsonResponse
    {
        $ad->load('campaign');
        abort_unless($ad->campaign->workspace_id === $this->workspace($request)->id, 404);
        $ad->update(['status' => 'archived', 'archived_at' => now()]);

        return response()->json(['message' => __('api.ad_archived')]);
    }
}
