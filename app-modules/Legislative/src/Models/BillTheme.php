<?php

namespace MeuCandidato\Legislative\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillTheme extends Model
{
    use HasUuids;

    protected $fillable = [
        'bill_id',
        'external_id',
        'theme_name',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
