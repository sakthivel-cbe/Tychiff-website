<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobDescriptionClientDecision extends Model
{
    protected $fillable = [
        'job_description_version_id', 'client_organization_id', 'client_spoc_reference',
        'decision', 'comments', 'recorded_by', 'decided_at',
    ];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function jobDescriptionVersion(): BelongsTo
    {
        return $this->belongsTo(JobDescriptionVersion::class);
    }

    public function clientOrganization(): BelongsTo
    {
        return $this->belongsTo(ClientOrganization::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
