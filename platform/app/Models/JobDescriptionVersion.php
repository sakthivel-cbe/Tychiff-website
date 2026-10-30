<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobDescriptionVersion extends Model
{
    public const STATUSES = ['Draft', 'AI Draft', 'Recruiter Review', 'Sent for Client Approval', 'Changes Requested', 'Approved', 'Superseded'];

    public const CONTENT_FIELDS = [
        'title', 'summary', 'responsibilities', 'required_skills', 'preferred_skills', 'qualification',
        'experience_description', 'location', 'work_mode', 'employment_type', 'compensation_information',
        'additional_information',
    ];

    protected $fillable = [
        'requirement_id', 'version', 'title', 'summary', 'responsibilities', 'required_skills',
        'preferred_skills', 'qualification', 'experience_description', 'location', 'work_mode',
        'employment_type', 'compensation_information', 'additional_information', 'source', 'status',
        'created_by', 'reviewed_by', 'reviewed_at', 'approved_by_reference', 'approval_recorded_by', 'approved_at', 'is_test_fixture',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'approved_at' => 'datetime', 'is_test_fixture' => 'boolean'];
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function requirementDocuments(): HasMany
    {
        return $this->hasMany(RequirementDocument::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(CandidateEvaluation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvalRecorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approval_recorded_by');
    }

    public function clientDecisions(): HasMany
    {
        return $this->hasMany(JobDescriptionClientDecision::class)->orderBy('decided_at');
    }
}
