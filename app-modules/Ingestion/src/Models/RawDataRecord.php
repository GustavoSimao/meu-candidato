<?php

namespace MeuCandidato\Ingestion\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RawDataRecord extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'ingestion_job_id',
        'source',
        'external_id',
        'raw_data',
        'processed',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'processed' => 'boolean',
    ];

    public function ingestionJob()
    {
        return $this->belongsTo(IngestionJob::class);
    }
}
