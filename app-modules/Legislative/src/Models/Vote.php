<?php

namespace MeuCandidato\Legislative\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use MeuCandidato\Candidate\Models\Politician;

class Vote extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'voting_session_id',
        'politician_id',
        'vote',
    ];

    public function votingSession()
    {
        return $this->belongsTo(VotingSession::class);
    }

    public function politician()
    {
        return $this->belongsTo(Politician::class);
    }
}
