<?php

namespace App\AI\Retrieval;

use App\AI\Data\Evidence;
use App\Models\JobDescriptionVersion;
use App\Models\Requirement;
use App\Models\RequirementDocument;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class JdRetriever
{
    public function retrieve(User $user, Requirement $requirement, bool $requireApproved = false): array
    {
        if (! $user->can('view', $requirement)) {
            throw new AuthorizationException;
        }

        $query = $requirement->jobDescriptionVersions();
        if ($requireApproved) {
            $query->where('status', 'Approved');
        }
        $jd = $query->first();
        if (! $jd) {
            return [];
        }
        if (! $user->can('view', $jd)) {
            throw new AuthorizationException;
        }

        return $this->fromVersion($jd, $requireApproved ? 'Approved JD evidence.' : 'Existing JD version context.');
    }

    public function retrieveVersion(User $user, JobDescriptionVersion $jd): array
    {
        $jd->loadMissing('requirement');
        if (! $user->can('view', $jd)) {
            throw new AuthorizationException;
        }

        return $this->fromVersion($jd, 'Authorized JD version evidence.');
    }

    private function fromVersion(JobDescriptionVersion $jd, string $reason): array
    {
        $result = [];
        foreach (JobDescriptionVersion::CONTENT_FIELDS as $field) {
            $value = $jd->{$field};
            if ($value === null || $value === '') {
                continue;
            }
            $result[] = new Evidence('jd-'.$jd->getKey().'-'.$field, 'job_description_version', (string) $jd->getKey(), $field, (string) $value, $reason.' Version '.$jd->version.'; status '.$jd->status.'.');
        }

        $documents = RequirementDocument::query()
            ->where('job_description_version_id', $jd->getKey())
            ->where('extraction_status', 'extracted')
            ->orderBy('id')->get();
        foreach ($documents as $document) {
            if (! is_string($document->extracted_text) || trim($document->extracted_text) === '') {
                continue;
            }
            $result[] = new Evidence(
                'requirement-document-'.$document->getKey().'-text', 'requirement_document', (string) $document->getKey(),
                'extracted_text', $document->extracted_text,
                'Authorized extracted text from '.$document->original_filename.'; JD version '.$jd->version.' ('.$jd->status.').',
            );
        }

        return $result;
    }
}
