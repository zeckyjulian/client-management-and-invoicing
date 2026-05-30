<?php

namespace App\Services;

use App\Events\TimeTracking\TimerStopped;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TimeTrackingService
{
    // Start new timer
    public function start(Project $project, User $user, array $data): TimeEntry
    {
        // Check if there's already a running timer for the user
        $running = TimeEntry::where('user_id', $user->id)->whereNull('ended_at')->first();

        if ($running) {
            throw ValidationException::withMessages([
                'timer' => 'You already have a running timer. Please stop it before starting a new one.',
            ]);
        }

        return TimeEntry::create([
            'workspace_id' => $project->workspace_id,
            'project_id' => $project->id,
            'user_id' => $user->id,
            'task_id' => $data['task_id'] ?? null,
            'description' => $data['description'] ?? null,
            'started_at' => now(),
            'billable' => $data['billable'] ?? true,
            'hourly_rate' => $data['hourly_rate'] ?? $project->hourly_rate,
        ]);
    }

    // Stop running timer
    public function stop(TimeEntry $timeEntry): TimeEntry
    {
        if ($timeEntry->ended_at !== null) {
            throw ValidationException::withMessages([
                'timer' => 'This timer has already been stopped.',
            ]);
        }

        $endedAt = now();
        $durationSeconds = intval(abs($endedAt->diffInSeconds($timeEntry->started_at)));

        $timeEntry->update([
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
        ]);

        // Fire event after timer is stopped
        TimerStopped::dispatch($timeEntry->fresh());

        return $timeEntry->fresh();
    }

    // Add manual time entry (without timer)
    public function addManual(Project $project, User $user, array $data): TimeEntry
    {
        $startedAt = \Carbon\Carbon::parse($data['started_at']);
        $endedAt = \Carbon\Carbon::parse($data['ended_at']);

        if ($endedAt->lte($startedAt)) {
            throw ValidationException::withMessages([
                'ended_at' => 'The end time must be after the start time.',
            ]);
        }

        $durationSeconds = intval(abs($endedAt->diffInSeconds($startedAt)));

        $entry = TimeEntry::create([
            'workspace_id' => $project->workspace_id,
            'project_id' => $project->id,
            'user_id' => $user->id,
            'task_id' => $data['task_id'] ?? null,
            'description' => $data['description'] ?? null,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
            'billable' => $data['billable'] ?? true,
            'hourly_rate' => $data['hourly_rate'] ?? $project->hourly_rate,
        ]);

        TimerStopped::dispatch($entry);

        return $entry;
    }

    // get the user's running timer
    public function getRunningTimer(User $user): ?TimeEntry
    {
        return TimeEntry::where('user_id', $user->id)
                        ->whereNull('ended_at')
                        ->with('project', 'task', 'user')
                        ->first();
    }
}
