<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientOrganization extends Model
{
    protected $fillable = ['name'];

    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class);
    }

    public function candidateConsents(): HasMany
    {
        return $this->hasMany(CandidateConsent::class);
    }
}
