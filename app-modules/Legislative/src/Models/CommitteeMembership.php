<?php

namespace MeuCandidato\Legislative\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeuCandidato\Candidate\Models\Politician;

class CommitteeMembership extends Model
{
    use HasUuids;

    protected $fillable = [
        'politician_id',
        'source',
        'external_id',
        'name',
        'acronym',
        'role',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function politician(): BelongsTo
    {
        return $this->belongsTo(Politician::class);
    }
}
