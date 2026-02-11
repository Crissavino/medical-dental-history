<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'dentist', 'assistant');
    }

    public function rules(): array
    {
        return [
            'tooth_number' => ['nullable', 'string', 'max:3'],
            'treatment_code' => ['required', 'string', 'max:20'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'surface' => ['nullable', 'in:mesial,distal,buccal,lingual,occlusal,incisal'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'status' => ['required', 'in:planned,in_progress,completed'],
        ];
    }
}
