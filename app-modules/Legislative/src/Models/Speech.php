<?php

namespace MeuCandidato\Legislative\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeuCandidato\Candidate\Models\Politician;

class Speech extends Model
{
    use HasUuids;

    protected $fillable = [
        'politician_id',
        'source',
        'external_id',
        'title',
        'content',
        'resume',
        'date',
        'session_name',
        'uri',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function politician(): BelongsTo
    {
        return $this->belongsTo(Politician::class);
    }
}
