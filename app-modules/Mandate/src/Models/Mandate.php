<?php

namespace MeuCandidato\Mandate\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use MeuCandidato\Candidate\Models\Politician;

class Mandate extends Model
{
    use HasUuids;

    protected $fillable = [
        'politician_id',
        'position',
        'salary',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    public function politician()
    {
        return $this->belongsTo(Politician::class);
    }
}
