<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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
            'category' => $this->category,
            'description' => $this->description,
            'amount' => $this->amount,
            'billable' => $this->billable,
            'receipt_url' => $this->receipt_url,
            'expense_date' => $this->expense_date->toDateString(),
            'project' => $this->when($this->relationLoaded('project') && $this->project, [
                'id' => $this->project->id,
                'name' => $this->project->name
            ]),
            'user' => $this->when($this->relationLoaded('user'), [
                'id' => $this->user->id,
                'name' => $this->user->name
            ]),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
