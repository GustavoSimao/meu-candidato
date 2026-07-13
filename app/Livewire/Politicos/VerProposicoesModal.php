<?php

namespace App\Livewire\Politicos;

use App\Support\CaseInsensitiveSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use MeuCandidato\Legislative\Models\Bill;

class VerProposicoesModal extends Component
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

    #[On('openProposicoesModal')]
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
            return view('livewire.politicos.ver-proposicoes-modal', [
                'bills' => new LengthAwarePaginator([], 0, 15),
            ]);
        }

        $bills = Bill::where(fn ($q) => $q->where('author_id', $this->politicianId)
            ->orWhereHas('coauthors', fn ($cq) => $cq->where('politician_id', $this->politicianId)))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $this->whereCaseInsensitive($sub, 'title', '%'.$this->search.'%')
                        ->orWhereRaw('LOWER(description) LIKE LOWER(?)', ['%'.$this->search.'%']);
                });
            })
            ->orderByDesc('year')
            ->paginate(15);

        return view('livewire.politicos.ver-proposicoes-modal', [
            'bills' => $bills,
        ]);
    }
}
