<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id', 'previous_state', 'new_state',
        'context', 'ip_address', 'user_agent', 'request_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_state' => 'array', 'new_state' => 'array', 'context' => 'array', 'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit events are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit events are append-only.'));
    }

    public static function record(string $action, array $attributes = []): self
    {
        return self::create(array_merge([
            'user_id' => auth()->id(),
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_id' => request()->header('X-Request-ID'),
            'created_at' => now(),
        ], $attributes));
    }
}
