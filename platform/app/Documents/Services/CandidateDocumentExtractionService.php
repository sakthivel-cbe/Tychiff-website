<?php

namespace App\Documents\Services;

use App\Documents\Exceptions\DocumentExtractionException;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CandidateDocumentExtractionService
{
    public function __construct(private readonly DocumentTextExtractionService $extractor) {}

    public function extract(User $user, CandidateDocument $document, bool $retry = false): CandidateDocument
    {
        if (! $user->can('viewDocument', $document)) {
            throw new AuthorizationException;
        }
        if ($document->extraction_status === 'extracted' && ! $retry) {
            return $document;
        }

        $document->forceFill([
            'extraction_status' => 'processing',
            'extraction_failure_code' => null,
            'extraction_attempts' => $document->extraction_attempts + 1,
        ])->save();
        $this->audit('candidate.document_extraction_started', $user, $document, ['attempt' => $document->extraction_attempts]);

        try {
            $disk = Storage::disk('private_documents');
            if (! $disk->exists($document->storage_path)) {
                throw new DocumentExtractionException('private_source_missing');
            }
            $text = $this->extractor->extract($disk->path($document->storage_path), $document->original_filename);
            $document->forceFill([
                'extracted_text' => $text,
                'extraction_status' => 'extracted',
                'extracted_at' => now(),
                'extraction_failure_code' => null,
            ])->save();
            $this->audit('candidate.document_extracted', $user, $document, ['character_count' => mb_strlen($text)]);
        } catch (Throwable $exception) {
            $failureCode = $exception instanceof DocumentExtractionException ? $exception->failureCode : 'extraction_failed';
            $document->forceFill([
                'extracted_text' => null,
                'extraction_status' => 'failed',
                'extraction_failure_code' => $failureCode,
            ])->save();
            $this->audit('candidate.document_extraction_failed', $user, $document, ['failure_code' => $failureCode]);
        }

        return $document->refresh();
    }

    private function audit(string $action, User $user, CandidateDocument $document, array $context): void
    {
        AuditLog::record($action, [
            'user_id' => $user->getKey(),
            'entity_type' => Candidate::class,
            'entity_id' => (string) $document->candidate_id,
            'context' => ['document_id' => $document->getKey(), 'document_type' => $document->document_type, ...$context],
        ]);
    }
}
