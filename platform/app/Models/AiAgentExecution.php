<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgentExecution extends Model
{
    protected $fillable = [
        'agent_type', 'user_id', 'requirement_id', 'candidate_id', 'candidate_evaluation_id', 'provider', 'model', 'fallback_provider',
        'status', 'result_status', 'failure_code', 'provider_trace', 'evidence_count', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['provider_trace' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function candidateEvaluation(): BelongsTo
    {
        return $this->belongsTo(CandidateEvaluation::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(AiAgentEvidence::class, 'execution_id');
    }
}
