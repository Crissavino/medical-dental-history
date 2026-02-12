<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnamnesisRequest;
use App\Models\AnamnesisVersion;
use App\Models\Patient;
use App\Services\AnamnesisPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function sign(Request $request, AnamnesisVersion $anamnesisVersion): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->hasRole('dentist', 'admin'), 403);

        if (!$user->signature_data) {
            $request->validate([
                'signature_data' => ['required', 'string'],
            ]);

            $user->update(['signature_data' => $request->input('signature_data')]);
        }

        $anamnesisVersion->update([
            'dentist_signature_data' => $user->signature_data,
            'signed_by' => $user->id,
            'signed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Anamnesis signed successfully.');
    }

    public function show(AnamnesisVersion $anamnesisVersion): Response
    {
        $anamnesisVersion->load('patient', 'recorder', 'signer:id,name');

        return Inertia::render('Patients/Show', [
            'patient' => $anamnesisVersion->patient,
            'selectedAnamnesis' => $anamnesisVersion,
            'anamnesisVersions' => $anamnesisVersion->patient
                ->anamnesisVersions()
                ->with('signer:id,name')
                ->orderByDesc('version')
                ->get(),
        ]);
    }
}
