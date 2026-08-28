<?php

namespace App\Listeners\Payment;

use App\Events\Invoice\InvoicePaid;
use App\Events\Payment\PaymentReceived;
use App\Notifications\Payment\PaymentReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandlePaymentReceivedListener implements ShouldQueue
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
    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment->load(['invoice.workspace.workspaceMembers.user']);
        $invoice = $payment->invoice;

        // Update the invoice status to 'paid'
        $invoice->update([
            'status' => 'paid',
            'paid_at' => $payment->paid_at,
        ]);

        // Fire InvoicePaid event
        InvoicePaid::dispatch($invoice->fresh());

        // Notify all the owner & admin workspace
        $invoice->workspace->workspaceMembers()
            ->whereIn('role', ['owner', 'admin'])
            ->with('user')
            ->get()
            ->each(fn ($member) =>
                $member->user->notify(new PaymentReceivedNotification($payment))
            );
    }
}
