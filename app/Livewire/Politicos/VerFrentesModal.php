<?php

namespace App\Livewire\Politicos;

use App\Support\CaseInsensitiveSearch;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use MeuCandidato\Legislative\Models\ParliamentaryFront;

class VerFrentesModal extends Component
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

    #[On('open-frentes')]
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
        $fronts = ParliamentaryFront::where('politician_id', $this->politicianId)
            ->when($this->search, function ($q) {
                $this->whereCaseInsensitive($q, 'title', '%'.$this->search.'%');
            })
            ->orderByDesc('legislature')
            ->paginate(15);

        return view('livewire.politicos.ver-frentes-modal', [
            'fronts' => $fronts,
        ]);
    }
}
