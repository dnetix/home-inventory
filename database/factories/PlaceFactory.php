<?php

namespace Database\Factories;

use App\Models\Home;
use App\Models\Place;
use App\Support\Dimensions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Place>
 */
class PlaceFactory extends Factory
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
            'parent_id' => null,
            'label' => ucfirst(fake()->unique()->word()),
            'glyph' => 'box',
            'description' => null,
            'dim' => null,
        ];
    }

    public function withDimensions(?Dimensions $dim = null): static
    {
        return $this->state(fn (): array => [
            'dim' => $dim ?? new Dimensions(
                fake()->numberBetween(300, 3000),
                fake()->numberBetween(300, 3000),
                fake()->numberBetween(300, 3000),
            ),
        ]);
    }

    public function childOf(Place $parent): static
    {
        return $this->state(fn (): array => [
            'home_id' => $parent->home_id,
            'parent_id' => $parent->id,
        ]);
    }
}
