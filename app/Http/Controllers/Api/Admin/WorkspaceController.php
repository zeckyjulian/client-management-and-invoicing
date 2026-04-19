<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminWorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspaces = Workspace::query()
            ->when($request->search, fn ($q) => 
                $q->where('name', 'ilike', "%{$request->search}%")
                ->orWhere('slug', 'ilike', "%{$request->search}%")
            )
            ->when($request->plan, fn ($q) =>
                $q->where('plan', $request->plan)
            )
            ->withCount('workspaceMembers')
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => AdminWorkspaceResource::collection($workspaces),
            'meta' => [
                'total' => $workspaces->total(),
                'per_page' => $workspaces->perPage(),
                'current_page' => $workspaces->currentPage(),
                'last_page' => $workspaces->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $workspace = Workspace::withCount('workspaceMembers')->with('workspaceMembers.user')->findOrFail($id);

        return response()->json([
            'data' => new AdminWorkspaceResource($workspace),
        ]);
    }

    public function updatePlan(Request $request, string $id): JsonResponse
    {
        $workspace = Workspace::findOrFail($id);

        $data = $request->validate([
            'plan' => 'required|in:free,pro,business',
        ]);

        $workspace->update(['plan' => $data['plan']]);

        return response()->json([
            'message' => 'Plan workspace updated successfully',
            'data' => new AdminWorkspaceResource($workspace),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $workspace = Workspace::findOrFail($id);
        $workspace->delete();

        return response()->json([
            'message' => 'Workspace deleted successfully'
        ]);
    }
}
