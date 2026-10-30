<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->foreignId('permission_id')->constrained()->restrictOnDelete();
            $table->text('reason');
            $table->boolean('current_state');
            $table->boolean('requested_state');
            $table->string('status', 24)->default('pending')->index();
            $table->timestamp('requested_at')->useCurrent();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamps();
            $table->index(['role_id', 'permission_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_change_requests');
    }
};
