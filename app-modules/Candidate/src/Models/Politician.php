<?php

namespace MeuCandidato\Candidate\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use MeuCandidato\Geography\Models\Address;
use MeuCandidato\Identity\Models\Follow;
use MeuCandidato\Legislative\Models\Bill;
use MeuCandidato\Legislative\Models\Vote;
use MeuCandidato\Mandate\Models\Mandate;
use MeuCandidato\Party\Models\Party;
use MeuCandidato\Transparency\Models\AssetDeclaration;
use MeuCandidato\Transparency\Models\CampaignFinancing;
use MeuCandidato\Transparency\Models\Expense;
use MeuCandidato\Transparency\Models\LegalProceeding;

class Politician extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'cpf',
        'party_id',
        'birth_date',
        'education',
        'declared_profession',
        'external_id',
        'photo_url',
        'government_plan_url',
        'position',
        'defends',
        'trendings',
        'active_processes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'active_processes' => 'integer',
    ];

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function mandates()
    {
        return $this->hasMany(Mandate::class);
    }

    public function address()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function latestAddress()
    {
        return $this->morphOne(Address::class, 'addressable')
            ->latest('created_at');
    }

    public function badges()
    {
        return $this->belongsToMany(BadgeDefinition::class, 'politician_badges')
            ->using(PoliticianBadge::class)
            ->withTimestamps();
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'author_id');
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function assetDeclarations()
    {
        return $this->hasMany(AssetDeclaration::class);
    }

    public function legalProceedings()
    {
        return $this->hasMany(LegalProceeding::class);
    }

    public function campaignFinancings()
    {
        return $this->hasMany(CampaignFinancing::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function followers()
    {
        return $this->hasMany(Follow::class);
    }
}
