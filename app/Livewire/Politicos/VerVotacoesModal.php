<?php

namespace App\Livewire\Politicos;

use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use MeuCandidato\Legislative\Models\Vote;

class VerVotacoesModal extends Component
{
    use WithPagination;

    public string $politicianId;

    public bool $isOpen = false;

    public string $search = '';

    public function mount(string $politicianId): void
    {
        $this->politicianId = $politicianId;
    }

    public function open(): void
    {
        $this->isOpen = true;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        if (! $this->politicianId) {
            return view('livewire.politicos.ver-votacoes-modal', [
                'votes' => new LengthAwarePaginator([], 0, 15),
            ]);
        }

        $votes = Vote::where('politician_id', $this->politicianId)
            ->join('voting_sessions', 'votes.voting_session_id', '=', 'voting_sessions.id')
            ->leftJoin('bills', 'voting_sessions.bill_id', '=', 'bills.id')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('bills.title', 'ilike', '%'.$this->search.'%')
                        ->orWhere('voting_sessions.description', 'ilike', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('voting_sessions.date')
            ->paginate(15);

        return view('livewire.politicos.ver-votacoes-modal', [
            'votes' => $votes,
        ]);
    }
}
