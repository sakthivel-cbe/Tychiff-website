<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 180)->unique();
            $table->timestamps();
        });

        Schema::create('requirements', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('client_organization_id')->constrained()->restrictOnDelete();
            $table->string('title', 180);
            $table->string('job_title', 180);
            $table->unsignedSmallInteger('openings');
            $table->string('employment_type', 40);
            $table->string('location', 180);
            $table->string('work_mode', 32);
            $table->string('salary_ctc', 180)->nullable();
            $table->decimal('min_experience_years', 5, 2)->nullable();
            $table->decimal('max_experience_years', 5, 2)->nullable();
            $table->text('required_skills')->nullable();
            $table->text('qualification')->nullable();
            $table->longText('job_description')->nullable();
            $table->date('original_target_date')->nullable();
            $table->date('target_date')->nullable();
            $table->string('priority', 16)->default('Normal');
            $table->string('status', 24)->default('Pending Review');
            $table->foreignId('recruitment_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('primary_spoc_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('intake_source', 32);
            $table->text('source_reference')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['recruitment_manager_id', 'status']);
            $table->index(['client_organization_id', 'created_at']);
            $table->index(['target_date', 'status']);
        });

        Schema::create('requirement_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('requirement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recruiter_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['requirement_id', 'ended_at']);
            $table->index(['recruiter_id', 'ended_at']);
        });

        Schema::create('requirement_target_date_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('requirement_id')->constrained()->cascadeOnDelete();
            $table->date('previous_target_date')->nullable();
            $table->date('new_target_date')->nullable();
            $table->text('reason');
            $table->string('client_confirmation_reference')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['requirement_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirement_target_date_changes');
        Schema::dropIfExists('requirement_assignments');
        Schema::dropIfExists('requirements');
        Schema::dropIfExists('client_organizations');
    }
};
