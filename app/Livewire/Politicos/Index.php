<?php

namespace App\Livewire\Politicos;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Geography\Models\Address;
use MeuCandidato\Party\Models\Party;

#[Title('Políticos')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public array $selectedPositions = [];

    public array $selectedParties = [];

    public string $selectedState = '';

    #[Computed]
    public function positions(): array
    {
        return Politician::distinct()
            ->whereNotNull('position')
            ->pluck('position')
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    #[Computed]
    public function parties(): array
    {
        return Party::pluck('acronym')
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    #[Computed]
    public function states(): array
    {
        return Address::where('addressable_type', Politician::class)
            ->whereNotNull('uf')
            ->distinct()
            ->pluck('uf')
            ->sort()
            ->values()
            ->all();
    }

    public function filtered(): LengthAwarePaginator
    {
        $query = Politician::with(['party', 'mandates', 'bills', 'latestAddress'])
            ->whereNotNull('external_id');

        if ($this->search !== '') {
            $query->where('name', 'ilike', "%{$this->search}%");
        }

        if ($this->selectedPositions !== []) {
            $query->whereIn('position', $this->selectedPositions);
        }

        if ($this->selectedParties !== []) {
            $query->whereHas('party', function ($q) {
                $q->whereIn('acronym', $this->selectedParties);
            });
        }

        if ($this->selectedState !== '') {
            $query->whereHas('address', function ($q) {
                $q->where('uf', $this->selectedState);
            });
        }

        return $query->orderBy('name')->paginate(12);
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->search !== ''
            || $this->selectedPositions !== []
            || $this->selectedParties !== []
            || $this->selectedState !== '';
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->selectedPositions = [];
        $this->selectedParties = [];
        $this->selectedState = '';
        $this->resetPage();
    }

    public function togglePosition(string $position): void
    {
        if (in_array($position, $this->selectedPositions)) {
            $this->selectedPositions = array_values(array_diff($this->selectedPositions, [$position]));
        } else {
            $this->selectedPositions[] = $position;
        }
        $this->resetPage();
    }

    public function toggleParty(string $party): void
    {
        if (in_array($party, $this->selectedParties)) {
            $this->selectedParties = array_values(array_diff($this->selectedParties, [$party]));
        } else {
            $this->selectedParties[] = $party;
        }
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.politicos.index', [
            'politicians' => $this->filtered(),
        ])->layout('layouts.guest');
    }
}
