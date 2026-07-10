<?php

namespace MeuCandidato\Legislative\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeuCandidato\Candidate\Models\Politician;

class Rapporteurship extends Model
{
    use HasUuids;

    protected $fillable = [
        'politician_id',
        'bill_external_id',
        'bill_description',
        'bill_ementa',
        'commission_name',
        'start_date',
        'end_date',
        'removal_reason',
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
