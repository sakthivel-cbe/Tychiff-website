<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequirementAssignment extends Model
{
    protected $fillable = ['requirement_id', 'recruiter_id', 'assigned_by', 'assigned_at', 'ended_at', 'reason'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
