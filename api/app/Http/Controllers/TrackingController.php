<?php

namespace App\Http\Controllers;

use App\Events\ImpressionRecorded;
use App\Models\Ad;
use App\Models\AdImpression;
use App\Services\PlanService;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function __invoke(Request $request, string $trackingKey, PlanService $plans)
    {
        $trackingKey = str_replace('.gif', '', $trackingKey);
        $ad = Ad::with('campaign.workspace')->where('tracking_key', $trackingKey)->whereNull('archived_at')->firstOrFail();
        abort_if($ad->campaign->kind === 'simulation', 404);
        $agent = $request->userAgent() ?: 'unknown';
        $impression = AdImpression::create([
            'ad_id' => $ad->id, 'source' => 'tracking_pixel', 'ip_address' => $request->ip(), 'user_agent' => $agent,
            'browser' => $this->browser($agent), 'platform' => $this->platform($agent), 'created_at' => now(),
        ]);
        $impression->setRelation('ad', $ad);
        if ($plans->realtime($ad->campaign->workspace)) {
            event(new ImpressionRecorded($impression));
        }

        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, [
            'Content-Type' => 'image/gif', 'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function browser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Edg') => 'Edge', str_contains($ua, 'OPR') => 'Opera', str_contains($ua, 'Chrome') => 'Chrome', str_contains($ua, 'Firefox') => 'Firefox', str_contains($ua, 'Safari') => 'Safari', default => 'Other'
        };
    }

    private function platform(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows', str_contains($ua, 'Android') => 'Android', str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS', str_contains($ua, 'Macintosh') => 'MacOS', str_contains($ua, 'Linux') => 'Linux', default => 'Other'
        };
    }
}
