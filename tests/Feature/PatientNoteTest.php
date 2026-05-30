<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_add_a_note(): void
    {
        $user = User::factory()->role('receptionist')->create();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('patient-notes.store', $patient), [
                'body' => 'Patient prefers afternoon appointments.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('patient_notes', [
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'body' => 'Patient prefers afternoon appointments.',
        ]);
    }

    public function test_body_is_required(): void
    {
        $user = User::factory()->role('assistant')->create();
        $patient = Patient::factory()->create();

        $this->actingAs($user)
            ->post(route('patient-notes.store', $patient), ['body' => ''])
            ->assertSessionHasErrors('body');
    }

    public function test_user_cannot_edit_another_users_note(): void
    {
        $author = User::factory()->role('dentist')->create();
        $other = User::factory()->role('dentist')->create();
        $note = PatientNote::factory()->create(['user_id' => $author->id]);

        $this->actingAs($other)
            ->put(route('patient-notes.update', $note), ['body' => 'hijack'])
            ->assertForbidden();

        $this->assertDatabaseMissing('patient_notes', ['id' => $note->id, 'body' => 'hijack']);
    }

    public function test_author_can_edit_own_note(): void
    {
        $author = User::factory()->role('dentist')->create();
        $note = PatientNote::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author)
            ->put(route('patient-notes.update', $note), ['body' => 'updated'])
            ->assertRedirect();

        $this->assertDatabaseHas('patient_notes', ['id' => $note->id, 'body' => 'updated']);
    }

    public function test_admin_can_delete_any_note(): void
    {
        $admin = User::factory()->role('admin')->create();
        $note = PatientNote::factory()->create();

        $this->actingAs($admin)
            ->delete(route('patient-notes.destroy', $note))
            ->assertRedirect();

        $this->assertDatabaseMissing('patient_notes', ['id' => $note->id]);
    }

    public function test_show_returns_notes_prop_with_author(): void
    {
        $user = User::factory()->role('admin')->create();
        $patient = Patient::factory()->create();
        PatientNote::factory()->create(['patient_id' => $patient->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('patients.show', $patient))
            ->assertInertia(fn ($page) => $page
                ->component('Patients/Show')
                ->has('notes', 1)
                ->has('notes.0.author', fn ($author) => $author->where('id', $user->id)->etc())
            );
    }
}
