<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected $fillable = [
        'workspace_id', 'project_id', 'task_id', 'user_id', 'description',
        'started_at', 'ended_at', 'duration_seconds', 'billable', 'hourly_rate', 'invoice_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'billable' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'duration_seconds' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function getDurationFormattedAttribute(): string
    {
        $seconds = $this->duration_seconds;
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    public function getBillableAmountAttribute(): float
    {
        if (!$this->billable || !$this->hourly_rate) {
            return 0;
        }

        return round(($this->duration_seconds / 3600) * $this->hourly_rate, 2);
    }
}
