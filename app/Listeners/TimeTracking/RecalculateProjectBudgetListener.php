<?php

namespace App\Listeners\TimeTracking;

use App\Events\TimeTracking\TimerStopped;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecalculateProjectBudgetListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public string $queue = 'default';

    /**
     * Handle the event.
     */
    public function handle(TimerStopped $event): void
    {
        $project = $event->timeEntry->project;

        if ($project->billing_type !== 'hourly') {
            return;
        }

        $totalBillable = $project->timeEntries()->where('billable', true)->sum('duration_seconds');

        cache()->put(
            "project:{$project->id}:billable_seconds",
            $totalBillable,
            now()->addHour()
        );
    }
}
