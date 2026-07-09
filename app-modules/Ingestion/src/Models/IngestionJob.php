<?php

namespace MeuCandidato\Ingestion\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IngestionJob extends Model
{
    use HasUuids;

    protected $fillable = [
        'source',
        'status',
        'started_at',
        'finished_at',
        'records_count',
        'error_log',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function rawDataRecords()
    {
        return $this->hasMany(RawDataRecord::class);
    }
}
