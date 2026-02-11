<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnamnesisRequest;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class IntakeController extends Controller
{
    public function show(?Patient $patient = null): Response
    {
        return Inertia::render('Intake/Wizard', [
            'patient' => $patient,
            'existingAnamnesis' => $patient?->latestAnamnesis,
        ]);
    }

    public function store(StoreAnamnesisRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->filled('patient_id')) {
            $patient = Patient::findOrFail($validated['patient_id']);
            // Update patient fields if provided
            $patient->update(array_filter([
                'cnp' => $validated['cnp'] ?? null,
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
            ]));
        } else {
            $patient = Patient::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'cnp' => $validated['cnp'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);
        }

        $patient->anamnesisVersions()->create([
            'form_data' => $validated['form_data'],
            'consent_given' => true,
            'consent_given_at' => now(),
            'consent_ip' => $request->ip(),
            'language' => $validated['language'],
        ]);

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Medical history submitted successfully.');
    }

    public function complete(): Response
    {
        return Inertia::render('Intake/Complete');
    }
}
