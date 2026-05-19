<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalHistoryPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_full_clinical_history_pdf(): void
    {
        $admin = User::factory()->role('admin')->create();
        $patient = Patient::factory()->create();
        $encounter = Encounter::factory()->completed()->create(['patient_id' => $patient->id]);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $response = $this->actingAs($admin)
            ->get(route('patients.clinical-history', $patient));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_receptionist_cannot_download_clinical_history(): void
    {
        $receptionist = User::factory()->role('receptionist')->create();
        $patient = Patient::factory()->create();

        $this->actingAs($receptionist)
            ->get(route('patients.clinical-history', $patient))
            ->assertForbidden();
    }
}
