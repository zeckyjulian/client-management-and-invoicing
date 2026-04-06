<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\JsonResponse;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'workspace_name' => 'required|string|max:255',
        ]);

        $result = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $workspace = Workspace::create([
                'name' => $data['workspace_name'],
                'slug' => Str::slug($data['workspace_name']) . '-' . Str::random(5),
                'plan' => 'free',
                'currency' => 'IDR',
            ]);

            $workspace->workspaceMembers()->create([
                'user_id' => $user->id,
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            $token = $user->createToken('api-token')->plainTextToken;

            return compact('user', 'workspace', 'token');
        });

        return response()->json([
            'message' => 'Registration successful',
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
            ],
            'workspace' => [
                'id' => $result['workspace']->id,
                'name' => $result['workspace']->name,
                'slug' => $result['workspace']->slug,
                'plan' => $result['workspace']->plan,
            ],
            'token' => $result['token'],
        ], 201);
    }
}
