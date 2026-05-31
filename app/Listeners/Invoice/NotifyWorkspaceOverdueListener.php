<?php

namespace App\Listeners\Invoice;

use App\Events\Invoice\InvoiceOverdue;
use App\Notifications\Invoice\InvoiceOverdueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyWorkspaceOverdueListener
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
    public function handle(InvoiceOverdue $event): void
    {
        $invoice = $event->invoice->load('workspace.workspaceMembers.user');

        $invoice->workspace->workspaceMembers()
            ->whereIn('role', ['owner', 'admin'])
            ->with('user')
            ->get()
            ->each(fn ($member) => 
                $member->user->notify(new InvoiceOverdueNotification($invoice))
            );
    }
}
