<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this->user()?->hasRole('admin', 'dentist', 'assistant')) {
            return false;
        }

        $type = $this->input('attachable_type');
        $id = $this->input('attachable_id');
        if ($type === \App\Models\Encounter::class && $id) {
            $parent = \App\Models\Encounter::find($id);
            if ($parent && $parent->isLocked()) {
                return false;
            }
        }

        return true;
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
