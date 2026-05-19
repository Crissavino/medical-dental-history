<?php

namespace Database\Factories;

use App\Models\Encounter;
use Illuminate\Database\Eloquent\Factories\Factory;

class TreatmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'tooth_number' => fake()->numberBetween(11, 48),
            'treatment_code' => 'D' . fake()->numerify('####'),
            'description' => fake()->sentence(),
            'surface' => fake()->randomElement(['mesial', 'distal', 'occlusal', 'buccal', 'lingual']),
            'cost' => fake()->randomFloat(2, 50, 500),
            'status' => 'completed',
        ];
    }
}
