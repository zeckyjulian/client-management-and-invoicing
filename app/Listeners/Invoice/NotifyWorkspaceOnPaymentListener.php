<?php

namespace App\Listeners\Invoice;

use App\Events\Invoice\InvoicePaid;
use App\Notifications\Invoice\InvoicePaidNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyWorkspaceOnPaymentListener
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
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice->load('workspace.workspaceMembers.user');

        // Notify all owner & admin workspace
        $invoice->workspace->workspaceMembers()
            ->whereIn('role', ['owner', 'admin'])
            ->with('user')
            ->get()
            ->each(fn ($member) => 
                $member->user->notify(new InvoicePaidNotification($invoice))
            );
    }
}
