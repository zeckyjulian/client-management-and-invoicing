<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeEntryResource extends JsonResource
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
            'description' => $this->description,
            'started_at' => $this->started_at->toDateTimeString(),
            'ended_at' => $this->ended_at?->toDateTimeString(),
            'duration' => $this->duration_formatted,
            'duration_seconds' => $this->duration_seconds,
            'billable' => $this->billable,
            'hourly_rate' => $this->hourly_rate,
            'billable_amount' => $this->billable_amount,
            'is_running' => is_null($this->ended_at),
            'project' => $this->when($this->relationLoaded('project'), [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ]),
            'task' => $this->when($this->relationLoaded('task') && $this->task,
                fn () => [
                    'id' => $this->task->id,
                    'name' => $this->task->name,
                ]
            ),
            'user' => $this->when($this->relationLoaded('user'), [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
