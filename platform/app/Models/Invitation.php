<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Invitation extends Model
{
    protected $fillable = ['email', 'token_hash', 'invited_by', 'expires_at', 'accepted_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'accepted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'invitation_roles', 'invitation_id', 'role_id');
    }

    public function isUsable(): bool
    {
        return $this->accepted_at === null && $this->revoked_at === null && $this->expires_at->isFuture();
    }
}
