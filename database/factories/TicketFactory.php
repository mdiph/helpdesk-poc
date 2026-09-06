<?php

namespace Database\Factories;

use App\Enums\SupportTier;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'category_id' => fn () => Category::factory(),
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'status' => TicketStatus::Open,
            'support_tier' => SupportTier::L1,
            'created_by' => fn () => User::factory()->l1(),
            'assigned_to' => null,
        ];
    }

    public function escalated(): static
    {
        return $this->state(fn () => [
            'support_tier' => SupportTier::L2,
            'escalated_at' => now(),
        ]);
    }

    public function status(TicketStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn () => ['assigned_to' => $user->id]);
    }
}
