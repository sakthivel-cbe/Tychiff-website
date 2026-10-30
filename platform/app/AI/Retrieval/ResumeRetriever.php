<?php

namespace App\AI\Retrieval;

use App\AI\Contracts\ResumeTextExtractor;
use App\AI\Data\Evidence;
use App\AI\Services\CandidateTextRedactor;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ResumeRetriever
{
    public function __construct(private readonly ResumeTextExtractor $extractor, private readonly CandidateTextRedactor $redactor) {}

    public function retrieve(User $user, Candidate $candidate, CandidateDocument $document): array
    {
        if ($document->candidate_id !== $candidate->getKey() || ! $user->can('view', $candidate) || ! $user->can('viewDocument', $document)) {
            throw new AuthorizationException;
        }

        $text = $this->extractor->extract($user, $document);
        if (! is_string($text) || trim($text) === '') {
            return [];
        }

        return [new Evidence(
            'resume-'.$document->getKey().'-text', 'candidate_document', (string) $document->getKey(), 'extracted_text',
            mb_substr($this->redactor->redact($text, $candidate), 0, 30000), 'Authorized resume text supplied by the configured extraction adapter.',
        )];
    }
}
