<?php

namespace App\Services;

use App\Models\ExtractionConsent;
use Barryvdh\DomPDF\Facade\Pdf;

class ExtractionConsentPdfService
{
    public function generate(ExtractionConsent $extractionConsent): \Barryvdh\DomPDF\PDF
    {
        $extractionConsent->load(['encounter.patient', 'recorder:id,name']);

        $pdf = Pdf::loadView('pdf.extraction-consent', [
            'consent' => $extractionConsent,
            'encounter' => $extractionConsent->encounter,
            'patient' => $extractionConsent->encounter->patient,
            'logoBase64' => $this->loadLogoBase64(),
        ]);
        $pdf->setPaper('A4');
        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('isRemoteEnabled', false);

        return $pdf;
    }

    public function filename(ExtractionConsent $extractionConsent): string
    {
        $extractionConsent->loadMissing('encounter.patient');
        $identifier = $extractionConsent->encounter->patient->identifier;

        return "extraction-consent-{$identifier}-{$extractionConsent->encounter_id}.pdf";
    }

    private function loadLogoBase64(): string
    {
        $pngPath = public_path('images/clinic-logo.png');

        return file_exists($pngPath) ? base64_encode(file_get_contents($pngPath)) : '';
    }
}
