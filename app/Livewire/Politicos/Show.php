<?php

namespace App\Livewire\Politicos;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Identity\Models\Follow;

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
        ])->withCount(['bills', 'expenses', 'votes'])->find($this->id);

        if (! $p) {
            return null;
        }

        $bills = $p->bills()->orderByDesc('year')->limit(3)->get([
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
            'bills_count' => $p->bills_count,
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
        ];
    }

    public function render(): View
    {
        return view('livewire.politicos.show')->layout('layouts.guest');
    }
}
