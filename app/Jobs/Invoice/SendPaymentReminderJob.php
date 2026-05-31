<?php

namespace App\Jobs\Invoice;

use App\Models\Invoice;
use App\Notifications\Invoice\InvoiceOverdueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly Invoice $invoice
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invoice = $this->invoice->load(['workspace.workspaceMembers.user']);

        $invoice->workspace->workspaceMembers()
            ->whereIn('role', ['owner', 'admin'])
            ->with('user')
            ->get()
            ->each(fn ($member) => 
                $member->user->notify(new InvoiceOverdueNotification($invoice))
            );
    }
}
