<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role_id' => fn () => Role::firstOrCreate(['name' => RoleName::L1->value])->id,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function role(RoleName $role): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(['name' => $role->value])->id,
        ]);
    }

    public function admin(): static
    {
        return $this->role(RoleName::Admin);
    }

    public function l1(): static
    {
        return $this->role(RoleName::L1);
    }

    public function l2(): static
    {
        return $this->role(RoleName::L2);
    }

    public function viewer(): static
    {
        return $this->role(RoleName::Viewer);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
