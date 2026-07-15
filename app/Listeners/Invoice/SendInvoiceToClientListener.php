<?php

namespace App\Listeners\Invoice;

use App\Events\Invoice\InvoiceSent;
use App\Jobs\Invoice\SendInvoiceEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendInvoiceToClientListener
{
    public string $queue = 'emails';

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
    public function handle(InvoiceSent $event): void
    {
        SendInvoiceEmailJob::dispatch($event->invoice)->onQueue('emails');
    }
}
