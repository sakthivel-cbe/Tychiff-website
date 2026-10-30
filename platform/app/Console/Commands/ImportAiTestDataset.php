<?php

namespace App\Console\Commands;

use App\Documents\Exceptions\DocumentExtractionException;
use App\Documents\Services\DocumentTextExtractionService;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\CandidateRecruiterAccess;
use App\Models\ClientOrganization;
use App\Models\JobDescriptionVersion;
use App\Models\Requirement;
use App\Models\RequirementDocument;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImportAiTestDataset extends Command
{
    protected $signature = 'platform:ai-test-import {--source=} {--management-user=} {--recruiter-user=} {--dry-run}';

    protected $description = 'Import the local-only TYCHIFF AI test document fixtures into private storage.';

    private const JDS = ['Lead AI Engineer Updated JD.pdf', 'Senior AI Engineer Updated JD.pdf'];

    public function handle(DocumentTextExtractionService $extractor): int
    {
        if (! app()->environment('local')) {
            $this->error('This fixture import is restricted to the local environment.');

            return self::FAILURE;
        }
        if (! $this->option('dry-run') && ! config('ai.test_workflow.enabled')) {
            $this->error('Enable AI_TEST_WORKFLOW_ENABLED in local configuration before importing fixtures.');

            return self::FAILURE;
        }
        $source = $this->option('source');
        if (! is_string($source) || ! is_dir($source)) {
            $this->error('Provide an existing source directory with --source=.');

            return self::FAILURE;
        }
        $files = collect(scandir($source) ?: [])->filter(fn (string $name) => is_file($source.DIRECTORY_SEPARATOR.$name)
            && in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['pdf', 'docx'], true))->values();
        $jds = $files->filter(fn (string $name) => in_array($name, self::JDS, true))->values();
        $resumes = $files->reject(fn (string $name) => in_array($name, self::JDS, true))->values();
        if ($jds->sort()->values()->all() !== collect(self::JDS)->sort()->values()->all() || $resumes->count() !== 10) {
            $this->error('Expected the two specified JD PDFs and exactly ten PDF/DOCX resumes; no import was performed.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            return $this->dryRun($source, [...$jds->all(), ...$resumes->all()], $extractor);
        }

        $management = User::query()->find($this->option('management-user'));
        $recruiter = User::query()->find($this->option('recruiter-user'));
        if (! $management?->hasRole('management') || ! $management->hasPermission('recruitment.view_all')
            || ! $recruiter?->hasRole('recruiter') || ! $recruiter->hasPermission('candidates.screen')) {
            $this->error('Import needs an existing authorized Management actor and Recruiter actor. No accounts or permissions were created.');

            return self::FAILURE;
        }

        $this->import($source, $jds->all(), $resumes->all(), $extractor, $management, $recruiter);

        return self::SUCCESS;
    }

    private function dryRun(string $source, array $files, DocumentTextExtractionService $extractor): int
    {
        $extracted = 0;
        $failed = 0;
        foreach ($files as $name) {
            try {
                $text = $extractor->extract($source.DIRECTORY_SEPARATOR.$name, $name);
                $this->line('EXTRACTED  '.str_pad((string) mb_strlen($text), 6, ' ', STR_PAD_LEFT).' chars  '.$name);
                $extracted++;
            } catch (Throwable $exception) {
                $code = $exception instanceof DocumentExtractionException ? $exception->failureCode : 'extraction_failed';
                $this->line('FAILED     '.$code.'  '.$name);
                $failed++;
            }
        }
        $this->newLine();
        $this->info("Dry run: {$extracted} extracted; {$failed} failed. No database or storage changes were made.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function import(string $source, array $jdFiles, array $resumeFiles, DocumentTextExtractionService $extractor, User $management, User $recruiter): void
    {
        $organization = ClientOrganization::query()->firstOrCreate(['name' => 'TYCHIFF Local AI Test Fixtures']);
        foreach ($jdFiles as $index => $name) {
            try {
                $hash = hash_file('sha256', $source.DIRECTORY_SEPARATOR.$name);
                $document = RequirementDocument::query()->where('source_sha256', $hash)->first();
                if ($document) {
                    $this->line('EXISTS JD  '.$name);

                    continue;
                }
                $title = pathinfo($name, PATHINFO_FILENAME);
                $requirement = Requirement::query()->create([
                    'reference' => 'TST-JD-'.Str::upper(Str::random(8)), 'client_organization_id' => $organization->getKey(),
                    'title' => $title, 'job_title' => $title, 'openings' => 1, 'employment_type' => 'Permanent',
                    'location' => 'Not specified in source', 'work_mode' => 'Not specified', 'status' => 'Pending Review',
                    'intake_source' => 'Internal', 'source_reference' => 'Local test fixture; not a client-approved requirement.',
                    'created_by' => $management->getKey(), 'is_test_fixture' => true,
                ]);
                $jd = $requirement->jobDescriptionVersions()->create([
                    'version' => 1, 'title' => $title, 'summary' => 'Test fixture. Refer to the privately stored original JD document.',
                    'source' => 'Imported Test Fixture', 'status' => 'Draft', 'created_by' => $management->getKey(), 'is_test_fixture' => true,
                ]);
                $document = $this->storeRequirementDocument($source, $name, $hash, $requirement, $jd, $management, $extractor);
                $this->line(strtoupper($document->extraction_status).' JD  '.$name);
            } catch (Throwable) {
                $this->error('FAILED JD  '.$name.' (safe failure; remaining documents will continue)');
            }
        }

        foreach ($resumeFiles as $name) {
            try {
                $hash = hash_file('sha256', $source.DIRECTORY_SEPARATOR.$name);
                if (CandidateDocument::query()->where('source_sha256', $hash)->exists()) {
                    $this->line('EXISTS     '.$name);

                    continue;
                }
                try {
                    $text = $extractor->extract($source.DIRECTORY_SEPARATOR.$name, $name);
                    $state = 'extracted';
                    $failure = null;
                } catch (Throwable $exception) {
                    $text = null;
                    $state = 'failed';
                    $failure = $exception instanceof DocumentExtractionException ? $exception->failureCode : 'extraction_failed';
                }
                $fullName = trim(preg_replace('/^\d+\s*/', '', pathinfo($name, PATHINFO_FILENAME)) ?? pathinfo($name, PATHINFO_FILENAME));
                $email = $text && preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $match) ? strtolower($match[0]) : null;
                $mobile = null;
                $emailNorm = $email ? mb_strtolower(trim($email)) : null;
                $mobileNorm = $mobile ? preg_replace('/\D+/', '', $mobile) : null;
                $matches = Candidate::query()->where(function ($query) use ($emailNorm, $mobileNorm, $fullName): void {
                    if ($emailNorm) {
                        $query->orWhere('email_normalized', $emailNorm);
                    }
                    if ($mobileNorm) {
                        $query->orWhere('mobile_normalized', $mobileNorm);
                    }
                    if (! $emailNorm && ! $mobileNorm) {
                        $query->orWhereRaw('LOWER(full_name) = ?', [mb_strtolower($fullName)]);
                    }
                })->get();
                if ($matches->count() > 1) {
                    $this->warn('SKIPPED CONFLICT  '.$name.' (existing identifiers matched multiple profiles)');

                    continue;
                }
                $candidate = $matches->first();
                if (! $candidate) {
                    $candidate = Candidate::query()->create([
                        'reference' => 'TST-CAN-'.Str::upper(Str::random(8)), 'full_name' => mb_substr($fullName, 0, 180),
                        'email' => $email, 'email_normalized' => $emailNorm, 'mobile' => $mobile,
                        'mobile_normalized' => $mobileNorm, 'status' => 'New', 'source' => 'Other',
                        'source_details' => 'Local test fixture import', 'created_by' => $recruiter->getKey(), 'is_test_fixture' => true,
                    ]);
                } elseif (! $candidate->is_test_fixture) {
                    $this->warn('SKIPPED EXISTING PRODUCTION PROFILE  '.$name);

                    continue;
                }
                $path = 'candidates/'.$candidate->reference.'/'.Str::ulid().'.'.strtolower(pathinfo($name, PATHINFO_EXTENSION));
                Storage::disk('private_documents')->put($path, file_get_contents($source.DIRECTORY_SEPARATOR.$name));
                $document = CandidateDocument::query()->create([
                    'candidate_id' => $candidate->getKey(), 'document_type' => 'resume', 'original_filename' => $name,
                    'storage_path' => $path, 'mime_type' => strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'docx'
                        ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : 'application/pdf',
                    'file_size' => filesize($source.DIRECTORY_SEPARATOR.$name),
                    'source_sha256' => $hash, 'extraction_status' => $state, 'extracted_text' => $text, 'extracted_at' => $state === 'extracted' ? now() : null,
                    'extraction_failure_code' => $failure, 'extraction_attempts' => 1, 'status' => 'active',
                    'uploaded_by' => $recruiter->getKey(), 'uploaded_at' => now(), 'is_test_fixture' => true,
                ]);
                CandidateRecruiterAccess::query()->firstOrCreate(
                    ['candidate_id' => $candidate->getKey(), 'recruiter_id' => $recruiter->getKey()],
                    ['status' => 'active', 'granted_by' => $recruiter->getKey(), 'granted_at' => now(), 'grant_reason' => 'Local test fixture import'],
                );
                AuditLog::record('candidate.test_fixture_imported', [
                    'user_id' => $recruiter->getKey(), 'entity_type' => Candidate::class, 'entity_id' => (string) $candidate->getKey(),
                    'context' => ['document_id' => $document->getKey(), 'extraction_status' => $state, 'source_hash' => $hash],
                ]);
                $this->line(strtoupper($state).'     '.$name);
            } catch (Throwable) {
                $this->error('FAILED     '.$name.' (safe failure; remaining documents will continue)');
            }
        }
    }

    private function storeRequirementDocument(string $source, string $name, string $hash, Requirement $requirement, JobDescriptionVersion $jd, User $management, DocumentTextExtractionService $extractor): RequirementDocument
    {
        $path = 'requirements/'.$requirement->reference.'/'.Str::ulid().'.pdf';
        Storage::disk('private_documents')->put($path, file_get_contents($source.DIRECTORY_SEPARATOR.$name));
        try {
            $text = $extractor->extract($source.DIRECTORY_SEPARATOR.$name, $name);
            $status = 'extracted';
            $failure = null;
        } catch (Throwable $exception) {
            $text = null;
            $status = 'failed';
            $failure = $exception instanceof DocumentExtractionException ? $exception->failureCode : 'extraction_failed';
        }
        $document = RequirementDocument::query()->create([
            'requirement_id' => $requirement->getKey(), 'job_description_version_id' => $jd->getKey(), 'document_type' => 'job_description',
            'original_filename' => $name, 'storage_path' => $path, 'mime_type' => 'application/pdf', 'file_size' => filesize($source.DIRECTORY_SEPARATOR.$name),
            'source_sha256' => $hash, 'extraction_status' => $status, 'extracted_text' => $text,
            'extracted_at' => $status === 'extracted' ? now() : null, 'extraction_failure_code' => $failure,
            'extraction_attempts' => 1, 'uploaded_by' => $management->getKey(), 'is_test_fixture' => true,
        ]);
        AuditLog::record('requirement.test_fixture_imported', [
            'user_id' => $management->getKey(), 'entity_type' => Requirement::class, 'entity_id' => (string) $requirement->getKey(),
            'context' => ['document_id' => $document->getKey(), 'job_description_version_id' => $jd->getKey(), 'extraction_status' => $status, 'source_hash' => $hash],
        ]);

        return $document;
    }
}
