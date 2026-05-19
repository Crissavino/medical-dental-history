<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncounterSigningTest extends TestCase
{
    use RefreshDatabase;

    private string $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_dentist_can_sign_in_progress_encounter(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $response = $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ]);

        $response->assertRedirect(route('encounters.show', $encounter));

        $encounter->refresh();
        $this->assertSame('completed', $encounter->status);
        $this->assertSame($this->pngBase64, $encounter->patient_signature_data);
        $this->assertSame($this->pngBase64, $encounter->dentist_signature_data);
        $this->assertSame($dentist->id, $encounter->dentist_signed_by);
        $this->assertNotNull($encounter->patient_signed_at);
        $this->assertNotNull($encounter->dentist_signed_at);
        $this->assertNotNull($encounter->signed_ip);
        $this->assertSame(64, strlen($encounter->signed_hash));
    }

    public function test_signing_creates_explicit_audit_log_entry(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ]);

        $log = AuditLog::where('entity_type', Encounter::class)
            ->where('entity_id', $encounter->id)
            ->where('action', 'signed')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($dentist->id, $log->user_id);
    }

    public function test_signing_fails_when_patient_signature_missing(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertSessionHasErrors(['patient_signature_data']);
    }

    public function test_signing_fails_when_encounter_not_in_progress(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'scheduled']);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertForbidden();
    }

    public function test_signing_fails_for_receptionist(): void
    {
        $receptionist = User::factory()->role('receptionist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $this->actingAs($receptionist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertForbidden();
    }

    public function test_signing_fails_when_no_treatments(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertSessionHasErrors();
    }

    public function test_cannot_sign_already_completed_encounter(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        // First sign (succeeds)
        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])->assertRedirect();

        // Second sign (rejected — status is now 'completed', authorize() returns false)
        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])->assertForbidden();
    }

    public function test_dentist_can_sign_with_stored_signature(): void
    {
        $dentist = User::factory()->role('dentist')->create([
            'signature_data' => $this->pngBase64,
        ]);
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'use_stored_dentist_signature' => true,
            ])
            ->assertRedirect();

        $encounter->refresh();
        $this->assertSame($this->pngBase64, $encounter->dentist_signature_data);
    }
}
