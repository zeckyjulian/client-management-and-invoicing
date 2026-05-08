<?php

namespace App\Providers;

use App\Events\TimeTracking\TimerStopped;
use App\Listeners\TimeTracking\RecalculateProjectBudgetListener;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    protected $listen = [
        TimerStopped::class => [
            RecalculateProjectBudgetListener::class,
        ],
    ];
}
