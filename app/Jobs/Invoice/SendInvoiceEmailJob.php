<?php

namespace App\Jobs\Invoice;

use App\Models\Invoice;
use App\Notifications\Invoice\InvoiceSentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

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
        $invoice = $this->invoice->load(['client', 'workspace', 'items']);

        // Notify the workspace owner that the invoice has been sent
        $invoice->workspace->workspaceMembers()
            ->where('role', 'owner')
            ->with('user')
            ->get()
            ->each(fn ($member) => 
                $member->user->notify(new InvoiceSentNotification($invoice))
            );
    }

    public function failed(Throwable $e): void
    {
        Log::error("Failed to send invoice email for invoice {$this->invoice->id}", [
            'error' => $e->getMessage(),
        ]);
    }
}
