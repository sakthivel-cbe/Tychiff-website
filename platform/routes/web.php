<?php

use App\Http\Controllers\AiTestWorkflowController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthenticatedHomeController;
use App\Http\Controllers\CandidateAccessController;
use App\Http\Controllers\CandidateConsentController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CandidateDocumentController;
use App\Http\Controllers\CandidateNoteController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\JobDescriptionController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PermissionChangeRequestController;
use App\Http\Controllers\PrivacyNoticeController;
use App\Http\Controllers\RequirementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/privacy', PrivacyNoticeController::class)->name('privacy.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/forgot-password', [PasswordController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'resetForm'])
        ->where('token', '[A-Za-z0-9]+')->middleware('throttle:10,1')->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
    ->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', EmailVerificationController::class)
    ->middleware(['auth', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
    ->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/home', [AuthenticatedHomeController::class, 'redirect'])->name('home');
    Route::get('/local-test/ai-workflow', [AiTestWorkflowController::class, 'index'])->name('ai-test-workflow.index');
    Route::post('/local-test/ai-workflow/run', [AiTestWorkflowController::class, 'run'])->middleware('throttle:10,1')->name('ai-test-workflow.run');
    Route::post('/local-test/ai-workflow/candidate-documents/{document}/extract', [AiTestWorkflowController::class, 'extractCandidate'])->whereNumber('document')->middleware('throttle:10,1')->name('ai-test-workflow.candidate-extract');
    Route::post('/local-test/ai-workflow/requirement-documents/{document}/extract', [AiTestWorkflowController::class, 'extractRequirement'])->whereNumber('document')->middleware('throttle:10,1')->name('ai-test-workflow.requirement-extract');
    Route::get('/foundation/system', fn () => app(AuthenticatedHomeController::class)->show('system'))
        ->middleware('can:system.manage')->name('foundation.system');
    Route::get('/foundation/recruitment-management', fn () => app(AuthenticatedHomeController::class)->show('recruitment-manager'))
        ->middleware('can:recruiters.assign')->name('foundation.recruitment-manager');
    Route::get('/foundation/management', fn () => app(AuthenticatedHomeController::class)->show('management'))
        ->middleware('can:recruitment.intervene')->name('foundation.management');
    Route::get('/foundation/recruiter', fn () => app(AuthenticatedHomeController::class)->show('recruiter'))
        ->middleware('can:requirements.view_assigned')->name('foundation.recruiter');
    Route::get('/foundation/admin', fn () => app(AuthenticatedHomeController::class)->show('admin'))
        ->middleware('can:admin.support')->name('foundation.admin');
    Route::get('/consent', [ConsentController::class, 'index'])->name('consent.index');
    Route::post('/consent/acknowledge', [ConsentController::class, 'store'])
        ->middleware('throttle:10,1')->name('consent.store');

    Route::get('/requirements', [RequirementController::class, 'index'])->name('requirements.index');
    Route::get('/requirements/create', [RequirementController::class, 'create'])->name('requirements.create');
    Route::post('/requirements', [RequirementController::class, 'store'])->middleware('throttle:20,1')->name('requirements.store');
    Route::get('/requirements/{requirement}', [RequirementController::class, 'show'])->whereNumber('requirement')->name('requirements.show');
    Route::patch('/requirements/{requirement}', [RequirementController::class, 'update'])->middleware('throttle:20,1')->name('requirements.update');
    Route::post('/requirements/{requirement}/approve', [RequirementController::class, 'approve'])->name('requirements.approve');
    Route::post('/requirements/{requirement}/activate', [RequirementController::class, 'activate'])->name('requirements.activate');
    Route::put('/requirements/{requirement}/assignments', [RequirementController::class, 'assign'])->middleware('throttle:20,1')->name('requirements.assignments.update');
    Route::post('/requirements/{requirement}/priority', [RequirementController::class, 'prioritize'])->name('requirements.priority');
    Route::post('/requirements/{requirement}/target-date', [RequirementController::class, 'reviseTargetDate'])->name('requirements.target-date');
    Route::post('/requirements/{requirement}/risk', [RequirementController::class, 'reportRisk'])->name('requirements.risk');
    Route::post('/requirements/{requirement}/status', [RequirementController::class, 'changeStatus'])->name('requirements.status');

    Route::get('/requirements/{requirement}/job-descriptions', [JobDescriptionController::class, 'index'])->name('job-descriptions.index');
    Route::get('/requirements/{requirement}/job-descriptions/create', [JobDescriptionController::class, 'create'])->name('job-descriptions.create');
    Route::post('/requirements/{requirement}/job-descriptions', [JobDescriptionController::class, 'store'])->middleware('throttle:20,1')->name('job-descriptions.store');
    Route::post('/requirements/{requirement}/job-descriptions/ai-draft', [JobDescriptionController::class, 'generate'])->middleware('throttle:5,1')->name('job-descriptions.generate');
    Route::get('/job-descriptions/{jobDescription}', [JobDescriptionController::class, 'show'])->name('job-descriptions.show');
    Route::patch('/job-descriptions/{jobDescription}', [JobDescriptionController::class, 'update'])->middleware('throttle:20,1')->name('job-descriptions.update');
    Route::post('/job-descriptions/{jobDescription}/review', [JobDescriptionController::class, 'submitForReview'])->name('job-descriptions.review');
    Route::post('/job-descriptions/{jobDescription}/send-for-approval', [JobDescriptionController::class, 'sendForClientApproval'])->name('job-descriptions.send-for-approval');
    Route::post('/job-descriptions/{jobDescription}/return-to-recruiter', [JobDescriptionController::class, 'returnToRecruiter'])->name('job-descriptions.return-to-recruiter');
    Route::post('/job-descriptions/{jobDescription}/client-decision', [JobDescriptionController::class, 'recordClientDecision'])->name('job-descriptions.client-decision');
    Route::post('/job-descriptions/{jobDescription}/versions', [JobDescriptionController::class, 'createNextVersion'])->name('job-descriptions.versions.store');

    Route::get('/candidates', [CandidateController::class, 'index'])->name('candidates.index');
    Route::get('/candidates/create', [CandidateController::class, 'create'])->name('candidates.create');
    Route::post('/candidates', [CandidateController::class, 'store'])->middleware('throttle:20,1')->name('candidates.store');
    Route::get('/candidates/{candidate}/edit', [CandidateController::class, 'edit'])->whereNumber('candidate')->name('candidates.edit');
    Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])->whereNumber('candidate')->name('candidates.show');
    Route::patch('/candidates/{candidate}', [CandidateController::class, 'update'])->whereNumber('candidate')->middleware('throttle:20,1')->name('candidates.update');
    Route::post('/candidates/{candidate}/status', [CandidateController::class, 'changeStatus'])->whereNumber('candidate')->name('candidates.status');
    Route::post('/candidates/{candidate}/requirements', [CandidateController::class, 'linkRequirement'])->whereNumber('candidate')->name('candidates.requirements.link');
    Route::post('/candidates/{candidate}/notes', [CandidateNoteController::class, 'store'])->whereNumber('candidate')->middleware('throttle:30,1')->name('candidates.notes.store');
    Route::get('/candidates/{candidate}/access', [CandidateAccessController::class, 'index'])->whereNumber('candidate')->name('candidates.access.index');
    Route::post('/candidates/{candidate}/access', [CandidateAccessController::class, 'grant'])->whereNumber('candidate')->name('candidates.access.grant');
    Route::delete('/candidates/{candidate}/access/{access}', [CandidateAccessController::class, 'revoke'])->whereNumber('candidate')->whereNumber('access')->name('candidates.access.revoke');
    Route::post('/candidates/{candidate}/documents', [CandidateDocumentController::class, 'store'])->whereNumber('candidate')->middleware('throttle:10,1')->name('candidates.documents.store');
    Route::get('/candidate-documents/{document}/download', [CandidateDocumentController::class, 'download'])->whereNumber('document')->name('candidate-documents.download');
    Route::post('/candidates/{candidate}/consents', [CandidateConsentController::class, 'store'])->whereNumber('candidate')->name('candidates.consents.store');
    Route::post('/candidate-consents/{consent}/withdraw', [CandidateConsentController::class, 'withdraw'])->whereNumber('consent')->name('candidate-consents.withdraw');
});

