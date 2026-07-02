<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\ExtractionConsent;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtractionConsentBlocksEncounterSignTest extends TestCase
{
    use RefreshDatabase;

    private string $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_signing_encounter_fails_when_extraction_treatment_has_no_consent(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertSessionHasErrors(['extraction_consent']);

        $this->assertSame('in_progress', $encounter->fresh()->status);
    }

    public function test_signing_encounter_succeeds_once_extraction_consent_exists(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);
        ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => 'text',
            'language' => 'en',
            'patient_signature_data' => $this->pngBase64,
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
        ]);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertRedirect(route('encounters.show', $encounter));

        $this->assertSame('completed', $encounter->fresh()->status);
    }

    public function test_signing_encounter_unaffected_when_no_extraction_treatments(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => false]);

        $this->actingAs($dentist)
            ->post(route('encounters.sign', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'dentist_signature_data' => $this->pngBase64,
            ])
            ->assertRedirect(route('encounters.show', $encounter));
    }
}
