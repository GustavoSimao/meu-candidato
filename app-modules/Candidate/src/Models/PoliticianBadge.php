<?php

namespace MeuCandidato\Candidate\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PoliticianBadge extends Pivot
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'politician_badges';

    protected $fillable = [
        'politician_id',
        'badge_definition_id',
    ];

    public function politician()
    {
        return $this->belongsTo(Politician::class);
    }

    public function badgeDefinition()
    {
        return $this->belongsTo(BadgeDefinition::class);
    }
}
