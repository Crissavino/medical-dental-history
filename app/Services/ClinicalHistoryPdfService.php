<?php

namespace App\Services;

use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;

class ClinicalHistoryPdfService
{
    public function generate(Patient $patient): \Barryvdh\DomPDF\PDF
    {
        $patient->load([
            'latestAnamnesis',
            'encounters' => fn ($q) => $q->orderBy('encounter_date'),
            'encounters.treatments',
            'encounters.provider:id,name',
            'encounters.dentistSigner:id,name',
        ]);

        $signedEncounters = $patient->encounters->where('status', 'completed')->values();
        $cancelledEncounters = $patient->encounters->where('status', 'cancelled')->values();

        $pdf = Pdf::loadView('pdf.clinical-history', [
            'patient' => $patient,
            'anamnesis' => $patient->latestAnamnesis,
            'signedEncounters' => $signedEncounters,
            'cancelledEncounters' => $cancelledEncounters,
            'logoBase64' => $this->loadLogoBase64(),
        ]);
        $pdf->setPaper('A4');
        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('isRemoteEnabled', false);
        return $pdf;
    }

    public function filename(Patient $patient): string
    {
        return "clinical-history-{$patient->identifier}.pdf";
    }

    private function loadLogoBase64(): string
    {
        $pngPath = public_path('images/clinic-logo.png');
        return file_exists($pngPath) ? base64_encode(file_get_contents($pngPath)) : '';
    }
}
