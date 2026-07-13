<?php

namespace App\Livewire\Politicos;

use App\Support\CaseInsensitiveSearch;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use MeuCandidato\Legislative\Models\BillCoauthor;

class VerCoautoresModal extends Component
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

    #[On('open-coautores')]
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
        $coauthors = BillCoauthor::where('politician_id', $this->politicianId)
            ->with('bill')
            ->when($this->search, function ($q) {
                $this->whereCaseInsensitive($q, 'author_name', '%'.$this->search.'%');
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.politicos.ver-coautores-modal', [
            'coauthors' => $coauthors,
        ]);
    }
}
