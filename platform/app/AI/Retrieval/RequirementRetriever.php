<?php

namespace App\AI\Retrieval;

use App\AI\Data\Evidence;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class RequirementRetriever
{
    public function retrieve(User $user, Requirement $requirement): array
    {
        if (! $user->can('view', $requirement)) {
            throw new AuthorizationException;
        }

        $requirement->loadMissing('clientOrganization');
        $fields = [
            'reference' => $requirement->reference,
            'role_title' => $requirement->job_title,
        ];
        if (! $requirement->is_test_fixture) {
            $fields += [
                'openings' => $requirement->openings,
                'employment_type' => $requirement->employment_type,
                'location' => $requirement->location,
                'work_mode' => $requirement->work_mode,
                'salary_ctc' => $requirement->salary_ctc,
                'experience_range' => [$requirement->min_experience_years, $requirement->max_experience_years],
                'required_skills' => $requirement->required_skills,
                'qualification' => $requirement->qualification,
                'client_provided_job_description' => $requirement->job_description,
            ];
        }

        return $this->evidence($requirement, $fields, 'Authorized requirement facts for this AI task.');
    }

    private function evidence(Requirement $requirement, array $fields, string $reason): array
    {
        $result = [];
        foreach ($fields as $field => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            $content = is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : (string) $value;
            $result[] = new Evidence('req-'.$requirement->getKey().'-'.$field, 'requirement', (string) $requirement->getKey(), $field, $content, $reason);
        }

        return $result;
    }
}
