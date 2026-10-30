<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiKnowledgeItem extends Model
{
    protected $fillable = [
        'title', 'source_type', 'content', 'version', 'approval_status', 'is_active', 'created_by', 'updated_by',
        'approved_by', 'effective_at', 'approved_at',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'effective_at' => 'datetime', 'approved_at' => 'datetime'];
    }
}
