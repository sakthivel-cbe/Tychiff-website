<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentRecord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'privacy_notice_version_id', 'consent_type', 'granted', 'recorded_at',
        'source', 'ip_address', 'user_agent', 'context',
    ];

    protected function casts(): array
    {
        return ['granted' => 'boolean', 'recorded_at' => 'datetime', 'context' => 'array'];
    }

    public function noticeVersion(): BelongsTo
    {
        return $this->belongsTo(PrivacyNoticeVersion::class, 'privacy_notice_version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
