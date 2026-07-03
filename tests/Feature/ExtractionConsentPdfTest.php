<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\ExtractionConsent;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtractionConsentPdfTest extends TestCase
{
    use RefreshDatabase;

    private string $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_dentist_can_download_extraction_consent_pdf(): void
    {
        $dentist = User::factory()->role('dentist')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);
        $consent = ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => 'Consent body text.',
            'language' => 'en',
            'patient_signature_data' => $this->pngBase64,
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
        ]);

        $response = $this->actingAs($dentist)
            ->get(route('extraction-consents.pdf', $consent));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_assistant_cannot_download_extraction_consent_pdf(): void
    {
        $assistant = User::factory()->role('assistant')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);
        $consent = ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => 'Consent body text.',
            'language' => 'en',
            'patient_signature_data' => $this->pngBase64,
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
        ]);

        $this->actingAs($assistant)
            ->get(route('extraction-consents.pdf', $consent))
            ->assertForbidden();
    }
}
