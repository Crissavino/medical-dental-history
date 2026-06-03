<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $encounter = $this->route('encounter');

        if (!$user || !$user->hasRole('admin', 'dentist', 'assistant')) {
            return false;
        }
        if ($encounter instanceof \App\Models\Encounter && $encounter->isLocked()) {
            return false;
        }
        return true;
    }

    public function rules(): array
    {
        return [
            'encounter_date' => ['required', 'date'],
            'chief_complaint' => ['nullable', 'string', 'max:2000'],
            'clinical_notes' => ['nullable', 'string', 'max:10000'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:scheduled,in_progress,completed,cancelled'],
            'treatments' => ['nullable', 'array'],
            'treatments.*.id' => ['nullable', 'integer', 'exists:treatments,id'],
            'treatments.*.tooth_number' => ['nullable', 'string', 'max:3'],
            'treatments.*.treatment_code' => ['required_with:treatments.*', 'string', 'max:20'],
            'treatments.*.description' => ['required_with:treatments.*', 'string', 'max:255'],
            'treatments.*.notes' => ['nullable', 'string', 'max:5000'],
            'treatments.*.surface' => ['nullable', 'string', 'in:mesial,distal,buccal,lingual,occlusal,incisal'],
            'treatments.*.cost' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'treatments.*.status' => ['required_with:treatments.*', 'in:planned,in_progress,completed'],
        ];
    }
}
