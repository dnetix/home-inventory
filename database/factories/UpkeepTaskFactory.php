<?php

namespace Database\Factories;

use App\Enums\UpkeepKind;
use App\Models\Home;
use App\Models\UpkeepTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UpkeepTask>
 */
class UpkeepTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'home_id' => Home::factory(),
            'item_id' => null,
            'subject' => ucfirst(fake()->word()),
            'kind' => UpkeepKind::Maint,
            'task' => fake()->sentence(3),
            'due_date' => fake()->dateTimeBetween('+8 days', '+2 months'),
            'every' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'due_date' => fake()->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    public function dueSoon(): static
    {
        return $this->state(fn (): array => [
            'due_date' => fake()->dateTimeBetween('+1 day', '+6 days'),
        ]);
    }

    public function expiry(): static
    {
        return $this->state(fn (): array => [
            'kind' => UpkeepKind::Expiry,
        ]);
    }

    public function recurring(string $every = 'P3M'): static
    {
        return $this->state(fn (): array => [
            'every' => $every,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (): array => [
            'due_date' => null,
        ]);
    }
}
