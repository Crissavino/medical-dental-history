<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTreatmentRequest;
use App\Http\Requests\UpdateTreatmentRequest;
use App\Models\Encounter;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;

class TreatmentController extends Controller
{
    public function store(StoreTreatmentRequest $request, Encounter $encounter): RedirectResponse
    {
        $encounter->treatments()->create($request->validated());

        return redirect()->route('encounters.show', $encounter)
            ->with('success', 'Treatment added successfully.');
    }

    public function update(UpdateTreatmentRequest $request, Treatment $treatment): RedirectResponse
    {
        $treatment->update($request->validated());

        return redirect()->route('encounters.show', $treatment->encounter)
            ->with('success', 'Treatment updated successfully.');
    }

    public function destroy(Treatment $treatment): RedirectResponse
    {
        $this->authorize('delete', $treatment);

        $encounter = $treatment->encounter;
        $treatment->delete();

        return redirect()->route('encounters.show', $encounter)
            ->with('success', 'Treatment deleted successfully.');
    }
}
