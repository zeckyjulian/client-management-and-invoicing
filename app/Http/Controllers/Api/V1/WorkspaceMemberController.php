<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceMemberResource;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceMemberController extends Controller
{
    public function __construct(private readonly WorkspaceService $service) {}

    public function index(): JsonResponse
    {
        $members = app('current.workspace')->workspaceMembers()->with('user')->get();

        return response()->json([
            'data' => WorkspaceMemberResource::collection($members)
        ]);
    }

    public function invite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => 'required|in:admin,member',
        ]);

        $member = $this->service->inviteMember(
            workspace: app('current.workspace'),
            email: $data['email'],
            role: $data['role']
        );

        return response()->json([
            'message' => 'Invitation sent successfully.',
            'data' => new WorkspaceMemberResource($member->load('user'))
        ], 201);
    }

    public function updateRole(Request $request, string $userId): JsonResponse
    {
        $data = $request->validate([
            'role' => 'required|in:admin,member',
        ]);

        $member = $this->service->updateRole(
            workspace: app('current.workspace'),
            userId: $userId,
            role: $data['role']
        );

        return response()->json([
            'message' => 'Member role updated successfully.',
            'data' => new WorkspaceMemberResource($member->load('user'))
        ]);
    }

    public function remove(string $userId): JsonResponse
    {
        $this->service->removeMember(
            workspace: app('current.workspace'),
            userId: $userId
        );

        return response()->json([
            'message' => 'Member removed from workspace.',
        ]);
    }
}
