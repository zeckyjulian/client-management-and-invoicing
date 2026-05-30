<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'company' => $this->company,
            'phone' => $this->phone,
            'address' => $this->address,
            'portal_active' => $this->portal_active,
            'projects_count' => $this->whenCounted('projects'),
            'projects' => ProjectResource::collection($this->whenLoaded('projects')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
