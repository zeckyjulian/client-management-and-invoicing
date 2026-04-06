<?php

namespace App\Models\Concerns;

use App\Models\Scopes\WorkspaceScope;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        static::creating(function ($model) {
            if (app()->has('current.workspace') && empty($model->workspace_id)) {
                $model->workspace_id = app('current.workspace')->id;
            }
        });

        static::addGlobalScope(new WorkspaceScope());
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}