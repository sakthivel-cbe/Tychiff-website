<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequirementTargetDateChange extends Model
{
    protected $fillable = [
        'requirement_id', 'previous_target_date', 'new_target_date', 'reason',
        'client_confirmation_reference', 'changed_by', 'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_target_date' => 'date',
            'new_target_date' => 'date',
            'changed_at' => 'datetime',
        ];
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
