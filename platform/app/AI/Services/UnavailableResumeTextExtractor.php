<?php

namespace App\AI\Services;

use App\AI\Contracts\ResumeTextExtractor;
use App\Models\CandidateDocument;
use App\Models\User;

class UnavailableResumeTextExtractor implements ResumeTextExtractor
{
    public function extract(User $user, CandidateDocument $document): ?string
    {
        return null;
    }
}
