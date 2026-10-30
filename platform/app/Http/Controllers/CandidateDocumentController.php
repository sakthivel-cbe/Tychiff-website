<?php

namespace App\Http\Controllers;

use App\Documents\Services\CandidateDocumentExtractionService;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CandidateDocumentController extends Controller
{
    public function store(Request $request, Candidate $candidate, CandidateDocumentExtractionService $extraction): RedirectResponse
    {
        $this->authorize('uploadDocument', $candidate);
        $validated = $request->validate([
            'document_type' => ['required', 'in:resume,profile_document'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $file = $validated['document'];
        $extension = strtolower($file->extension() ?: '');
        if (! in_array($extension, ['pdf', 'doc', 'docx'], true)) {
            throw ValidationException::withMessages(['document' => 'Upload a PDF, DOC, or DOCX file.']);
        }

        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?: 'candidate-document.'.$extension;
        $name = mb_substr($name, 0, 255);
        $path = 'candidates/'.$candidate->reference.'/'.Str::ulid().'.'.$extension;

        $storedPath = $file->storeAs('candidates/'.$candidate->reference, basename($path), 'private_documents');
        try {
            $document = CandidateDocument::query()->create([
                'candidate_id' => $candidate->getKey(),
                'document_type' => $validated['document_type'],
                'original_filename' => $name,
                'storage_path' => $storedPath,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'file_size' => $file->getSize(),
                'status' => 'active',
                'uploaded_by' => $request->user()->getKey(),
                'uploaded_at' => now(),
                'source_sha256' => hash_file('sha256', $file->getRealPath()),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('private_documents')->delete($storedPath);
            throw $exception;
        }

        AuditLog::record('candidate.document_uploaded', [
            'entity_type' => Candidate::class,
            'entity_id' => (string) $candidate->getKey(),
            'new_state' => ['document_id' => $document->getKey(), 'document_type' => $document->document_type],
        ]);
        if ($document->document_type === 'resume') {
            $document = $extraction->extract($request->user(), $document);
        }

        return back()->with('status', $document->extraction_status === 'extracted'
            ? 'Candidate document stored privately and text extracted.'
            : ($document->extraction_status === 'failed'
                ? 'Candidate document stored privately; text extraction failed and can be retried.'
                : 'Candidate document stored in private storage.'));
    }

    public function extract(Request $request, CandidateDocument $document, CandidateDocumentExtractionService $extraction): RedirectResponse
    {
        $this->authorize('viewDocument', $document);
        $document = $extraction->extract($request->user(), $document, retry: true);

        return back()->with('status', $document->extraction_status === 'extracted'
            ? 'Candidate resume text extracted.'
            : 'Resume text extraction failed. The private original remains available for retry.');
    }

    public function download(CandidateDocument $document): StreamedResponse
    {
        $this->authorize('viewDocument', $document);
        $disk = Storage::disk('private_documents');
        abort_unless($disk->exists($document->storage_path), 404);

        AuditLog::record('candidate.document_viewed', [
            'entity_type' => Candidate::class,
            'entity_id' => (string) $document->candidate_id,
            'context' => ['document_id' => $document->getKey(), 'document_type' => $document->document_type],
        ]);

        return $disk->download($document->storage_path, $document->original_filename, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
