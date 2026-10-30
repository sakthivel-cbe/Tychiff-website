<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrivacyNoticeVersion extends Model
{
    protected $fillable = ['version', 'title', 'body', 'published_at', 'published_by'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(ConsentRecord::class, 'privacy_notice_version_id');
    }
}
