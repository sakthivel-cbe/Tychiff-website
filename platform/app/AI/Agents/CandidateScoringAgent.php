<?php

namespace App\AI\Agents;

use App\AI\Data\AgentResult;
use App\AI\Retrieval\CandidateRetriever;
use App\AI\Retrieval\JdRetriever;
use App\AI\Retrieval\KnowledgeRetriever;
use App\AI\Retrieval\RequirementRetriever;
use App\AI\Services\AgentExecutionService;
use App\Models\Candidate;
use App\Models\CandidateEvaluation;
use App\Models\JobDescriptionVersion;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CandidateScoringAgent
{
    public function __construct(
        private readonly CandidateRetriever $candidates,
        private readonly RequirementRetriever $requirements,
        private readonly JdRetriever $jds,
        private readonly KnowledgeRetriever $knowledge,
        private readonly AgentExecutionService $execution,
    ) {}

    public function score(User $user, Candidate $candidate, Requirement $requirement, ?CandidateEvaluation $evaluation = null): AgentResult
    {
        if (! $user->can('view', $candidate) || ! $user->can('view', $requirement)) {
            throw new AuthorizationException;
        }
        $jd = $this->referenceJd($user, $requirement, $evaluation);
        if (! $jd) {
            throw new \InvalidArgumentException('Candidate scoring requires an approved JD.');
        }

        $evidence = [
            ...$this->requirements->retrieve($user, $requirement),
            ...$this->jds->retrieveVersion($user, $jd),
            ...$this->candidates->retrieve($user, $candidate),
            ...$this->knowledge->retrieve($user, $requirement->job_title.' '.$requirement->required_skills),
        ];
        $scoreSchema = [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['criterion', 'score', 'explanation', 'evidence_refs'],
            'properties' => [
                'criterion' => ['type' => 'string'], 'score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                'explanation' => ['type' => 'string'], 'evidence_refs' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1],
            ],
        ];
        $narrativeSchema = [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['detail', 'evidence_refs'],
            'properties' => ['detail' => ['type' => 'string'], 'evidence_refs' => ['type' => 'array', 'items' => ['type' => 'string']]],
        ];
        $schema = [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['overall_score', 'criteria_scores', 'matched_skills', 'missing_skills', 'strengths', 'risks', 'explanation', 'evidence_refs', 'warnings'],
            'properties' => [
                'overall_score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                'criteria_scores' => ['type' => 'array', 'items' => $scoreSchema],
                'matched_skills' => ['type' => 'array', 'items' => ['type' => 'string']],
                'missing_skills' => ['type' => 'array', 'items' => ['type' => 'string']],
                'strengths' => ['type' => 'array', 'items' => $narrativeSchema],
                'risks' => ['type' => 'array', 'items' => $narrativeSchema],
                'explanation' => ['type' => 'string'], 'evidence_refs' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1],
                'warnings' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
        ];
        $rules = [
            'overall_score' => ['required', 'integer', 'between:0,100'], 'criteria_scores' => ['present', 'array'],
            'criteria_scores.*.criterion' => ['required', 'string', 'max:180'],
            'criteria_scores.*.score' => ['required', 'integer', 'between:0,100'],
            'criteria_scores.*.explanation' => ['required', 'string', 'max:2000'],
            'criteria_scores.*.evidence_refs' => ['present', 'array'],
            'criteria_scores.*.evidence_refs.*' => ['string', 'max:180'],
            'matched_skills' => ['present', 'array'], 'missing_skills' => ['present', 'array'],
            'matched_skills.*' => ['string', 'max:180'], 'missing_skills.*' => ['string', 'max:180'],
            'strengths' => ['present', 'array'], 'risks' => ['present', 'array'],
            'strengths.*.detail' => ['required', 'string', 'max:2000'], 'strengths.*.evidence_refs' => ['present', 'array'],
            'risks.*.detail' => ['required', 'string', 'max:2000'], 'risks.*.evidence_refs' => ['present', 'array'],
            'strengths.*.evidence_refs.*' => ['string', 'max:180'], 'risks.*.evidence_refs.*' => ['string', 'max:180'],
            'explanation' => ['required', 'string', 'max:5000'], 'evidence_refs' => ['present', 'array', 'min:1'],
            'evidence_refs.*' => ['string', 'max:180'], 'warnings' => ['present', 'array'],
            'warnings.*' => ['string', 'max:2000'],
        ];

        return $this->execution->execute(
            $user, 'candidate_scoring', $requirement, $candidate, $evidence,
            'You are TYCHIFF’s candidate-role fit assistant. Provide a transparent evidence-based fit estimate, not an employment decision. Use only supplied evidence. Do not use protected characteristics or infer them. Do not reject, select, submit, or contact anyone. Explain gaps and uncertainty, and cite evidence IDs.',
            'Compare the authorized candidate profile with the approved JD and relevant requirement criteria. Score only the supplied criteria and explain the score. Treat unknown values as unknown, not as failures.',
            $schema, $rules, $evaluation,
        );
    }

    private function referenceJd(User $user, Requirement $requirement, ?CandidateEvaluation $evaluation): ?JobDescriptionVersion
    {
        if ($evaluation === null) {
            return $requirement->approvedJobDescription();
        }
        if ($evaluation->candidate_id !== $evaluation->candidateDocument?->candidate_id
            || $evaluation->requirement_id !== $requirement->getKey()) {
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
