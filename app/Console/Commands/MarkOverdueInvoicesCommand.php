<?php

namespace App\Console\Commands;

use App\Events\Invoice\InvoiceOverdue;
use App\Models\Invoice;
use Illuminate\Console\Command;

class MarkOverdueInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:mark-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark unpaid invoices past due date as overdue';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $count = 0;

        Invoice::withoutGlobalScopes()
            ->where('status', 'sent')
            ->whereDate('due_date', '<', today())
            ->chunkById(100, function ($invoices) use (&$count) {
                $invoices->each(function (Invoice $invoice) use (&$count) {
                    $invoice->update(['status' => 'overdue']);
                    InvoiceOverdue::dispatch($invoice);
                    $count++;
                });
            });

        $this->info("Marked {$count} invoice(s) as overdue.");
    }
}
