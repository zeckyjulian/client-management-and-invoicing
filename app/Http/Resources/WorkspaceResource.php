<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceResource extends JsonResource
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
            'slug' => $this->slug,
            'plan' => $this->plan,
            'currency' => $this->currency,
            'settings' => $this->settings,
            'members_count' => $this->whenCounted('workspaceMembers'),
            'my_role' => $this->when(
                isset($this->pivot),
                fn () => $this->pivot?->role
            ),
            'members' => WorkspaceMemberResource::collection(
                $this->whenLoaded('workspaceMembers')
            ),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
