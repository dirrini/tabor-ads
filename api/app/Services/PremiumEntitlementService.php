<?php

namespace App\Services;

use App\Models\Subscription;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class PremiumEntitlementService
{
    public function syncPayment(array $payment, ?string $knownCycle = null): ?Subscription
    {
        $providerId = (string) ($payment['id'] ?? '');
        $workspaceId = data_get($payment, 'metadata.workspace_id');
        if (! $workspaceId && preg_match('/^workspace:(\d+)$/', (string) ($payment['external_reference'] ?? ''), $matches)) {
            $workspaceId = (int) $matches[1];
        }
        if (! $providerId || ! $workspaceId) {
            return null;
        }

        $cycle = $knownCycle ?: (string) data_get($payment, 'metadata.billing_cycle', 'monthly');
        if (! in_array($cycle, ['monthly', 'annual'], true)) {
            $cycle = 'monthly';
        }
        $months = (int) config('mercadopago.premium_plans.'.$cycle.'.duration_months', $cycle === 'annual' ? 12 : 1);
        $subscription = Subscription::firstOrCreate(
            ['provider' => 'mercadopago', 'provider_subscription_id' => $providerId],
            ['workspace_id' => $workspaceId, 'plan_code' => 'premium', 'status' => 'pending']
        );
        $status = match ((string) ($payment['status'] ?? 'pending')) {
            'approved' => 'active',
            'rejected', 'cancelled', 'canceled', 'refunded', 'charged_back' => 'canceled',
            default => 'pending',
        };
        $changes = ['status' => $status, 'provider_plan_id' => $cycle];
        if ($status === 'active' && $subscription->status !== 'active') {
            $latestEnd = Subscription::query()
                ->where('workspace_id', $workspaceId)
                ->where('provider', 'mercadopago')
                ->where('plan_code', 'premium')
                ->where('status', 'active')
                ->where('id', '!=', $subscription->id)
                ->max('current_period_end');
            $start = $latestEnd && now()->lt($latestEnd) ? Carbon::parse($latestEnd) : now();
            $changes['current_period_start'] = $start;
            $changes['current_period_end'] = $this->addMonths($start, $months);
        }
        if ($status === 'canceled') {
            $changes['canceled_at'] = now();
        }
        $subscription->update($changes);

        return $subscription;
    }

    private function addMonths(CarbonInterface $start, int $months): CarbonInterface
    {
        return $start->copy()->addMonthsNoOverflow($months);
    }
}
