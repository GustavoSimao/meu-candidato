<?php

namespace MeuCandidato\Legislative\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use MeuCandidato\Candidate\Models\Politician;

class ParliamentaryBloc extends Model
{
    use HasUuids;

    protected $fillable = [
        'external_id',
        'name',
        'legislature',
        'is_federation',
    ];

    protected $casts = [
        'is_federation' => 'boolean',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            Politician::class,
            'parliamentary_bloc_members',
            'bloc_id',
            'politician_id'
        )->withTimestamps();
    }
}
