<?php

namespace App\AI\Retrieval;

use App\AI\Data\Evidence;
use App\AI\Services\CandidateTextRedactor;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CandidateRetriever
{
    public function __construct(private readonly CandidateTextRedactor $redactor) {}

    public function retrieve(User $user, Candidate $candidate): array
    {
        if (! $user->can('view', $candidate)) {
            throw new AuthorizationException;
        }

        $fields = [
            'current_designation' => $candidate->current_designation,
            'total_experience_years' => $candidate->total_experience_years,
            'relevant_experience_years' => $candidate->relevant_experience_years,
            'primary_skills' => $candidate->primary_skills,
            'secondary_skills' => $candidate->secondary_skills,
            'highest_qualification' => $candidate->highest_qualification,
            'current_employer' => $candidate->current_employer,
            'current_ctc' => $candidate->current_ctc,
            'expected_ctc' => $candidate->expected_ctc,
            'notice_period_days' => $candidate->notice_period_days,
            'preferred_location' => $candidate->preferred_location,
            'preferred_work_mode' => $candidate->preferred_work_mode,
            'availability' => $candidate->availability,
            'profile_summary' => $candidate->profile_summary,
        ];
        $evidence = [];
        foreach ($fields as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $evidence[] = new Evidence('candidate-'.$candidate->getKey().'-'.$field, 'candidate', (string) $candidate->getKey(), $field, $this->redactor->redact((string) $value, $candidate), 'Authorized candidate profile field relevant to role fit.');
        }

        return $evidence;
    }
}
