<?php

namespace App\Policies;

use App\Models\User;

/** Action-level gates only; record-level checks need future candidate ownership data. */
class CandidatePolicy
{
    public function screen(User $user): bool
    {
        return $user->hasPermission('candidates.screen');
    }

    public function submit(User $user): bool
    {
        return $user->hasPermission('candidates.submit');
    }
}
