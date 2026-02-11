<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class GdprExportController extends Controller
{
    public function export(Patient $patient): StreamedResponse
    {
        $this->authorize('export', $patient);

        $patient->load([
            'anamnesisVersions',
            'encounters.treatments',
            'encounters.provider:id,name',
            'attachments',
        ]);

        $zipPath = storage_path("app/temp/gdpr-export-{$patient->id}.zip");

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('patient.json', json_encode([
            'identifier' => $patient->identifier,
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'date_of_birth' => $patient->date_of_birth?->toDateString(),
            'gender' => $patient->gender,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'address' => $patient->address,
            'city' => $patient->city,
            'county' => $patient->county,
            'created_at' => $patient->created_at->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $zip->addFromString('anamnesis.json', json_encode(
            $patient->anamnesisVersions->map(fn ($v) => [
                'version' => $v->version,
                'form_data' => $v->form_data,
                'language' => $v->language,
                'consent_given' => $v->consent_given,
                'consent_given_at' => $v->consent_given_at?->toIso8601String(),
                'created_at' => $v->created_at->toIso8601String(),
            ]),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ));

        $zip->addFromString('encounters.json', json_encode(
            $patient->encounters->map(fn ($e) => [
                'date' => $e->encounter_date->toDateString(),
                'provider' => $e->provider?->name,
                'chief_complaint' => $e->chief_complaint,
                'clinical_notes' => $e->clinical_notes,
                'diagnosis' => $e->diagnosis,
                'status' => $e->status,
                'treatments' => $e->treatments->map(fn ($t) => [
                    'tooth_number' => $t->tooth_number,
                    'treatment_code' => $t->treatment_code,
                    'description' => $t->description,
                    'surface' => $t->surface,
                    'cost' => $t->cost,
                    'status' => $t->status,
                ]),
            ]),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ));

        foreach ($patient->attachments as $attachment) {
            $filePath = Storage::disk('local')->path($attachment->file_path);
            if (file_exists($filePath)) {
                $zip->addFile($filePath, 'attachments/' . $attachment->file_name);
            }
        }

        $zip->close();

        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
            unlink($zipPath);
        }, "gdpr-export-{$patient->identifier}.zip", [
            'Content-Type' => 'application/zip',
        ]);
    }
}