Route::post('/admin/invitations', [InvitationController::class, 'store'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])->name('invitations.store');
Route::post('/admin/permission-change-requests', [PermissionChangeRequestController::class, 'store'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])->name('permission-changes.store');
Route::get('/admin/permission-change-requests', [PermissionChangeRequestController::class, 'index'])
    ->middleware(['auth', 'verified', 'throttle:30,1'])->name('permission-changes.index');
Route::get('/admin/permission-change-requests/{changeRequest}', [PermissionChangeRequestController::class, 'show'])
    ->middleware(['auth', 'verified', 'throttle:30,1'])->name('permission-changes.show');
Route::post('/admin/permission-change-requests/{changeRequest}/approve', [PermissionChangeRequestController::class, 'approve'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])->name('permission-changes.approve');
Route::post('/admin/permission-change-requests/{changeRequest}/reject', [PermissionChangeRequestController::class, 'reject'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])->name('permission-changes.reject');
Route::post('/admin/permission-change-requests/{changeRequest}/cancel', [PermissionChangeRequestController::class, 'cancel'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])->name('permission-changes.cancel');
Route::get('/invitations/{token}', [InvitationController::class, 'show'])
    ->whereAlphaNumeric('token')->middleware('throttle:10,1')->name('invitations.accept.show');
Route::post('/invitations/{token}', [InvitationController::class, 'accept'])
    ->whereAlphaNumeric('token')->middleware('throttle:5,1')->name('invitations.accept');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/foundation/verified', fn () => response()->json(['message' => 'Verified session active.']))
        ->name('foundation.verified');
});
