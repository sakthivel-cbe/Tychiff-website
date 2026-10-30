<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 120)->index();
            $table->string('entity_type', 160)->nullable()->index();
            $table->string('entity_id', 80)->nullable()->index();
            $table->json('previous_state')->nullable();
            $table->json('new_state')->nullable();
            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_id', 100)->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('privacy_notice_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 40)->unique();
            $table->string('title');
            $table->longText('body');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('privacy_notice_version_id')->constrained('privacy_notice_versions')->restrictOnDelete();
            $table->string('consent_type', 80);
            $table->boolean('granted');
            $table->timestamp('recorded_at')->useCurrent();
            $table->string('source', 80)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->index(['user_id', 'consent_type', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_records');
        Schema::dropIfExists('privacy_notice_versions');
        Schema::dropIfExists('audit_logs');
    }
};
