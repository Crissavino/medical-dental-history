<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Patient::class);

        $query = Patient::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('identifier', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $patients = $query->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Patients/Index', [
            'patients' => $patients,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Patient::class);

        return Inertia::render('Patients/Create');
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $patient = Patient::create($request->validated());

        if ($request->boolean('redirect_to_anamnesis')) {
            return redirect()->route('intake.show', $patient)
                ->with('success', 'Patient created successfully.');
        }

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Patient created successfully.');
    }

    public function show(Patient $patient): Response
    {
        $this->authorize('view', $patient);

        $patient->load([
            'latestAnamnesis',
            'encounters' => fn ($q) => $q->orderByDesc('encounter_date')->limit(10),
            'encounters.provider:id,name',
            'encounters.treatments',
            'attachments.uploader:id,name',
        ]);

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
            'anamnesisVersions' => $patient->anamnesisVersions()
                ->orderByDesc('version')
                ->get(),
        ]);
    }

    public function edit(Patient $patient): Response
    {
        $this->authorize('update', $patient);

        return Inertia::render('Patients/Edit', [
            'patient' => $patient,
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->validated());

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Patient updated successfully.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorize('delete', $patient);

        $patient->delete();

        return redirect()->route('patients.index')
            ->with('success', 'Patient deleted successfully.');
    }
}
