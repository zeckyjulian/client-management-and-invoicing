<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
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
            'email' => $this->email,
            'avatar' => $this->avatar,
            'is_super_admin' => $this->is_super_admin,
            'workspaces_count' => $this->whenCounted('workspaces'),
            'workspaces' => $this->when(
                $this->relationLoaded('workspaces'),
                fn () => $this->workspaces->map(fn ($ws) => [
                    'id' => $ws->id,
                    'name' => $ws->name,
                    'plan' => $ws->plan,
                    'role' => $ws->pivot->role,
                ])
            ),
            'created_at' => $this->created_at->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}
