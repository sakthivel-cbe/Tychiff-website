<?php

namespace App\AI\Agents;

use App\AI\Data\AgentResult;
use App\AI\Retrieval\JdRetriever;
use App\AI\Retrieval\KnowledgeRetriever;
use App\AI\Retrieval\RequirementRetriever;
use App\AI\Services\AgentExecutionService;
use App\Models\JobDescriptionVersion;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class JdPreparationAgent
{
    public function __construct(
        private readonly RequirementRetriever $requirements,
        private readonly JdRetriever $jds,
        private readonly KnowledgeRetriever $knowledge,
        private readonly AgentExecutionService $execution,
    ) {}

    public function prepare(User $user, Requirement $requirement): AgentResult
    {
        if (! $user->can('generateDraft', [JobDescriptionVersion::class, $requirement])) {
            throw new AuthorizationException;
        }

        $evidence = [
            ...$this->requirements->retrieve($user, $requirement),
            ...$this->jds->retrieve($user, $requirement),
            ...$this->knowledge->retrieve($user, $requirement->job_title.' '.$requirement->required_skills.' '.$requirement->qualification),
        ];
        $fields = [
            'title', 'summary', 'responsibilities', 'required_skills', 'preferred_skills', 'qualification',
            'experience_description', 'location', 'work_mode', 'employment_type', 'compensation_information', 'additional_information',
        ];
        $fields[] = 'evidence_refs';
        $properties = array_fill_keys(array_slice($fields, 0, -1), ['type' => 'string']);
        $properties['evidence_refs'] = ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1];
        $schema = [
            'type' => 'object', 'additionalProperties' => false, 'required' => $fields,
            'properties' => $properties,
        ];
        $rules = array_fill_keys(array_slice($fields, 0, -1), ['present', 'string', 'max:30000']);
        $rules['evidence_refs'] = ['present', 'array', 'min:1'];
        $rules['evidence_refs.*'] = ['string', 'max:180'];
        $system = 'You are TYCHIFF’s JD preparation agent. Draft an accurate, readable job description using only supplied evidence. Existing approved values and client facts must not be changed or invented. This result is always an unapproved draft for recruiter review; never approve, activate, publish, send to a client, or decide hiring outcomes.';

        return $this->execution->execute(
            $user, 'jd_preparation', $requirement, null, $evidence, $system,
            'Prepare or improve the job description. Preserve explicit facts and identify unsupported/missing content by leaving fields empty; never invent compensation, benefits, employer commitments or legal terms.',
            $schema, $rules,
        );
    }
}
