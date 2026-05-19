<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncounterPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_pdf_of_completed_encounter(): void
    {
        $admin = User::factory()->role('admin')->create();
        $encounter = Encounter::factory()->completed()->create();
        Treatment::factory()->create(['encounter_id' => $encounter->id]);

        $response = $this->actingAs($admin)
            ->get(route('encounters.pdf', $encounter));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_pdf_blocked_for_in_progress_encounter(): void
    {
        $admin = User::factory()->role('admin')->create();
        $encounter = Encounter::factory()->create(['status' => 'in_progress']);

        $this->actingAs($admin)
            ->get(route('encounters.pdf', $encounter))
            ->assertForbidden();
    }

    public function test_pdf_blocked_for_receptionist(): void
    {
        $receptionist = User::factory()->role('receptionist')->create();
        $encounter = Encounter::factory()->completed()->create();

        $this->actingAs($receptionist)
            ->get(route('encounters.pdf', $encounter))
            ->assertForbidden();
    }
}
