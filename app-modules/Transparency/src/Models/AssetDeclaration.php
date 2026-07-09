<?php

namespace MeuCandidato\Transparency\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use MeuCandidato\Candidate\Models\Politician;

class AssetDeclaration extends Model
{
    use HasUuids;

    protected $fillable = [
        'politician_id',
        'year',
        'type',
        'description',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    public function politician()
    {
        return $this->belongsTo(Politician::class);
    }
}
