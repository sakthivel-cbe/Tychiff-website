<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 36)->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('full_name', 180);
            $table->string('email', 254)->nullable();
            $table->string('email_normalized', 254)->nullable()->unique();
            $table->string('mobile', 40)->nullable();
            $table->string('mobile_normalized', 40)->nullable()->unique();
            $table->string('alternate_contact', 100)->nullable();
            $table->string('location', 180)->nullable();
            $table->string('current_designation', 180)->nullable();
            $table->decimal('total_experience_years', 5, 2)->nullable();
            $table->decimal('relevant_experience_years', 5, 2)->nullable();
            $table->text('primary_skills')->nullable();
            $table->text('secondary_skills')->nullable();
            $table->string('highest_qualification', 180)->nullable();
            $table->string('current_employer', 180)->nullable();
            $table->string('current_ctc', 100)->nullable();
            $table->string('expected_ctc', 100)->nullable();
            $table->unsignedSmallInteger('notice_period_days')->nullable();
            $table->string('preferred_location', 180)->nullable();
            $table->string('preferred_work_mode', 32)->nullable();
            $table->string('availability', 64)->nullable();
            $table->longText('profile_summary')->nullable();
            $table->string('status', 24)->default('New');
            $table->string('source', 40);
            $table->string('source_details', 180)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['full_name', 'created_at']);
            $table->index('source');
        });

        Schema::create('candidate_recruiter_access', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();
            $table->foreignId('recruiter_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 16)->default('active');
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('granted_at');
            $table->text('grant_reason')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'recruiter_id']);
            $table->index(['recruiter_id', 'status']);
            $table->index(['candidate_id', 'status']);
        });

        Schema::create('candidate_requirement_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();
            $table->foreignId('requirement_id')->constrained()->restrictOnDelete();
            $table->foreignId('linked_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('linked_at');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'requirement_id']);
            $table->index(['requirement_id', 'linked_at']);
        });

        Schema::create('candidate_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('requirement_id')->constrained()->restrictOnDelete();
            $table->string('status', 16)->default('valid');
            $table->timestamp('consented_at');
            $table->string('consent_method', 32);
            $table->string('consent_text_reference', 255);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('withdrawal_reason')->nullable();
            $table->timestamps();

            $table->index(['candidate_id', 'client_organization_id', 'requirement_id', 'status'], 'candidate_consent_scope_index');
            $table->index(['candidate_id', 'consented_at']);
        });

        Schema::create('candidate_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();
            $table->string('document_type', 32);
            $table->string('original_filename', 255);
            $table->string('storage_path', 500)->unique();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->string('status', 16)->default('active');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index(['candidate_id', 'status', 'uploaded_at']);
        });

        Schema::create('candidate_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();
            $table->longText('note');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at');

            $table->index(['candidate_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_notes');
        Schema::dropIfExists('candidate_documents');
        Schema::dropIfExists('candidate_consents');
        Schema::dropIfExists('candidate_requirement_links');
        Schema::dropIfExists('candidate_recruiter_access');
        Schema::dropIfExists('candidates');
    }
};
