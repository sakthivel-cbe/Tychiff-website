<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table): void {
            $table->boolean('is_test_fixture')->default(false)->index();
        });
        Schema::table('requirements', function (Blueprint $table): void {
            $table->boolean('is_test_fixture')->default(false)->index();
        });
        Schema::table('job_description_versions', function (Blueprint $table): void {
            $table->boolean('is_test_fixture')->default(false)->index();
        });
        Schema::table('candidate_documents', function (Blueprint $table): void {
            $table->char('source_sha256', 64)->nullable()->unique();
            $table->string('extraction_status', 24)->default('pending');
            $table->longText('extracted_text')->nullable();
            $table->timestamp('extracted_at')->nullable();
            $table->string('extraction_failure_code', 64)->nullable();
            $table->unsignedSmallInteger('extraction_attempts')->default(0);
            $table->boolean('is_test_fixture')->default(false)->index();
            $table->index(['extraction_status', 'updated_at']);
        });

        Schema::create('requirement_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('requirement_id')->constrained()->restrictOnDelete();
            $table->foreignId('job_description_version_id')->nullable()->constrained('job_description_versions')->nullOnDelete();
            $table->string('document_type', 40)->default('job_description');
            $table->string('original_filename', 255);
            $table->string('storage_path', 500)->unique();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->char('source_sha256', 64)->unique();
            $table->string('extraction_status', 24)->default('pending');
            $table->longText('extracted_text')->nullable();
            $table->timestamp('extracted_at')->nullable();
            $table->string('extraction_failure_code', 64)->nullable();
            $table->unsignedSmallInteger('extraction_attempts')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->boolean('is_test_fixture')->default(false)->index();
            $table->timestamps();
            $table->index(['requirement_id', 'document_type', 'created_at']);
            $table->index(['extraction_status', 'updated_at']);
        });

        Schema::create('candidate_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();
            $table->foreignId('candidate_document_id')->constrained('candidate_documents')->restrictOnDelete();
            $table->foreignId('requirement_id')->constrained()->restrictOnDelete();
            $table->foreignId('job_description_version_id')->constrained('job_description_versions')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->boolean('is_test_only')->default(false);
            $table->string('status', 32)->default('pending');
            $table->string('validation_status', 32)->default('pending');
            $table->string('scoring_status', 32)->default('pending');
            $table->longText('validation_result')->nullable();
            $table->longText('scoring_result')->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['candidate_id', 'requirement_id', 'created_at']);
            $table->index(['job_description_version_id', 'status']);
        });

        Schema::table('ai_agent_executions', function (Blueprint $table): void {
            $table->foreignId('candidate_evaluation_id')->nullable()
                ->after('candidate_id')->constrained('candidate_evaluations')->nullOnDelete();
            $table->index(['candidate_evaluation_id', 'agent_type']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_executions', function (Blueprint $table): void {
            $table->dropForeign(['candidate_evaluation_id']);
            $table->dropIndex(['candidate_evaluation_id', 'agent_type']);
            $table->dropColumn('candidate_evaluation_id');
        });
        Schema::dropIfExists('candidate_evaluations');
        Schema::dropIfExists('requirement_documents');
        Schema::table('candidate_documents', function (Blueprint $table): void {
            $table->dropIndex(['extraction_status', 'updated_at']);
            $table->dropUnique(['source_sha256']);
            $table->dropIndex(['is_test_fixture']);
            $table->dropColumn([
                'source_sha256', 'extraction_status', 'extracted_text', 'extracted_at',
                'extraction_failure_code', 'extraction_attempts', 'is_test_fixture',
            ]);
        });
        Schema::table('job_description_versions', fn (Blueprint $table) => $table->dropColumn('is_test_fixture'));
        Schema::table('requirements', fn (Blueprint $table) => $table->dropColumn('is_test_fixture'));
        Schema::table('candidates', fn (Blueprint $table) => $table->dropColumn('is_test_fixture'));
    }
};
