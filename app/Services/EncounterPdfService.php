<?php

namespace App\Services;

use App\Models\Encounter;
use Barryvdh\DomPDF\Facade\Pdf;

class EncounterPdfService
{
    public function generate(Encounter $encounter): \Barryvdh\DomPDF\PDF
    {
        $encounter->load(['patient', 'provider:id,name', 'dentistSigner:id,name', 'treatments']);

        $pdf = Pdf::loadView('pdf.encounter', [
            'encounter' => $encounter,
            'patient' => $encounter->patient,
            'logoBase64' => $this->loadLogoBase64(),
        ]);
        $pdf->setPaper('A4');
        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('isRemoteEnabled', false);
        return $pdf;
    }

    public function filename(Encounter $encounter): string
    {
        $encounter->loadMissing('patient');
        return "encounter-{$encounter->patient->identifier}-{$encounter->id}.pdf";
    }

    private function loadLogoBase64(): string
    {
        $pngPath = public_path('images/clinic-logo.png');
        return file_exists($pngPath) ? base64_encode(file_get_contents($pngPath)) : '';
    }
}
