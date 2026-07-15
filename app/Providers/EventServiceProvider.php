<?php

namespace App\Providers;

use App\Events\Invoice\InvoiceCreated;
use App\Events\Invoice\InvoiceOverdue;
use App\Events\Invoice\InvoicePaid;
use App\Events\Invoice\InvoiceSent;
use App\Events\TimeTracking\TimerStopped;
use App\Listeners\Invoice\NotifyWorkspaceOnPaymentListener;
use App\Listeners\Invoice\NotifyWorkspaceOverdueListener;
use App\Listeners\Invoice\SendInvoiceToClientListener;
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
        InvoiceCreated::class => [],

        InvoiceSent::class => [
            SendInvoiceToClientListener::class,
        ],

        InvoicePaid::class => [
            NotifyWorkspaceOnPaymentListener::class,
        ],

        InvoiceOverdue::class => [
            NotifyWorkspaceOverdueListener::class,
        ],

        TimerStopped::class => [
            RecalculateProjectBudgetListener::class,
        ],
    ];
}
