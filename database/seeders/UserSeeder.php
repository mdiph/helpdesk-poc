<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id', 'name');

        // The initial administrator is configured through environment
        // variables. firstOrCreate is used (not updateOrCreate) so this
        // seeder is safe to re-run: it never overwrites an existing
        // account's password or name.
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role_id' => $roles[RoleName::Admin->value],
                'is_active' => true,
            ]
        );

        // Demo agents / viewer are only created outside production so a fresh
        // production database contains nothing but the configured admin.
        if (app()->environment('production')) {
            return;
        }

        $demo = [
            ['L1 Agent', 'l1@example.com', RoleName::L1],
            ['L2 Agent', 'l2@example.com', RoleName::L2],
            ['Read Only', 'viewer@example.com', RoleName::Viewer],
        ];

        foreach ($demo as [$name, $email, $role]) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role_id' => $roles[$role->value],
                    'is_active' => true,
                ]
            );
        }
    }
}
