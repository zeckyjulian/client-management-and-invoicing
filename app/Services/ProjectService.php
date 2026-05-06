<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Workspace;

class ProjectService
{
    public function create(Workspace $workspace, array $data): Project
    {
        return Project::create([
            ...$data,
            'workspace_id' => $workspace->id,
        ]);
    }
}
