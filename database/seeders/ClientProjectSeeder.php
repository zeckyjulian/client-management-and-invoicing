<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workspace = Workspace::first();

        $client = Client::create([
            'workspace_id' => $workspace->id,
            'name' => 'Forward Together Corporation',
            'email' => 'contact@forwardtogether.com',
            'company' => 'Forward Together Corporation',
            'phone' => '555-123-4567',
        ]);

        $project = Project::create([
            'workspace_id' => $workspace->id,
            'client_id' => $client->id,
            'name' => 'Website Redesign',
            'description' => 'Redesign the corporate website for better user experience.',
            'billing_type' => 'hourly',
            'hourly_rate' => 100,
            'status' => 'active',
            'start_date' => now(),
        ]);

        $project->tasks()->createMany([
            ['title' => 'Design mockup', 'status' => 'done', 'priority' => 2],
            ['title' => 'Frontend dev', 'status' => 'in_progress', 'priority' => 2],
            ['title' => 'Backend API', 'status' => 'todo', 'priority' => 1],
            ['title' => 'Testing & QA', 'status' => 'todo', 'priority' => 0],
        ]);
    }
}
