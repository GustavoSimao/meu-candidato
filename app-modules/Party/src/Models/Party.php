<?php

namespace MeuCandidato\Party\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use MeuCandidato\Candidate\Models\Politician;

class Party extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'acronym',
        'number',
    ];

    public function politicians()
    {
        return $this->hasMany(Politician::class);
    }
}
