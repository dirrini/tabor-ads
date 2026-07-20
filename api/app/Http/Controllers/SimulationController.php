<?php

namespace App\Http\Controllers;

use App\Events\ImpressionRecorded;
use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\AdImpression;
use App\Models\Campaign;
use App\Services\WorkspaceAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SimulationController extends Controller
{
    use ResolvesWorkspace;

    private const MAX_SECONDS = 180;

    public function start(Request $request, WorkspaceAccessService $access): JsonResponse
    {
        $workspace = $this->workspace($request);
        $access->assertCanCreateCampaigns($request->user(), $workspace);
        $campaignIds = Campaign::query()
            ->where('workspace_id', $workspace->id)
            ->where('kind', 'simulation')
            ->whereNull('archived_at')
            ->orderBy('id')
            ->pluck('id');

        if ($campaignIds->isEmpty()) {
            throw ValidationException::withMessages(['simulation' => __('api.simulation_campaign_required')]);
        }

        $token = Str::random(48);
        $expiresAt = now()->addSeconds(self::MAX_SECONDS);
        Cache::put($this->cacheKey($workspace->id, $request->user()->id), [
            'token' => $token,
            'expires_at' => $expiresAt->timestamp,
            'weights' => $this->campaignWeights($campaignIds->all()),
        ], $expiresAt);

        return response()->json([
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'max_seconds' => self::MAX_SECONDS,
        ]);
    }

    public function tick(Request $request, WorkspaceAccessService $access): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:48']]);
        $workspace = $this->workspace($request);
        $access->assertCanCreateCampaigns($request->user(), $workspace);
        $session = $this->activeSession($workspace->id, $request->user()->id, $data['token']);

        $campaigns = Campaign::query()
            ->where('workspace_id', $workspace->id)
            ->where('kind', 'simulation')
            ->whereNull('archived_at')
            ->with(['ads' => fn ($query) => $query->whereNull('archived_at')->oldest()])
            ->get();

        $browserPool = ['Chrome', 'Chrome', 'Chrome', 'Safari', 'Firefox', 'Edge'];
        $events = [];
        $campaignResults = [];
        $now = now();

        foreach ($campaigns as $campaign) {
            $ad = $campaign->ads->first() ?? $campaign->ads()->create([
                'name' => __('api.simulation_ad'),
                'status' => 'active',
                'tracking_key' => Str::uuid(),
            ]);
            $weight = (float) ($session['weights'][$campaign->id] ?? 1.0);
            $count = max(2, min(9, (int) round(random_int(2, 9) * $weight)));
            $rows = [];
            $distribution = [];

            for ($i = 0; $i < $count; $i++) {
                $browser = $browserPool[array_rand($browserPool)];
                $distribution[$browser] = ($distribution[$browser] ?? 0) + 1;
                $rows[] = [
                    'ad_id' => $ad->id,
                    'source' => 'simulator',
                    'browser' => $browser,
                    'platform' => 'Simulation',
                    'created_at' => $now,
                ];
            }

            DB::table('ad_impressions')->insert($rows);
            $campaign->setRelation('workspace', $workspace);
            $ad->setRelation('campaign', $campaign);

            foreach ($distribution as $browser => $browserCount) {
                $impression = new AdImpression([
                    'ad_id' => $ad->id,
                    'source' => 'simulator',
                    'browser' => $browser,
                    'platform' => 'Simulation',
                    'created_at' => $now,
                ]);
                $impression->setRelation('ad', $ad);
                event(new ImpressionRecorded($impression, $browserCount));
                $events[] = [
                    'campaign_id' => $campaign->id,
                    'ad_id' => $ad->id,
                    'browser' => $browser,
                    'date' => $now->toDateString(),
                    'count' => $browserCount,
                ];
            }

            $campaignResults[] = [
                'campaign_id' => $campaign->id,
                'generated' => $count,
                'weight' => $weight,
            ];
        }

        return response()->json([
            'events' => $events,
            'campaigns' => $campaignResults,
            'generated' => collect($campaignResults)->sum('generated'),
        ]);
    }

    public function stop(Request $request, WorkspaceAccessService $access): JsonResponse
    {
        $data = $request->validate(['token' => ['nullable', 'string', 'size:48']]);
        $workspace = $this->workspace($request);
        $access->assertCanCreateCampaigns($request->user(), $workspace);
        $key = $this->cacheKey($workspace->id, $request->user()->id);
        $session = Cache::get($key);

        if ($session && isset($data['token']) && hash_equals($session['token'], $data['token'])) {
            Cache::forget($key);
        }

        return response()->json(['message' => __('api.simulation_stopped')]);
    }

    private function activeSession(int $workspaceId, int $userId, string $token): array
    {
        $session = Cache::get($this->cacheKey($workspaceId, $userId));
        if (! $session || $session['expires_at'] <= now()->timestamp || ! hash_equals($session['token'], $token)) {
            throw ValidationException::withMessages(['simulation' => __('api.simulation_expired')]);
        }

        return $session;
    }

    private function campaignWeights(array $campaignIds): array
    {
        $count = count($campaignIds);
        if ($count === 1) {
            return [$campaignIds[0] => 1.0];
        }

        $weights = [];
        foreach (array_keys($campaignIds) as $index) {
            $weights[] = round(0.6 + (0.8 * $index / ($count - 1)), 4);
        }
        shuffle($weights);

        $result = [];
        foreach ($campaignIds as $index => $campaignId) {
            $result[$campaignId] = $weights[$index];
        }

        return $result;
    }

    private function cacheKey(int $workspaceId, int $userId): string
    {
        return 'simulation-run:'.$workspaceId.':'.$userId;
    }
}
