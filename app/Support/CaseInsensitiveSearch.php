<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait CaseInsensitiveSearch
{
    protected function whereCaseInsensitive(Builder $query, string $column, string $value): Builder
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            return $query->where($column, 'ilike', $value);
        }

        return $query->whereRaw("LOWER({$column}) LIKE LOWER(?)", [$value]);
    }
}
