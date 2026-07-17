<?php

namespace Database\Factories;

use App\Models\Home;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
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
            'label' => fake()->unique()->word(),
            'color' => fake()->hexColor(),
        ];
    }
}
