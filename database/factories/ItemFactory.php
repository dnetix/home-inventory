<?php

namespace Database\Factories;

use App\Enums\ItemStatus;
use App\Models\Home;
use App\Models\Item;
use App\Support\Dimensions;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
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
            'category_id' => null,
            'place_id' => null,
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'value' => null,
            'qty' => 1,
            'dim' => null,
            'note' => null,
            'photo_path' => null,
        ];
    }

    public function missing(): static
    {
        return $this->state(fn (): array => ['status' => ItemStatus::Missing]);
    }

    public function broken(): static
    {
        return $this->state(fn (): array => ['status' => ItemStatus::Broken]);
    }

    public function removed(): static
    {
        return $this->state(fn (): array => ['status' => ItemStatus::Removed]);
    }

    public function valued(?Money $value = null): static
    {
        return $this->state(fn (): array => [
            'value' => $value ?? new Money(fake()->numberBetween(500, 100_000)),
        ]);
    }

    public function withDimensions(?Dimensions $dim = null): static
    {
        return $this->state(fn (): array => [
            'dim' => $dim ?? new Dimensions(
                fake()->numberBetween(50, 800),
                fake()->numberBetween(50, 800),
                fake()->numberBetween(50, 800),
            ),
        ]);
    }
}
