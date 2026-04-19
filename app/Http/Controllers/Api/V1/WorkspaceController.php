<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function __construct(private readonly WorkspaceService $service) {}

    public function index(Request $request): JsonResponse
    {
        $workspaces = $request->user()->workspaces()->withPivot('role', 'joined_at')->withCount('workspaceMembers')->get();

        return response()->json([
            'data' => WorkspaceResource::collection($workspaces),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'currency' => 'sometimes|string|size:3',
        ]);

        $workspace = $this->service->create($request->user(), $data);

        return response()->json([
            'message' => 'Workspace created successfully.',
            'data' => new WorkspaceResource($workspace),
        ], 201);
    }

    public function show(): JsonResponse
    {
        $workspace = app('current.workspace')->load('workspaceMembers.user');

        return response()->json([
            'data' => new WorkspaceResource($workspace),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'currency' => 'sometimes|string|size:3',
            'settings' => 'sometimes|array',
            'settings.slack_webhook' => 'sometimes|nullable|url',
        ]);

        $workspace = $this->service->update(app('current.workspace'), $data);

        return response()->json([
            'message' => 'Workspace updated successfully.',
            'data' => new WorkspaceResource($workspace),
        ]);
    }
}
