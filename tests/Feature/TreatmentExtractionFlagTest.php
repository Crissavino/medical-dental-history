<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreatmentExtractionFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_encounter_with_extraction_treatment_persists_the_flag(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $patient = Patient::factory()->create();

        $this->actingAs($dentist)->post(route('patient.encounters.store', $patient), [
            'encounter_date' => now()->toDateString(),
            'status' => 'in_progress',
            'treatments' => [[
                'tooth_number' => '18',
                'treatment_code' => 'D7140',
                'description' => 'Extraction',
                'status' => 'planned',
                'is_extraction' => true,
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('treatments', [
            'treatment_code' => 'D7140',
            'is_extraction' => true,
        ]);
    }

    public function test_updating_treatment_can_toggle_extraction_flag(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        $treatment = Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => false]);

        $this->actingAs($dentist)->put(route('encounters.update', $encounter), [
            'encounter_date' => $encounter->encounter_date->toDateString(),
            'status' => 'in_progress',
            'treatments' => [[
                'id' => $treatment->id,
                'tooth_number' => (string) $treatment->tooth_number,
                'treatment_code' => $treatment->treatment_code,
                'description' => $treatment->description,
                'status' => $treatment->status,
                'is_extraction' => true,
            ]],
        ])->assertRedirect();

        $this->assertTrue($treatment->fresh()->is_extraction);
    }
}
