<?php

namespace App\Http\Controllers;

use App\Contracts\JdDraftingProvider;
use App\Exceptions\JdDraftingUnavailableException;
use App\Models\AuditLog;
use App\Models\JobDescriptionClientDecision;
use App\Models\JobDescriptionVersion;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class JobDescriptionController extends Controller
{
    public function index(Requirement $requirement): View
    {
        $this->authorize('view', $requirement);
        $requirement->load(['clientOrganization', 'jobDescriptionVersions.creator', 'jobDescriptionVersions.reviewer', 'jobDescriptionVersions.approvalRecorder']);
        $canCreate = request()->user()->can('createForRequirement', [JobDescriptionVersion::class, $requirement]);
        $latest = $requirement->jobDescriptionVersions->first();

        return view('job-descriptions.index', compact('requirement', 'canCreate', 'latest'));
    }

    public function create(Requirement $requirement): View
    {
        $this->authorize('createForRequirement', [JobDescriptionVersion::class, $requirement]);

        if ($requirement->jobDescriptionVersions()->exists()) {
            return abort(409, 'A JD version already exists. Create a new version from the version history.');
        }

        return view('job-descriptions.create', [
            'requirement' => $requirement->load('clientOrganization'),
            'jd' => $this->draftDefaults($requirement),
            'action' => route('job-descriptions.store', $requirement),
            'method' => 'post',
        ]);
    }

    public function store(Request $request, Requirement $requirement): RedirectResponse
    {
        $this->authorize('createForRequirement', [JobDescriptionVersion::class, $requirement]);
        abort_if($requirement->jobDescriptionVersions()->exists(), 409, 'A JD version already exists.');
        $content = array_replace($this->draftDefaults($requirement), $this->validatedContent($request));

        $jd = DB::transaction(function () use ($request, $requirement, $content): JobDescriptionVersion {
            $lockedRequirement = Requirement::query()->lockForUpdate()->findOrFail($requirement->getKey());
            abort_if($lockedRequirement->jobDescriptionVersions()->exists(), 409, 'A JD version already exists.');

            $jd = $lockedRequirement->jobDescriptionVersions()->create([
                ...$content,
                'version' => 1,
                'source' => 'manual',
                'status' => 'Draft',
                'created_by' => $request->user()->getKey(),
            ]);

            $this->audit('jd.created', $jd, null, ['version' => 1, 'status' => 'Draft', 'source' => 'manual']);

            return $jd;
        });

        return redirect()->route('job-descriptions.show', $jd)->with('status', 'JD draft created.');
    }

    public function generate(Request $request, Requirement $requirement, JdDraftingProvider $provider): RedirectResponse
    {
        $this->authorize('generateDraft', [JobDescriptionVersion::class, $requirement]);
        $latest = $requirement->latestJobDescription();

        if ($latest && ! in_array($latest->status, ['Draft', 'AI Draft', 'Changes Requested', 'Approved', 'Superseded'], true)) {
            throw ValidationException::withMessages(['jd' => 'This JD version is under review or client approval and cannot be replaced.']);
        }

        $facts = [
            'job_title' => $requirement->job_title,
            'openings' => $requirement->openings,
            'employment_type' => $requirement->employment_type,
            'location' => $requirement->location,
            'work_mode' => $requirement->work_mode,
            'salary_ctc_supplied_by_client' => $requirement->salary_ctc,
            'minimum_experience_years' => $requirement->min_experience_years,
            'maximum_experience_years' => $requirement->max_experience_years,
            'required_skills_supplied' => $requirement->required_skills,
            'qualification_supplied' => $requirement->qualification,
            'client_job_description_supplied' => $requirement->job_description,
            '_context' => [
                'requirement_id' => $requirement->getKey(),
                'user_id' => $request->user()->getKey(),
            ],
        ];

        try {
            $generated = $provider->generate($facts);
        } catch (JdDraftingUnavailableException $exception) {
            Log::warning('JD draft provider unavailable', ['exception_class' => $exception::class]);
            $this->audit('jd.ai_draft_failed', null, null, [
                'requirement_id' => $requirement->getKey(),
                'reason' => 'provider_unavailable',
            ]);

            return back()->withErrors(['ai' => $exception->getMessage()])->withInput();
        } catch (Throwable $exception) {
            Log::warning('JD draft provider failed', ['exception_class' => $exception::class]);
            $this->audit('jd.ai_draft_failed', null, null, [
                'requirement_id' => $requirement->getKey(),
                'reason' => 'provider_error',
            ]);

            return back()->withErrors(['ai' => 'AI drafting is temporarily unavailable. You can continue with manual JD preparation.'])->withInput();
        }

        $jd = DB::transaction(function () use ($request, $requirement, $generated): JobDescriptionVersion {
            $lockedRequirement = Requirement::query()->lockForUpdate()->findOrFail($requirement->getKey());
            $currentLatest = $lockedRequirement->jobDescriptionVersions()->first();
            $content = $this->aiContent($generated, $lockedRequirement);

            if ($currentLatest && in_array($currentLatest->status, ['Draft', 'AI Draft'], true)) {
                $before = $currentLatest->only(JobDescriptionVersion::CONTENT_FIELDS);
                $currentLatest->fill($content)->forceFill(['source' => 'ai_assisted', 'status' => 'AI Draft'])->save();
                $jd = $currentLatest;
                $this->audit('jd.ai_draft_generated', $jd, $before, [...$content, 'status' => 'AI Draft']);

                return $jd;
            }

            if ($currentLatest && $currentLatest->status !== 'Approved' && $currentLatest->status !== 'Superseded') {
                throw ValidationException::withMessages(['jd' => 'This JD version changed while the draft was being generated. Review the current version and try again.']);
            }

            $version = (int) $lockedRequirement->jobDescriptionVersions()->max('version') + 1;
            $jd = $lockedRequirement->jobDescriptionVersions()->create([
                ...$content,
                'version' => $version,
                'source' => 'ai_assisted',
                'status' => 'AI Draft',
                'created_by' => $request->user()->getKey(),
            ]);
            $this->audit('jd.version_created', $jd, null, ['version' => $version, 'source' => 'ai_assisted']);
            $this->audit('jd.ai_draft_generated', $jd, null, [...$content, 'status' => 'AI Draft']);

            return $jd;
        });

        return redirect()->route('job-descriptions.show', $jd)
            ->with('status', 'AI-assisted draft created. Review and edit all content before submitting it for review.');
    }

    public function show(JobDescriptionVersion $jobDescription): View
    {
        $this->authorize('view', $jobDescription);
        $jobDescription->load([
            'requirement.clientOrganization', 'creator', 'reviewer', 'approvalRecorder',
            'clientDecisions.clientOrganization', 'clientDecisions.recorder',
        ]);
        $jobDescription->requirement->load(['jobDescriptionVersions.creator', 'jobDescriptionVersions.reviewer', 'jobDescriptionVersions.approvalRecorder']);

        return view('job-descriptions.show', ['jd' => $jobDescription]);
    }

    public function update(Request $request, JobDescriptionVersion $jobDescription): RedirectResponse
    {
        $this->authorize('edit', $jobDescription);
        $content = $this->validatedContent($request);
        DB::transaction(function () use ($jobDescription, $content): void {
            $jd = JobDescriptionVersion::query()->lockForUpdate()->findOrFail($jobDescription->getKey());
            $before = $jd->only(JobDescriptionVersion::CONTENT_FIELDS);
            $jd->fill($content)->save();
            $this->audit('jd.edited', $jd, $before, $jd->only(JobDescriptionVersion::CONTENT_FIELDS));
        });

        return back()->with('status', 'JD draft updated.');
    }

    public function submitForReview(Request $request, JobDescriptionVersion $jobDescription): RedirectResponse
    {
        $this->authorize('submitForReview', $jobDescription);
        $this->transition($jobDescription, 'Recruiter Review', 'jd.submitted_for_review', $request->user());

        return back()->with('status', 'JD submitted for Recruitment Manager review.');
    }

    public function sendForClientApproval(Request $request, JobDescriptionVersion $jobDescription): RedirectResponse
    {
        $this->authorize('sendForClientApproval', $jobDescription);
        DB::transaction(function () use ($request, $jobDescription): void {
            $jd = JobDescriptionVersion::query()->lockForUpdate()->findOrFail($jobDescription->getKey());
            if ($jd->status !== 'Recruiter Review') {
                throw ValidationException::withMessages(['jd' => 'This version is no longer awaiting internal review.']);
            }

            $oldStatus = $jd->status;
            $jd->forceFill([
                'status' => 'Sent for Client Approval',
                'reviewed_by' => $request->user()->getKey(),
                'reviewed_at' => now(),
            ])->save();
            $this->audit('jd.sent_for_client_approval', $jd, ['status' => $oldStatus], ['status' => $jd->status]);
        });

        return back()->with('status', 'JD marked ready and sent for client approval. Record the client decision when received.');
    }

    public function returnToRecruiter(Request $request, JobDescriptionVersion $jobDescription): RedirectResponse
    {
        $this->authorize('returnToRecruiter', $jobDescription);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:2000']]);
        DB::transaction(function () use ($jobDescription, $validated): void {
            $jd = JobDescriptionVersion::query()->lockForUpdate()->findOrFail($jobDescription->getKey());
            if ($jd->status !== 'Recruiter Review') {
                throw ValidationException::withMessages(['jd' => 'This version is no longer in Recruitment Manager review.']);
            }

            $oldStatus = $jd->status;
            $jd->forceFill(['status' => 'Draft'])->save();
            $this->audit('jd.returned_to_recruiter', $jd, ['status' => $oldStatus], ['status' => 'Draft'], ['reason' => $validated['reason']]);
        });

        return back()->with('status', 'JD returned to the Recruiter for revision.');
    }

    public function recordClientDecision(Request $request, JobDescriptionVersion $jobDescription): RedirectResponse
    {
        $this->authorize('recordClientDecision', $jobDescription);
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,changes_requested'],
            'client_spoc_reference' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'required_if:decision,changes_requested', 'string', 'max:10000'],
        ]);

        DB::transaction(function () use ($request, $jobDescription, $validated): void {
            $jd = JobDescriptionVersion::query()->lockForUpdate()->findOrFail($jobDescription->getKey());
            if ($jd->status !== 'Sent for Client Approval') {
                throw ValidationException::withMessages(['decision' => 'This version is no longer awaiting a client decision.']);
            }

            $decision = JobDescriptionClientDecision::create([
                'job_description_version_id' => $jd->getKey(),
                'client_organization_id' => $jd->requirement->client_organization_id,
                'client_spoc_reference' => $validated['client_spoc_reference'] ?? null,
                'decision' => $validated['decision'],
                'comments' => $validated['comments'] ?? null,
                'recorded_by' => $request->user()->getKey(),
                'decided_at' => now(),
            ]);

            $oldStatus = $jd->status;
            if ($validated['decision'] === 'approved') {
                $jd->forceFill([
                    'status' => 'Approved',
                    'approved_by_reference' => $validated['client_spoc_reference'] ?? null,
                    'approval_recorded_by' => $request->user()->getKey(),
                    'approved_at' => now(),
                ])->save();

                JobDescriptionVersion::query()
                    ->where('requirement_id', $jd->requirement_id)
                    ->where('id', '!=', $jd->getKey())
                    ->where('status', 'Approved')
                    ->lockForUpdate()
                    ->get()
                    ->each(function (JobDescriptionVersion $previous): void {
                        $old = $previous->status;
                        $previous->forceFill(['status' => 'Superseded'])->save();
                        $this->audit('jd.superseded', $previous, ['status' => $old], ['status' => 'Superseded']);
                    });

                $this->audit('jd.client_approved', $jd, ['status' => $oldStatus], ['status' => 'Approved'], [
                    'decision_id' => $decision->getKey(),
                    'client_spoc_reference' => $validated['client_spoc_reference'] ?? null,
                    'comments' => $validated['comments'] ?? null,
                ]);
                $this->audit('jd.approved', $jd, ['status' => $oldStatus], ['status' => 'Approved'], ['decision_id' => $decision->getKey()]);

                return;
            }

            $jd->forceFill(['status' => 'Changes Requested'])->save();
            $this->audit('jd.changes_requested', $jd, ['status' => $oldStatus], ['status' => 'Changes Requested'], [
                'decision_id' => $decision->getKey(),
                'client_spoc_reference' => $validated['client_spoc_reference'] ?? null,
                'comments' => $validated['comments'] ?? null,
            ]);

            $version = (int) $jd->requirement->jobDescriptionVersions()->max('version') + 1;
            $newVersion = $jd->requirement->jobDescriptionVersions()->create([
                ...$jd->only(JobDescriptionVersion::CONTENT_FIELDS),
                'version' => $version,
                'source' => 'client_changes',
                'status' => 'Draft',
                'created_by' => $request->user()->getKey(),
            ]);
            $this->audit('jd.version_created', $newVersion, null, [
                'version' => $version,
                'source' => 'client_changes',
                'status' => 'Draft',
                'from_version' => $jd->version,
            ]);
        });

        return redirect()->route('job-descriptions.index', $jobDescription->requirement)
            ->with('status', $validated['decision'] === 'approved'
                ? 'Client approval was recorded for this exact JD version.'
                : 'Client changes were recorded and a new draft version was created.');
    }

    public function createNextVersion(Request $request, JobDescriptionVersion $jobDescription): RedirectResponse
    {
        $this->authorize('createNextVersion', $jobDescription);
        $newVersion = DB::transaction(function () use ($request, $jobDescription): JobDescriptionVersion {
            $requirement = Requirement::query()->lockForUpdate()->findOrFail($jobDescription->requirement_id);
            $latest = $requirement->jobDescriptionVersions()->first();
            if (! $latest || $latest->getKey() !== $jobDescription->getKey()) {
                throw ValidationException::withMessages(['jd' => 'Create a new version from the latest JD version.']);
            }

            $version = (int) $requirement->jobDescriptionVersions()->max('version') + 1;
            $new = $requirement->jobDescriptionVersions()->create([
                ...$jobDescription->only(JobDescriptionVersion::CONTENT_FIELDS),
                'version' => $version,
                'source' => 'revised_copy',
                'status' => 'Draft',
                'created_by' => $request->user()->getKey(),
            ]);
            $this->audit('jd.version_created', $new, null, [
                'version' => $version,
                'source' => 'revised_copy',
                'status' => 'Draft',
                'from_version' => $jobDescription->version,
            ]);

            return $new;
        });

        return redirect()->route('job-descriptions.show', $newVersion)->with('status', 'New JD version created as a draft. It requires review and client approval again.');
    }

    private function validatedContent(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:10000'],
            'responsibilities' => ['nullable', 'string', 'max:30000'],
            'required_skills' => ['nullable', 'string', 'max:10000'],
            'preferred_skills' => ['nullable', 'string', 'max:10000'],
            'qualification' => ['nullable', 'string', 'max:10000'],
            'experience_description' => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:180'],
            'work_mode' => ['nullable', 'in:On-site,Hybrid,Remote'],
            'employment_type' => ['nullable', 'string', 'max:40'],
            'additional_information' => ['nullable', 'string', 'max:30000'],
        ]);
    }

    private function draftDefaults(Requirement $requirement): array
    {
        return [
            'title' => $requirement->job_title,
            'summary' => '',
            'responsibilities' => '',
            'required_skills' => $requirement->required_skills,
            'preferred_skills' => '',
            'qualification' => $requirement->qualification,
            'experience_description' => $this->experienceText($requirement),
            'location' => $requirement->location,
            'work_mode' => $requirement->work_mode,
            'employment_type' => $requirement->employment_type,
            'compensation_information' => $requirement->salary_ctc,
            'additional_information' => '',
        ];
    }

    private function aiContent(array $generated, Requirement $requirement): array
    {
        return [
            'title' => $generated['title'] ?? $requirement->job_title,
            'summary' => $generated['summary'] ?? '',
            'responsibilities' => $generated['responsibilities'] ?? '',
            'required_skills' => $generated['required_skills'] ?? $requirement->required_skills,
            'preferred_skills' => $generated['preferred_skills'] ?? '',
            'qualification' => $generated['qualification'] ?? $requirement->qualification,
            'experience_description' => $generated['experience_description'] ?? $this->experienceText($requirement),
            'location' => $generated['location'] ?? $requirement->location,
            'work_mode' => in_array($generated['work_mode'] ?? null, ['On-site', 'Hybrid', 'Remote'], true) ? $generated['work_mode'] : $requirement->work_mode,
            'employment_type' => $generated['employment_type'] ?? $requirement->employment_type,
            'compensation_information' => $requirement->salary_ctc,
            'additional_information' => $generated['additional_information'] ?? '',
        ];
    }

    private function experienceText(Requirement $requirement): string
    {
        if ($requirement->min_experience_years === null && $requirement->max_experience_years === null) {
            return '';
        }

        return trim(($requirement->min_experience_years ?? 'Any').' to '.($requirement->max_experience_years ?? 'Any').' years');
    }

    private function transition(JobDescriptionVersion $jd, string $status, string $action, User $actor): void
    {
        DB::transaction(function () use ($jd, $status, $action, $actor): void {
            $locked = JobDescriptionVersion::query()->lockForUpdate()->findOrFail($jd->getKey());
            if (! in_array($locked->status, ['Draft', 'AI Draft'], true)) {
                throw ValidationException::withMessages(['jd' => 'This JD version is no longer available for recruiter review.']);
            }

            $oldStatus = $locked->status;
            $locked->forceFill(['status' => $status])->save();
            $this->audit($action, $locked, ['status' => $oldStatus], ['status' => $status], ['actor_id' => $actor->getKey()]);
        });
    }

    private function audit(string $action, ?JobDescriptionVersion $jd, ?array $previous, ?array $next, array $context = []): void
    {
        AuditLog::record($action, [
            'entity_type' => JobDescriptionVersion::class,
            'entity_id' => $jd ? (string) $jd->getKey() : null,
            'previous_state' => $previous,
            'new_state' => $next,
            'context' => array_merge($context, $jd ? ['requirement_id' => $jd->requirement_id, 'jd_version' => $jd->version] : []),
        ]);
    }
}
