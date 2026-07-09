<?php

namespace MeuCandidato\Transparency\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use MeuCandidato\Candidate\Models\Politician;

class LegalProceeding extends Model
{
    use HasUuids;

    protected $fillable = [
        'politician_id',
        'court',
        'case_number',
        'status',
        'description',
        'started_at',
        'ended_at',
        'outcome',
        'source_url',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    public function politician()
    {
        return $this->belongsTo(Politician::class);
    }
}
