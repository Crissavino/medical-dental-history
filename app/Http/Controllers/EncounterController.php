<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEncounterRequest;
use App\Http\Requests\UpdateEncounterRequest;
use App\Models\Encounter;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EncounterController extends Controller
{
    public function index(Patient $patient): Response
    {
        $this->authorize('viewAny', Encounter::class);

        $encounters = $patient->encounters()
            ->with(['provider:id,name', 'treatments'])
            ->orderByDesc('encounter_date')
            ->paginate(10);

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
            'encounters' => $encounters,
        ]);
    }

    public function create(Patient $patient): Response
    {
        $this->authorize('create', Encounter::class);

        return Inertia::render('Encounters/Editor', [
            'patient' => $patient,
        ]);
    }

    public function store(StoreEncounterRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();
        $treatments = $validated['treatments'] ?? [];
        unset($validated['treatments']);

        $encounter = $patient->encounters()->create([
            ...$validated,
            'provider_id' => auth()->id(),
        ]);

        foreach ($treatments as $treatmentData) {
            $encounter->treatments()->create($treatmentData);
        }

        return redirect()->route('encounters.show', $encounter)
            ->with('success', 'Encounter created successfully.');
    }

    public function show(Encounter $encounter): Response
    {
        $this->authorize('view', $encounter);

        $encounter->load([
            'patient',
            'provider:id,name',
            'treatments',
            'attachments.uploader:id,name',
        ]);

        return Inertia::render('Encounters/Show', [
            'encounter' => $encounter,
        ]);
    }

    public function edit(Encounter $encounter): Response
    {
        $this->authorize('update', $encounter);

        $encounter->load('patient', 'treatments');

        return Inertia::render('Encounters/Editor', [
            'encounter' => $encounter,
        ]);
    }

    public function update(UpdateEncounterRequest $request, Encounter $encounter): RedirectResponse
    {
        $validated = $request->validated();
        $treatments = $validated['treatments'] ?? [];
        unset($validated['treatments']);

        $encounter->update($validated);

        $submittedIds = collect($treatments)->pluck('id')->filter()->all();
        $encounter->treatments()->whereNotIn('id', $submittedIds)->delete();

        foreach ($treatments as $treatmentData) {
            if (!empty($treatmentData['id'])) {
                $encounter->treatments()->where('id', $treatmentData['id'])->update(
                    collect($treatmentData)->except('id')->all()
                );
            } else {
                $encounter->treatments()->create($treatmentData);
            }
        }

        return redirect()->route('encounters.show', $encounter)
            ->with('success', 'Encounter updated successfully.');
    }

    public function destroy(Encounter $encounter): RedirectResponse
    {
        $this->authorize('delete', $encounter);

        $patient = $encounter->patient;
        $encounter->delete();

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Encounter deleted successfully.');
    }
}
