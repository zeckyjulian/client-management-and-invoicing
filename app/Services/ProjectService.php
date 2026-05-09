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

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh();
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
