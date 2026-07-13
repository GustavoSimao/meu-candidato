<?php

namespace MeuCandidato\Legislative\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VotingSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'external_id',
        'bill_id',
        'date',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function partyOrientations()
    {
        return $this->hasMany(PartyOrientation::class);
    }
}
