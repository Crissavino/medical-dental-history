<?php

namespace Database\Seeders;

use App\Models\AnamnesisVersion;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnamnesisSeeder extends Seeder
{
    public function run(): void
    {
        $dentist = User::where('role', 'dentist')->first();
        $patients = Patient::all();

        $defaultDiseases = [
            'heart' => [
                'angina_pectoris' => false, 'myocardial_infarction' => false,
                'arrhythmias' => false, 'blocks' => false, 'heart_failure' => false,
                'heart_failure_nyha' => '', 'valvulopathies' => false,
                'endocarditis' => false, 'cardiac_surgery' => false,
            ],
            'vascular' => [
                'peripheral_arterial_disease' => false, 'thrombophlebitis' => false,
                'hypotension' => false, 'hypertension' => false,
                'hypertension_max' => '', 'stroke' => false,
            ],
            'respiratory' => [
                'asthma' => false, 'emphysema' => false,
                'chronic_bronchitis' => false, 'tuberculosis' => false,
            ],
            'digestive' => ['gastritis_ulcer' => false],
            'hepatic' => ['steatosis' => false, 'chronic_hepatitis' => false, 'cirrhosis' => false],
            'renal' => ['insufficiency' => false, 'hemodialysis' => false],
            'diabetes' => ['insulin_dependent' => false, 'oral_antidiabetics' => false],
            'endocrine' => ['hypothyroidism' => false, 'hyperthyroidism' => false],
            'rheumatic' => ['rheumatoid_arthritis' => false, 'collagenoses' => false],
            'skeletal' => ['osteoporosis' => false],
            'neurological' => ['epilepsy' => false],
            'psychiatric' => ['depression' => false, 'schizophrenia' => false],
            'neurovegetative' => ['panic_attacks' => false],
            'hematologic' => [
                'anemia' => false, 'thalassemia' => false, 'leukemia' => false,
                'hemophilia' => false, 'thrombocytopenia' => false, 'von_willebrand' => false,
            ],
            'infectious' => [
                'hepatitis_b' => false, 'hepatitis_c' => false,
                'hepatitis_d' => false, 'hiv' => false,
            ],
            'neoplasms' => false, 'neoplasms_details' => '',
            'other_diseases' => false, 'other_diseases_details' => '',
        ];

        $sampleAnamneses = [
            [
                'special_situations' => ['pregnant' => false, 'menstruating' => false, 'gestational_age' => ''],
                'allergies' => ['drug_allergies' => ['Penicillin'], 'non_drug_allergies' => []],
                'current_treatment' => [
                    'medications' => [['name' => 'Lisinopril', 'dose' => '10mg']],
                    'antibiotics_last_2_weeks' => false, 'antibiotic_details' => [],
                    'anticoagulants' => false, 'anticoagulant_inr' => '',
                    'bisphosphonates' => false, 'bisphosphonate_route' => '',
                    'bisphosphonate_duration' => '', 'bisphosphonate_beta_ctx' => '',
                ],
                'diseases' => array_replace_recursive($defaultDiseases, [
                    'vascular' => ['hypertension' => true, 'hypertension_max' => '160/95'],
                ]),
                'surgical_history' => ['previous_surgeries' => '', 'transfusions' => false, 'surgery_complications' => ''],
                'dental_history' => [
                    'anesthesia_types' => ['local' => true, 'plexal' => false, 'troncular' => false, 'general' => false],
                    'adverse_reactions' => '',
                ],
                'habits' => [
                    'tobacco' => false, 'tobacco_amount' => '', 'tobacco_duration' => '',
                    'alcohol' => false, 'alcohol_amount' => '', 'alcohol_duration' => '',
                    'drugs' => false, 'drugs_amount' => '', 'drugs_duration' => '',
                ],
            ],
            [
                'special_situations' => ['pregnant' => false, 'menstruating' => false, 'gestational_age' => ''],
                'allergies' => ['drug_allergies' => [], 'non_drug_allergies' => []],
                'current_treatment' => [
                    'medications' => [['name' => 'Metformin', 'dose' => '500mg']],
                    'antibiotics_last_2_weeks' => false, 'antibiotic_details' => [],
                    'anticoagulants' => false, 'anticoagulant_inr' => '',
                    'bisphosphonates' => false, 'bisphosphonate_route' => '',
                    'bisphosphonate_duration' => '', 'bisphosphonate_beta_ctx' => '',
                ],
                'diseases' => array_replace_recursive($defaultDiseases, [
                    'diabetes' => ['oral_antidiabetics' => true],
                ]),
                'surgical_history' => ['previous_surgeries' => 'Appendectomy 2018', 'transfusions' => false, 'surgery_complications' => ''],
                'dental_history' => [
                    'anesthesia_types' => ['local' => true, 'plexal' => true, 'troncular' => false, 'general' => false],
                    'adverse_reactions' => '',
                ],
                'habits' => [
                    'tobacco' => true, 'tobacco_amount' => '10', 'tobacco_duration' => '5',
                    'alcohol' => true, 'alcohol_amount' => '4', 'alcohol_duration' => '10',
                    'drugs' => false, 'drugs_amount' => '', 'drugs_duration' => '',
                ],
            ],
            [
                'special_situations' => ['pregnant' => false, 'menstruating' => false, 'gestational_age' => ''],
                'allergies' => ['drug_allergies' => ['Lidocaine'], 'non_drug_allergies' => ['Latex']],
                'current_treatment' => [
                    'medications' => [],
                    'antibiotics_last_2_weeks' => false, 'antibiotic_details' => [],
                    'anticoagulants' => false, 'anticoagulant_inr' => '',
                    'bisphosphonates' => false, 'bisphosphonate_route' => '',
                    'bisphosphonate_duration' => '', 'bisphosphonate_beta_ctx' => '',
                ],
                'diseases' => $defaultDiseases,
                'surgical_history' => ['previous_surgeries' => '', 'transfusions' => false, 'surgery_complications' => ''],
                'dental_history' => [
                    'anesthesia_types' => ['local' => true, 'plexal' => false, 'troncular' => false, 'general' => false],
                    'adverse_reactions' => 'Dizziness after local anesthesia',
                ],
                'habits' => [
                    'tobacco' => false, 'tobacco_amount' => '', 'tobacco_duration' => '',
                    'alcohol' => false, 'alcohol_amount' => '', 'alcohol_duration' => '',
                    'drugs' => false, 'drugs_amount' => '', 'drugs_duration' => '',
                ],
            ],
            [
                'special_situations' => ['pregnant' => true, 'menstruating' => false, 'gestational_age' => '24'],
                'allergies' => ['drug_allergies' => [], 'non_drug_allergies' => ['Nickel']],
                'current_treatment' => [
                    'medications' => [['name' => 'Prenatal vitamins', 'dose' => '1/day']],
                    'antibiotics_last_2_weeks' => false, 'antibiotic_details' => [],
                    'anticoagulants' => false, 'anticoagulant_inr' => '',
                    'bisphosphonates' => false, 'bisphosphonate_route' => '',
                    'bisphosphonate_duration' => '', 'bisphosphonate_beta_ctx' => '',
                ],
                'diseases' => array_replace_recursive($defaultDiseases, [
                    'respiratory' => ['asthma' => true],
                ]),
                'surgical_history' => ['previous_surgeries' => '', 'transfusions' => false, 'surgery_complications' => ''],
                'dental_history' => [
                    'anesthesia_types' => ['local' => true, 'plexal' => false, 'troncular' => true, 'general' => false],
                    'adverse_reactions' => '',
                ],
                'habits' => [
                    'tobacco' => false, 'tobacco_amount' => '', 'tobacco_duration' => '',
                    'alcohol' => false, 'alcohol_amount' => '', 'alcohol_duration' => '',
                    'drugs' => false, 'drugs_amount' => '', 'drugs_duration' => '',
                ],
            ],
        ];

        foreach ($patients as $index => $patient) {
            $formData = $sampleAnamneses[$index % count($sampleAnamneses)];

            AnamnesisVersion::create([
                'patient_id' => $patient->id,
                'version' => 1,
                'form_data' => $formData,
                'consent_given' => true,
                'consent_given_at' => now()->subMonths(rand(1, 12)),
                'consent_ip' => '192.168.1.' . rand(1, 254),
                'language' => $index % 3 === 0 ? 'ro' : 'en',
                'recorded_by' => $dentist->id,
            ]);

            // Add a second version for some patients
            if ($index % 3 === 0) {
                $updatedFormData = $formData;
                $updatedFormData['current_treatment']['medications'][] = ['name' => 'Ibuprofen', 'dose' => '400mg'];

                AnamnesisVersion::create([
                    'patient_id' => $patient->id,
                    'version' => 2,
                    'form_data' => $updatedFormData,
                    'consent_given' => true,
                    'consent_given_at' => now()->subWeeks(rand(1, 4)),
                    'consent_ip' => '192.168.1.' . rand(1, 254),
                    'language' => 'en',
                    'recorded_by' => $dentist->id,
                ]);
            }
        }
    }
}
