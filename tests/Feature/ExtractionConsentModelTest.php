<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\ExtractionConsent;
use App\Models\Treatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtractionConsentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_unconsented_extractions_is_true_when_extraction_treatment_has_no_consent(): void
    {
        $encounter = Encounter::factory()->create();
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);

        $this->assertTrue($encounter->fresh()->hasUnconsentedExtractions());
    }

    public function test_has_unconsented_extractions_is_false_without_extraction_treatments(): void
    {
        $encounter = Encounter::factory()->create();
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => false]);

        $this->assertFalse($encounter->fresh()->hasUnconsentedExtractions());
    }

    public function test_has_unconsented_extractions_is_false_once_consent_exists(): void
    {
        $encounter = Encounter::factory()->create();
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);
        ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => 'text',
            'language' => 'en',
            'patient_signature_data' => 'data:image/png;base64,abc',
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
        ]);

        $this->assertFalse($encounter->fresh()->hasUnconsentedExtractions());
    }

    public function test_extraction_consent_belongs_to_encounter_and_recorder(): void
    {
        $encounter = Encounter::factory()->create();
        $consent = ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => 'text',
            'language' => 'en',
            'patient_signature_data' => 'data:image/png;base64,abc',
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
        ]);

        $this->assertTrue($consent->encounter->is($encounter));
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $consent->signed_at);
    }
}
