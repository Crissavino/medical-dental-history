<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncounterRectificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dentist_can_create_rectifier_for_completed_encounter(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $original = Encounter::factory()->completed()->create();
        Treatment::factory()->count(2)->create(['encounter_id' => $original->id]);

        $response = $this->actingAs($dentist)
            ->post(route('encounters.rectify', $original));

        $response->assertRedirect();
        $rectifier = Encounter::where('rectifies_encounter_id', $original->id)->first();

        $this->assertNotNull($rectifier);
        $this->assertSame($original->patient_id, $rectifier->patient_id);
        $this->assertSame('in_progress', $rectifier->status);
        $this->assertSame($original->chief_complaint, $rectifier->chief_complaint);
        $this->assertCount(2, $rectifier->treatments);
    }

    public function test_rectification_writes_audit_log_on_original(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $original = Encounter::factory()->completed()->create();

        $this->actingAs($dentist)->post(route('encounters.rectify', $original));

        $log = AuditLog::where('entity_type', Encounter::class)
            ->where('entity_id', $original->id)
            ->where('action', 'rectified')
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('rectified_by_encounter_id', $log->metadata_json);
    }

    public function test_cannot_rectify_in_progress_encounter(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);

        $this->actingAs($dentist)
            ->post(route('encounters.rectify', $encounter))
            ->assertForbidden();
    }

    public function test_receptionist_cannot_rectify(): void
    {
        $receptionist = User::factory()->role('receptionist')->create();
        $encounter = Encounter::factory()->completed()->create();

        $this->actingAs($receptionist)
            ->post(route('encounters.rectify', $encounter))
            ->assertForbidden();
    }
}
