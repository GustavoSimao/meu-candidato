<?php

namespace MeuCandidato\Legislative\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use MeuCandidato\Candidate\Models\Politician;

class Bill extends Model
{
    use HasUuids;

    protected $fillable = [
        'external_id',
        'title',
        'description',
        'author_id',
        'status',
        'year',
    ];

    public function author()
    {
        return $this->belongsTo(Politician::class, 'author_id');
    }

    public function votingSessions()
    {
        return $this->hasMany(VotingSession::class);
    }
}
