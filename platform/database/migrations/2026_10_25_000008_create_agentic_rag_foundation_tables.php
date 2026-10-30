<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_items', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('source_type', 48);
            $table->longText('content');
            $table->string('version', 64)->default('1');
            $table->string('approval_status', 24)->default('pending');
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('effective_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['approval_status', 'is_active', 'effective_at'], 'ai_knowledge_eligibility_index');
            $table->index(['source_type', 'title']);
        });

        Schema::create('ai_agent_executions', function (Blueprint $table): void {
            $table->id();
            $table->string('agent_type', 48);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('requirement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 32)->nullable();
            $table->string('model', 180)->nullable();
            $table->string('fallback_provider', 32)->nullable();
            $table->string('status', 24)->default('running');
            $table->string('result_status', 24)->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->json('provider_trace')->nullable();
            $table->unsignedSmallInteger('evidence_count')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['agent_type', 'status', 'started_at']);
            $table->index(['requirement_id', 'started_at']);
            $table->index(['candidate_id', 'started_at']);
        });

        Schema::create('ai_agent_evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('execution_id')->constrained('ai_agent_executions')->cascadeOnDelete();
            $table->string('provider_reference', 40);
            $table->string('source_type', 48);
            $table->string('source_record_id', 64);
            $table->string('field', 120);
            $table->string('retrieval_reason', 255);
            $table->timestamp('captured_at');
            $table->unique(['execution_id', 'provider_reference'], 'ai_evidence_execution_reference_unique');
            $table->index(['source_type', 'source_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_evidence');
        Schema::dropIfExists('ai_agent_executions');
        Schema::dropIfExists('ai_knowledge_items');
    }
};
