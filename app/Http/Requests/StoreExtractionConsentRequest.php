<?php

namespace App\Http\Requests;

use App\Models\Encounter;
use Illuminate\Foundation\Http\FormRequest;

class StoreExtractionConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $encounter = $this->route('encounter');
        if (!$encounter instanceof Encounter) {
            return false;
        }
        return $this->user()?->can('consentExtraction', $encounter) ?? false;
    }

    public function rules(): array
    {
        return [
            'patient_signature_data' => ['required', 'string', 'starts_with:data:image/'],
            'language' => ['required', 'in:en,ro,es'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $encounter = $this->route('encounter');
            if (!$encounter instanceof Encounter) {
                return;
            }
            if ($encounter->treatments()->where('is_extraction', true)->doesntExist()) {
                $validator->errors()->add(
                    'extraction_consent',
                    'This encounter has no extraction treatment to consent to.'
                );
            }
            if ($encounter->extractionConsent()->exists()) {
                $validator->errors()->add(
                    'extraction_consent',
                    'Extraction consent has already been signed for this encounter.'
                );
            }
        });
    }
}
