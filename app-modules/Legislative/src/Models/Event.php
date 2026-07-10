<?php

namespace MeuCandidato\Legislative\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeuCandidato\Candidate\Models\Politician;

class Event extends Model
{
    use HasUuids;

    protected $fillable = [
        'politician_id',
        'source',
        'external_id',
        'title',
        'type',
        'start_date',
        'end_date',
        'location',
        'description',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function politician(): BelongsTo
    {
        return $this->belongsTo(Politician::class);
    }
}
