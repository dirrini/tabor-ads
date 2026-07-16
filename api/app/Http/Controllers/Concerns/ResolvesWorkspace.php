<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait ResolvesWorkspace
{
    protected function workspace(Request $request): Workspace
    {
        $workspace = $request->user()?->currentWorkspace();
        if (! $workspace) {
            throw new AccessDeniedHttpException(__('api.workspace_missing'));
        }

        return $workspace;
    }

    protected function role(Request $request, Workspace $workspace): string
    {
        return (string) $workspace->members()->where('users.id', $request->user()->id)->firstOrFail()->pivot->role;
    }
}
