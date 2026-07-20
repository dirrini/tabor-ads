<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceAccessService;
use App\Services\WorkspaceInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request, WorkspaceInvitationService $invitations): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'workspace_name' => ['nullable', 'string', 'max:120'],
            'invitation_token' => ['nullable', 'string'],
            'locale' => ['nullable', 'in:pt-BR,en'],
        ]);

        $invitation = ! empty($data['invitation_token']) ? $invitations->findValid($data['invitation_token']) : null;

        $user = DB::transaction(function () use ($data, $invitation, $invitations) {
            $user = User::create(['name' => $data['name'], 'email' => strtolower($data['email']), 'password' => $data['password'], 'locale' => $data['locale'] ?? 'pt-BR']);
            if ($invitation) {
                $invitations->accept($user, $invitation);
            } else {
                $workspaceName = $data['workspace_name'] ?? null;
                $workspace = Workspace::create(['name' => $workspaceName ?: $data['name'].' Workspace', 'slug' => Str::slug($workspaceName ?: $data['name']).'-'.Str::lower(Str::random(6))]);
                $workspace->members()->attach($user->id, [
                    'role' => 'owner',
                    'can_create_campaigns' => true,
                    'can_view_metrics' => true,
                    'joined_at' => now(),
                ]);
                $user->update(['current_workspace_id' => $workspace->id]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        if (! $user->hasVerifiedEmail()) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (Throwable $error) {
                report($error);
            }
        }

        return response()->json($this->payload($user), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', strtolower($credentials['email']))->first();
        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password) || $user->status !== 'active') {
            return response()->json(['message' => __('api.invalid_credentials')], 422);
        }
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json($this->payload($user));
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => __('api.session_ended')]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->payload($request->user()));
    }

    public function preferences(Request $request): JsonResponse
    {
        $data = $request->validate(['locale' => ['required', 'in:pt-BR,en']]);
        $request->user()->update(['locale' => $data['locale']]);

        return response()->json(['user' => $request->user()->only(['id', 'name', 'email', 'locale'])]);
    }

    private function payload(User $user): array
    {
        $workspace = $user->currentWorkspace();
        $permissions = $workspace ? app(WorkspaceAccessService::class)->permissions($user, $workspace) : null;

        return ['user' => [
            ...$user->only(['id', 'name', 'email', 'locale']),
            'email_verified' => $user->hasVerifiedEmail(),
        ], 'workspace' => $workspace ? [
            'id' => $workspace->id, 'name' => $workspace->name, 'slug' => $workspace->slug,
            'plan' => $workspace->planCode(), 'role' => $permissions['role'], 'permissions' => $permissions,
            'limits' => config('plans.'.$workspace->planCode()),
        ] : null];
    }
}
