<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use BelongsToWorkspace, HasUuids, SoftDeletes;

    protected $fillable = [
        'workspace_id', 'client_id', 'name', 'description', 'status', 'billing_tpe',
        'budget', 'hourly_rate', 'start_date', 'end_date',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function getTotalTrackedSecondsAttribute(): int
    {
        return $this->timeEntries()->sum('duration_seconds');
    }

    public function getBillableSecondsAttribute(): int
    {
        return $this->timeEntries()->where('billable', true)->sum('duration_seconds');
    }
}
