<?php

namespace App\Livewire\Politicos;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Identity\Models\Follow;
use MeuCandidato\Legislative\Models\Bill;

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
    public function politician(): ?array
    {
        $p = Politician::with([
            'party',
            'mandates',
            'latestAddress',
            'badges',
            'committeeMemberships',
            'parliamentaryFronts',
            'leadershipPositions',
            'rapporteurships',
            'billCoauthors',
            'parliamentaryBlocs',
        ])->withCount(['expenses', 'votes', 'speeches', 'events'])->find($this->id);

        if (! $p) {
            return null;
        }

        $billsCount = Bill::where(fn ($q) => $q->where('author_id', $p->id)
            ->orWhereHas('coauthors', fn ($cq) => $cq->where('politician_id', $p->id)))
            ->count();

        $bills = Bill::where(fn ($q) => $q->where('author_id', $p->id)
            ->orWhereHas('coauthors', fn ($cq) => $cq->where('politician_id', $p->id)))
            ->with(['themes', 'progress' => function ($q) {
                $q->orderByDesc('date')->limit(1);
            }])
            ->orderByDesc('year')
            ->limit(3)
            ->get([
                'id', 'external_id', 'title', 'description', 'status', 'year',
            ]);

        $votes = $p->votes()
            ->join('voting_sessions', 'votes.voting_session_id', '=', 'voting_sessions.id')
            ->leftJoin('bills', 'voting_sessions.bill_id', '=', 'bills.id')
            ->orderByDesc('voting_sessions.date')
            ->limit(3)
            ->get([
                'votes.vote',
                'bills.title as bill_title',
                'voting_sessions.external_id as session_external_id',
                'voting_sessions.description as session_description',
                'voting_sessions.date as session_date',
            ]);

        $expenses = $p->expenses()
            ->orderByDesc('document_date')
            ->limit(3)
            ->get(['id', 'type', 'description', 'value', 'document_date']);

        $speeches = $p->speeches()
            ->orderByDesc('date')
            ->limit(5)
            ->get(['id', 'source', 'title', 'resume', 'date', 'session_name', 'uri']);

        $events = $p->events()
            ->orderByDesc('start_date')
            ->limit(5)
            ->get(['id', 'title', 'type', 'start_date', 'location']);

        $expenseAgg = DB::table('expenses')
            ->where('politician_id', $p->id)
            ->selectRaw('SUM(value) as total, type, COUNT(*) as cnt')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        return [
            'id' => $p->id,
            'name' => $p->name,
            'photo' => $p->photo_url,
            'party' => $p->party?->acronym ?? 'S/',
            'party_name' => $p->party?->name ?? 'Partido desconhecido',
            'state' => $p->latestAddress?->uf ?? '—',
            'position' => $p->position ?? '—',
            'education' => $p->education,
            'birth_date' => $p->birth_date?->format('d/m/Y'),
            'declared_profession' => $p->declared_profession,
            'defends' => $p->defends,
            'bills_count' => $billsCount,
            'expenses_count' => $p->expenses_count,
            'votes_count' => $p->votes_count,
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
            'votes' => $votes->map(fn ($v) => [
                'vote' => $v->vote,
                'bill_title' => $v->bill_title ?? $v->session_description ?? '—',
                'session_external_id' => $v->session_external_id,
                'date' => $v->session_date ? date('d/m/Y', strtotime($v->session_date)) : null,
            ])->values()->all(),
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
            'total_expenses' => (float) $expenseAgg->sum('total'),
            'badges' => $p->badges->map(fn ($b) => [
                'name' => $b->label,
                'color' => $b->color,
                'type' => $b->badge_type,
                'description' => $b->description,
            ])->values()->all(),
            'speeches_count' => $p->speeches_count,
            'events_count' => $p->events_count,
            'speeches' => $speeches->map(fn ($s) => [
                'id' => $s->id,
                'source' => $s->source,
                'title' => $s->title ?? $s->resume ?? '—',
                'date' => $s->date ? date('d/m/Y', strtotime($s->date)) : null,
                'session_name' => $s->session_name,
                'uri' => $s->uri,
            ])->values()->all(),
            'events' => $events->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'type' => $e->type,
                'date' => $e->start_date ? date('d/m/Y', strtotime($e->start_date)) : null,
                'location' => $e->location,
            ])->values()->all(),
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
        ];
    }

    public function render(): View
    {
        return view('livewire.politicos.show')->layout('layouts.guest');
    }
}
