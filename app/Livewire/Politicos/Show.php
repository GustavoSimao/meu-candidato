<?php

namespace App\Livewire\Politicos;

use App\Support\FrenteCategorizer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Identity\Models\Follow;
use MeuCandidato\Legislative\Models\Bill;
use MeuCandidato\Legislative\Models\PartyOrientation;

class Show extends Component
{
    public string $id;

    public function mount(string $id): void
    {
        $this->id = $id;
    }

    public function toggleFollow(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'));

            return;
        }

        $userId = Auth::id();
        $exists = Follow::where('user_id', $userId)
            ->where('politician_id', $this->id)
            ->exists();

        if ($exists) {
            Follow::where('user_id', $userId)
                ->where('politician_id', $this->id)
                ->delete();
        } else {
            Follow::create([
                'user_id' => $userId,
                'politician_id' => $this->id,
            ]);
        }

        $this->dispatch('refreshFollow');
        $this->refresh();
    }

    #[Computed]
    public function isFollowing(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        return Follow::where('user_id', Auth::id())
            ->where('politician_id', $this->id)
            ->exists();
    }

    #[Computed]
    public function followersCount(): int
    {
        return Follow::where('politician_id', $this->id)->count();
    }

    #[Computed]
    public function cargo(): string
    {
        $p = Politician::find($this->id);

        return $p ? $this->detectarCargo($p) : 'deputado';
    }

    #[Computed]
    public function sourceFooter(): string
    {
        return $this->getSourceFooter($this->cargo);
    }

    #[Computed]
    public function politician(): ?array
    {
        $p = Politician::with([
            'party',
            'mandates',
            'latestAddress',
            'committeeMemberships',
            'parliamentaryFronts',
            'leadershipPositions',
            'rapporteurships',
            'billCoauthors',
            'parliamentaryBlocs',
            'assetDeclarations',
            'legalProceedings',
            'campaignFinancings',
        ])->withCount(['expenses', 'votes', 'speeches', 'events'])->find($this->id);

        if (! $p) {
            return null;
        }

        $cargo = $this->detectarCargo($p);

        $billsCount = Bill::where(fn ($q) => $q->where('author_id', $p->id)
            ->orWhereHas('coauthors', fn ($cq) => $cq->where('politician_id', $p->id)))
            ->count();

        $lastBill = Bill::where(fn ($q) => $q->where('author_id', $p->id)
            ->orWhereHas('coauthors', fn ($cq) => $cq->where('politician_id', $p->id)))
            ->orderByDesc('year')
            ->first(['year', 'created_at']);

        $bills = Bill::where(fn ($q) => $q->where('author_id', $p->id)
            ->orWhereHas('coauthors', fn ($cq) => $cq->where('politician_id', $p->id)))
            ->with(['themes', 'progress' => function ($q) {
                $q->orderByDesc('date')->limit(1);
            }])
            ->orderByDesc('year')
            ->limit(5)
            ->get([
                'id', 'external_id', 'title', 'description', 'status', 'year',
            ]);

        $lastVote = $p->votes()
            ->join('voting_sessions', 'votes.voting_session_id', '=', 'voting_sessions.id')
            ->orderByDesc('voting_sessions.date')
            ->first(['voting_sessions.date']);

        $votes = $p->votes()
            ->join('voting_sessions', 'votes.voting_session_id', '=', 'voting_sessions.id')
            ->leftJoin('bills', 'voting_sessions.bill_id', '=', 'bills.id')
            ->orderByDesc('voting_sessions.date')
            ->limit(5)
            ->get([
                'votes.vote',
                'votes.voting_session_id',
                'bills.title as bill_title',
                'voting_sessions.external_id as session_external_id',
                'voting_sessions.description as session_description',
                'voting_sessions.date as session_date',
            ]);

        $partyAcronym = $p->party?->acronym;
        $votesWithOrientation = $votes->map(function ($vote) use ($partyAcronym) {
            $orientation = $partyAcronym ? PartyOrientation::where('voting_session_id', $vote->voting_session_id)
                ->where('party_acronym', $partyAcronym)
                ->value('orientation') : null;

            return [
                'vote' => $vote->vote,
                'bill_title' => $vote->bill_title ?? $vote->session_description ?? '—',
                'session_external_id' => $vote->session_external_id,
                'date' => $vote->session_date ? date('d/m/Y', strtotime($vote->session_date)) : null,
                'party_orientation' => $orientation,
                'aligned' => $orientation !== null ? $this->votesAligned($vote->vote, $orientation) : null,
            ];
        });

        $expenses = $p->expenses()
            ->orderByDesc('document_date')
            ->limit(5)
            ->get(['id', 'type', 'description', 'value', 'document_date']);

        $expenseAgg = DB::table('expenses')
            ->where('politician_id', $p->id)
            ->selectRaw('SUM(value) as total, type, COUNT(*) as cnt')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        $totalExpenses = (float) $expenseAgg->sum('total');

        $bancada = $p->party?->acronym;
        $bancadaAvg = null;
        if ($bancada && $totalExpenses > 0) {
            $bancadaAvg = DB::table('expenses')
                ->join('politicians', 'expenses.politician_id', '=', 'politicians.id')
                ->join('parties', 'politicians.party_id', '=', 'parties.id')
                ->where('parties.acronym', $bancada)
                ->where('expenses.politician_id', '!=', $p->id)
                ->avg('expenses.value') ?? 0;
            $bancadaAvg = (float) $bancadaAvg * ($p->expenses_count ?: 1);
        }

        $assetDeclarations = $p->assetDeclarations()
            ->orderByDesc('year')
            ->get(['id', 'year', 'type', 'description', 'value']);

        $totalBens = (float) $assetDeclarations->sum('value');

        $legalProceedings = $p->legalProceedings()
            ->get(['id', 'case_number', 'court', 'status', 'description', 'source_url']);

        $campaignFinancings = $p->campaignFinancings()
            ->get(['id', 'source', 'type', 'value', 'election_year']);

        $totalCampanha = (float) $campaignFinancings->sum('value');

        $fontesCampanha = $campaignFinancings->groupBy('type')->map(fn ($items) => [
            'type' => $items->first()->type,
            'total' => (float) $items->sum('value'),
            'count' => $items->count(),
        ])->values()->all();

        $socialMedia = $p->social_media ?? [];

        $socialTwitter = collect($socialMedia)
            ->firstWhere('platform', 'twitter')['url'] ?? null;

        $socialInstagram = collect($socialMedia)
            ->firstWhere('platform', 'instagram')['url'] ?? null;

        $mandate = $p->mandates->sortByDesc('started_at')->first();
        $mandatePeriod = null;
        if ($mandate) {
            $start = $mandate->started_at?->format('Y');
            $end = $mandate->ended_at ? $mandate->ended_at->format('Y') : 'atual';
            $mandatePeriod = $start ? $start.'–'.$end : null;
        }

        $totalVotes = $p->votes_count;
        $presenca = $totalVotes > 0
            ? min(100, (int) round($totalVotes / max(1, $p->events_count + $totalVotes) * 100))
            : null;

        return [
            'id' => $p->id,
            'name' => $p->name,
            'nome_urna' => $p->nome_urna,
            'photo' => $p->photo_url,
            'party' => $p->party?->acronym ?? 'S/',
            'party_name' => $p->party?->name ?? 'Partido desconhecido',
            'state' => $p->latestAddress?->uf ?? '—',
            'position' => $p->position ?? '—',
            'education' => $p->education,
            'birth_date' => $p->birth_date?->format('d/m/Y'),
            'declared_profession' => $p->declared_profession,
            'defends' => $p->defends,
            'email' => $p->email,
            'phone' => $p->phone,
            'office' => $p->office,
            'uf_birth' => $p->uf_birth,
            'municipality_birth' => $p->municipality_birth,
            'social_media_twitter' => $socialTwitter,
            'social_media_instagram' => $socialInstagram,
            'cargo' => $cargo,
            'mandate_description' => $this->getMandateDescription($cargo),
            'mandate_period' => $mandatePeriod,
            'presenca_percentual' => $presenca,
            'tcu_parecer' => null,
            'tcu_ano' => null,
            'bills_count' => $billsCount,
            'expenses_count' => $p->expenses_count,
            'votes_count' => $p->votes_count,
            'speeches_count' => $p->speeches_count,
            'events_count' => $p->events_count,
            'last_bill_year' => $lastBill?->year,
            'last_vote_date' => $lastVote?->date ? date('d/m/Y', strtotime($lastVote->date)) : null,
            'mandates' => $p->mandates->sortByDesc('started_at')->map(fn ($m) => [
                'position' => $m->position,
                'started_at' => $m->started_at?->format('d/m/Y'),
                'ended_at' => $m->ended_at?->format('d/m/Y'),
                'salary' => $m->salary,
            ])->values()->all(),
            'bills' => $bills->map(fn ($b) => [
                'id' => $b->id,
                'external_id' => $b->external_id,
                'title' => $b->title,
                'description' => $b->description,
                'status' => $b->status,
                'year' => $b->year,
                'themes' => $b->themes->pluck('theme_name')->filter()->values()->all(),
                'latest_progress' => $b->progress->first()?->description,
                'latest_progress_date' => $b->progress->first()?->date?->format('d/m/Y'),
            ])->values()->all(),
            'votes' => $votesWithOrientation->values()->all(),
            'expenses' => $expenses->map(fn ($e) => [
                'type' => $e->type,
                'description' => $e->description,
                'value' => (float) $e->value,
                'date' => $e->document_date ? date('d/m/Y', strtotime($e->document_date)) : null,
            ])->values()->all(),
            'expense_breakdown' => $expenseAgg->map(fn ($row) => [
                'type' => $row->type,
                'total' => (float) $row->total,
                'count' => (int) $row->cnt,
            ])->all(),
            'total_expenses' => $totalExpenses,
            'bancada_avg' => $bancadaAvg,
            'committees' => $p->committeeMemberships->map(fn ($c) => [
                'name' => $c->name,
                'acronym' => $c->acronym,
                'role' => $c->role,
                'source' => $c->source,
                'start_date' => $c->start_date?->format('d/m/Y'),
                'end_date' => $c->end_date?->format('d/m/Y'),
            ])->values()->all(),
            'fronts' => $p->parliamentaryFronts->map(fn ($f) => [
                'title' => $f->title,
                'legislature' => $f->legislature,
            ])->values()->all(),
            'fronts_grouped' => FrenteCategorizer::group(
                $p->parliamentaryFronts->map(fn ($f) => [
                    'title' => $f->title,
                    'legislature' => $f->legislature,
                    'external_id' => $f->external_id,
                ])->values()->all()
            ),
            'fronts_total' => $p->parliamentaryFronts->count(),
            'leaderships' => $p->leadershipPositions->map(fn ($l) => [
                'position' => $l->position,
                'party' => $l->party_acronym,
                'house' => $l->house,
                'start_date' => $l->start_date?->format('d/m/Y'),
                'end_date' => $l->end_date?->format('d/m/Y'),
            ])->values()->all(),
            'rapporteurships' => $p->rapporteurships->map(fn ($r) => [
                'bill_description' => $r->bill_description,
                'bill_ementa' => $r->bill_ementa,
                'commission' => $r->commission_name,
                'start_date' => $r->start_date?->format('d/m/Y'),
                'end_date' => $r->end_date?->format('d/m/Y'),
                'removal_reason' => $r->removal_reason,
            ])->values()->all(),
            'coauthors' => $p->billCoauthors->map(fn ($ca) => [
                'author_name' => $ca->author_name,
                'bill_title' => $ca->bill?->title ?? '—',
            ])->values()->all(),
            'blocs' => $p->parliamentaryBlocs->map(fn ($b) => [
                'name' => $b->name,
                'is_federation' => $b->is_federation,
                'legislature' => $b->legislature,
            ])->values()->all(),
            'asset_declarations' => $assetDeclarations->map(fn ($a) => [
                'year' => $a->year,
                'type' => $a->type,
                'description' => $a->description,
                'value' => (float) $a->value,
            ])->values()->all(),
            'total_bens' => $totalBens,
            'legal_proceedings' => $legalProceedings->map(fn ($lp) => [
                'process_number' => $lp->case_number,
                'court' => $lp->court,
                'status' => $lp->status,
                'description' => $lp->description,
                'source_url' => $lp->source_url,
            ])->values()->all(),
            'campaign_financings' => $fontesCampanha,
            'total_campanha' => $totalCampanha,
            'doadores' => [],
            'committees_count' => count($p->committeeMemberships),
        ];
    }

    private function detectarCargo(Politician $p): string
    {
        $position = strtolower($p->position ?? '');

        if (str_contains($position, 'presidente') && ! str_contains($position, 'vice')) {
            return 'presidente';
        }
        if (str_contains($position, 'vice')) {
            return 'vice';
        }
        if (str_contains($position, 'senador')) {
            return 'senador';
        }

        return 'deputado';
    }

    private function getMandateDescription(string $cargo): string
    {
        return match ($cargo) {
            'deputado' => 'Representa o povo na câmara e propõe leis de âmbito nacional',
            'senador' => 'Representa o estado no Senado e aprova indicações para o STF',
            'presidente' => 'Chefe do Executivo federal; nomeia ministros, sanciona ou veta leis e comanda as Forças Armadas',
            'vice' => 'Substitui o presidente quando necessário e exerce funções delegadas por ele',
        };
    }

    private function getSourceFooter(string $cargo): string
    {
        return match ($cargo) {
            'deputado' => 'câmara dos deputados · TSE · portal da transparência',
            'senador' => 'senado federal · TSE · portal da transparência',
            'presidente' => 'TCU · senado federal · DOU · portal da transparência · TSE',
            'vice' => 'DOU · TSE · portal da transparência',
        };
    }

    private function votesAligned(?string $vote, ?string $orientation): ?bool
    {
        if ($vote === null || $orientation === null) {
            return null;
        }

        $voteNorm = $this->normalizeVote($vote);
        $orientNorm = $this->normalizeVote($orientation);

        if ($voteNorm === '' || $orientNorm === '') {
            return null;
        }

        return $voteNorm === $orientNorm;
    }

    private function normalizeVote(string $vote): string
    {
        $lower = mb_strtolower(trim($vote));

        return match (true) {
            str_contains($lower, 'sim') => 'sim',
            str_contains($lower, 'não') || str_contains($lower, 'nao') => 'não',
            str_contains($lower, 'abstenção') || str_contains($lower, 'abstencao') => 'abstenção',
            default => $lower,
        };
    }

    public function render(): View
    {
        return view('livewire.politicos.show')->layout('layouts.guest');
    }
}
