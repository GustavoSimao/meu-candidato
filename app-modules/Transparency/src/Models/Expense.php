<?php

namespace MeuCandidato\Transparency\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeuCandidato\Candidate\Models\Politician;

class Expense extends Model
{
    use HasUuids;

    protected $fillable = [
        'politician_id',
        'year',
        'type',
        'description',
        'value',
        'supplier_cnpj_cpf',
        'document_number',
        'document_date',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'document_date' => 'date',
    ];

    public function politician(): BelongsTo
    {
        return $this->belongsTo(Politician::class);
    }
}
