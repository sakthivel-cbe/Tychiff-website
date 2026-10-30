<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequirementDocument extends Model
{
    protected $fillable = [
        'requirement_id', 'job_description_version_id', 'document_type', 'original_filename', 'storage_path',
        'mime_type', 'file_size', 'source_sha256', 'extraction_status', 'extracted_text', 'extracted_at',
        'extraction_failure_code', 'extraction_attempts', 'uploaded_by', 'is_test_fixture',
    ];

    protected function casts(): array
    {
        return [
            'extracted_text' => 'encrypted',
            'extracted_at' => 'datetime',
            'is_test_fixture' => 'boolean',
            'file_size' => 'integer',
            'extraction_attempts' => 'integer',
        ];
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function jobDescriptionVersion(): BelongsTo
    {
        return $this->belongsTo(JobDescriptionVersion::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
