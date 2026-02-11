<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnamnesisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required_without:first_name', 'nullable', 'exists:patients,id'],
            'first_name' => ['required_without:patient_id', 'nullable', 'string', 'max:255'],
            'last_name' => ['required_without:patient_id', 'nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'cnp' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'first_visit_date' => ['nullable', 'date'],

            // Special situations
            'form_data' => ['required', 'array'],
            'form_data.special_situations' => ['required', 'array'],
            'form_data.special_situations.pregnant' => ['nullable', 'boolean'],
            'form_data.special_situations.menstruating' => ['nullable', 'boolean'],
            'form_data.special_situations.gestational_age' => ['nullable', 'string', 'max:10'],

            // Allergies
            'form_data.allergies' => ['required', 'array'],
            'form_data.allergies.drug_allergies' => ['nullable', 'array'],
            'form_data.allergies.drug_allergies.*' => ['string', 'max:255'],
            'form_data.allergies.non_drug_allergies' => ['nullable', 'array'],
            'form_data.allergies.non_drug_allergies.*' => ['string', 'max:255'],

            // Current treatment
            'form_data.current_treatment' => ['required', 'array'],
            'form_data.current_treatment.medications' => ['nullable', 'array'],
            'form_data.current_treatment.medications.*.name' => ['nullable', 'string', 'max:255'],
            'form_data.current_treatment.medications.*.dose' => ['nullable', 'string', 'max:255'],
            'form_data.current_treatment.antibiotics_last_2_weeks' => ['nullable', 'boolean'],
            'form_data.current_treatment.antibiotic_details' => ['nullable', 'array'],
            'form_data.current_treatment.antibiotic_details.*.drug' => ['nullable', 'string', 'max:255'],
            'form_data.current_treatment.antibiotic_details.*.dose' => ['nullable', 'string', 'max:255'],
            'form_data.current_treatment.anticoagulants' => ['nullable', 'boolean'],
            'form_data.current_treatment.anticoagulant_inr' => ['nullable', 'string', 'max:20'],
            'form_data.current_treatment.bisphosphonates' => ['nullable', 'boolean'],
            'form_data.current_treatment.bisphosphonate_route' => ['nullable', 'string', 'in:oral,iv,'],
            'form_data.current_treatment.bisphosphonate_duration' => ['nullable', 'string', 'max:100'],
            'form_data.current_treatment.bisphosphonate_beta_ctx' => ['nullable', 'string', 'max:20'],

            // Diseases — all boolean fields
            'form_data.diseases' => ['required', 'array'],
            'form_data.diseases.heart' => ['nullable', 'array'],
            'form_data.diseases.vascular' => ['nullable', 'array'],
            'form_data.diseases.respiratory' => ['nullable', 'array'],
            'form_data.diseases.digestive' => ['nullable', 'array'],
            'form_data.diseases.hepatic' => ['nullable', 'array'],
            'form_data.diseases.renal' => ['nullable', 'array'],
            'form_data.diseases.diabetes' => ['nullable', 'array'],
            'form_data.diseases.endocrine' => ['nullable', 'array'],
            'form_data.diseases.rheumatic' => ['nullable', 'array'],
            'form_data.diseases.skeletal' => ['nullable', 'array'],
            'form_data.diseases.neurological' => ['nullable', 'array'],
            'form_data.diseases.psychiatric' => ['nullable', 'array'],
            'form_data.diseases.neurovegetative' => ['nullable', 'array'],
            'form_data.diseases.hematologic' => ['nullable', 'array'],
            'form_data.diseases.infectious' => ['nullable', 'array'],
            'form_data.diseases.neoplasms' => ['nullable', 'boolean'],
            'form_data.diseases.neoplasms_details' => ['nullable', 'string', 'max:500'],
            'form_data.diseases.other_diseases' => ['nullable', 'boolean'],
            'form_data.diseases.other_diseases_details' => ['nullable', 'string', 'max:500'],

            // Surgical history
            'form_data.surgical_history' => ['nullable', 'array'],
            'form_data.surgical_history.previous_surgeries' => ['nullable', 'string', 'max:1000'],
            'form_data.surgical_history.transfusions' => ['nullable', 'boolean'],
            'form_data.surgical_history.surgery_complications' => ['nullable', 'string', 'max:1000'],

            // Dental history
            'form_data.dental_history' => ['nullable', 'array'],
            'form_data.dental_history.anesthesia_types' => ['nullable', 'array'],
            'form_data.dental_history.adverse_reactions' => ['nullable', 'string', 'max:1000'],

            // Habits
            'form_data.habits' => ['nullable', 'array'],
            'form_data.habits.tobacco' => ['nullable', 'boolean'],
            'form_data.habits.tobacco_amount' => ['nullable', 'string', 'max:100'],
            'form_data.habits.tobacco_duration' => ['nullable', 'string', 'max:100'],
            'form_data.habits.alcohol' => ['nullable', 'boolean'],
            'form_data.habits.alcohol_amount' => ['nullable', 'string', 'max:100'],
            'form_data.habits.alcohol_duration' => ['nullable', 'string', 'max:100'],
            'form_data.habits.drugs' => ['nullable', 'boolean'],
            'form_data.habits.drugs_amount' => ['nullable', 'string', 'max:100'],
            'form_data.habits.drugs_duration' => ['nullable', 'string', 'max:100'],

            'consent_given' => ['required', 'accepted'],
            'language' => ['required', 'in:en,ro'],
        ];
    }
}
