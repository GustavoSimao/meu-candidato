<?php

namespace App\Livewire\Politicos;

use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use MeuCandidato\Legislative\Models\Bill;

class VerProposicoesModal extends Component
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
            return view('livewire.politicos.ver-proposicoes-modal', [
                'bills' => new LengthAwarePaginator([], 0, 15),
            ]);
        }

        $bills = Bill::where('author_id', $this->politicianId)
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'ilike', '%'.$this->search.'%')
                        ->orWhere('description', 'ilike', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('year')
            ->paginate(15);

        return view('livewire.politicos.ver-proposicoes-modal', [
            'bills' => $bills,
        ]);
    }
}
