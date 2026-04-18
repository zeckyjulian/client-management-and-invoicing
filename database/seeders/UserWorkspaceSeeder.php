<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserWorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'managementadmin@gmail.com',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
        ]);

        $owner = User::create([
            'name' => 'Supriyono',
            'email' => 'supriyono@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $workspace = Workspace::create([
            'name' => 'Supriyono Workspace',
            'slug' => 'supriyono-workspace',
            'plan' => 'pro',
            'currency' => 'IDR',
            'settings' => [],
        ]);

        $workspace->workspaceMembers()->create([
            'user_id' => $owner->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $member = User::create([
            'name' => 'Diana',
            'email' => 'diana@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $workspace->workspaceMembers()->create([
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }
}
