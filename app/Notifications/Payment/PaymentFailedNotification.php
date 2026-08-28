<?php

namespace App\Notifications\Payment;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Payment $payment
    )
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->payment->invoice;

        return (new MailMessage)
            ->subject("Payment Failed — Invoice #{$invoice->invoice_number}")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("Payment for Invoice #{$invoice->invoice_number} has failed.")
            ->line("Amount : {$invoice->workspace->currency} " . number_format($this->payment->amount, 2))
            ->line("Please contact your client to retry the payment.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_failed',
            'payment_id' => $this->payment->id,
            'invoice_id' => $this->payment->invoice_id,
            'invoice_number' => $this->payment->invoice->invoice_number,
            'amount' => $this->payment->amount,
        ];
    }
}
