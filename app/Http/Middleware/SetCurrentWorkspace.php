<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentWorkspace
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $workspaceId = $request->header('X-Workspace-Id');

        if (! $workspaceId) {
            return response()->json([
                'message' => 'Header X-Workspace-ID is required.',
            ], 400);
        }

        $workspace = Workspace::find($workspaceId);

        if (! $workspace) {
            return response()->json([
                'message' => 'Workspace not found,',
            ], 404);
        }

        $member = $workspace->workspaceMembers()
                            ->where('user_id', $request->user()->id)
                            ->first();
        
        if (! $member) {
            return response()->json([
                'message' => 'You are not a member of this workspace.',
            ], 403);
        }

        app()->instance('current.workspace', $workspace);
        app()->instance('current.workspace_member', $member);

        return $next($request);
    }
}
