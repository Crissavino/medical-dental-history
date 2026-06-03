<?php

namespace App\Http\Controllers;

use App\Http\Requests\SignEncounterRequest;
use App\Http\Requests\StoreEncounterRequest;
use App\Http\Requests\UpdateEncounterRequest;
use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\Patient;
use App\Services\EncounterPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

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
            'currentUser' => [
                'id' => auth()->id(),
                'name' => auth()->user()->name,
                'has_signature' => (bool) auth()->user()->signature_data,
            ],
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
            'dentistSigner:id,name',
            'rectifies:id,encounter_date',
            'rectifier:id,encounter_date,rectifies_encounter_id',
            'treatments',
            'attachments.uploader:id,name',
        ]);

        return Inertia::render('Encounters/Show', [
            'encounter' => $encounter,
            'currentUser' => [
                'id' => auth()->id(),
                'name' => auth()->user()->name,
                'has_signature' => (bool) auth()->user()->signature_data,
            ],
            'can' => [
                'sign' => auth()->user()->can('sign', $encounter),
            ],
        ]);
    }

    public function edit(Encounter $encounter): Response
    {
        $this->authorize('update', $encounter);

        $encounter->load('patient', 'treatments');

        return Inertia::render('Encounters/Editor', [
            'encounter' => $encounter,
            'currentUser' => [
                'id' => auth()->id(),
                'name' => auth()->user()->name,
                'has_signature' => (bool) auth()->user()->signature_data,
            ],
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

    public function sign(SignEncounterRequest $request, Encounter $encounter): RedirectResponse
    {
        $patientSignedAt = now();
        $dentistSignedAt = now();

        DB::transaction(function () use ($request, $encounter, $patientSignedAt, $dentistSignedAt) {
            $locked = Encounter::where('id', $encounter->id)
                ->whereNull('patient_signature_data')
                ->whereNull('dentist_signature_data')
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                // already signed by a concurrent request
                abort(409, 'Encounter is already signed.');
            }

            $treatments = $locked->treatments()->orderBy('id')->get([
                'id', 'tooth_number', 'treatment_code', 'description', 'surface', 'cost', 'status',
            ]);

            $hash = hash('sha256', implode('|', [
                $locked->id,
                $locked->encounter_date?->toDateString(),
                (string) $locked->chief_complaint,
                (string) $locked->diagnosis,
                (string) $locked->clinical_notes,
                $treatments->toJson(),
                $patientSignedAt->toIso8601String(),
                $dentistSignedAt->toIso8601String(),
            ]));

            $useStored = (bool) $request->input('use_stored_dentist_signature');
            $dentistSig = $useStored
                ? auth()->user()->signature_data
                : $request->input('dentist_signature_data');

            $locked->update([
                'patient_signature_data' => $request->input('patient_signature_data'),
                'dentist_signature_data' => $dentistSig,
                'patient_signed_at' => $patientSignedAt,
                'dentist_signed_by' => auth()->id(),
                'dentist_signed_at' => $dentistSignedAt,
                'signed_ip' => $request->ip(),
                'signed_hash' => $hash,
                'status' => 'completed',
            ]);

            AuditLog::create([
                'entity_type' => Encounter::class,
                'entity_id' => $locked->id,
                'user_id' => auth()->id(),
                'action' => 'signed',
                'ip_address' => $request->ip(),
                'metadata_json' => [
                    'patient_signed_at' => $patientSignedAt->toIso8601String(),
                    'dentist_signed_at' => $dentistSignedAt->toIso8601String(),
                    'dentist_signed_by_user_id' => auth()->id(),
                    'signed_ip' => $request->ip(),
                    'treatments_count' => count($treatments),
                    'encounter_hash' => $hash,
                ],
            ]);
        });

        return redirect()->route('encounters.show', $encounter)
            ->with('success', 'Encounter signed and locked.');
    }

    public function rectify(Encounter $encounter): RedirectResponse
    {
        $this->authorize('rectify', $encounter);

        $rectifier = DB::transaction(function () use ($encounter) {
            $rectifier = $encounter->patient->encounters()->create([
                'provider_id' => auth()->id(),
                'encounter_date' => now()->toDateString(),
                'chief_complaint' => $encounter->chief_complaint,
                'clinical_notes' => $encounter->clinical_notes,
                'diagnosis' => $encounter->diagnosis,
                'status' => 'in_progress',
                'rectifies_encounter_id' => $encounter->id,
            ]);

            foreach ($encounter->treatments as $treatment) {
                $rectifier->treatments()->create([
                    'tooth_number' => $treatment->tooth_number,
                    'treatment_code' => $treatment->treatment_code,
                    'description' => $treatment->description,
                    'surface' => $treatment->surface,
                    'cost' => $treatment->cost,
                    'status' => $treatment->status,
                    'notes' => $treatment->notes ?? null,
                ]);
            }

            AuditLog::create([
                'entity_type' => Encounter::class,
                'entity_id' => $encounter->id,
                'user_id' => auth()->id(),
                'action' => 'rectified',
                'ip_address' => request()->ip(),
                'metadata_json' => [
                    'rectified_by_encounter_id' => $rectifier->id,
                    'rectified_by_user_id' => auth()->id(),
                ],
            ]);

            return $rectifier;
        });

        return redirect()->route('encounters.edit', $rectifier)
            ->with('success', 'Rectification encounter created. Edit and re-sign.');
    }

    public function pdf(Encounter $encounter, EncounterPdfService $pdfService): HttpResponse
    {
        $this->authorize('downloadPdf', $encounter);

        $pdf = $pdfService->generate($encounter);
        return $pdf->download($pdfService->filename($encounter));
    }
}
