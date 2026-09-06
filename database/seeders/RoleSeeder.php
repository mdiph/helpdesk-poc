<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $descriptions = [
            RoleName::Admin->value => 'Full system access: users, tickets, reports, configuration.',
            RoleName::L1->value => 'First-line support: create, update, assign and escalate tickets.',
            RoleName::L2->value => 'Second-line support: handle tickets escalated or assigned to L2.',
            RoleName::Viewer->value => 'Read-only access to tickets, dashboard and reports.',
        ];

        foreach ($descriptions as $name => $description) {
            Role::updateOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
