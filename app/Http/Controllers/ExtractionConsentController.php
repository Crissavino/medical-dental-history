<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtractionConsentRequest;
use App\Models\Encounter;
use App\Models\ExtractionConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class ExtractionConsentController extends Controller
{
    public function store(StoreExtractionConsentRequest $request, Encounter $encounter): RedirectResponse
    {
        $language = $request->validated('language');

        ExtractionConsent::create([
            'encounter_id' => $encounter->id,
            'consent_text' => $this->consentText($language),
            'language' => $language,
            'patient_signature_data' => $request->validated('patient_signature_data'),
            'signed_at' => now(),
            'signed_ip' => $request->ip(),
            'recorded_by' => auth()->id(),
        ]);

        return redirect()->route('encounters.show', $encounter)
            ->with('success', 'Extraction consent signed.');
    }

    private function consentText(string $lang): string
    {
        $path = resource_path("js/i18n/{$lang}.json");

        if (!file_exists($path)) {
            $path = resource_path('js/i18n/en.json');
        }

        $json = json_decode(file_get_contents($path), true);

        return Arr::get($json, 'extractionConsent.text', '');
    }
}
