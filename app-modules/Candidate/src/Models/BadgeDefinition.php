<?php

namespace MeuCandidato\Candidate\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BadgeDefinition extends Model
{
    use HasUuids;

    protected $fillable = [
        'badge_type',
        'label',
        'description',
        'color',
        'rules',
        'is_active',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_active' => 'boolean',
    ];

    public function politicians()
    {
        return $this->belongsToMany(Politician::class, 'politician_badges')
            ->using(PoliticianBadge::class)
            ->withTimestamps();
    }
}
