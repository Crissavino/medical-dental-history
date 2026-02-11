<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    private const ATTACHABLE_MAP = [
        'patient' => Patient::class,
        'encounter' => Encounter::class,
        'treatment' => Treatment::class,
    ];

    public function store(StoreAttachmentRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $type = self::ATTACHABLE_MAP[$request->input('attachable_type')];
        $id = $request->input('attachable_id');

        $path = $file->store('attachments/' . $request->input('attachable_type') . '/' . $id, 'local');

        Attachment::create([
            'attachable_type' => $type,
            'attachable_id' => $id,
            'uploaded_by' => auth()->id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'description' => $request->input('description'),
        ]);

        return back()->with('success', 'File uploaded successfully.');
    }

    public function show(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->file_name,
        );
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        $this->authorize('delete', $attachment);

        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Attachment deleted successfully.');
    }
}
