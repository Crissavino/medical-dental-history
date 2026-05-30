<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientNoteController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('view', $patient);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $patient->notesLog()->create([
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Note added.');
    }

    public function update(Request $request, PatientNote $patientNote): RedirectResponse
    {
        $this->authorizeManage($patientNote);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $patientNote->update(['body' => $validated['body']]);

        return back()->with('success', 'Note updated.');
    }

    public function destroy(PatientNote $patientNote): RedirectResponse
    {
        $this->authorizeManage($patientNote);

        $patientNote->delete();

        return back()->with('success', 'Note deleted.');
    }

    /**
     * A note may be edited or deleted by its author or by an admin.
     */
    private function authorizeManage(PatientNote $patientNote): void
    {
        abort_unless(
            $patientNote->user_id === auth()->id() || auth()->user()->hasRole('admin'),
            403,
        );
    }
}
