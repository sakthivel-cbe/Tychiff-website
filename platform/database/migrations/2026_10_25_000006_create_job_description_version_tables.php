<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_description_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('requirement_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title', 180);
            $table->text('summary')->nullable();
            $table->longText('responsibilities')->nullable();
            $table->longText('required_skills')->nullable();
            $table->longText('preferred_skills')->nullable();
            $table->longText('qualification')->nullable();
            $table->string('experience_description', 500)->nullable();
            $table->string('location', 180)->nullable();
            $table->string('work_mode', 32)->nullable();
            $table->string('employment_type', 40)->nullable();
            $table->string('compensation_information', 180)->nullable();
            $table->longText('additional_information')->nullable();
            $table->string('source', 32);
            $table->string('status', 40)->default('Draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('approved_by_reference', 255)->nullable();
            $table->foreignId('approval_recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['requirement_id', 'version']);
            $table->index(['requirement_id', 'status', 'version']);
            $table->index(['status', 'updated_at']);
        });

        Schema::create('job_description_client_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_description_version_id')->constrained('job_description_versions')->restrictOnDelete();
            $table->foreignId('client_organization_id')->constrained()->restrictOnDelete();
            $table->string('client_spoc_reference', 255)->nullable();
            $table->string('decision', 32);
            $table->text('comments')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('decided_at');
            $table->timestamps();

            $table->index(['job_description_version_id', 'decided_at']);
            $table->index(['client_organization_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_description_client_decisions');
        Schema::dropIfExists('job_description_versions');
    }
};
