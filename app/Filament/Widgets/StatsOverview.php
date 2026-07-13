<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Legislative\Models\Bill;
use MeuCandidato\Mandate\Models\Mandate;
use MeuCandidato\Party\Models\Party;
use MeuCandidato\Transparency\Models\Expense;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Políticos', Cache::remember('stats.politicians', 3600, fn () => Politician::whereHas('mandates', function ($q) {
                $q->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            })->count()))
                ->description('Em exercício')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('Partidos', Cache::remember('stats.parties', 3600, fn () => Party::count()))
                ->description('Legendas registradas')
                ->descriptionIcon('heroicon-m-flag')
                ->color('primary'),
            Stat::make('Mandatos', Cache::remember('stats.mandates', 3600, fn () => Mandate::count()))
                ->description('Total de mandatos')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),
            Stat::make('Proposições', Cache::remember('stats.bills', 3600, fn () => Bill::count()))
                ->description('Projetos de lei')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
            Stat::make('Despesas CEAP', 'R$ '.number_format(Cache::remember('stats.total_expenses', 3600, fn () => (float) Expense::sum('value')), 2, ',', '.'))
                ->description('Total de despesas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),
        ];
    }
}
