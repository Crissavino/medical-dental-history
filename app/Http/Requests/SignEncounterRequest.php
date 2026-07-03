<?php

namespace App\Http\Requests;

use App\Models\Encounter;
use Illuminate\Foundation\Http\FormRequest;

class SignEncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $encounter = $this->route('encounter');
        if (!$encounter instanceof Encounter) {
            return false;
        }
        if ($encounter->isLocked()) {
            return false;
        }
        return $this->user()?->hasRole('admin', 'dentist') ?? false;
    }

    public function rules(): array
    {
        return [
            'patient_signature_data' => ['required', 'string', 'starts_with:data:image/'],
            'dentist_signature_data' => ['nullable', 'string', 'starts_with:data:image/'],
            'use_stored_dentist_signature' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $encounter = $this->route('encounter');
            if ($encounter && $encounter->treatments()->count() === 0) {
                $validator->errors()->add(
                    'treatments',
                    'Encounter must have at least one treatment before signing.'
                );
            }
            if ($encounter && $encounter->hasUnconsentedExtractions()) {
                $validator->errors()->add(
                    'extraction_consent',
                    'Extraction consent must be signed before closing this encounter.'
                );
            }
            $useStored = (bool) $this->input('use_stored_dentist_signature');
            $hasSig = (bool) $this->input('dentist_signature_data');
            if (!$useStored && !$hasSig) {
                $validator->errors()->add(
                    'dentist_signature_data',
                    'Dentist signature is required (or toggle "use stored").'
                );
            }
            if ($useStored && !$this->user()?->signature_data) {
                $validator->errors()->add(
                    'use_stored_dentist_signature',
                    'You have no stored professional signature.'
                );
            }
        });
    }
}
