<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'priority_label' => match($this->priority) {
                1 => 'low',
                2 => 'medium',
                3 => 'high',
                default => 'low',
            },
            'estimated_minutes' => $this->estimated_minutes,
            'total_tracked' => gmdate('H:i:s', $this->total_tracked_seconds),
            'due_date' => $this->due_date?->toDateString(),
            'assigned_to' => $this->when(
                $this->relationLoaded('assignedTo') && $this->assignedTo,
                fn () => [
                    'id'   => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                ]
            ),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
