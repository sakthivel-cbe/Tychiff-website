<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentEvidence extends Model
{
    public $timestamps = false;

    protected $fillable = ['execution_id', 'provider_reference', 'source_type', 'source_record_id', 'field', 'retrieval_reason', 'captured_at'];

    protected function casts(): array
    {
        return ['captured_at' => 'datetime'];
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AiAgentExecution::class, 'execution_id');
    }
}
