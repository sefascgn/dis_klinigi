<?php

namespace App\Http\Controllers;

use App\Http\Requests\PatientDocumentRequest;
use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientDocumentController extends Controller
{
    public function store(PatientDocumentRequest $request, Patient $patient): RedirectResponse
    {
        $file = $request->file('file');

        $document = new PatientDocument([
            'type' => $request->validated('type'),
            'title' => $request->validated('title') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
        ]);
        $document->patient_id = $patient->id;
        $document->path = $file->store("patient-documents/{$patient->id}", PatientDocument::DISK);
        $document->original_name = $file->getClientOriginalName();
        $document->mime_type = $file->getMimeType();
        $document->size = $file->getSize();
        $document->uploaded_by = $request->user()->id;
        $document->save();

        return redirect()->route('patients.show', [$patient, 'tab' => 'documents'])->with('success', 'Belge yüklendi.');
    }

    /**
     * Görüntü ve PDF'ler tarayıcıda açılır; dosyalar herkese açık klasörde değildir.
     */
    public function show(PatientDocument $document): StreamedResponse
    {
        Gate::authorize('view', $document);

        $disposition = request()->boolean('download') ? 'attachment' : 'inline';

        return Storage::disk(PatientDocument::DISK)->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ], $disposition);
    }

    public function destroy(PatientDocument $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $document->delete();

        return redirect()->route('patients.show', [$document->patient_id, 'tab' => 'documents'])->with('success', 'Belge silindi.');
    }
}
