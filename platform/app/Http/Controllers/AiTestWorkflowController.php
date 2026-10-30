<?php

namespace App\Http\Controllers;

use App\AI\Services\CandidateEvaluationService;
use App\Documents\Services\CandidateDocumentExtractionService;
use App\Documents\Services\RequirementDocumentExtractionService;
use App\Models\CandidateDocument;
use App\Models\CandidateEvaluation;
use App\Models\JobDescriptionVersion;
use App\Models\Requirement;
use App\Models\RequirementDocument;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiTestWorkflowController extends Controller
{
    public function index(Request $request): View
    {
        $this->guard($request);
        $requirements = Requirement::query()->where('is_test_fixture', true)->whereHas('jobDescriptionVersions', fn ($q) => $q->where('is_test_fixture', true))->with(['jobDescriptionVersions', 'documents'])->orderBy('id')->get();
        $documents = CandidateDocument::query()->where('is_test_fixture', true)->where('document_type', 'resume')->with('candidate')->orderBy('id')->get()
            ->filter(fn (CandidateDocument $doc) => $request->user()->can('viewDocument', $doc));
        $evaluations = CandidateEvaluation::query()->where('is_test_only', true)->with(['candidate', 'requirement', 'jobDescriptionVersion', 'agentExecutions.evidence'])->latest()->limit(40)->get();

        return view('ai-test-workflow.index', compact('requirements', 'documents', 'evaluations'));
    }

    public function run(Request $request, CandidateEvaluationService $evaluations): RedirectResponse
    {
        $this->guard($request);
        $data = $request->validate([
            'requirement_id' => ['required', 'integer', 'exists:requirements,id'],
            'job_description_version_id' => ['required', 'integer', 'exists:job_description_versions,id'],
            'document_ids' => ['required', 'array', 'min:1', 'max:10'], 'document_ids.*' => ['required', 'integer', 'distinct', 'exists:candidate_documents,id'],
        ]);
        $requirement = Requirement::query()->findOrFail($data['requirement_id']);
        $jd = JobDescriptionVersion::query()->findOrFail($data['job_description_version_id']);
        $this->authorize('view', $requirement);
        abort_unless($requirement->is_test_fixture && $jd->is_test_fixture && $jd->requirement_id === $requirement->getKey(), 404);

        $documents = CandidateDocument::query()->whereIn('id', $data['document_ids'])->where('is_test_fixture', true)->where('document_type', 'resume')->with('candidate')->get();
        abort_unless($documents->count() === count($data['document_ids']), 404);
        $results = $evaluations->bulk($request->user(), $documents, $requirement, $jd);
        $unavailable = collect($results)->filter(fn ($result) => ($result->status ?? $result['status'] ?? null) === 'unavailable')->count();
        $failed = count($results) - $unavailable;

        return back()->with('status', count($results).' evaluation(s) processed; '.$unavailable.' unavailable; '.$failed.' completed or failed. Live AI is not claimed unless a configured provider executed it.');
    }

    public function extractCandidate(Request $request, CandidateDocument $document, CandidateDocumentExtractionService $extraction): RedirectResponse
    {
        $this->guard($request);
        abort_unless($document->is_test_fixture, 404);
        $this->authorize('viewDocument', $document);
        $result = $extraction->extract($request->user(), $document, retry: true);

        return back()->with('status', 'Resume extraction '.$result->extraction_status.'.');
    }

    public function extractRequirement(Request $request, RequirementDocument $document, RequirementDocumentExtractionService $extraction): RedirectResponse
    {
        $this->guard($request);
        abort_unless($document->is_test_fixture, 404);
        $this->authorize('view', $document->requirement);
        $result = $extraction->extract($request->user(), $document, retry: true);

        return back()->with('status', 'JD extraction '.$result->extraction_status.'.');
    }

    private function guard(Request $request): void
    {
        abort_unless(app()->environment('local', 'testing') && config('ai.test_workflow.enabled') === true, 404);
        if (! $request->user()?->hasRole('management') || ! $request->user()->hasPermission('recruitment.view_all')) {
            throw new AuthorizationException;
        }
    }
}
