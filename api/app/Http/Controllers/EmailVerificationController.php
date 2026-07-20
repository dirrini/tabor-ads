<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $sameSession = $request->user()?->is($user);
        $path = $sameSession ? '/app/dashboard?verified=1' : '/login?verified=1&email='.urlencode($user->email);

        return redirect(rtrim(config('app.frontend_url'), '/').$path);
    }

    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => __('api.email_already_verified')]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => __('api.verification_sent')], 202);
    }
}
