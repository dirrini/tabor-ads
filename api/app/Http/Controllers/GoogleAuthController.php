<?php

namespace App\Http\Controllers;

use App\Models\OauthIdentity;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $locale = in_array($request->query('locale'), ['pt-BR', 'en'], true) ? $request->query('locale') : 'pt-BR';
        $request->session()->put('oauth_locale', $locale);
        if ($request->filled('invite')) {
            $request->session()->put('oauth_invitation_token', $request->query('invite'));
        } else {
            $request->session()->forget('oauth_invitation_token');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, WorkspaceInvitationService $invitations)
    {
        $google = Socialite::driver('google')->user();
        abort_unless($google->getEmail(), 422, __('api.google_email_missing'));
        $locale = $request->session()->pull('oauth_locale', 'pt-BR');
        $invitationToken = $request->session()->pull('oauth_invitation_token');
        $email = Str::lower(trim($google->getEmail()));

        $user = DB::transaction(function () use ($google, $locale, $email, $invitationToken, $invitations) {
            $identity = OauthIdentity::query()
                ->where('provider', 'google')
                ->where('provider_user_id', $google->getId())
                ->lockForUpdate()
                ->first();

            if ($identity) {
                $identity->update(['avatar_url' => $google->getAvatar()]);
                $user = $identity->user;
            } else {
                $user = User::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->lockForUpdate()
                    ->first();

                if ($user) {
                    if (! $user->email_verified_at) {
                        $user->update(['email_verified_at' => now()]);
                    }
                } else {
                    $user = User::create([
                        'name' => $google->getName() ?: __('api.default_user_name'),
                        'email' => $email,
                        'email_verified_at' => now(),
                        'password' => null,
                        'locale' => $locale,
                    ]);
                }

                OauthIdentity::create([
                    'user_id' => $user->id,
                    'provider' => 'google',
                    'provider_user_id' => $google->getId(),
                    'avatar_url' => $google->getAvatar(),
                ]);
            }

            if ($invitationToken) {
                $invitations->accept($user, $invitations->findValid($invitationToken));
            } elseif (! $user->currentWorkspace()) {
                $workspace = Workspace::create(['name' => $user->name.' Workspace', 'slug' => Str::slug($user->name).'-'.Str::lower(Str::random(6))]);
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

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect(rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/').'/app/dashboard');
    }
}
