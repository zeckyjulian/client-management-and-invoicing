<?php

namespace App\Console\Commands;

use App\Jobs\Invoice\SendPaymentReminderJob;
use App\Models\Invoice;
use Illuminate\Console\Command;

class SentPaymentRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send payment reminders for overdue invoices';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $count = 0;

        Invoice::withoutGlobalScopes()
            ->where('status', 'overdue')
            ->chunkById(100, function ($invoices) use (&$count) {
                $invoices->each(function (Invoice $invoice) use (&$count) {
                    SendPaymentReminderJob::dispatch($invoice)->onQueue('emails');
                    $count++;
                });
            });

        $this->info("Sent reminders for {$count} overdue invoice(s).");
    }
}
