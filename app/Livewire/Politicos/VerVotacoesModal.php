<?php

namespace App\Livewire\Politicos;

use App\Support\CaseInsensitiveSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use MeuCandidato\Legislative\Models\Vote;

class VerVotacoesModal extends Component
{
    use CaseInsensitiveSearch;
    use WithPagination;

    public ?string $politicianId = null;

    public bool $isOpen = false;

    public string $search = '';

    public function mount(string $politicianId): void
    {
        $this->politicianId = $politicianId;
    }

    #[On('openVotacoesModal')]
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
            ->with(['votingSession.bill'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->whereHas('votingSession.bill', fn ($b) => $b->whereRaw('LOWER(title) LIKE LOWER(?)', ['%'.$this->search.'%']))
                        ->orWhereHas('votingSession', fn ($vs) => $vs->whereRaw('LOWER(description) LIKE LOWER(?)', ['%'.$this->search.'%']));
                });
            })
            ->join('voting_sessions', 'votes.voting_session_id', '=', 'voting_sessions.id')
            ->orderByDesc('voting_sessions.date')
            ->paginate(15);

        return view('livewire.politicos.ver-votacoes-modal', [
            'votes' => $votes,
        ]);
    }
}
