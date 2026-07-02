<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\ExtractionConsent;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtractionConsentTest extends TestCase
{
    use RefreshDatabase;

    private string $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_assistant_can_sign_extraction_consent_for_extraction_treatment(): void
    {
        $assistant = User::factory()->role('assistant')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);

        $response = $this->actingAs($assistant)
            ->post(route('extraction-consents.store', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'language' => 'es',
            ]);

        $response->assertRedirect(route('encounters.show', $encounter));

        $this->assertDatabaseHas('extraction_consents', [
            'encounter_id' => $encounter->id,
            'language' => 'es',
            'recorded_by' => $assistant->id,
        ]);
        $consent = ExtractionConsent::first();
        $this->assertNotEmpty($consent->consent_text);
        $this->assertNotNull($consent->signed_at);
        $this->assertNotNull($consent->signed_ip);
    }

    public function test_receptionist_cannot_sign_extraction_consent(): void
    {
        $receptionist = User::factory()->role('receptionist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);

        $this->actingAs($receptionist)
            ->post(route('extraction-consents.store', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'language' => 'es',
            ])
            ->assertForbidden();
    }

    public function test_cannot_sign_consent_without_extraction_treatment(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => false]);

        $this->actingAs($dentist)
            ->post(route('extraction-consents.store', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'language' => 'es',
            ])
            ->assertSessionHasErrors();
    }

    public function test_cannot_sign_consent_twice_for_same_encounter(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);

        $this->actingAs($dentist)
            ->post(route('extraction-consents.store', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'language' => 'es',
            ])->assertRedirect();

        $this->actingAs($dentist)
            ->post(route('extraction-consents.store', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'language' => 'es',
            ])->assertSessionHasErrors();
    }

    public function test_cannot_sign_consent_on_locked_encounter(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->completed()->create();
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);

        $this->actingAs($dentist)
            ->post(route('extraction-consents.store', $encounter), [
                'patient_signature_data' => $this->pngBase64,
                'language' => 'es',
            ])
            ->assertForbidden();
    }
}
