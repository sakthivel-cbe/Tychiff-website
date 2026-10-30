<?php

namespace App\AI\Services;

use App\AI\Contracts\ResumeTextExtractor;
use App\Models\CandidateDocument;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class PrivateDocumentResumeTextExtractor implements ResumeTextExtractor
{
    public function extract(User $user, CandidateDocument $document): ?string
    {
        if ($document->status !== 'active' || ! $user->can('viewDocument', $document)) {
            throw new AuthorizationException;
        }
        if ($document->extraction_status !== 'extracted') {
            return null;
        }

        $text = $document->extracted_text;

        return is_string($text) && trim($text) !== '' ? $text : null;
    }
}
