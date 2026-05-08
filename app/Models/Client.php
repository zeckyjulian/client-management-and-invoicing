<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Client extends Model
{
    use BelongsToWorkspace, HasUuids, SoftDeletes;

    protected $fillable = [
        'workspace_id', 'name', 'email', 'company', 'phone',
        'address', 'portal_token', 'portal_active',
    ];

    protected $casts = [
        'portal_active' => 'boolean',
    ];

    protected $hidden = [
        'portal_token',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function generatePortalToken(): string
    {
        $token = Str::random(40);
        $this->update([
            'portal_token' => hash('sha256', $token),
            'portal_active' => true,
        ]);
        return $token;
    }
}
