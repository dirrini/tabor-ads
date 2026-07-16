<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\Campaign;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    use ResolvesWorkspace;

    public function index(Request $request): JsonResponse
    {
        $workspace = $this->workspace($request);
        $campaigns = $workspace->campaigns()->withCount('ads')->with(['ads' => fn ($q) => $q->whereNull('archived_at')])->latest()->get();

        return response()->json([
            'data' => $campaigns,
            'standard_count' => $campaigns->where('kind', 'standard')->whereNull('archived_at')->count(),
            'plan' => $workspace->planCode(),
            'limits' => config('plans.'.$workspace->planCode()),
        ]);
    }

    public function store(Request $request, PlanService $plans): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['draft', 'active', 'paused'])],
            'simulation' => ['sometimes', 'boolean'],
        ]);
        $workspace = $this->workspace($request);

        $campaign = DB::transaction(function () use ($workspace, $request, $data, $plans) {
            $workspace = $workspace->newQuery()->lockForUpdate()->findOrFail($workspace->id);
            $simulation = (bool) ($data['simulation'] ?? false);
            if (! $simulation) {
                $plans->assertCanCreateCampaign($workspace);
            }

            return Campaign::create([
                'workspace_id' => $workspace->id, 'created_by' => $request->user()->id,
                'name' => $data['name'], 'status' => $data['status'] ?? 'draft',
                'kind' => $simulation ? 'simulation' : 'standard', 'public_id' => Str::uuid(),
            ]);
        });

        return response()->json(['data' => $campaign], 201);
    }

    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $this->assertCampaign($request, $campaign);
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:120'], 'status' => ['sometimes', Rule::in(['draft', 'active', 'paused'])]]);
        $campaign->update($data);

        return response()->json(['data' => $campaign->fresh('ads')]);
    }

    public function archive(Request $request, Campaign $campaign): JsonResponse
    {
        $this->assertCampaign($request, $campaign);
        abort_if($campaign->kind === 'simulation', 422, __('api.simulation_cannot_archive'));
        $campaign->update(['status' => 'archived', 'archived_at' => now()]);

        return response()->json(['message' => __('api.campaign_archived')]);
    }

    private function assertCampaign(Request $request, Campaign $campaign): void
    {
        abort_unless($campaign->workspace_id === $this->workspace($request)->id, 404);
    }
}
