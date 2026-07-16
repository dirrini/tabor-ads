<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    use ResolvesWorkspace;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $workspace = $this->workspace($request);
        $membership = $workspace->members()->where('users.id', $user->id)->firstOrFail();
        $subscription = $workspace->currentPremiumSubscription();

        return response()->json(['data' => [
            'user' => [
                ...$user->only(['id', 'name', 'email', 'locale', 'created_at']),
                'email_verified' => $user->email_verified_at !== null,
                'has_password' => $user->password !== null,
                'providers' => $user->oauthIdentities()->pluck('provider')->values(),
            ],
            'workspace' => [
                ...$workspace->only(['id', 'name', 'slug', 'created_at']),
                'plan' => $workspace->planCode(),
                'role' => $membership->pivot->role,
                'joined_at' => $membership->pivot->joined_at,
                'limits' => config('plans.'.$workspace->planCode()),
                'usage' => [
                    'campaigns' => $workspace->campaigns()->where('kind', 'standard')->whereNull('archived_at')->count(),
                    'simulation_campaigns' => $workspace->campaigns()->where('kind', 'simulation')->whereNull('archived_at')->count(),
                    'members' => $workspace->members()->count(),
                ],
            ],
            'subscription' => $subscription ? [
                ...$subscription->only(['provider', 'provider_plan_id', 'status']),
                'current_period_start' => $subscription->current_period_start?->toIso8601String(),
                'current_period_end' => $subscription->current_period_end?->toIso8601String(),
                'grace_until' => $subscription->grace_until?->toIso8601String(),
            ] : null,
        ]]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['nullable', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if ($user->password !== null) {
            if (empty($data['current_password'])) {
                throw ValidationException::withMessages([
                    'current_password' => __('api.current_password_required'),
                ]);
            }

            if (! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => __('api.current_password_invalid'),
                ]);
            }
        }

        $user->update(['password' => $data['password']]);

        return response()->json(['message' => __('api.password_updated')]);
    }
}
