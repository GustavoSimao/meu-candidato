<?php

namespace MeuCandidato\Legislative\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeuCandidato\Candidate\Models\Politician;

class BillCoauthor extends Model
{
    use HasUuids;

    protected $fillable = [
        'bill_id',
        'politician_id',
        'author_name',
        'author_external_id',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function politician(): BelongsTo
    {
        return $this->belongsTo(Politician::class);
    }
}
