<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'dentist', 'assistant');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,dicom'],
            'attachable_type' => ['required', 'in:patient,encounter,treatment'],
            'attachable_id' => ['required', 'integer'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
