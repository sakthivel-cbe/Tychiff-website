<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateConsent extends Model
{
    protected $fillable = [
        'candidate_id', 'client_organization_id', 'requirement_id', 'status', 'consented_at',
        'consent_method', 'consent_text_reference', 'recorded_by', 'withdrawn_at', 'withdrawn_by', 'withdrawal_reason',
    ];

    protected function casts(): array
    {
        return ['consented_at' => 'datetime', 'withdrawn_at' => 'datetime'];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function clientOrganization(): BelongsTo
    {
        return $this->belongsTo(ClientOrganization::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function withdrawnBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawn_by');
    }
}
