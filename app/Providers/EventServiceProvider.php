<?php

namespace App\Providers;

use App\Events\Invoice\InvoiceCreated;
use App\Events\Invoice\InvoiceOverdue;
use App\Events\Invoice\InvoicePaid;
use App\Events\Invoice\InvoiceSent;
use App\Events\Payment\PaymentFailed;
use App\Events\Payment\PaymentReceived;
use App\Events\TimeTracking\TimerStopped;
use App\Listeners\Invoice\NotifyWorkspaceOnPaymentListener;
use App\Listeners\Invoice\NotifyWorkspaceOverdueListener;
use App\Listeners\Invoice\SendInvoiceToClientListener;
use App\Listeners\Payment\HandlePaymentFailedListener;
use App\Listeners\Payment\HandlePaymentReceivedListener;
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

        PaymentReceived::class => [
            HandlePaymentReceivedListener::class,
        ],

        PaymentFailed::class => [
            HandlePaymentFailedListener::class,
        ],

        TimerStopped::class => [
            RecalculateProjectBudgetListener::class,
        ],
    ];
}
