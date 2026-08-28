<?php

namespace App\Listeners\Payment;

use App\Events\Payment\PaymentFailed;
use App\Notifications\Payment\PaymentFailedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandlePaymentFailedListener implements ShouldQueue
{
    public string $queue = 'default';

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment->load(['invoice.workspace.workspaceMembers.user']);
        $invoice = $payment->invoice;

        // Notify all the owner & admin workspace
        $invoice->workspace->workspaceMembers()
            ->whereIn('role', ['owner', 'admin'])
            ->with('user')
            ->get()
            ->each(fn ($member) =>
                $member->user->notify(new PaymentFailedNotification($payment))
            );
    }
}
