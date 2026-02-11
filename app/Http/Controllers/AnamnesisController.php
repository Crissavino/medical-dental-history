<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnamnesisRequest;
use App\Models\AnamnesisVersion;
use App\Models\Patient;
use App\Services\AnamnesisPdfService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AnamnesisController extends Controller
{
    public function create(Patient $patient): Response
    {
        return Inertia::render('Intake/Wizard', [
            'patient' => $patient,
            'existingAnamnesis' => $patient->latestAnamnesis,
        ]);
    }

    public function store(StoreAnamnesisRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();

        $patient->anamnesisVersions()->create([
            'form_data' => $validated['form_data'],
            'consent_given' => true,
            'consent_given_at' => now(),
            'consent_ip' => $request->ip(),
            'language' => $validated['language'],
            'recorded_by' => auth()->id(),
            'signature_data' => $validated['signature_data'] ?? null,
        ]);

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Anamnesis saved successfully.');
    }

    public function pdf(AnamnesisVersion $anamnesisVersion, AnamnesisPdfService $pdfService, ?string $lang = null): HttpResponse
    {
        $pdf = $pdfService->generate($anamnesisVersion, $lang);
        $filename = $pdfService->filename($anamnesisVersion, $lang);

        return $pdf->download($filename);
    }

    public function show(AnamnesisVersion $anamnesisVersion): Response
    {
        $anamnesisVersion->load('patient', 'recorder');

        return Inertia::render('Patients/Show', [
            'patient' => $anamnesisVersion->patient,
            'selectedAnamnesis' => $anamnesisVersion,
            'anamnesisVersions' => $anamnesisVersion->patient
                ->anamnesisVersions()
                ->orderByDesc('version')
                ->get(),
        ]);
    }
}
