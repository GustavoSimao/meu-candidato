<?php

namespace MeuCandidato\Legislative\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillProgress extends Model
{
    use HasUuids;

    protected $fillable = [
        'bill_id',
        'external_id',
        'description',
        'date',
        'sequence_number',
    ];

    protected $casts = [
        'date' => 'date',
        'sequence_number' => 'integer',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
