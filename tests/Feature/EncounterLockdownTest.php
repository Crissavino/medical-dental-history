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
}
