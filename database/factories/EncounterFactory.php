<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'provider_id' => User::factory()->role('dentist'),
            'encounter_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'chief_complaint' => fake()->sentence(),
            'clinical_notes' => fake()->paragraph(),
            'diagnosis' => fake()->sentence(),
            'status' => 'in_progress',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'patient_signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
            'dentist_signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
            'patient_signed_at' => now(),
            'dentist_signed_at' => now(),
            'signed_ip' => '127.0.0.1',
            'signed_hash' => str_repeat('a', 64),
        ]);
    }
}
