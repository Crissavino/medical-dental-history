<?php

namespace Database\Seeders;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class EncounterSeeder extends Seeder
{
    public function run(): void
    {
        $dentists = User::where('role', 'dentist')->get();
        $patients = Patient::all();

        $complaints = [
            'Toothache in upper right area',
            'Routine checkup and cleaning',
            'Broken filling on molar',
            'Sensitivity to cold drinks',
            'Gum swelling and pain',
            'Chipped front tooth',
            'Jaw pain and clicking',
            'Teeth whitening consultation',
            'Crown replacement needed',
            'Wisdom tooth pain',
        ];

        $diagnoses = [
            'Dental caries - tooth 16',
            'Gingivitis - generalized',
            'Pulpitis - tooth 36',
            'Periodontitis - localized',
            'Fractured restoration - tooth 26',
            'Dentin hypersensitivity',
            'TMJ dysfunction',
            'Dental fluorosis',
            'Pericoronitis - tooth 48',
            'Healthy - no treatment needed',
        ];

        $statuses = ['completed', 'completed', 'completed', 'scheduled', 'in_progress'];

        foreach ($patients as $patientIndex => $patient) {
            $numEncounters = rand(2, 5);

            for ($i = 0; $i < $numEncounters; $i++) {
                $daysAgo = rand(1, 365);
                $status = $i === 0 && $patientIndex < 3 ? 'scheduled' : $statuses[array_rand($statuses)];

                Encounter::create([
                    'patient_id' => $patient->id,
                    'provider_id' => $dentists->random()->id,
                    'encounter_date' => $status === 'scheduled' ? now()->addDays(rand(1, 30)) : now()->subDays($daysAgo),
                    'chief_complaint' => $complaints[array_rand($complaints)],
                    'clinical_notes' => $status !== 'scheduled' ? 'Patient examined. ' . fake()->sentence(10) : null,
                    'diagnosis' => $status === 'completed' ? $diagnoses[array_rand($diagnoses)] : null,
                    'status' => $status,
                ]);
            }
        }
    }
}
