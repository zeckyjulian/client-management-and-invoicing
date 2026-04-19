<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkspaceService
{
    public function create(User $user, array $data): Workspace
    {
        $workspace = DB::transaction(function () use ($user, $data) {
            $workspace = Workspace::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::random(5),
                'plan' => 'free',
                'currency' => $data['currency'] ?? 'IDR',
            ]);

            $workspace->workspaceMembers()->create([
                'user_id' => $user->id,
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            return $workspace;
        });

        return $workspace;
    }

    public function update(Workspace $workspace, array $data): Workspace
    {
        if (isset($data['settings'])) {
            $data['settings'] = array_merge(
                $workspace->settings ?? [],
                $data['settings']
            );
        }

        $workspace->update($data);

        return $workspace->fresh();
    }

    public function inviteMember(Workspace $workspace, string $email, string $role = 'member'): WorkspaceMember
    {
        $user = User::where('email', $email)->firstOrFail();

        $alreadyMember = $workspace->workspaceMembers()
                                    ->where('user_id', $user->id)
                                    ->exists();

        if ($alreadyMember) {
            throw ValidationException::withMessages([
                'email' => 'This user is already a member of the workspace.',
            ]);
        }

        return $workspace->workspaceMembers()->create([
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    public function removeMember(Workspace $workspace, string $userId): void
    {
        $member = $workspace->workspaceMembers()
                            ->where('user_id', $userId)
                            ->firstOrFail();

        if ($member->role === 'owner') {
            throw ValidationException::withMessages([
                'user' => 'Cannot remove an owner from the workspace.',
            ]);
        }

        $member->delete();
    }

    public function updateRole(Workspace $workspace, string $userId, string $role): WorkspaceMember
    {
        $member = $workspace->workspaceMembers()
                            ->where('user_id', $userId)
                            ->firstOrFail();

        if ($member->role === 'owner') {
            throw ValidationException::withMessages([
                'user' => 'Cannot change the role of an owner.',
            ]);
        }

        $member->update(['role' => $role]);

        return $member->fresh();
    }
}
