<?php

namespace App\Http\Controllers;

use App\Services\WorkspaceInvitationService;
use Illuminate\Http\JsonResponse;

class InvitationController extends Controller
{
    public function show(string $token, WorkspaceInvitationService $invitations): JsonResponse
    {
        $invitation = $invitations->findValid($token);

        return response()->json(['data' => [
            'name' => $invitation->name,
            'email' => $invitation->email,
            'workspace' => $invitation->workspace()->value('name'),
            'expires_at' => $invitation->expires_at->toIso8601String(),
            'permissions' => [
                'can_create_campaigns' => $invitation->can_create_campaigns,
                'can_view_metrics' => $invitation->can_view_metrics,
            ],
        ]]);
    }
}
