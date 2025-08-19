<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionPool>
 */
class QuestionPoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->word),
            'slug' => Str::slug($this->faker->unique()->sentence),
            'intended_phase' => $this->faker->randomElement(['any', 'phase1', 'phase2', 'phase3']),
            'meta' => [],
        ];
    }
}
