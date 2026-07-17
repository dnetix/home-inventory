<?php

namespace Database\Factories;

use App\Models\Home;
use App\Models\UpkeepLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UpkeepLog>
 */
class UpkeepLogFactory extends Factory
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
            'upkeep_task_id' => null,
            'user_id' => null,
            'task' => fake()->sentence(3),
            'completed_on' => fake()->dateTimeBetween('-2 months', 'now'),
        ];
    }
}
