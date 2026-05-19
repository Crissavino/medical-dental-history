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

    public function test_gdpr_export_zip_includes_clinical_history_pdf(): void
    {
        $admin = User::factory()->role('admin')->create();
        $patient = Patient::factory()->create();
        Encounter::factory()->completed()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($admin)
            ->get(route('patients.gdpr-export', $patient));

        $response->assertOk();

        // Capture the streamed body and inspect ZIP contents
        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'gdpr');
        file_put_contents($tmp, $body);

        $zip = new \ZipArchive();
        $zip->open($tmp);
        $found = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if ($zip->getNameIndex($i) === 'clinical-history.pdf') {
                $found = true;
                break;
            }
        }
        $zip->close();
        @unlink($tmp);

        $this->assertTrue($found, 'clinical-history.pdf missing from GDPR export zip');
    }
}
