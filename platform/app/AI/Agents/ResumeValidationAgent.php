<?php

namespace App\AI\Agents;

use App\AI\Data\AgentResult;
use App\AI\Data\Evidence;
use App\AI\Retrieval\CandidateRetriever;
use App\AI\Retrieval\JdRetriever;
use App\AI\Retrieval\RequirementRetriever;
use App\AI\Retrieval\ResumeRetriever;
use App\AI\Services\AgentExecutionService;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\CandidateEvaluation;
use App\Models\JobDescriptionVersion;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ResumeValidationAgent
{
    public function __construct(
        private readonly CandidateRetriever $candidates,
        private readonly ResumeRetriever $resumes,
        private readonly RequirementRetriever $requirements,
        private readonly JdRetriever $jds,
        private readonly AgentExecutionService $execution,
    ) {}

    public function validate(User $user, Candidate $candidate, CandidateDocument $document, Requirement $requirement, ?CandidateEvaluation $evaluation = null): AgentResult
    {
        if (! $user->can('view', $candidate) || ! $user->can('viewDocument', $document) || ! $user->can('view', $requirement)) {
            throw new AuthorizationException;
        }
        if ($document->candidate_id !== $candidate->getKey()) {
            throw new AuthorizationException;
        }
        $jd = $this->referenceJd($user, $requirement, $evaluation);
        if (! $jd) {
            throw new \InvalidArgumentException('Resume validation requires an approved JD.');
        }

        $resumeEvidence = $this->resumes->retrieve($user, $candidate, $document);
        if ($resumeEvidence === []) {
            $resumeEvidence[] = new Evidence(
                'resume-'.$document->getKey().'-text-unavailable', 'candidate_document', (string) $document->getKey(),
                'extraction_status', 'Resume file text is not available to this agent. Assess profile evidence only and state that the file was not parsed.',
                'The configured resume text extraction adapter did not provide text.',
            );
        }
        $evidence = [
            ...$this->requirements->retrieve($user, $requirement),
            ...$this->jds->retrieveVersion($user, $jd),
            ...$this->candidates->retrieve($user, $candidate),
            ...$resumeEvidence,
        ];
        $criterionSchema = [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['criterion', 'detail', 'evidence_refs'],
            'properties' => [
                'criterion' => ['type' => 'string'], 'detail' => ['type' => 'string'],
                'evidence_refs' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1],
            ],
        ];
        $schema = [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['overall_validation', 'required_criteria', 'matched_criteria', 'missing_criteria', 'unclear_criteria', 'warnings', 'evidence_refs', 'confidence'],
            'properties' => [
                'overall_validation' => ['type' => 'string', 'enum' => ['evidence_available', 'insufficient_evidence']],
                'required_criteria' => ['type' => 'array', 'items' => ['type' => 'string']],
                'matched_criteria' => ['type' => 'array', 'items' => $criterionSchema],
                'missing_criteria' => ['type' => 'array', 'items' => $criterionSchema],
                'unclear_criteria' => ['type' => 'array', 'items' => $criterionSchema],
                'warnings' => ['type' => 'array', 'items' => ['type' => 'string']],
                'evidence_refs' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1],
                'confidence' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
            ],
        ];
        $rules = [
            'overall_validation' => ['required', 'in:evidence_available,insufficient_evidence'],
            'required_criteria' => ['present', 'array'], 'required_criteria.*' => ['string', 'max:180'],
            'matched_criteria' => ['present', 'array'], 'missing_criteria' => ['present', 'array'],
            'unclear_criteria' => ['present', 'array'], 'warnings' => ['present', 'array'],
            'matched_criteria.*.criterion' => ['required', 'string', 'max:180'],
            'matched_criteria.*.detail' => ['required', 'string', 'max:2000'],
            'matched_criteria.*.evidence_refs' => ['present', 'array'],
            'matched_criteria.*.evidence_refs.*' => ['string', 'max:180'],
            'missing_criteria.*.criterion' => ['required', 'string', 'max:180'],
            'missing_criteria.*.detail' => ['required', 'string', 'max:2000'],
            'missing_criteria.*.evidence_refs' => ['present', 'array'],
            'missing_criteria.*.evidence_refs.*' => ['string', 'max:180'],
            'unclear_criteria.*.criterion' => ['required', 'string', 'max:180'],
            'unclear_criteria.*.detail' => ['required', 'string', 'max:2000'],
            'unclear_criteria.*.evidence_refs' => ['present', 'array'],
            'unclear_criteria.*.evidence_refs.*' => ['string', 'max:180'],
            'warnings.*' => ['string', 'max:2000'],
            'evidence_refs' => ['present', 'array', 'min:1'], 'evidence_refs.*' => ['string', 'max:180'],
            'confidence' => ['required', 'in:low,medium,high'],
        ];

        return $this->execution->execute(
            $user, 'resume_validation', $requirement, $candidate, $evidence,
            'You are TYCHIFF’s resume validation agent. Compare supplied candidate profile/resume evidence only with the approved JD and requirement. Never reject or select a candidate. Report missing information and contradictions as uncertainty. Every finding must cite supplied evidence IDs; do not invent facts.',
            'Validate the candidate evidence against explicit JD requirements. Separate matched, missing, and unclear criteria. If resume text is unavailable, clearly state that validation is profile-only and lower confidence.',
            $schema, $rules, $evaluation,
        );
    }

    private function referenceJd(User $user, Requirement $requirement, ?CandidateEvaluation $evaluation): ?JobDescriptionVersion
    {
        if ($evaluation === null) {
            return $requirement->approvedJobDescription();
        }
        if ($evaluation->candidate_id === null || $evaluation->requirement_id !== $requirement->getKey()) {
            throw new AuthorizationException;
        }
        $jd = JobDescriptionVersion::query()->find($evaluation->job_description_version_id);
        $testMode = $evaluation->is_test_only && app()->environment('local', 'testing')
            && config('ai.test_workflow.enabled') === true && $requirement->is_test_fixture
            && $jd?->is_test_fixture && in_array($jd->status, ['Draft', 'AI Draft'], true);
        if (! $jd || (! $testMode && $jd->status !== 'Approved') || ! $user->can('view', $jd)) {
            throw new \InvalidArgumentException('Evaluation requires an authorized approved JD or an enabled local test fixture.');
        }

        return $jd;
    }
}
