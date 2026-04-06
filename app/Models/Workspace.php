<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasUuids, SoftDeletes;
    
    protected $fillable = [
        'name', 'slug', 'plan', 'currency', 'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps();
    }

    public function workspaceMembers(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function isOwner(User $user): bool
    {
        return $this->workspaceMembers()
                    ->where('user_id', $user->id)
                    ->where('role', 'owner')
                    ->exists();
    }

    public function hasRole(User $user, string ...$roles): bool
    {
        return $this->workspaceMembers()
                    ->where('user_id', $user->id)
                    ->whereIn('role', $roles)
                    ->exists();
    }
}
