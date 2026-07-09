<?php

namespace MeuCandidato\Geography\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasUuids;

    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'uf',
        'cidade',
        'municipio',
        'bairro',
        'logradouro',
        'cep',
    ];

    public function addressable()
    {
        return $this->morphTo();
    }
}
