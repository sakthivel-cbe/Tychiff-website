<?php

namespace Tests\Feature;

use App\AI\Agents\CandidateScoringAgent;
use App\AI\Agents\JdPreparationAgent;
use App\AI\Agents\ResumeValidationAgent;
use App\AI\Contracts\AiProvider;
use App\AI\Contracts\ResumeTextExtractor;
use App\AI\Data\Evidence;
use App\AI\Exceptions\AiProviderException;
use App\AI\Providers\AiProviderManager;
use App\AI\Providers\AnthropicProvider;
use App\AI\Retrieval\ResumeRetriever;
use App\AI\Services\AgentExecutionService;
use App\Models\AiAgentExecution;
use App\Models\AiKnowledgeItem;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\CandidateRecruiterAccess;
use App\Models\ClientOrganization;
use App\Models\JobDescriptionVersion;
use App\Models\Requirement;
use App\Models\RequirementAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;
use Throwable;

class AgenticRagFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_openai_only_selects_openai(): void
    {
        config(['ai.provider' => 'openai', 'ai.providers.openai.api_key' => 'test-key', 'ai.providers.openai.model' => 'test-model', 'ai.providers.anthropic.api_key' => '', 'ai.providers.anthropic.model' => '']);
        $this->assertSame(['openai'], array_map(fn ($provider) => $provider->name(), app(AiProviderManager::class)->plan()['providers']));
    }

    public function test_claude_only_selects_anthropic(): void
    {
        config(['ai.provider' => 'openai', 'ai.providers.openai.api_key' => '', 'ai.providers.openai.model' => '', 'ai.providers.anthropic.api_key' => 'test-key', 'ai.providers.anthropic.model' => 'test-model']);
        $plan = app(AiProviderManager::class)->plan();
        $this->assertSame(['anthropic'], array_map(fn ($provider) => $provider->name(), $plan['providers']));
        $this->assertSame('openai', $plan['unavailable_preference']);
    }

    public function test_anthropic_adapter_keeps_secret_in_header_and_returns_structured_json(): void
    {
        Http::fake(['https://api.anthropic.example/v1/messages' => Http::response([
            'content' => [['type' => 'text', 'text' => '{"answer":"supported"}']],
        ])]);
        $provider = new AnthropicProvider('server-only-secret', 'model-from-config', 'https://api.anthropic.example/v1/messages', '2023-06-01', 1024, 15);
        $result = $provider->generateStructured('System task.', 'Evidence source.', ['type' => 'object', 'required' => ['answer']]);

        $this->assertSame('supported', $result['answer']);
        Http::assertSent(fn ($request) => $request->hasHeader('x-api-key', 'server-only-secret')
            && $request->hasHeader('anthropic-version', '2023-06-01')
            && $request['model'] === 'model-from-config'
            && $request['output_config']['format']['type'] === 'json_schema'
            && $request['output_config']['format']['schema']['required'] === ['answer']);
    }

    public function test_both_providers_respect_openai_or_anthropic_preference(): void
    {
        config(['ai.providers.openai.api_key' => 'test-openai-key', 'ai.providers.openai.model' => 'openai-test-model', 'ai.providers.anthropic.api_key' => 'test-anthropic-key', 'ai.providers.anthropic.model' => 'anthropic-test-model', 'ai.fallback_enabled' => false]);
        config(['ai.provider' => 'openai']);
        $this->assertSame(['openai'], array_map(fn ($provider) => $provider->name(), app(AiProviderManager::class)->plan()['providers']));
        config(['ai.provider' => 'anthropic']);
        $this->assertSame(['anthropic'], array_map(fn ($provider) => $provider->name(), app(AiProviderManager::class)->plan()['providers']));
    }

    public function test_missing_preferred_provider_uses_other_configured_provider(): void
    {
        config(['ai.provider' => 'anthropic', 'ai.providers.anthropic.api_key' => '', 'ai.providers.anthropic.model' => '', 'ai.providers.openai.api_key' => 'test-key', 'ai.providers.openai.model' => 'test-model']);
        $plan = app(AiProviderManager::class)->plan();
        $this->assertSame(['openai'], array_map(fn ($provider) => $provider->name(), $plan['providers']));
        $this->assertSame('anthropic', $plan['unavailable_preference']);
    }

    public function test_no_provider_returns_controlled_unavailable_result_without_fabricating_output(): void
    {
        $user = $this->userWithRole('recruiter');
        $this->app->instance(AiProviderManager::class, new TestProviderPlanManager([]));
        $result = app(AgentExecutionService::class)->execute($user, 'test_agent', null, null, [], 'system', 'task', [], ['answer' => ['required', 'string']]);
        $this->assertFalse($result->available);
        $this->assertNull($result->data);
        $this->assertSame('no_provider_configured', $result->failureCode);
        $this->assertDatabaseHas('ai_agent_executions', ['id' => $result->executionId, 'status' => 'failed', 'failure_code' => 'no_provider_configured']);
    }

    public function test_primary_failure_uses_fallback_and_records_provider_trace(): void
    {
        $user = $this->userWithRole('recruiter');
        $primary = new TestAiProvider('openai', 'test-openai', new AiProviderException('provider_http_error'));
        $evidence = new Evidence('ev-1', 'requirement', '1', 'title', 'Engineer', 'Test source');
        $fallback = new TestAiProvider('anthropic', 'test-claude', ['answer' => 'evidence supported', 'evidence_refs' => [$evidence->providerReference]]);
        $this->app->instance(AiProviderManager::class, new TestProviderPlanManager([$primary, $fallback]));
        $result = app(AgentExecutionService::class)->execute(
            $user, 'test_agent', null, null,
            [$evidence],
            'system', 'task', ['type' => 'object', 'properties' => ['answer' => ['type' => 'string'], 'evidence_refs' => ['type' => 'array']]],
            ['answer' => ['required', 'string'], 'evidence_refs' => ['present', 'array'], 'evidence_refs.*' => ['string']],
        );
        $this->assertTrue($result->available);
        $this->assertSame('anthropic', $result->provider);
        $this->assertDatabaseHas('ai_agent_executions', ['id' => $result->executionId, 'fallback_provider' => 'anthropic', 'status' => 'succeeded']);
        $execution = AiAgentExecution::query()->findOrFail($result->executionId);
        $this->assertSame('failed', $execution->provider_trace[0]['result']);
        $this->assertSame('success', $execution->provider_trace[1]['result']);
        $this->assertDatabaseHas('ai_agent_evidence', ['execution_id' => $result->executionId, 'provider_reference' => $evidence->providerReference, 'source_type' => 'requirement', 'source_record_id' => '1', 'field' => 'title']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ai.agent_execution_succeeded', 'entity_id' => (string) $result->executionId]);
    }

    public function test_malformed_output_is_rejected_and_second_provider_is_attempted(): void
    {
        $user = $this->userWithRole('recruiter');
        $bad = new TestAiProvider('openai', 'test-openai', ['answer' => ['unexpected' => true]]);
        $good = new TestAiProvider('anthropic', 'test-claude', ['answer' => 'valid']);
        $this->app->instance(AiProviderManager::class, new TestProviderPlanManager([$bad, $good]));
        $result = app(AgentExecutionService::class)->execute($user, 'test_agent', null, null, [], 'system', 'task', ['type' => 'object', 'properties' => ['answer' => ['type' => 'string']]], ['answer' => ['required', 'string']]);
        $this->assertTrue($result->available);
        $this->assertSame('anthropic', $result->provider);
        $this->assertStringStartsWith('malformed_output', AiAgentExecution::query()->findOrFail($result->executionId)->provider_trace[0]['failure_code']);
    }

    public function test_both_provider_failures_return_controlled_failure(): void
    {
        $user = $this->userWithRole('recruiter');
        $this->app->instance(AiProviderManager::class, new TestProviderPlanManager([
            new TestAiProvider('openai', 'test-openai', new AiProviderException('provider_http_error')),
            new TestAiProvider('anthropic', 'test-claude', new AiProviderException('provider_http_error')),
        ]));
        $result = app(AgentExecutionService::class)->execute($user, 'test_agent', null, null, [], 'system', 'task', [], ['answer' => ['required', 'string']]);
        $this->assertFalse($result->available);
        $this->assertNull($result->data);
        $this->assertSame('providers_unavailable', $result->failureCode);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ai.agent_execution_failed', 'entity_id' => (string) $result->executionId]);
    }

    public function test_jd_agent_uses_authorized_requirement_approved_knowledge_and_keeps_result_a_draft(): void
    {
        $user = $this->userWithRole('recruiter');
        $requirement = $this->requirementFor($user);
        $knowledge = AiKnowledgeItem::query()->create([
            'title' => 'Approved skills guidance', 'source_type' => 'recruitment_guideline', 'content' => 'For PHP roles, keep required skills evidence based.',
            'version' => 'v1', 'approval_status' => 'approved', 'is_active' => true,
        ]);
        $fields = ['title', 'summary', 'responsibilities', 'required_skills', 'preferred_skills', 'qualification', 'experience_description', 'location', 'work_mode', 'employment_type', 'compensation_information', 'additional_information', 'evidence_refs'];
        $provider = new TestAiProvider('openai', 'test-model', function (string $prompt) use ($fields): array {
            $evidence = json_decode($prompt, true, 512, JSON_THROW_ON_ERROR)['evidence'];
            $output = array_fill_keys($fields, 'Evidence-based draft text');
            $output['evidence_refs'] = [$evidence[0]['evidence_id']];

            return $output;
        });
        $this->app->instance(AiProviderManager::class, new TestProviderPlanManager([$provider]));
        $result = app(JdPreparationAgent::class)->prepare($user, $requirement);

        $this->assertTrue($result->available);
        $this->assertContains('requirement', array_column($result->evidence, 'source_type'));
        $this->assertContains('approved_knowledge', array_column($result->evidence, 'source_type'));
        $this->assertContains((string) $knowledge->id, array_column($result->evidence, 'source_record_id'));
        $this->assertSame('Approved', $requirement->fresh()->status);
        $this->assertSame(1, JobDescriptionVersion::query()->count());
        $this->assertDatabaseHas('ai_agent_executions', ['id' => $result->executionId, 'agent_type' => 'jd_preparation', 'status' => 'succeeded']);
        $this->assertStringContainsString('no database or tools', mb_strtolower($provider->lastPrompt));
    }

    public function test_candidate_and_resume_retrieval_require_candidate_and_document_authorization(): void
    {
        $owner = $this->userWithRole('recruiter');
        $outsider = $this->userWithRole('recruiter');
        $candidate = $this->candidateFor($owner);
        $document = CandidateDocument::query()->create([
            'candidate_id' => $candidate->id, 'document_type' => 'resume', 'original_filename' => 'resume.pdf',
            'storage_path' => 'candidates/'.$candidate->reference.'/resume.pdf', 'mime_type' => 'application/pdf',
            'file_size' => 100, 'status' => 'active', 'uploaded_by' => $owner->id, 'uploaded_at' => now(),
        ]);

        $this->expectException(AuthorizationException::class);
        app(ResumeRetriever::class)->retrieve($outsider, $candidate, $document);
    }

    public function test_resume_validation_requires_approved_jd_and_uses_extracted_text_only_through_adapter(): void
    {
        $user = $this->userWithRole('recruiter');
        $requirement = $this->requirementFor($user);
        $candidate = $this->candidateFor($user);
        $candidate->forceFill([
            'mobile' => '+1 (555) 000-1234', 'mobile_normalized' => '15550001234',
            'profile_summary' => $candidate->full_name.' '.$candidate->email.' +1 (555) 000-1234 is an experienced engineer.',
        ])->save();
        $document = CandidateDocument::query()->create([
            'candidate_id' => $candidate->id, 'document_type' => 'resume', 'original_filename' => 'resume.pdf',
            'storage_path' => 'candidates/'.$candidate->reference.'/resume.pdf', 'mime_type' => 'application/pdf',
            'file_size' => 100, 'status' => 'active', 'uploaded_by' => $user->id, 'uploaded_at' => now(),
        ]);
        $extractor = new class implements ResumeTextExtractor
        {
            public function extract(User $user, CandidateDocument $document): ?string
            {
                return $document->candidate->full_name.', '.$document->candidate->email.', +1 (555) 000-1234. Resume text: five years of PHP experience.';
            }
        };
        $this->app->instance(ResumeTextExtractor::class, $extractor);
        $provider = new TestAiProvider('openai', 'test-model', function (string $prompt): array {
            $evidence = json_decode($prompt, true, 512, JSON_THROW_ON_ERROR)['evidence'];

            return [
                'matched_criteria' => [], 'missing_criteria' => [], 'unclear_criteria' => [], 'warnings' => [],
                'evidence_refs' => [$evidence[0]['evidence_id']], 'confidence' => 'low',
            ];
        });
        $this->app->instance(AiProviderManager::class, new TestProviderPlanManager([$provider]));
        $result = app(ResumeValidationAgent::class)->validate($user, $candidate, $document, $requirement);

        $this->assertTrue($result->available, json_encode(AiAgentExecution::query()->find($result->executionId)?->provider_trace));
        $this->assertContains('candidate_document', array_column($result->evidence, 'source_type'));
        $this->assertStringContainsString('Resume text: five years', $provider->lastPrompt);
        $this->assertStringNotContainsString($candidate->full_name, $provider->lastPrompt);
        $this->assertStringNotContainsString($candidate->email, $provider->lastPrompt);
        $this->assertStringNotContainsString('+1 (555) 000-1234', $provider->lastPrompt);
        $this->assertDatabaseHas('ai_agent_executions', ['id' => $result->executionId, 'agent_type' => 'resume_validation', 'candidate_id' => $candidate->id]);
    }

    public function test_candidate_scoring_is_explainable_and_does_not_change_business_status(): void
    {
        $user = $this->userWithRole('recruiter');
        $requirement = $this->requirementFor($user);
        $candidate = $this->candidateFor($user);
        $provider = new TestAiProvider('anthropic', 'test-model', function (string $prompt): array {
            $evidence = json_decode($prompt, true, 512, JSON_THROW_ON_ERROR)['evidence'];
            $reference = collect($evidence)->firstWhere('field', 'primary_skills')['evidence_id'];

            return [
                'overall_score' => 70, 'criteria_scores' => [['criterion' => 'PHP skills', 'score' => 80, 'explanation' => 'Profile lists PHP.', 'evidence_refs' => [$reference]]],
                'matched_skills' => ['PHP'], 'missing_skills' => [], 'strengths' => [], 'risks' => [],
                'explanation' => 'The profile lists PHP experience.', 'evidence_refs' => [$reference], 'warnings' => [],
            ];
        });
        $this->app->instance(AiProviderManager::class, new TestProviderPlanManager([$provider]));
        $result = app(CandidateScoringAgent::class)->score($user, $candidate, $requirement);

        $this->assertTrue($result->available, json_encode(AiAgentExecution::query()->find($result->executionId)?->provider_trace));
        $this->assertSame(70, $result->data['overall_score']);
        $this->assertSame('Active', $candidate->fresh()->status);
        $this->assertSame('Approved', $requirement->fresh()->status);
        $this->assertDatabaseHas('ai_agent_executions', ['id' => $result->executionId, 'agent_type' => 'candidate_scoring', 'status' => 'succeeded']);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->value('id'));

        return $user;
    }

    private function requirementFor(User $recruiter): Requirement
    {
        $manager = $this->userWithRole('recruitment-manager');
        $management = $this->userWithRole('management');
        $client = ClientOrganization::query()->create(['name' => 'AI Test Client '.Str::random(6)]);
        $requirement = Requirement::query()->create([
            'reference' => 'REQ-'.Str::upper(Str::random(20)), 'client_organization_id' => $client->id,
            'title' => 'AI test requirement', 'job_title' => 'Senior PHP Engineer', 'openings' => 1,
            'employment_type' => 'Full-time', 'location' => 'Remote', 'work_mode' => 'Remote', 'priority' => 'Normal',
            'status' => 'Approved', 'intake_source' => 'Client RFP', 'required_skills' => 'PHP, Laravel',
            'min_experience_years' => 4, 'max_experience_years' => 10, 'created_by' => $management->id,
            'recruitment_manager_id' => $manager->id,
        ]);
        RequirementAssignment::query()->create(['requirement_id' => $requirement->id, 'recruiter_id' => $recruiter->id, 'assigned_by' => $manager->id, 'assigned_at' => now()]);
        JobDescriptionVersion::query()->create([
            'requirement_id' => $requirement->id, 'version' => 1, 'title' => 'Senior PHP Engineer',
            'summary' => 'Build services using PHP and Laravel.', 'responsibilities' => 'Develop and maintain backend services.',
            'required_skills' => 'PHP, Laravel', 'preferred_skills' => 'MySQL', 'qualification' => 'Bachelor degree preferred',
            'experience_description' => '4 to 10 years', 'location' => 'Remote', 'work_mode' => 'Remote',
            'employment_type' => 'Full-time', 'compensation_information' => '', 'additional_information' => '',
            'source' => 'manual', 'status' => 'Approved', 'created_by' => $recruiter->id,
        ]);

        return $requirement;
    }

    private function candidateFor(User $recruiter): Candidate
    {
        $email = Str::lower(Str::random(10)).'@example.test';
        $candidate = Candidate::query()->create([
            'reference' => 'CAN-'.Str::upper((string) Str::ulid()), 'full_name' => 'AI Test Candidate',
            'email' => $email, 'email_normalized' => $email,
            'current_designation' => 'PHP Engineer', 'total_experience_years' => 6, 'relevant_experience_years' => 5,
            'primary_skills' => 'PHP, Laravel', 'highest_qualification' => 'Bachelor degree', 'status' => 'Active',
            'source' => 'Referral', 'created_by' => $recruiter->id,
        ]);
        CandidateRecruiterAccess::query()->create([
            'candidate_id' => $candidate->id, 'recruiter_id' => $recruiter->id, 'status' => 'active',
            'granted_by' => $recruiter->id, 'granted_at' => now(),
        ]);

        return $candidate;
    }
}

class TestProviderPlanManager extends AiProviderManager
{
    public function __construct(private readonly array $testProviders) {}

    public function plan(): array
    {
        return ['providers' => $this->testProviders, 'unavailable_preference' => null];
    }
}

class TestAiProvider implements AiProvider
{
    public string $lastPrompt = '';

    public function __construct(private readonly string $providerName, private readonly string $modelName, private readonly array|Throwable|\Closure $result) {}

    public function name(): string
    {
        return $this->providerName;
    }

    public function model(): string
    {
        return $this->modelName;
    }

    public function generateStructured(string $systemPrompt, string $userPrompt, array $schema): array
    {
        $this->lastPrompt = $systemPrompt."\n".$userPrompt;
        if ($this->result instanceof Throwable) {
            throw $this->result;
        }
        if ($this->result instanceof \Closure) {
            return ($this->result)($userPrompt);
        }

        return $this->result;
    }
}
