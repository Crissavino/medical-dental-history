<?php

namespace Database\Seeders;

use App\Models\Encounter;
use App\Models\Treatment;
use Illuminate\Database\Seeder;

class TreatmentSeeder extends Seeder
{
    public function run(): void
    {
        $treatments = [
            ['code' => 'EX001', 'description' => 'Oral examination', 'cost' => 50.00],
            ['code' => 'CLN01', 'description' => 'Professional cleaning / scaling', 'cost' => 120.00],
            ['code' => 'FIL01', 'description' => 'Composite filling', 'cost' => 150.00],
            ['code' => 'FIL02', 'description' => 'Amalgam filling', 'cost' => 100.00],
            ['code' => 'RCT01', 'description' => 'Root canal treatment', 'cost' => 450.00],
            ['code' => 'EXT01', 'description' => 'Simple extraction', 'cost' => 200.00],
            ['code' => 'EXT02', 'description' => 'Surgical extraction', 'cost' => 350.00],
            ['code' => 'CRN01', 'description' => 'Porcelain crown', 'cost' => 600.00],
            ['code' => 'CRN02', 'description' => 'Zirconia crown', 'cost' => 800.00],
            ['code' => 'XRY01', 'description' => 'Dental X-ray (periapical)', 'cost' => 30.00],
            ['code' => 'XRY02', 'description' => 'Panoramic X-ray', 'cost' => 80.00],
            ['code' => 'WHT01', 'description' => 'Teeth whitening session', 'cost' => 250.00],
            ['code' => 'VNR01', 'description' => 'Porcelain veneer', 'cost' => 700.00],
            ['code' => 'IMP01', 'description' => 'Dental implant placement', 'cost' => 1500.00],
            ['code' => 'PRO01', 'description' => 'Complete denture', 'cost' => 900.00],
        ];

        $teeth = ['11', '12', '13', '14', '15', '16', '17', '18', '21', '22', '23', '24', '25', '26', '27', '28', '31', '32', '33', '34', '35', '36', '37', '38', '41', '42', '43', '44', '45', '46', '47', '48'];
        $surfaces = ['mesial', 'distal', 'buccal', 'lingual', 'occlusal', null, null];
        $statuses = ['planned', 'in_progress', 'completed', 'completed', 'completed'];

        $encounters = Encounter::where('status', '!=', 'scheduled')->get();

        foreach ($encounters as $encounter) {
            $numTreatments = rand(1, 4);

            for ($i = 0; $i < $numTreatments; $i++) {
                $treatment = $treatments[array_rand($treatments)];

                Treatment::create([
                    'encounter_id' => $encounter->id,
                    'tooth_number' => in_array($treatment['code'], ['EX001', 'CLN01', 'XRY02', 'WHT01', 'PRO01']) ? null : $teeth[array_rand($teeth)],
                    'treatment_code' => $treatment['code'],
                    'description' => $treatment['description'],
                    'notes' => rand(0, 1) ? fake()->sentence() : null,
                    'surface' => in_array($treatment['code'], ['FIL01', 'FIL02']) ? $surfaces[array_rand($surfaces)] : null,
                    'cost' => $treatment['cost'],
                    'status' => $encounter->status === 'completed' ? 'completed' : $statuses[array_rand($statuses)],
                ]);
            }
        }
    }
}
