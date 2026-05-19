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
        if ($encounter->status !== 'in_progress') {
            return false;
        }
        return $this->user()?->hasRole('admin', 'dentist') ?? false;
    }

    public function rules(): array
    {
        return [
            'patient_signature_data' => ['required', 'string', 'starts_with:data:image/'],
            'dentist_signature_data' => ['required', 'string', 'starts_with:data:image/'],
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
        });
    }
}
