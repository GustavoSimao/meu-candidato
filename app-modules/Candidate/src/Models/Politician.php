<?php

namespace MeuCandidato\Candidate\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use MeuCandidato\Geography\Models\Address;
use MeuCandidato\Identity\Models\Follow;
use MeuCandidato\Legislative\Models\Bill;
use MeuCandidato\Legislative\Models\BillCoauthor;
use MeuCandidato\Legislative\Models\BillProgress;
use MeuCandidato\Legislative\Models\BillTheme;
use MeuCandidato\Legislative\Models\CommitteeMembership;
use MeuCandidato\Legislative\Models\Event;
use MeuCandidato\Legislative\Models\LeadershipPosition;
use MeuCandidato\Legislative\Models\ParliamentaryBloc;
use MeuCandidato\Legislative\Models\ParliamentaryFront;
use MeuCandidato\Legislative\Models\Rapporteurship;
use MeuCandidato\Legislative\Models\Speech;
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

    protected $table = 'politicians';

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

    public function speeches()
    {
        return $this->hasMany(Speech::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function parliamentaryFronts()
    {
        return $this->hasMany(ParliamentaryFront::class);
    }

    public function committeeMemberships()
    {
        return $this->hasMany(CommitteeMembership::class);
    }

    public function leadershipPositions()
    {
        return $this->hasMany(LeadershipPosition::class);
    }

    public function rapporteurships()
    {
        return $this->hasMany(Rapporteurship::class);
    }

    public function billThemes()
    {
        return $this->hasManyThrough(BillTheme::class, Bill::class, 'author_id', 'bill_id');
    }

    public function billProgress()
    {
        return $this->hasManyThrough(BillProgress::class, Bill::class, 'author_id', 'bill_id');
    }

    public function billCoauthors()
    {
        return $this->hasMany(BillCoauthor::class);
    }

    public function parliamentaryBlocs()
    {
        return $this->belongsToMany(ParliamentaryBloc::class, 'parliamentary_bloc_members', 'politician_id', 'bloc_id')
            ->withTimestamps();
    }

    public function followers()
    {
        return $this->hasMany(Follow::class);
    }
}
