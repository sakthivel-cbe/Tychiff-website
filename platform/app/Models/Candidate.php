<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    public const STATUSES = ['New', 'Active', 'On Hold', 'Not Available', 'Inactive'];

    public const SOURCES = ['Direct Candidate', 'Referral', 'Job Portal', 'BNI / Network', 'Website', 'Recruiter Sourcing', 'Other'];

    protected $fillable = [
        'reference', 'user_id', 'full_name', 'email', 'email_normalized', 'mobile', 'mobile_normalized',
        'alternate_contact', 'location', 'current_designation', 'total_experience_years',
        'relevant_experience_years', 'primary_skills', 'secondary_skills', 'highest_qualification',
        'current_employer', 'current_ctc', 'expected_ctc', 'notice_period_days', 'preferred_location',
        'preferred_work_mode', 'availability', 'profile_summary', 'status', 'source', 'source_details', 'created_by', 'is_test_fixture',
    ];

    protected function casts(): array
    {
        return [
            'total_experience_years' => 'decimal:2',
            'relevant_experience_years' => 'decimal:2',
            'notice_period_days' => 'integer',
            'is_test_fixture' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accessRecords(): HasMany
    {
        return $this->hasMany(CandidateRecruiterAccess::class);
    }

    public function activeAccessRecords(): HasMany
    {
        return $this->accessRecords()->where('status', 'active');
    }

    public function hasActiveAccess(User $user): bool
    {
        return $this->activeAccessRecords()->where('recruiter_id', $user->getKey())->exists();
    }

    public function requirements(): BelongsToMany
    {
        return $this->belongsToMany(Requirement::class, 'candidate_requirement_links')
            ->withPivot(['linked_by', 'linked_at', 'reason'])->withTimestamps();
    }

    public function consents(): HasMany
    {
        return $this->hasMany(CandidateConsent::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CandidateDocument::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(CandidateEvaluation::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CandidateNote::class)->orderByDesc('created_at');
    }

    public function hasValidConsentFor(int $clientOrganizationId, int $requirementId): bool
    {
        return $this->consents()
            ->where('client_organization_id', $clientOrganizationId)
            ->where('requirement_id', $requirementId)
            ->where('status', 'valid')
            ->whereNull('withdrawn_at')
            ->exists();
    }
}
