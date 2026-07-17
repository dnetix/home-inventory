<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Home;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
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
            'color' => fake()->hexColor(),
        ];
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn (): array => [
            'home_id' => $parent->home_id,
            'parent_id' => $parent->id,
        ]);
    }
}
