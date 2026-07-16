<?php

namespace App\Livewire\Politicos;

use App\Support\CaseInsensitiveSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
    use CaseInsensitiveSearch;
    use WithPagination;

    public string $search = '';

    public string $selectedPosition = '';

    public string $selectedParty = '';

    public string $selectedState = '';

    public string $activeTab = 'eleitos';

    public function trending(): Collection
    {
        return Politician::with(['party', 'latestAddress'])
            ->whereNotNull('trending_order')
            ->orderBy('trending_order')
            ->get();
    }

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
        $query = Politician::with(['party', 'mandates', 'latestAddress', 'bills'])
            ->whereNotNull('external_id')
            ->whereHas('mandates', function ($q) {
                $q->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            });

        if ($this->search !== '') {
            $query = $this->whereCaseInsensitive($query, 'name', "%{$this->search}%");
        }

        if ($this->selectedPosition !== '') {
            $query->where('position', $this->selectedPosition);
        }

        if ($this->selectedParty !== '') {
            $query->whereHas('party', function ($q) {
                $q->where('acronym', $this->selectedParty);
            });
        }

        if ($this->selectedState !== '') {
            $query->whereHas('address', function ($q) {
                $q->where('uf', $this->selectedState);
            });
        }

        return $query->orderBy('name')->paginate(12);
    }

    public function filteredCandidates(): LengthAwarePaginator
    {
        $query = Politician::with(['party', 'mandates', 'latestAddress'])
            ->whereNotNull('external_id')
            ->whereDoesntHave('mandates', function ($q) {
                $q->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            });

        if ($this->search !== '') {
            $query = $this->whereCaseInsensitive($query, 'name', "%{$this->search}%");
        }

        if ($this->selectedPosition !== '') {
            $query->where('position', $this->selectedPosition);
        }

        if ($this->selectedParty !== '') {
            $query->whereHas('party', function ($q) {
                $q->where('acronym', $this->selectedParty);
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
            || $this->selectedPosition !== ''
            || $this->selectedParty !== ''
            || $this->selectedState !== '';
    }

    public function toggleTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->selectedPosition = '';
        $this->selectedParty = '';
        $this->selectedState = '';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.politicos.index', [
            'politicians' => $this->activeTab === 'candidatos' ? $this->filteredCandidates() : $this->filtered(),
            'trending' => $this->trending(),
        ])->layout('layouts.guest');
    }
}
