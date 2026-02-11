<?php

namespace App\Services;

use App\Models\AnamnesisVersion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Arr;

class AnamnesisPdfService
{
    public function generate(AnamnesisVersion $anamnesisVersion): \Barryvdh\DomPDF\PDF
    {
        $anamnesisVersion->load('patient');

        $lang = $anamnesisVersion->language ?? 'en';
        $translations = $this->loadTranslations($lang);

        $patient = $anamnesisVersion->patient;
        $formData = $anamnesisVersion->form_data ?? [];

        $pdf = Pdf::loadView('pdf.anamnesis', [
            't' => $translations,
            'patient' => $patient,
            'formData' => $formData,
            'version' => $anamnesisVersion,
            'lang' => $lang,
        ]);

        $pdf->setPaper('A4');
        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('isRemoteEnabled', false);

        return $pdf;
    }

    public function filename(AnamnesisVersion $anamnesisVersion): string
    {
        $anamnesisVersion->load('patient');
        $identifier = $anamnesisVersion->patient->identifier;
        $version = $anamnesisVersion->version;
        $lang = $anamnesisVersion->language ?? 'en';

        return "anamnesis-{$identifier}-v{$version}-{$lang}.pdf";
    }

    private function loadTranslations(string $lang): array
    {
        $path = resource_path("js/i18n/{$lang}.json");

        if (!file_exists($path)) {
            $path = resource_path('js/i18n/en.json');
        }

        $json = json_decode(file_get_contents($path), true);

        return Arr::dot($json);
    }
}
