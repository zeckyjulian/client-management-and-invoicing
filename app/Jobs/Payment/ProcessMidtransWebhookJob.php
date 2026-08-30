<?php

namespace App\Jobs\Payment;

use App\Events\Payment\PaymentFailed;
use App\Events\Payment\PaymentReceived;
use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessMidtransWebhookJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly array $payload
    )
    {
        //
    }

    private function handleSuccess(Payment $payment): void
    {
        $payment->update([
            'status' => 'success',
            'paid_at' => now(),
        ]);

        PaymentReceived::dispatch($payment->fresh());
    }

    private function handleFailed(Payment $payment, string $status): void
    {
        $payment->update([
            'status' => match($status){
                'cancel' => 'canceled',
                'expire' => 'expired',
                default => 'failed',
            },
        ]);

        PaymentFailed::dispatch($payment->fresh());
    }

    public function failed(Throwable $e): void
    {
        Log::error('Failed to process Midtrans webhook for order: ' . ($this->payload['order_id'] ?? 'unknown'), [
            'error' => $e->getMessage(),
        ]);
    }

    private function processTransactionStatus(
        Payment $payment,
        string $transactionStatus,
        ?string $fraudStatus
    ): void {
        match(true) {
            // Success
            $transactionStatus === 'capture' && $fraudStatus === 'accept',
            $transactionStatus === 'settlement' => $this->handleSuccess($payment),

            // Pending
            $transactionStatus === 'pending' => $payment->update(['status' => 'pending']),

            // Failed
            $transactionStatus === 'deny',
            $transactionStatus === 'cancel',
            $transactionStatus === 'expire',
            $transactionStatus === 'failure' => $this->handleFailed($payment, $transactionStatus),

            // Fraud
            $transactionStatus === 'capture' && $fraudStatus === 'challenge'
                => $payment->update(['status' => 'pending']),

            default => Log::info("Unhandled transaction status: {$transactionStatus}")
        };
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $orderId = $this->payload['order_id'];
        $transactionStatus = $this->payload['transaction_status'];
        $fraudStatus = $this->payload['fraud_status'] ?? null;

        $payment = Payment::where('midtrans_order_id', $orderId)->first();

        if (!$payment) {
            Log::warning("Payment not found for order_id: {$orderId}");
            return;
        }

        // Save raw payload for audit trail
        $payment->update([
            'midtrans_transaction_id' => $this->payload['transaction_id'] ?? null,
            'payment_type' => $this->payload['payment_type'] ?? null,
            'midtrans_payload' => $this->payload,
        ]);

        // Determine the status based on the Midtrans response
        $this->processTransactionStatus($payment, $transactionStatus, $fraudStatus);
    }
}
