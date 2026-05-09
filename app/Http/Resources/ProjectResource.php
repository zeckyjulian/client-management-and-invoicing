<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'billing_type' => $this->billing_type,
            'budget' => $this->budget,
            'hourly_rate' => $this->hourly_rate,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'tasks_count' => $this->whenCounted('tasks'),
            'total_tracked' => gmdate('H:i:s', $this->total_tracked_seconds),
            'client' => new ClientResource($this->whenLoaded('client')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
