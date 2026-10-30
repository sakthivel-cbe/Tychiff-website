<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requirement extends Model
{
    public const STATUSES = ['Pending Review', 'Approved', 'Active', 'At Risk', 'On Hold', 'Closed'];

    public const PRIORITIES = ['Critical', 'High', 'Normal', 'Low'];

    protected $fillable = [
        'reference', 'client_organization_id', 'title', 'job_title', 'openings', 'employment_type',
        'location', 'work_mode', 'salary_ctc', 'min_experience_years', 'max_experience_years',
        'required_skills', 'qualification', 'job_description', 'original_target_date', 'target_date',
        'priority', 'status', 'recruitment_manager_id', 'primary_spoc_id', 'intake_source',
        'source_reference', 'created_by', 'is_test_fixture',
    ];

    protected function casts(): array
    {
        return [
            'original_target_date' => 'date',
            'target_date' => 'date',
            'openings' => 'integer',
            'min_experience_years' => 'decimal:2',
            'max_experience_years' => 'decimal:2',
            'is_test_fixture' => 'boolean',
        ];
    }

    public function clientOrganization(): BelongsTo
    {
        return $this->belongsTo(ClientOrganization::class);
    }

    public function recruitmentManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruitment_manager_id');
    }

    public function primarySpoc(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_spoc_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RequirementAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->whereNull('ended_at');
    }

    public function targetDateChanges(): HasMany
    {
        return $this->hasMany(RequirementTargetDateChange::class)->orderBy('changed_at');
    }

    public function jobDescriptionVersions(): HasMany
    {
        return $this->hasMany(JobDescriptionVersion::class)->orderByDesc('version');
    }

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(Candidate::class, 'candidate_requirement_links')
            ->withPivot(['linked_by', 'linked_at', 'reason'])->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RequirementDocument::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(CandidateEvaluation::class);
    }

    public function latestJobDescription(): ?JobDescriptionVersion
    {
        return $this->jobDescriptionVersions()->first();
    }

    public function hasApprovedJobDescription(): bool
    {
        return $this->approvedJobDescription() !== null;
    }

    public function approvedJobDescription(): ?JobDescriptionVersion
    {
        return $this->jobDescriptionVersions()->where('status', 'Approved')->first();
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->activeAssignments()->where('recruiter_id', $user->getKey())->exists();
    }
}
