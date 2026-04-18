<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->search, fn($q) => 
                $q->where('name', 'ilike', "%{$request->search}%")
                ->orWhere('email', 'ilike', "%{$request->search}%")
            )
            ->when($request->is_super_admin, fn ($q) => 
                $q->where('is_super_admin', true)
            )
            ->withCount('workspaces')
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => AdminUserResource::collection($users),
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::withCount('workspaces')->with('workspaces')->findOrFail($id);

        return response()->json([
            'data' => new AdminUserResource($user),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'is_super_admin' => 'sometimes|boolean',
        ]);

        // Prevent super admin from revoking his own rights
        if (
            isset($data['is_super_admin']) &&
            $data['is_super_admin'] === false &&
            $request->user()->id === $user->id
        ) {
            return response()->json([
                'message' => 'You cannot revoke Super Admin rights from your own account.'
            ], 422);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => new AdminUserResource($user),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent super admin deleted his own account
        if ($user->id === request()->user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
