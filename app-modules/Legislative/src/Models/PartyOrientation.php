<?php

namespace MeuCandidato\Legislative\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyOrientation extends Model
{
    use HasUuids;

    protected $fillable = [
        'voting_session_id',
        'party_acronym',
        'orientation',
    ];

    public function votingSession(): BelongsTo
    {
        return $this->belongsTo(VotingSession::class);
    }
}
