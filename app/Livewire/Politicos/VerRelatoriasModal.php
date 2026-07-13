<?php

namespace App\Livewire\Politicos;

use App\Support\CaseInsensitiveSearch;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use MeuCandidato\Legislative\Models\Rapporteurship;

class VerRelatoriasModal extends Component
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

    #[On('open-relatorias')]
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
        $rapporteurships = Rapporteurship::where('politician_id', $this->politicianId)
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $this->whereCaseInsensitive($sub, 'bill_description', '%'.$this->search.'%')
                        ->orWhereRaw('LOWER(commission_name) LIKE LOWER(?)', ['%'.$this->search.'%']);
                });
            })
            ->orderByDesc('start_date')
            ->paginate(15);

        return view('livewire.politicos.ver-relatorias-modal', [
            'rapporteurships' => $rapporteurships,
        ]);
    }
}
