<?php

namespace Tests\Feature;

use App\Models\Encounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncounterLockdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_encounter_is_locked(): void
    {
        $encounter = Encounter::factory()->completed()->create();
        $this->assertTrue($encounter->isLocked());
    }

    public function test_in_progress_encounter_is_not_locked(): void
    {
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        $this->assertFalse($encounter->isLocked());
    }

    public function test_cancelled_encounter_is_locked_for_edits(): void
    {
        $encounter = Encounter::factory()->create(['status' => 'cancelled']);
        $this->assertTrue($encounter->isLocked());
    }

    public function test_rectifier_relation_returns_rectifying_encounter(): void
    {
        $original = Encounter::factory()->completed()->create();
        $rectifier = Encounter::factory()->create([
            'patient_id' => $original->patient_id,
            'provider_id' => $original->provider_id,
            'rectifies_encounter_id' => $original->id,
        ]);

        $this->assertTrue($original->rectifier->is($rectifier));
        $this->assertTrue($rectifier->rectifies->is($original));
    }

    public function test_completed_encounter_cannot_be_updated(): void
    {
        $admin = \App\Models\User::factory()->role('admin')->create();
        $encounter = Encounter::factory()->completed()->create();

        $this->actingAs($admin)
            ->put(route('encounters.update', $encounter), [
                'encounter_date' => '2026-01-01',
                'chief_complaint' => 'changed',
            ])
            ->assertForbidden();
    }

    public function test_completed_encounter_cannot_be_deleted(): void
    {
        $admin = \App\Models\User::factory()->role('admin')->create();
        $encounter = Encounter::factory()->completed()->create();

        $this->actingAs($admin)
            ->delete(route('encounters.destroy', $encounter))
            ->assertForbidden();
    }

    public function test_treatment_cannot_be_created_on_completed_encounter(): void
    {
        $admin = \App\Models\User::factory()->role('admin')->create();
        $encounter = Encounter::factory()->completed()->create();

        $this->actingAs($admin)
            ->post(route('treatments.store', $encounter), [
                'treatment_code' => 'D1234',
                'description' => 'late add',
            ])
            ->assertForbidden();
    }

    public function test_attachment_cannot_be_created_on_completed_encounter(): void
    {
        $admin = \App\Models\User::factory()->role('admin')->create();
        $encounter = Encounter::factory()->completed()->create();

        $file = \Illuminate\Http\UploadedFile::fake()->create('xray.png', 100, 'image/png');

        $this->actingAs($admin)
            ->post(route('attachments.store'), [
                'attachable_type' => 'encounter',
                'attachable_id' => $encounter->id,
                'file' => $file,
            ])
            ->assertForbidden();
    }
}
