<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'midtrans_order_id'      => $this->midtrans_order_id,
            'midtrans_transaction_id'=> $this->midtrans_transaction_id,
            'payment_type'           => $this->payment_type,
            'status'                 => $this->status,
            'amount'                 => $this->amount,
            'snap_redirect_url'      => $this->snap_redirect_url,
            'paid_at'                => $this->paid_at?->toDateTimeString(),
            'invoice'                => $this->when($this->relationLoaded('invoice'), [
                'id'             => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'total'          => $this->invoice->total,
            ]),
            'created_at'             => $this->created_at->toDateTimeString(),
        ];
    }
}
