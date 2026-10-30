<?php

namespace App\AI\Services;

use App\AI\Agents\CandidateScoringAgent;
use App\AI\Agents\ResumeValidationAgent;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\CandidateEvaluation;
use App\Models\JobDescriptionVersion;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Throwable;

class CandidateEvaluationService
{
    public function __construct(
        private readonly ResumeValidationAgent $validation,
        private readonly CandidateScoringAgent $scoring,
    ) {}

    public function run(User $user, Candidate $candidate, CandidateDocument $document, Requirement $requirement, JobDescriptionVersion $jd): CandidateEvaluation
    {
        $testOnly = $this->authorize($user, $candidate, $document, $requirement, $jd);
        $evaluation = CandidateEvaluation::query()->create([
            'candidate_id' => $candidate->getKey(), 'candidate_document_id' => $document->getKey(),
            'requirement_id' => $requirement->getKey(), 'job_description_version_id' => $jd->getKey(),
            'requested_by' => $user->getKey(), 'is_test_only' => $testOnly,
            'status' => 'running', 'validation_status' => 'pending', 'scoring_status' => 'pending', 'started_at' => now(),
        ]);
        AuditLog::record('candidate.evaluation_requested', [
            'user_id' => $user->getKey(), 'entity_type' => CandidateEvaluation::class,
            'entity_id' => (string) $evaluation->getKey(),
            'context' => ['requirement_id' => $requirement->getKey(), 'candidate_id' => $candidate->getKey(), 'job_description_version_id' => $jd->getKey(), 'test_only' => $testOnly],
        ]);

        if ($document->extraction_status !== 'extracted' || ! is_string($document->extracted_text) || trim($document->extracted_text) === '') {
            return $this->finishUnavailable($evaluation, 'resume_text_unavailable');
        }
        if ($jd->requirementDocuments()->where('extraction_status', 'extracted')->doesntExist()) {
            return $this->finishUnavailable($evaluation, 'jd_text_unavailable');
        }

        try {
            $validation = $this->validation->validate($user, $candidate, $document, $requirement, $evaluation);
            $evaluation->validation_status = $validation->available ? 'completed' : 'unavailable';
            $evaluation->validation_result = $validation->data;
            $evaluation->failure_code = $validation->failureCode;
            $evaluation->save();

            // Do not calculate or imply fit when validation inference did not execute.
            if (! $validation->available) {
                $evaluation->scoring_status = 'unavailable';
                $evaluation->status = 'unavailable';
                $evaluation->failure_code ??= 'live_ai_unavailable';
                $evaluation->completed_at = now();
                $evaluation->save();

                return $evaluation->refresh();
            }

            $scoring = $this->scoring->score($user, $candidate, $requirement, $evaluation);
            $evaluation->scoring_status = $scoring->available ? 'completed' : 'unavailable';
            $evaluation->scoring_result = $scoring->data;
            $evaluation->failure_code = $scoring->failureCode;
            $evaluation->status = $scoring->available ? 'completed' : 'unavailable';
        } catch (Throwable $exception) {
            $evaluation->failure_code = 'evaluation_failed';
            $evaluation->status = 'failed';
            AuditLog::record('candidate.evaluation_failed', [
                'user_id' => $user->getKey(), 'entity_type' => CandidateEvaluation::class,
                'entity_id' => (string) $evaluation->getKey(), 'context' => ['failure_code' => 'evaluation_failed'],
            ]);
        }

        $evaluation->completed_at = now();
        $evaluation->save();

        return $evaluation->refresh();
    }

    /** @param Collection<int, CandidateDocument> $documents */
    public function bulk(User $user, Collection $documents, Requirement $requirement, JobDescriptionVersion $jd): array
    {
        $results = [];
        foreach ($documents as $document) {
            try {
                $candidate = $document->candidate;
                $results[] = $this->run($user, $candidate, $document, $requirement, $jd);
            } catch (Throwable) {
                $results[] = ['candidate_document_id' => $document->getKey(), 'status' => 'failed', 'failure_code' => 'evaluation_failed'];
            }
        }

        return $results;
    }

    private function authorize(User $user, Candidate $candidate, CandidateDocument $document, Requirement $requirement, JobDescriptionVersion $jd): bool
    {
        if ($document->candidate_id !== $candidate->getKey() || $jd->requirement_id !== $requirement->getKey()
            || ! $user->can('view', $candidate) || ! $user->can('viewDocument', $document)
            || ! $user->can('view', $requirement) || ! $user->can('view', $jd)) {
            throw new AuthorizationException;
        }
        $testOnly = $candidate->is_test_fixture && $document->is_test_fixture && $requirement->is_test_fixture
            && $jd->is_test_fixture && config('ai.test_workflow.enabled') === true
            && app()->environment('local', 'testing') && in_array($jd->status, ['Draft', 'AI Draft'], true);
        if (! $testOnly && $jd->status !== 'Approved') {
            throw new AuthorizationException;
        }

        return $testOnly;
    }

    private function finishUnavailable(CandidateEvaluation $evaluation, string $code): CandidateEvaluation
    {
        $evaluation->forceFill([
            'status' => 'unavailable', 'validation_status' => 'unavailable', 'scoring_status' => 'unavailable',
            'failure_code' => $code, 'completed_at' => now(),
        ])->save();

        return $evaluation->refresh();
    }
}
