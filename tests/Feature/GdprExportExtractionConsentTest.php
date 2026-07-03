<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\ExtractionConsent;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class GdprExportExtractionConsentTest extends TestCase
{
    use RefreshDatabase;

    private string $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_export_includes_extraction_consent_pdf_when_present(): void
    {
        $admin = User::factory()->role('admin')->create();
        $patient = Patient::factory()->create();
        $encounter = Encounter::factory()->create(['patient_id' => $patient->id, 'status' => 'in_progress']);
        Treatment::factory()->create(['encounter_id' => $encounter->id, 'is_extraction' => true]);
        ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => 'Consent body text.',
            'language' => 'en',
            'patient_signature_data' => $this->pngBase64,
            'signed_at' => now(),
            'signed_ip' => '127.0.0.1',
            'recorded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('patients.gdpr-export', $patient));
        $response->assertOk();

        $tmpPath = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tmpPath, $response->streamedContent());

        $zip = new ZipArchive();
        $zip->open($tmpPath);
        $this->assertNotFalse($zip->locateName("extraction-consents/encounter-{$encounter->id}.pdf"));
        $zip->close();
        unlink($tmpPath);
    }

    public function test_export_has_no_extraction_consents_entry_when_none_signed(): void
    {
        $admin = User::factory()->role('admin')->create();
        $patient = Patient::factory()->create();
        Encounter::factory()->create(['patient_id' => $patient->id, 'status' => 'in_progress']);

        $response = $this->actingAs($admin)->get(route('patients.gdpr-export', $patient));
        $response->assertOk();

        $tmpPath = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tmpPath, $response->streamedContent());

        $zip = new ZipArchive();
        $zip->open($tmpPath);
        $this->assertSame(0, $zip->numFiles - $zip->numFiles + ($zip->locateName('extraction-consents/') === false ? 0 : 1));
        $zip->close();
        unlink($tmpPath);
    }
}
