<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateDocument extends Model
{
    protected $fillable = [
        'candidate_id', 'document_type', 'original_filename', 'storage_path', 'mime_type',
        'file_size', 'status', 'uploaded_by', 'uploaded_at', 'source_sha256', 'extraction_status',
        'extracted_text', 'extracted_at', 'extraction_failure_code', 'extraction_attempts', 'is_test_fixture',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer', 'uploaded_at' => 'datetime', 'extracted_text' => 'encrypted',
            'extracted_at' => 'datetime', 'extraction_attempts' => 'integer', 'is_test_fixture' => 'boolean',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(CandidateEvaluation::class);
    }
}
