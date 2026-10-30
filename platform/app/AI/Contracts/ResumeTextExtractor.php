<?php

namespace App\AI\Contracts;

use App\Models\CandidateDocument;
use App\Models\User;

interface ResumeTextExtractor
{
    public function extract(User $user, CandidateDocument $document): ?string;
}
