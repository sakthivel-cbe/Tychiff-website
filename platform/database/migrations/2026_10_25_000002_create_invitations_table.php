<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invitation_roles', function (Blueprint $table) {
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->primary(['invitation_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_roles');
        Schema::dropIfExists('invitations');
    }
};
