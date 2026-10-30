<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateEvaluation extends Model
{
    protected $fillable = [
        'candidate_id', 'candidate_document_id', 'requirement_id', 'job_description_version_id', 'requested_by',
        'is_test_only', 'status', 'validation_status', 'scoring_status', 'validation_result', 'scoring_result',
        'failure_code', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_test_only' => 'boolean',
            'validation_result' => 'encrypted:array',
            'scoring_result' => 'encrypted:array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function candidateDocument(): BelongsTo
    {
        return $this->belongsTo(CandidateDocument::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function jobDescriptionVersion(): BelongsTo
    {
        return $this->belongsTo(JobDescriptionVersion::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function agentExecutions(): HasMany
    {
        return $this->hasMany(AiAgentExecution::class);
    }
}
