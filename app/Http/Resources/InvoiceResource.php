<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'issue_date' => $this->issue_date->toDateString(),
            'due_date' => $this->due_date->toDateString(),
            'subtotal' => $this->subtotal,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'discount' => $this->discount,
            'total' => $this->total,
            'notes' => $this->notes,
            'sent_at' => $this->sent_at?->toDateTimeString(),
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'is_editable' => $this->isEditable(),
            'client' => new ClientResource($this->whenLoaded('client')),
            'project' => $this->when($this->relationLoaded('project') && $this->project, [
                'id' => $this->project->id,
                'name' => $this->project->name
            ]),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
