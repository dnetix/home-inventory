<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Lend;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lend>
 */
class LendFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'home_id' => fn (array $attributes) => Item::withoutGlobalScope('home')->find($attributes['item_id'])->home_id,
            'person' => fake()->firstName(),
            'out_date' => fake()->dateTimeBetween('-3 weeks', '-1 week'),
            'due_date' => fake()->dateTimeBetween('+3 days', '+3 weeks'),
            'remind' => false,
            'returned_at' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'due_date' => fake()->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn (): array => [
            'returned_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function noDueDate(): static
    {
        return $this->state(fn (): array => [
            'due_date' => null,
        ]);
    }
}
